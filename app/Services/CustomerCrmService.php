<?php
declare(strict_types=1);
namespace App\Services;

/** Owner-only customer context. Email association is not identity verification. */
final class CustomerCrmService {
    public function __construct(private DatabaseService $store = new DatabaseService()) {}
    private const SOURCES = [
        'users'=>['email','Account'], 'contact_submissions'=>['email','Contact enquiry'],
        'support_tickets'=>['customer_email','Support ticket'], 'therapy_sessions'=>['user_id','Therapy request'],
        'orders'=>['customer_email','Order'],
    ];

    private function records(string $table, ?string $email = null, array $accounts = []): array {
        // Indexed owner lookup for one customer's context. Limit the directory scan
        // to recent records; don't expose account passwords or clinical form bodies.
        $field = self::SOURCES[$table][0];
        $sql = "SELECT id,_data FROM " . $table;
        $params = [];
        if ($email !== null) {
            $owner = $table === 'therapy_sessions' ? array_search($email,$accounts,true) : $email;
            if ($owner === false) return [];
            $sql .= " WHERE _owner = ?"; $params[] = $owner;
        }
        $sql .= " ORDER BY _updated_at DESC LIMIT " . ($email === null ? '300' : '50');
        $rows = $this->store->query($sql,$params);
        $out = [];
        foreach ($rows as $row) {
            $r = json_decode((string)($row['_data'] ?? ''), true);
            if (!is_array($r)) continue;
            $address = strtolower(trim((string)($table === 'therapy_sessions' ? ($accounts[(string)($r[$field]??'')]??'') : ($r[$field] ?? ''))));
            if (!filter_var($address,FILTER_VALIDATE_EMAIL) || ($email !== null && $address !== $email)) continue;
            if ($table === 'users' && ($r['role'] ?? '') !== 'customer') continue;
            $out[] = [
                'email'=>$address, 'name'=>(string)($r['name'] ?? ''),
                'id'=>(string)($r['id'] ?? $row['id'] ?? ''),
                'date'=>(string)($r['updated_at'] ?? $r['created_at'] ?? ''),
                'status'=>(string)($r['status'] ?? ($table === 'users' ? 'registered' : 'unknown')),
                'source'=>$table, 'label'=>self::SOURCES[$table][1],
            ];
        }
        return $out;
    }

    private function sources(): array {
        return array_filter(array_keys(self::SOURCES), static fn(string $name): bool => $name !== 'orders' || module_on('shop'));
    }

    private function accounts(?string $email = null): array {
        $accounts=[];
        foreach ($this->records('users',$email) as $r) $accounts[$r['id']]=$r['email'];
        return $accounts;
    }

    public function directory(string $search = ''): array {
        $people = [];
        $accounts=$this->accounts();
        foreach ($this->sources() as $source) foreach ($this->records($source,null,$accounts) as $r) {
            $email = $r['email'];
            $people[$email] ??= ['email'=>$email,'name'=>'','account'=>false,'counts'=>[],'latest'=>''];
            $person = &$people[$email];
            if ($source === 'users') { $person['account']=true; $person['name']=$r['name']; }
            elseif ($person['name'] === '' && $r['name'] !== '') $person['name']=$r['name'];
            $person['counts'][$source] = ($person['counts'][$source] ?? 0)+1;
            if (strcmp($r['date'],$person['latest']) > 0) $person['latest']=$r['date'];
            unset($person);
        }
        $rows = array_values(array_filter($people, static fn(array $r): bool => $search === '' || str_contains(mb_strtolower($r['email'].' '.$r['name']),mb_strtolower($search))));
        usort($rows,static fn(array $a,array $b): int => strcmp($b['latest'],$a['latest']));
        return array_slice($rows,0,100);
    }

    public function timeline(string $email): array {
        $email = strtolower(trim($email));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) return [];
        $out = [];
        $accounts=$this->accounts($email);
        foreach ($this->sources() as $source) $out = array_merge($out,$this->records($source,$email,$accounts));
        usort($out,static fn(array $a,array $b): int => strcmp($b['date'],$a['date']));
        return array_slice($out,0,100);
    }
}
