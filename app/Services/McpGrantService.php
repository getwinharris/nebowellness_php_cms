<?php
declare(strict_types=1);
namespace App\Services;

/** Delegation is independent of browser login and provider/API credentials. */
final class McpGrantService {
    public const SCOPE='published-clinic:read';
    public function __construct(private DatabaseService $store) {}

    public function issue(string $label,int $days): array {
        $label=trim($label);
        if($label===''||strlen($label)>100||preg_match('/[\x00-\x1f\x7f]/',$label)||$days<1||$days>90) {
            throw new \InvalidArgumentException('Specify a client label and validity of 1–90 days.');
        }
        $id=bin2hex(random_bytes(16));
        $token='nebo_mcp_'.$id.'.'.bin2hex(random_bytes(32));
        $now=time();
        $record=['id'=>$id,'label'=>$label,'scope'=>self::SCOPE,'token_digest'=>hash('sha256',$token),
            'status'=>'active','created_at'=>gmdate('c',$now),'expires_at'=>gmdate('c',$now+$days*86400)];
        $this->store->upsert('mcp_grants',$record);
        if($this->authorize('Bearer '.$token)===null)throw new \RuntimeException('Grant persistence could not be verified.');
        return self::summary($record)+['token'=>$token];
    }

    public function authorize(string $header): ?array {
        if(!preg_match('/\ABearer (nebo_mcp_([0-9a-f]{32})\.[0-9a-f]{64})\z/i',$header,$m))return null;
        $record=$this->store->find('mcp_grants',strtolower($m[2]));
        if(!$record||($record['status']??null)!=='active'||($record['scope']??null)!==self::SCOPE)return null;
        $expiry=$record['expires_at']??null;
        if(!is_string($expiry)||!preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})\z/',$expiry))return null;
        $expires=strtotime($expiry);
        if($expires===false||$expires<=time()||!is_string($record['token_digest']??null)||
            !hash_equals($record['token_digest'],hash('sha256',$m[1])))return null;
        return self::summary($record);
    }

    public function listing(): array {
        return array_map(self::summary(...),$this->store->read('mcp_grants'));
    }

    public function revoke(string $id): bool {
        if(!preg_match('/\A[0-9a-f]{32}\z/',$id))throw new \InvalidArgumentException('Invalid grant ID.');
        $row=$this->store->find('mcp_grants',$id);
        if(!$row)return false;
        $this->store->upsert('mcp_grants',['id'=>$id,'status'=>'revoked','updated_at'=>gmdate('c')]);
        return ($this->store->find('mcp_grants',$id)['status']??null)==='revoked';
    }

    private static function summary(array $row): array {
        return array_intersect_key($row,array_flip(['id','label','scope','status','created_at','expires_at','updated_at']));
    }
}
