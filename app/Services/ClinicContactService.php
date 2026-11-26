<?php
declare(strict_types=1);
namespace App\Services;

/** A single public contact source. Numbers are maintained by the owner. */
final class ClinicContactService {
    public function __construct(private SettingsService $settings = new SettingsService()) {}
    public static function normalize(mixed $input): array {
        if (!is_string($input)) throw new \InvalidArgumentException('Enter one international number per line.');
        $numbers=[];
        foreach (preg_split('/[\r\n,]+/',$input) ?: [] as $part) {
            $part=trim($part);
            if ($part==='') continue;
            $number=preg_replace('/[\s()-]+/','',$part);
            if (!preg_match('/^\+[1-9][0-9]{7,14}$/D',$number)) throw new \InvalidArgumentException('Use international phone numbers beginning with + and country code.');
            $numbers[]=$number;
        }
        $numbers=array_values(array_unique($numbers));
        if (count($numbers)>4) throw new \InvalidArgumentException('Use at most four numbers per channel.');
        return $numbers;
    }

    public function channels(): array {
        return self::channelsFromSettings($this->settings->public());
    }

    public static function channelsFromSettings(array $s): array {
        $out=[];
        foreach (['phones'=>'clinic_phones','whatsapp'=>'clinic_whatsapp'] as $key=>$field) {
            $items=is_array($s[$field]??null)?$s[$field]:[];
            $out[$key]=array_slice(array_values(array_filter($items,static fn($n):bool=>is_string($n)&&preg_match('/^\+[1-9][0-9]{7,14}$/D',$n)===1)),0,4);
        }
        return $out;
    }
    public static function whatsappUrl(string $number, string $text = ''): string {
        if (!preg_match('/^\+[1-9][0-9]{7,14}$/D',$number)) throw new \InvalidArgumentException('Invalid WhatsApp destination.');
        return 'https://wa.me/'.substr($number,1).($text!==''?'?text='.rawurlencode($text):'');
    }
    public function handoff(string $question): ?array {
        $number=$this->channels()['whatsapp'][0]??'';
        if ($number==='') return null;
        // Do not place health details, names or chat transcripts into third-party URL logs.
        $topic=match(true) {
            (bool)preg_match('/\b(price|fee|cost|payment)\b/i',$question)=>'consultation fees or payment',
            (bool)preg_match('/\b(book|appointment|consultation)\b/i',$question)=>'requesting a consultation',
            (bool)preg_match('/\b(program|programme|service)\b/i',$question)=>'choosing a wellness programme',
            (bool)preg_match('/\b(ticket|support|issue|problem)\b/i',$question)=>'a support request',
            default=>'a question for the clinic',
        };
        return ['type'=>'whatsapp','label'=>'Continue on WhatsApp','path'=>self::whatsappUrl($number,'Hello, I would like help with '.$topic.'.')];
    }
}
