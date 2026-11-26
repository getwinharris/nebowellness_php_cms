<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Native read-ability catalogue. The scope is supplied by trusted existing
 * agent entry points, never by a model argument. External MCP projects only published clinic abilities.
 */
final class NativeAbilityRegistry
{
    public function __construct(private DatabaseService $store,private string $scope,private array $modules,private ?\Closure $contextLoader=null) {
        if (!in_array($scope,['support','admin'],true)) throw new \InvalidArgumentException('Unknown native ability scope.');
    }

    private static function object(array $properties,?array $required=null): array {
        return ['type'=>'object','properties'=>$properties===[]?(object)[]:$properties,
            'required'=>$required??array_keys($properties),'additionalProperties'=>false];
    }

    private static function string(int $max,int $min=0): array {
        return ['type'=>'string','minLength'=>$min,'maxLength'=>$max];
    }

    private static function listOf(array $item,int $max): array {
        return ['type'=>'array','items'=>$item,'maxItems'=>$max];
    }

    private static function definition(string $description,array $output,array $input=[],?string $module=null): array {
        return ['version'=>1,'category'=>'published-clinic','description'=>$description,
            'input_schema'=>self::object($input),'output_schema'=>$output,
            'annotations'=>['readonly'=>true,'destructive'=>false,'idempotent'=>true],
            'scopes'=>['support','admin'],'module'=>$module];
    }

    private static function countSchema(): array { return ['type'=>'integer','minimum'=>0]; }

    private static function counts(array $keys): array {
        return self::object(array_fill_keys($keys,self::countSchema()));
    }

    private static function owner(string $description,array $output,array $input=[],?string $module=null,?array $required=null): array {
        return array_replace(self::definition($description,$output,$input,$module),[
            'category'=>'owner-operations','scopes'=>['admin'],'input_schema'=>self::object($input,$required),
        ]);
    }

    private static function sessionContextDefinition(): array {
        $slug=self::string(120,1)+['pattern'=>'^[a-z0-9]+(?:-[a-z0-9]+)*$'];
        $path=static fn(string $type):array=>self::string(140,1)+['pattern'=>'^/'.$type.'/[a-z0-9]+(?:-[a-z0-9]+)*$'];
        $pages=[];
        foreach (['contact'=>'/contact','shop'=>'/shop','cart'=>'/cart','checkout'=>'/checkout','orders'=>'/account/dashboard/orders',
            'campaigns'=>'/campaigns','blog'=>'/blog','therapy requests'=>'/account/therapy'] as $key=>$url) {
            $pages[$key]=self::string(80,1)+['const'=>$url];
        }
        $money=['type'=>'integer','minimum'=>0];$currency=self::string(3,3)+['const'=>'INR'];
        $bool=['type'=>'boolean'];$phone=self::string(16,9)+['pattern'=>'^\\+[1-9][0-9]{7,14}$'];
        $fields=[
            'signed_in'=>$bool,'account_access'=>$bool+['const'=>false],'shop_enabled'=>$bool+['const'=>false],
            'orders'=>self::listOf(self::object(['id'=>self::string(120,1),'status'=>self::string(30,1)+['enum'=>OwnerAbilityService::ORDER_STATES],
                'total_minor'=>$money,'currency'=>$currency,'shipped_at'=>self::string(80),'invoice_number'=>self::string(120),'invoice_date'=>self::string(80)]),0),
            'cart'=>self::listOf(self::object(['slug'=>$slug,'name'=>self::string(200),'qty'=>['type'=>'integer','minimum'=>1,'maximum'=>10000]]),0),
            'articles'=>self::listOf(self::object(['title'=>self::string(200,1),'url'=>$path('blog')]),12),
            'site'=>self::object(['pages'=>self::object($pages,['contact']),
                'campaigns'=>self::listOf(self::object(['title'=>self::string(200,1),'summary'=>self::string(400),'url'=>$path('campaigns')]),12),
                'programmes'=>self::listOf(self::object(['title'=>self::string(200,1),'duration'=>self::string(80),'url'=>$path('campaigns')]),30),
                'products'=>self::listOf(self::object(['name'=>self::string(200,1),'slug'=>$slug,'url'=>$path('product'),'price_minor'=>$money,'currency'=>$currency,
                    'stock_status'=>self::string(80),'description'=>self::string(600),'highlights'=>self::listOf(self::string(160),6),
                    'description_points'=>self::listOf(self::string(160),6),'specifications'=>self::listOf(self::object(['name'=>self::string(100,1),'value'=>self::string(200)]),6)]),0),
                'support_scope'=>self::string(160,1)]),
            'contact_channels'=>self::object(['phones'=>self::listOf($phone,4),'whatsapp'=>self::listOf($phone,4)]),
            'policies'=>self::object(array_fill_keys(['delivery_within_india','delivery_outside_india','shipping_cost','payment_methods','tracking','returns_or_problems'],self::string(400,1))),
            'settings'=>self::object(['currency'=>self::string(3,3),'timezone'=>self::string(80,1),'shipping_mode'=>self::string(40,1),'flat_rate_minor'=>$money],[]),
        ];
        return array_replace(self::definition(
            'Retrieve permitted public clinic and service navigation. Only the authenticated customer may receive their service-request navigation link. Retail records are never exposed.',
            self::object($fields,array_values(array_diff(array_keys($fields),['settings','policies'])))
        ),['category'=>'session-context','scopes'=>['support']]);
    }

    private static function definitions(): array {
        $fee=self::object([
            'id'=>self::string(40,1)+['pattern'=>'^[a-z0-9][a-z0-9-]{0,39}$'],
            'title'=>self::string(100,1),'description'=>self::string(500),
            'amount_minor'=>['type'=>'integer','minimum'=>0,'maximum'=>999999999],
            'currency'=>self::string(3,3)+['enum'=>array_keys(ConsultationFeeService::CURRENCIES)],
            'min_minutes'=>['type'=>'integer','minimum'=>1,'maximum'=>1440],
            'max_minutes'=>['type'=>'integer','minimum'=>1,'maximum'=>1440],
            'inclusions'=>self::listOf(self::string(200,1),20),
            'display_price'=>self::string(40,1),'duration'=>self::string(40,1),
        ]);
        $practitioner=self::object([
            'name'=>self::string(200,1),'credentials'=>self::string(1000),
            'speciality'=>self::string(300),'registration_number'=>self::string(80),
            'url'=>self::string(50,1)+['const'=>'/#team'],
        ]);
        $programme=[
            'title'=>self::string(200,1),'summary'=>self::string(6000),
            'duration'=>self::string(80),
            'url'=>self::string(131,1)+['pattern'=>'^/campaigns/[a-z0-9]+(?:-[a-z0-9]+)*$'],
        ];
        $count=self::countSchema();
        $topicKeys=array_keys(OwnerAbilityService::ENQUIRY_TOPICS);
        $topicWords=self::object(array_fill_keys($topicKeys,self::listOf(self::string(40,1),20)));
        $slug=self::string(120,1)+['pattern'=>'^[a-z0-9]+(?:-[a-z0-9]+)*$'];
        $campaignUrl=self::string(131,1)+['pattern'=>'^/campaigns/[a-z0-9]+(?:-[a-z0-9]+)*$'];
        $dashboard=['registered_users'=>$count,'consultants'=>$count,'historical_appointments'=>$count,
            'support_tickets'=>$count,'shop_enabled'=>['type'=>'boolean','const'=>false],'scope'=>self::string(120,1)];
        return [
            'published_consultation_fees'=>self::definition(
                'Read current published online consultation fees, currency, duration and included services. Display prices only, not programme package prices, payment offers or confirmed bookings.',
                self::object([
                    'fees'=>self::listOf($fee,ConsultationFeeService::LIMIT),
                    'inclusions'=>self::listOf(self::string(200,1),20),
                    'source'=>self::string(100,1)+['const'=>ConsultationFeeService::SOURCE],
                    'scope'=>self::string(200,1)+['const'=>ConsultationFeeService::PUBLIC_SCOPE],
                ])
            ),
            'published_practitioners'=>self::definition(
                'Read visible clinic practitioner profiles, professional credentials and published registration numbers.',
                self::object(['practitioners'=>self::listOf($practitioner,40)])
            ),
            'published_programmes'=>self::definition(
                'Read published homepage programmes and their durations and detail links.',
                self::object(['programmes'=>self::listOf(self::object($programme),30)]),[],'campaigns'
            ),
            'published_programme_detail'=>self::definition(
                'Retrieve one published programme detail by its exact slug.',
                self::object($programme+['body'=>self::string(12000)]),
                ['slug'=>self::string(120,1)+['pattern'=>'^[a-z0-9]+(?:-[a-z0-9]+)*$']],'campaigns'
            ),
            'search_clinic_knowledge'=>self::definition(
                'Search published fees, visible practitioners, programme passages and published articles. Returns ranked excerpts and real source URLs.',
                self::object(['matches'=>self::listOf(self::object([
                    'source_type'=>self::string(30,1)+['enum'=>['consultation_fee','practitioner','programme','article']],
                    'title'=>self::string(200,1),'url'=>self::string(160,1)+['pattern'=>'^/(?:#team|contact#consultation-fees|(?:campaigns|blog)/[a-z0-9]+(?:-[a-z0-9]+)*)$'],
                    'score'=>['type'=>'integer','minimum'=>1,'maximum'=>208],'excerpt'=>self::string(1800),
                ]),5),'method'=>self::string(60,1)+['const'=>'lexical title and passage matching'],'scope'=>self::string(50,1)+['const'=>'Published clinic records only']]),
                ['query'=>self::string(500,1)+['pattern'=>'\S']]
            ),
            'search_articles'=>self::definition(
                'Search published blog and help articles by keyword. Returns titles and paths.',
                self::object(['found'=>['type'=>'integer','minimum'=>0,'maximum'=>500],
                    'articles'=>self::listOf(self::object(['title'=>self::string(200,1),
                        'path'=>self::string(126,1)+['pattern'=>'^/blog/[a-z0-9]+(?:-[a-z0-9]+)*$'],
                        'category'=>self::string(120),'score'=>['type'=>'integer','minimum'=>1,'maximum'=>120]]),5)]),
                ['query'=>self::string(500,1)+['pattern'=>'\S']],'blog'
            ),
            'clinic_context'=>self::sessionContextDefinition(),
            'owner_dashboard_summary'=>self::owner(
                'Read current owner-only service and CRM operational counts.',
                self::object($dashboard)
            ),
            'appointment_summary'=>self::owner(
                'Count historical appointments by status. These are not current bookings or therapy enquiries.',
                self::object(['total'=>$count,'by_status'=>self::counts(OwnerAbilityService::APPOINTMENT_STATES),
                    'scope'=>self::string(50,1)+['const'=>'Historical appointments only']])
            ),
            'support_enquiries'=>self::owner(
                'Count actual customer support tickets, statuses, unanswered messages and overlapping inferred topic counts; no private ticket text or identity.',
                self::object(['total'=>$count,'by_status'=>self::counts(OwnerAbilityService::TICKET_STATES),
                    'by_topic'=>self::counts($topicKeys),'awaiting_reply'=>$count,'topic_words_used'=>$topicWords,
                    'topic'=>self::string(30)+['enum'=>array_merge([''],$topicKeys)],'matching'=>$count,'message'=>self::string(120)]),
                ['topic'=>self::string(30,1)+['enum'=>$topicKeys,'description'=>'Optional topic: orders, delivery, payment, refund, product.'] ],null,[]
            ),
            'campaign_pages'=>self::owner(
                'List owner campaign landing pages including drafts; up to 20 records, total matching count, published path only.',
                self::object(['found'=>$count,'campaigns'=>self::listOf(self::object(['title'=>self::string(200,1),'slug'=>$slug,
                    'status'=>self::string(10,1)+['enum'=>['draft','published']],
                    'public_url'=>array_replace($campaignUrl,['type'=>['string','null']]),'summary'=>self::string(300)]),20)]),
                ['slug'=>$slug+['description'=>'Optional exact campaign slug.']],'campaigns',[]
            ),
            'therapy_enquiry_summary'=>self::owner(
                'Count therapy enquiry requests by status over the last 1–90 rolling days (default 30). Requests are not confirmed appointments.',
                self::object(['days'=>['type'=>'integer','minimum'=>1,'maximum'=>90],'request_count'=>$count,
                    'inbox_status'=>self::counts(['new','read','closed','other']),'admin_url'=>self::string(80,1)+['const'=>'/admin/contact-submissions']]),
                ['days'=>['type'=>'integer','minimum'=>1,'maximum'=>90,'description'=>'Rolling days, defaults to 30.'],
                    'slug'=>$slug+['description'=>'Optional exact campaign slug.']],'campaigns',[]
            ),
        ];
    }

    public function registered(string $name): bool { return isset(self::definitions()[$name]); }

    private function allowed(array $definition): bool {
        return in_array($this->scope,$definition['scopes'],true)
            && ($definition['category']!=='session-context'||$this->contextLoader!==null)
            && ($definition['module']===null||!empty($this->modules[$definition['module']]));
    }

    public function inspect(string $name): ?array {
        $definition=self::definitions()[$name]??null;
        return $definition!==null&&$this->allowed($definition)?['name'=>$name]+$definition:null;
    }

    public function discover(): array {
        $result=[];
        foreach (array_keys(self::definitions()) as $name) if (($definition=$this->inspect($name))!==null) $result[]=$definition;
        return $result;
    }

    /** Preserve the existing model function name and provider projection. */
    public function declarations(): array {
        return array_map(static function(array $definition):array {
            $input=$definition['input_schema'];
            // The transport projection keeps the supported provider schema vocabulary.
            // The full native constraints are enforced again before domain invocation.
            $input['properties']=array_map(static fn(array $field):array=>array_intersect_key($field,['type'=>true,'description'=>true]),(array)$input['properties']);
            return ['name'=>$definition['name'],'description'=>$definition['description'],'parameters'=>$input];
        },$this->discover());
    }

    private static function error(string $code,string $message): array {
        return ['error'=>$message,'code'=>$code];
    }

    public function validateArguments(string $name,array $args): ?array {
        $definition=$this->inspect($name);
        if ($definition===null) return null;
        // Only known optional nullable scalar arguments are normalized.
        foreach ($args as $key=>$value) {
            if ($value===null&&isset(((array)$definition['input_schema']['properties'])[$key])&&!in_array($key,$definition['input_schema']['required'],true)) unset($args[$key]);
        }
        if (!AbilitySchema::valid($args,$definition['input_schema'])) return null;
        return $args;
    }

    public function invoke(string $name,array $args): array {
        $definition=$this->inspect($name);
        if ($definition===null) return self::error('ability_not_available','Unknown or unavailable ability.');
        $args=$this->validateArguments($name,$args);
        if ($args===null) return self::error('ability_invalid_input','Invalid ability input.');
        try {
            // Recheck the native module at execution; discovery alone is not authority.
            if ($definition['module']!==null&&!((new SettingsService($this->store))->strictModules()[$definition['module']]??false)) {
                return self::error('ability_not_available','Unknown or unavailable ability.');
            }
            $result=match($name) {
                'published_consultation_fees'=>(new ConsultationFeeService($this->store))->published(),
                'published_practitioners'=>['practitioners'=>(new ClinicKnowledgeService($this->store))->practitioners()],
                'published_programmes'=>['programmes'=>(new ClinicKnowledgeService($this->store))->programmes()],
                'published_programme_detail'=>(new ClinicKnowledgeService($this->store))->programmeDetail($args['slug']),
                'search_clinic_knowledge'=>(new ClinicKnowledgeService($this->store))->search($args['query']),
                'search_articles'=>(new ClinicKnowledgeService($this->store))->searchArticles($args['query']),
                'clinic_context'=>($this->contextLoader)(),
                'owner_dashboard_summary'=>(new OwnerAbilityService($this->store))->dashboard(false),
                'appointment_summary'=>(new OwnerAbilityService($this->store))->appointments(),
                'support_enquiries'=>(new OwnerAbilityService($this->store))->supportEnquiries($args),
                'campaign_pages'=>(new OwnerAbilityService($this->store))->campaigns($args),
                'therapy_enquiry_summary'=>(new OwnerAbilityService($this->store))->therapyEnquiries($args),
                default=>throw new \LogicException('Unmapped native ability.'),
            };
            if ($result===null) return self::error('ability_not_found','Published programme not found.');
            if ($name==='clinic_context'&&is_array($result)) {
                $current=(new SettingsService($this->store))->strictModules();
                if ((!empty($result['account_access'])&&(empty($result['signed_in'])||empty($result['shop_enabled'])))||
                    (!empty($result['orders'])&&(empty($result['signed_in'])||empty($result['account_access'])))||
                    (empty($current['shop'])&&(!empty($result['shop_enabled'])||!empty($result['orders'])||!empty($result['cart'])||!empty($result['site']['products'])||isset($result['settings'])||isset($result['policies'])||
                        array_intersect(['shop','cart','checkout','orders'],array_keys($result['site']['pages']??[]))))||
                    (empty($current['campaigns'])&&(!empty($result['site']['campaigns'])||!empty($result['site']['programmes'])||isset($result['site']['pages']['campaigns'])))||
                    (empty($current['blog'])&&(!empty($result['articles'])||isset($result['site']['pages']['blog'])))) {
                    return self::error('ability_invalid_output','The lookup returned out-of-scope context.');
                }
            }
            $encoded=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if (strlen($encoded)>48000||!AbilitySchema::valid($result,$definition['output_schema'])) {
                return self::error('ability_invalid_output','The lookup did not return a valid bounded result.');
            }
            return $result;
        } catch (\Throwable $error) {
            error_log('Native ability '.$name.' unavailable: '.get_class($error));
            return self::error('ability_unavailable','The lookup is unavailable right now.');
        }
    }
}
