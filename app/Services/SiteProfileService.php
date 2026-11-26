<?php
declare(strict_types=1);
namespace App\Services;

/** A complete, versioned public identity; private draft/history never reach a public projection. */
final class SiteProfileService
{
    public const VERSION=1;
    public const FIELDS=[
        'name'=>['Site name',100],'short_name'=>['Short name',24],'admin_name'=>['Admin brand',100],
        'tagline'=>['Tagline',180],'description'=>['Public description',500],
        'footer_description'=>['Footer description',300],'public_email'=>['Public contact email',254],
        'address'=>['Display address',300],'locality'=>['Locality',120],'region'=>['Region',120],
        'country'=>['Two-letter country code',2],'postal_code'=>['Postal code',24],
        'hours_weekday'=>['Opening hours (first line)',120],'hours_weekend'=>['Opening hours (second line)',120],
        'organization_type'=>['Organization type',30],'instagram_url'=>['Instagram profile URL',500],
        'facebook_url'=>['Facebook profile URL',500],'youtube_url'=>['YouTube profile URL',500],
    ];
    private const SOCIAL_HOSTS=[
        'instagram_url'=>['instagram.com','www.instagram.com'],
        'facebook_url'=>['facebook.com','www.facebook.com'],
        'youtube_url'=>['youtube.com','www.youtube.com'],
    ];
    public function __construct(private SettingsService $settings=new SettingsService()) {}

    public static function reference(): array {return require app_path('config/reference-site-profile.php');}
    private static function neutral(): array {
        return array_fill_keys(array_keys(self::FIELDS),'');
    }
    private static function emptyProfile(string $mode): array {
        return array_replace(self::neutral(),['name'=>'Service website','short_name'=>'Website','admin_name'=>'Site administration','organization_type'=>'Organization'])+
            ['mode'=>$mode,'logo_url'=>'','favicon_url'=>'','palette'=>BrandAssetService::effectivePalette([])];
    }
    private static function unavailable(): array {return self::emptyProfile('unavailable');}
    private static function referenceEnabled(): bool {
        // The reference copy belongs to this deployment, not the framework.
        // A second installation must not inherit Nebo's identity before setup.
        $host=strtolower((string)parse_url((require app_path('config/database.php'))['app_url'],PHP_URL_HOST));
        return in_array($host,['nebowellness.com','www.nebowellness.com'],true);
    }
    private static function editorDefaults(): array {
        return self::referenceEnabled()?self::reference():array_intersect_key(self::emptyProfile('unconfigured'),self::FIELDS);
    }
    public static function validate(array $input): array {
        if (array_diff(array_keys($input),array_keys(self::FIELDS))||array_diff(array_keys(self::FIELDS),array_keys($input))) {
            throw new \InvalidArgumentException('Supply exactly the site profile fields.');
        }
        $out=[];
        foreach (self::FIELDS as $key=>[$label,$max]) {
            $value=$input[$key];
            if (!is_string($value)||!mb_check_encoding($value,'UTF-8')) throw new \InvalidArgumentException('Invalid '.$label.'.');
            $value=trim($value);
            if (mb_strlen($value)>$max||preg_match('/[\x00-\x1f\x7f<>]/u',$value)) throw new \InvalidArgumentException('Invalid '.$label.'.');
            if (in_array($key,['name','short_name','admin_name','organization_type'],true)&&$value==='') throw new \InvalidArgumentException($label.' is required.');
            if ($key==='public_email'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Enter a valid public contact email.');
            if ($key==='country'&&$value!==''&&!preg_match('/^[A-Z]{2}$/D',$value)) throw new \InvalidArgumentException('Enter an uppercase two-letter country code.');
            if ($key==='organization_type'&&!in_array($value,['Organization','LocalBusiness','MedicalClinic','ProfessionalService'],true)) throw new \InvalidArgumentException('Choose a supported organization type.');
            if (isset(self::SOCIAL_HOSTS[$key])&&$value!=='') {
                $url=parse_url($value);
                if (!filter_var($value,FILTER_VALIDATE_URL)||!is_array($url)||($url['scheme']??'')!=='https'||
                    !in_array(strtolower($url['host']??''),self::SOCIAL_HOSTS[$key],true)||
                    isset($url['user'])||isset($url['pass'])||isset($url['port'])||
                    empty($url['path'])||$url['path']==='/'||str_contains($value,'\\')) throw new \InvalidArgumentException('Enter a valid HTTPS '.$label.'.');
            }
            $out[$key]=$value;
        }
        return $out;
    }
    public static function state(array $record): array {
        if (!array_key_exists('site_profile',$record)) return ['version'=>self::VERSION,'published'=>null,'draft'=>null,'previous'=>null,'can_restore'=>false];
        $state=$record['site_profile'];
        if (!is_array($state)||count($state)!==5||array_diff(array_keys($state),['version','published','draft','previous','can_restore'])||
            ($state['version']??null)!==self::VERSION||!is_bool($state['can_restore']??null)) throw new \DomainException('Site profile is unavailable or invalid.');
        foreach (['published','draft','previous'] as $key) {
            if (!array_key_exists($key,$state)) throw new \DomainException('Site profile is unavailable or invalid.');
            if ($state[$key]!==null) {
                if (!is_array($state[$key])) throw new \DomainException('Site profile is unavailable or invalid.');
                $state[$key]=self::validate($state[$key]);
            }
        }
        if (!$state['can_restore']&&$state['previous']!==null) throw new \DomainException('Invalid site profile history.');
        return $state;
    }
    /** Domain transition shared by owner forms and future explicitly authorized proposals. */
    public static function transition(array $record,string $action,?array $input=null): array {
        $state=self::state($record);
        switch ($action) {
            case 'save_draft':
                if ($input===null) throw new \InvalidArgumentException('Profile fields required.');
                $state['draft']=self::validate($input);break;
            case 'publish':
                if ($state['draft']===null) throw new \DomainException('Save a draft before publishing.');
                $state['previous']=$state['published'];$state['can_restore']=true;
                $state['published']=$state['draft'];$state['draft']=null;break;
            case 'discard':
                $state['draft']=null;break;
            case 'restore':
                if (!$state['can_restore']) throw new \DomainException('No prior version is available.');
                [$state['published'],$state['previous']]=[$state['previous'],$state['published']];
                $state['draft']=null;break;
            default:throw new \InvalidArgumentException('Unknown site profile action.');
        }
        return $state;
    }
    public function editor(): array {
        $record=$this->settings->appearanceRecord();
        $state=self::state($record);
        return ['revision'=>ResourceService::revision($record),'state'=>$state,
            'values'=>$state['draft']??$state['published']??self::editorDefaults()];
    }
    public function save(string $action,string $revision,?array $input): void {
        $record=$this->settings->appearanceForRevision($revision);
        $state=self::transition($record,$action,$input);
        $this->settings->saveSiteProfileState($state,$revision);
    }
    private static function imagePath(mixed $value): string {
        return is_string($value)&&preg_match('~^/assets/images/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.(?:png|jpe?g|ico|svg|webp|gif)$~iD',$value)?$value:'';
    }
    public static function resolve(array $settings): array {
        try {
            $published=null;
            if (array_key_exists('site_profile',$settings)) {
                if (($settings['id']??null)!==SettingsService::SETTINGS_ID) return self::unavailable();
                $state=$settings['site_profile'];
                if (!is_array($state)||($state['version']??null)!==self::VERSION||!array_key_exists('published',$state)) return self::unavailable();
                if ($state['published']!==null) {
                    if (!is_array($state['published'])) return self::unavailable();
                    $published=self::validate($state['published']);
                }
            }
            if ($published===null&&!self::referenceEnabled()) {
                return array_replace(self::emptyProfile('unconfigured'),['palette'=>BrandAssetService::effectivePalette($settings)]);
            }
            $profile=$published??self::reference();
            $mode=$published===null?'reference':'published';
            $logo=self::imagePath($settings['logo_url']??null);
            $favicon=self::imagePath($settings['favicon_url']??null);
            // Never resurrect known stale artwork from the retired deployment copy.
            if (str_contains(strtolower($logo),'logo-small.jpeg')||str_contains(strtolower($logo),'sripanchami')||str_contains($logo,'nebo-symbol.')) $logo='';
            if (str_contains(strtolower($favicon),'sps-')||str_contains(strtolower($favicon),'sripanchami')) $favicon='';
            if ($mode==='reference') {
                if ($logo==='') $logo='/assets/images/nebo-official-logo.jpg';
                if ($favicon==='') $favicon='/assets/images/nebo-symbol.png';
            }
            return $profile+['mode'=>$mode,'logo_url'=>$logo,'favicon_url'=>$favicon,'palette'=>BrandAssetService::effectivePalette($settings)];
        } catch (\Throwable) {return self::unavailable();}
    }
    public const IDENTITY_SOURCE='/contact';
    /** A semantic read projection, never a raw settings record. */
    public static function publicIdentity(array $record): array {
        if (($record['id']??null)!==SettingsService::SETTINGS_ID) throw new \DomainException('Canonical site identity unavailable.');
        $profile=self::resolve($record);
        if (!in_array($profile['mode'],['reference','published'],true)) throw new \DomainException('Published site identity unavailable.');
        $channels=ClinicContactService::channelsFromSettings($record);
        $keys=['name','short_name','tagline','description','public_email','address','hours_weekday','hours_weekend','organization_type'];
        return array_intersect_key($profile,array_flip($keys))+
            ['phones'=>$channels['phones'],'whatsapp'=>$channels['whatsapp'],'source'=>self::IDENTITY_SOURCE];
    }

    /** Fetch the same canonical record as the publication workflow. */
    public function identity(): array {
        return self::publicIdentity($this->settings->appearanceRecord());
    }

    public function published(): array {
        try {return self::resolve($this->settings->strictPublic());}
        catch (\Throwable) {return self::unavailable();}
    }
    public static function manifest(array $profile,bool $admin=false): array {
        if ($profile['mode']==='reference') {
            if (!$admin) return array_replace(json_decode(file_get_contents(app_path('assets/pwa/manifest-user.json')),true,flags:JSON_THROW_ON_ERROR),[
                'theme_color'=>$profile['palette']['palette_primary'],'background_color'=>$profile['palette']['palette_canvas'],
            ]);
            return ['name'=>'Nebo Admin','short_name'=>'Nebo Admin','description'=>'Admin panel for Nebo Wellness.',
                'start_url'=>'/admin','scope'=>'/admin/','display'=>'standalone','background_color'=>'#222222','theme_color'=>$profile['palette']['palette_primary'],
                'icons'=>[['src'=>'/assets/images/logo-192.png','sizes'=>'192x192','type'=>'image/png'],['src'=>'/assets/images/logo-512.png','sizes'=>'512x512','type'=>'image/png']]];
        }
        // Do not advertise another installation's icons or falsely claim icon size.
        // Existing owner-selected square PNG can be reused at its actual dimensions.
        $icons=[];
        $logo=$profile['logo_url'];
        if ($logo!==''&&str_ends_with(strtolower($logo),'.png')) {
            $image=@getimagesize(app_path(ltrim($logo,'/')));
            if ($image&&$image[0]===$image[1]&&in_array($image[0],[192,512],true)&&$image[2]===IMAGETYPE_PNG) {
                $icons[]=['src'=>$logo,'sizes'=>$image[0].'x'.$image[1],'type'=>'image/png','purpose'=>'any'];
            }
        }
        return ['name'=>$admin?$profile['short_name'].' Admin':$profile['name'],
            'short_name'=>$admin?$profile['short_name'].' Admin':$profile['short_name'],
            'description'=>$admin?'Administration for '.$profile['name']:$profile['description'],
            'start_url'=>$admin?'/admin':'/','scope'=>$admin?'/admin/':'/','display'=>'standalone',
            'background_color'=>$profile['palette']['palette_canvas'],'theme_color'=>$profile['palette']['palette_primary'],'icons'=>$icons];
    }
    public static function faviconMime(string $path): string {
        return match(strtolower(pathinfo($path,PATHINFO_EXTENSION))) {
            'svg'=>'image/svg+xml','ico'=>'image/x-icon','jpg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif',default=>'image/png',
        };
    }
}
