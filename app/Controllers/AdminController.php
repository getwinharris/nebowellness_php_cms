<?php
namespace App\Controllers;
use App\Services\{AgentConversation,AuditLogService,MailQueueService,AuthService,BlogDraftService,CampaignPageService,ConsultationService,EnvService,MailStorageService,MarkdownRenderer,MediaService,OrderService,ResourceService,SchemaService,SecretService,SettingsService,StoragePermissionService};
final class AdminController extends BaseController {
    protected string $layout = 'admin';
    public function __construct() {
        (new AuthService())->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->isApiRequest = in_array(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), ['/admin/agent/ask','/admin/blog/ai-draft','/admin/blog/enhance-title','/admin/blog/enhance-content'], true);
            $this->validateCsrf();
        }
        $this->seoKey = 'admin';
    }

    public function dashboard(): void{
        $shopEnabled = module_on('shop');
        $productCount = $shopEnabled ? count((new ResourceService('products'))->all()) : 0;
        $orderCount = $shopEnabled ? count((new ResourceService('orders'))->all()) : 0;
        // Public consultation booking has been retired, but historical requests remain
        // an owner responsibility. Keep them visible from the admin dashboard rather
        // than making their recovery depend on a public-site module flag.
        $appointments = (new ResourceService('appointments'))->all();
        $dashboardDays = ($_GET['days'] ?? null) === '30' ? 30 : 7;
        $agentMonitoring = null;
        try { $agentMonitoring = (new AuditLogService())->dashboardForPeriod($dashboardDays); }
        catch (\Throwable) { /* Monitoring must not prevent dashboard access. */ }
        usort($appointments, static fn(array $a, array $b): int => strcmp(
            (string)($b['created_at'] ?? $b['preferred_date'] ?? ''),
            (string)($a['created_at'] ?? $a['preferred_date'] ?? '')
        ));
        $this->render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'productCount' => $productCount,
            'orderCount' => $orderCount,
            'bookingCount' => count($appointments),
            'appointments' => array_slice($appointments, 0, 5),
            'agentMonitoring' => $agentMonitoring,
            'dashboardDays' => $dashboardDays,
            'shopEnabled' => $shopEnabled,
            'campaignsEnabled' => module_on('campaigns'),
        ]);
    }
    public function products(): void{
        $this->requireModule('shop');
        $this->render('admin/product-form',['pageTitle'=>'Products','title'=>'Products','collection'=>'products','items'=>(new ResourceService('products'))->all(),'categories'=>(new ResourceService('categories'))->all(),'mediaFiles'=>$this->mediaFor('products')]);
    }
    public function saveProduct(): void{$this->requireModule('shop');$this->saveProductRecord();}
    public function deleteProduct(): void{$this->requireModule('shop');$this->delete('products');}
    public function categories(): void{$this->requireModule('shop');$this->resource('Categories','categories',['name','description']);}
    public function saveCategory(): void{$this->requireModule('shop');$this->save('categories');}
    public function deleteCategory(): void{$this->requireModule('shop');$this->delete('categories');}
    public function coupons(): void{$this->requireModule('shop');$this->resource('Coupons','coupons',[
        'code','discount_type','discount_value','max_discount',
        'starts_at','ends_at','min_spend','max_spend',
        'usage_limit','usage_limit_per_customer','free_shipping','active',
    ]);}
    public function saveCoupon(): void{$this->requireModule('shop');$this->save('coupons');}
    public function deleteCoupon(): void{$this->requireModule('shop');$this->delete('coupons');}
    public function orders(): void{$this->requireModule('shop');$this->list('Orders','orders');}
    public function order(string $id): void{
        $this->requireModule('shop');
        $orders = (new ResourceService('orders'))->all();
        $order = null;
        foreach ($orders as $item) {
            if (($item['id'] ?? '') === $id) { $order = $item; break; }
        }
        $this->render('admin/detail',['pageTitle' => 'Order '.$id, 'title' => 'Order '.$id, 'order' => $order]);
    }
    public function saveOrderStatus(string $id): void{
        $this->requireModule('shop');
        $status = (string)($_POST['status'] ?? 'confirmed');
        $tracking = [
            'tracking_id'  => (string)($_POST['tracking_id'] ?? ''),
            'tracking_url' => (string)($_POST['tracking_url'] ?? ''),
            'courier_name' => (string)($_POST['courier_name'] ?? ''),
        ];
        try {
            (new OrderService())->updateStatus($id, $status, null, $tracking);
            (new AuditLogService())->record('save','order.status',$id,['status'=>$status,'tracking_id'=>$tracking['tracking_id']]);
            $this->flash('Order status updated.','success');
        } catch (\InvalidArgumentException $e) {
            // Surface the real reason — a missing tracking ID used to read as a generic
            // "unable to update", leaving the owner guessing.
            $this->flash($e->getMessage(),'error');
        } catch (\Throwable) {
            $this->flash('Unable to update order status.','error');
        }
        $this->redirect('/admin/orders/'.$id);
    }
    public function shipping(): void{$this->requireModule('shop');$this->render('admin/settings',['pageTitle' => 'Shipping', 'title' => 'Shipping']);}
    public function consultants(): void{
        $this->render('admin/consultant-form',['pageTitle'=>'Consultants','title'=>'Consultants','collection'=>'consultants','items'=>(new ResourceService('consultants'))->all(),'mediaFiles'=>$this->mediaFor('consultants')]);
    }
    public function saveConsultant(): void {
        $resource = new ResourceService('consultants');
        $id = is_string($_POST['id'] ?? null) ? trim($_POST['id']) : '';
        $revision = is_string($_POST['revision'] ?? null) ? $_POST['revision'] : '';
        $current = null;
        foreach ($resource->all() as $row) {
            if ($id !== '' && ($row['id'] ?? '') === $id) $current = $row;
        }
        if ($id !== '' && ($current === null || !hash_equals(ResourceService::revision($current), $revision))) {
            $this->flash('This profile changed. Reload the latest version.', 'error');
            $this->redirect('/admin/consultants');
        }
        $data = $current ?? [];
        try {
            foreach (['name','slug','credentials','speciality','description','gender','status','experience_years','display_order','registration_number','languages','photo_url','photo_urls'] as $field) {
                if (!array_key_exists($field, $_POST)) continue;
                if (!is_string($_POST[$field])) throw new \InvalidArgumentException('Invalid profile field.');
                $data[$field] = trim($_POST[$field]);
            }
            if (array_key_exists('registration_number', $_POST)) $data['registration_number'] = \App\Services\ConsultantService::registrationNumber($_POST['registration_number']);
            if (empty($data['name'])) throw new \InvalidArgumentException('Enter a name before saving.');
            $slug = trim((string)($data['slug'] ?? ''));
            if ($slug === '') $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $data['name']) ?? '', '-'));
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) throw new \InvalidArgumentException('Enter a valid profile URL slug.');
            foreach ($resource->all() as $row) {
                if (($row['slug'] ?? '') === $slug && ($row['id'] ?? '') !== $id) throw new \InvalidArgumentException('That profile slug already exists.');
            }
            $data['slug'] = $slug;
            if (!in_array($data['status'] ?? 'visible', ['visible','hidden'], true)) throw new \InvalidArgumentException('Choose a valid visibility.');
            if (isset($data['languages']) && is_string($data['languages'])) $data['languages'] = $this->splitList($data['languages']);
            $gallery = $data['photo_urls'] ?? [];
            $photos = is_array($gallery) ? $gallery : $this->splitList($gallery);
            if (!empty($data['photo_url'])) array_unshift($photos, (string)$data['photo_url']);
            $uploaded = $this->uploadedMedia('consultants');
            $photos = array_values(array_unique(array_filter(array_merge($photos, array_column($uploaded, 'url')))));
            $data['photo_urls'] = $photos;
            $data['photo_url'] = $photos[0] ?? '';
            if ($id !== '') $data['id'] = $id;
            $record = $resource->save($data, $id !== '' ? $revision : null);
        } catch (\InvalidArgumentException | \DomainException $error) {
            $this->flash($error->getMessage(), 'error');
            $this->redirect('/admin/consultants');
        }
        if ($uploaded) (new MediaService())->recordUsage($uploaded, 'consultants', (string)$record['id'], (string)$record['name']);
        (new AuditLogService())->record('save','consultants',(string)$record['id'],['fields'=>array_keys($data),'uploaded_media'=>count($uploaded)]);
        $this->flash('Consultant profile saved.', 'success');
        $this->redirect('/admin/consultants');
    }
    public function deleteConsultant(): void{
        $id=(string)($_POST['id']??'');
        (new ResourceService('consultants'))->delete($id); (new AuditLogService())->record('delete','consultants',$id); $this->flash('Deleted.','info'); $this->redirect('/admin/consultants');
    }
    public function appointments(): void{$this->list('Sessions','appointments');}
    public function consultationAnalytics(): void{$this->render('admin/consultation-analytics',['pageTitle'=>'Consultation Analytics','metrics'=>(new ConsultationService())->analytics()]);}
    public function campaigns(): void{$this->requireModule('campaigns');$this->resource('Campaign Pages','campaign_pages',$this->schemaFields('campaign_pages',['title','slug','summary','body','image_url','cta_label','cta_url','form_title','form_intro','form_button','form_success','form_phone','form_message','form_fields','form_require_otp','seo_title','seo_description','status']));}
    public function saveCampaign(): void{
        $this->requireModule('campaigns');
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($slug === '') $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '', '-'));
        if ($title === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->campaignSaveError('Enter a title and a URL slug using lowercase letters, numbers, and hyphens.');
        }
        $data = ['slug' => $slug, 'title' => $title];
        $id = trim((string)($_POST['id'] ?? ''));
        if ($id !== '') $data['id'] = $id;
        foreach ((new ResourceService('campaign_pages'))->all() as $existing) {
            if ($id !== '' && (string)($existing['id'] ?? '') === $id && !hash_equals(ResourceService::revision($existing), (string)($_POST['revision'] ?? ''))) {
                $this->campaignSaveError('This campaign changed since you opened it. Reload and edit the latest version.', status: 409);
            }
            if (($existing['slug'] ?? '') === $slug && (string)($existing['id'] ?? '') !== $id) {
                $this->campaignSaveError('That URL slug belongs to another campaign. Choose a unique slug.');
            }
        }
        foreach (['summary','body','image_url','cta_label','cta_url','form_title','form_intro','form_button','form_success','seo_title','seo_description'] as $field) {
            $data[$field] = trim((string)($_POST[$field] ?? ''));
        }
        foreach (['form_phone', 'form_message'] as $field) {
            $data[$field] = in_array($_POST[$field] ?? '', ['optional','required','hidden'], true) ? $_POST[$field] : 'optional';
        }
        try { $data += CampaignPageService::programFields($_POST); } catch (\InvalidArgumentException $error) { $this->campaignSaveError($error->getMessage()); }
        $data['form_require_otp'] = !empty($_POST['form_require_otp']);
        $fieldsJson = $_POST['form_fields'] ?? '';
        $decodedFields = is_string($fieldsJson) ? (trim($fieldsJson) === '' ? [] : json_decode($fieldsJson)) : null;
        if (!is_array($decodedFields)) $this->campaignSaveError('Send the custom fields as a list.');
        if (count($decodedFields) > CampaignPageService::MAX_FIELDS) {
            $this->campaignSaveError('Add at most ' . CampaignPageService::MAX_FIELDS . ' custom fields.');
        }
        [$data['form_fields'], $fieldErrors] = CampaignPageService::validateFields($decodedFields);
        if ($fieldErrors !== []) $this->campaignSaveError('Correct the highlighted custom fields and save again.', $fieldErrors);
        $uploaded = $this->uploadedMedia('campaign_pages');
        if ($uploaded && $data['image_url'] === '') $data['image_url'] = (string)$uploaded[0]['url'];
        $data['status'] = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
        try { $record = (new ResourceService('campaign_pages'))->save($data, $id !== '' ? (string)($_POST['revision'] ?? '') : null); }
        catch (\DomainException $error) { $this->campaignSaveError($error->getMessage(), status: 409); }
        if ($uploaded) (new MediaService())->recordUsage($uploaded, 'campaign_pages', (string)$record['id'], $title);
        (new AuditLogService())->record('save','campaign_pages',(string)$record['id'],['fields'=>array_keys($data),'uploaded_media'=>count($uploaded)]);
        $this->flash('Campaign page saved.', 'success');
        if ($this->wantsCampaignJson()) $this->jsonResponse(['redirect' => '/admin/campaigns']);
        $this->redirect('/admin/campaigns');
    }
    private function wantsCampaignJson(): bool {
        return ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    }
    private function campaignSaveError(string $message, array $fieldErrors = [], int $status = 422): never {
        if ($this->wantsCampaignJson()) {
            $this->jsonResponse(['error' => $message, 'fieldErrors' => (object)$fieldErrors], $status);
        }
        $this->flash($message, 'error');
        $this->redirect('/admin/campaigns');
    }
    public function deleteCampaign(): void{
        $this->requireModule('campaigns');
        $id = trim((string)($_POST['id'] ?? ''));
        if ($id !== '') {
            (new ResourceService('campaign_pages'))->delete($id);
            (new AuditLogService())->record('delete','campaign_pages',$id);
        }
        $this->flash('Campaign page deleted.', 'info');
        $this->redirect('/admin/campaigns');
    }
    public function settings(): void{$this->render('admin/settings',['pageTitle' => 'Settings', 'title' => 'Site Settings', 'settings'=>(new SettingsService())->public(), 'adminCredentials'=>(new EnvService())->adminCredentials()]);}
    public function saveSettings(): void{
        // The settings page posts several independent forms to this one action, so only
        // apply the fields actually submitted. Defaulting absent keys would blank out
        // every field belonging to the other forms (notably the GST configuration).
        $service = new SettingsService();
        if (array_key_exists('fee_action',$_POST)) {
            try {
                (new \App\Services\ConsultationFeeService())->save($_POST,is_string($_POST['fee_revision']??null)?$_POST['fee_revision']:'');
                (new AuditLogService())->record('save','settings','public',['fields'=>['consultation_catalogue']]);
                $this->flash('Consultation catalogue saved.','success');
            } catch (\InvalidArgumentException | \DomainException $error) { $this->flash($error->getMessage(),'error'); }
            catch (\Throwable) { $this->flash('Could not save the consultation catalogue.','error'); }
            $this->redirect('/admin/settings#consultation-catalogue');
        }
        if (array_key_exists('clinic_phones', $_POST) || array_key_exists('clinic_whatsapp', $_POST)) {
            try {
                $service->saveClinicChannels($_POST['clinic_phones']??null,$_POST['clinic_whatsapp']??null,is_string($_POST['contact_revision']??null)?$_POST['contact_revision']:'');
                (new AuditLogService())->record('save','settings','public',['fields'=>['clinic_phones','clinic_whatsapp']]);
                $this->flash('Contact channels saved.','success');
            } catch (\InvalidArgumentException | \DomainException $error) { $this->flash($error->getMessage(),'error'); }
            catch (\Throwable) { $this->flash('Could not save contact channels.','error'); }
            $this->redirect('/admin/settings');
        }
        if (array_key_exists('foundation_customers_min', $_POST)) {
            try {
                $revision = is_string($_POST['foundation_revision'] ?? null) ? $_POST['foundation_revision'] : '';
                $service->saveFoundationCustomersMin($_POST['foundation_customers_min'], $revision);
                (new AuditLogService())->record('save','settings','public',['fields'=>['foundation_customers_min']]);
                $this->flash('Foundation figure saved.','success');
            } catch (\InvalidArgumentException | \DomainException $error) {
                $this->flash($error->getMessage(),'error');
            } catch (\Throwable) {
                $this->flash('The figure could not be saved. Please reload and try again.','error');
            }
            $this->redirect('/admin/settings');
        }
        // Only patch the submitted fields, so an unrelated settings save cannot overwrite the figure.
        $settings = [];
        foreach (['shipping_mode','currency','timezone','gstin','gst_legal_name','gst_trade_name','gst_address','gst_state','gst_state_code','default_hsn_code'] as $key) {
            if (array_key_exists($key, $_POST)) $settings[$key] = trim((string)$_POST[$key]);
        }
        if (array_key_exists('flat_rate', $_POST)) $settings['flat_rate'] = max(0, (float)$_POST['flat_rate']);
        if (array_key_exists('default_gst_rate', $_POST)) $settings['default_gst_rate'] = max(0, min(100, (float)$_POST['default_gst_rate']));
        $changedModules = [];
        foreach (array_keys(SettingsService::MODULES) as $key) {
            $field = 'module_' . $key;
            if (!array_key_exists($field, $_POST)) continue;
            $settings[$field] = ((string)$_POST[$field] === '1') ? '1' : '0';
            $changedModules[] = $field;
        }
        $service->savePublic($settings);
        (new AuditLogService())->record('save','settings','public',['fields'=>array_merge(array_values(array_intersect(['shipping_mode','flat_rate','currency','timezone','gstin'], array_keys($_POST))), $changedModules)]);
        $this->flash('Settings saved.','success');
        $this->redirect('/admin/settings');
    }
    public function saveAdminCredentials(): void{(new EnvService())->saveAdminCredentials($_POST); (new AuditLogService())->record('save','admin-credentials','env'); $this->flash('Admin credentials saved.','success'); $this->redirect('/admin/settings');}
    public function integrations(): void {
        $secrets=new SecretService();
        $config=$secrets->getModelConfig();
        $this->render('admin/integrations',['pageTitle'=>'Integrations','secrets'=>$secrets->all(),
            'probeTargets'=>\App\Services\AiClient::probeTargets($config),
            'googleCallbackUri'=>\App\Services\GoogleCustomerAuthService::callbackUri()]);
    }
    public function saveIntegrations(): void {
        try {
            $values = \App\Integrations\GoogleSiteKit\GoogleSiteKitClient::settings($_POST);
            (new SecretService())->save(SecretService::integrationChanges($values));
            (new AuditLogService())->record('save','integrations','secrets');
            $this->flash('Integration settings saved.', 'success');
        } catch (\InvalidArgumentException $error) {
            $this->flash($error->getMessage(), 'error');
        } catch (\Throwable) {
            $this->flash('Integration settings could not be saved. Try again.', 'error');
        }
        $this->redirect('/admin/integrations');
    }

    public function searchPerformance(): void {
        $this->seoOverrides['title'] = 'Search performance';
        $days = (int)($_GET['days'] ?? 28);
        if (!in_array($days, [7, 28, 90], true)) $days = 28;
        $_SESSION['google_report_cache'] ??= [];
        $reports = (new \App\Integrations\GoogleSiteKit\GoogleSiteKitClient())->reports((new SecretService())->all(), $days, $_SESSION['google_report_cache']);
        $this->render('admin/search-performance', ['pageTitle'=>'Search performance', 'reports'=>$reports, 'days'=>$days]);
    }
    public function agent(): void{
        $secrets = new SecretService();
        $modelConfig = $secrets->getModelConfig();
        // Everything the owner may write @ against, so the chat can autocomplete rather
        // than making them remember slugs and filenames.
        $attachables = (new \App\Services\AgentAttachmentService())->catalogue();
        $agentMonitoring = null;
        $agentTraces = [];
        try {
            $audit = new AuditLogService();
            $now = time();
            $agentMonitoring = AuditLogService::agentSummary($audit->agentRunsBetween($now - 86400, $now), $now);
            $agentTraces = $audit->latestAgentTraces($now);
        }
        catch (\Throwable $error) { error_log('Agent workspace monitoring unavailable: ' . get_class($error)); }
        $this->render('admin/agent',['pageTitle'=>'Enterprise Agent','modelConfig'=>$modelConfig,'attachables'=>$attachables,'shopEnabled'=>module_on('shop'),'campaignsEnabled'=>module_on('campaigns'),'agentMonitoring'=>$agentMonitoring,'agentTraces'=>$agentTraces]);
    }
    public function agentAsk(): void{
        $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
        $chat = new AgentConversation('admin');
        $turnId = $this->handleConversation($chat, $message);
        if ($message === '' || mb_strlen($message) > 8000) {
            $this->jsonResponse(['error'=>'Enter a message of 1 to 8,000 characters.'], 422);
            return;
        }
        $startedAt = microtime(true);
        $trace = new \App\Services\AgentRunTrace();
        header('X-Agent-Trace: ' . $trace->id);
        $this->journalResponse($chat, $turnId, $message, $trace, true);
        try {
            // @terms, @privacy, @some-article and @filename.jpg pull that document into
            // the prompt. Resolved here so both a draft command and an ordinary question
            // can refer to one.
            $attachments = (new \App\Services\AgentAttachmentService())->resolve($message);

            // /create-blog and /add-product draft content and hand back a filled-in form.
            // The agent never writes to the site: the owner reads the draft and saves it
            // through the same route the normal admin screens post to.
            $command = \App\Services\AgentDraftService::command($message);
            if ($command !== null) {
                if ($command === 'create-blog' && !module_on('blog')) {
                    $this->jsonResponse(['error'=>'Blog is disabled. Enable it in Site Settings before drafting an article.'],409);
                    return;
                }
                if ($command === 'add-product') {
                    $this->jsonResponse(['error'=>'Retail product drafting is retired in bapXcms.'],409);
                    return;
                }
                $draft = (new \App\Services\AgentDraftService(ai: new \App\Services\AiClient(trace: $trace)))->draft(
                    $command,
                    \App\Services\AgentDraftService::subject($message),
                    $attachments['context']
                );
                $draft['missing'] = $attachments['missing'];
                $outcome = match ($draft['mode']) {
                    'generated' => 'draft',
                    'manual' => 'manual',
                    default => 'draft_unavailable',
                };
                (new AuditLogService())->agentRun('admin', $outcome, $startedAt, $trace);
                $this->jsonResponse(['draft' => $draft]);
                return;
            }
            $secrets = new SecretService();
            $modelConfig = $secrets->getModelConfig();
            $tools=new \App\Services\AgentToolRegistry(question:$message);
            $context='Retail commerce is retired. Use service, CMS and CRM abilities only.';
            $context.="\nUse an authorized tool for current operational records. Do not load unrelated data for ordinary writing or conversation.";
            if ($attachments['context'] !== '') {
                $context .= "\n\nDocuments the owner attached with @:" . $attachments['context'];
            }
            if (!empty($modelConfig['apiKey'])) {
                if ($turnId !== null) $context .= $chat->promptContext();
                $answer = $this->callAiApi($modelConfig, $message, $context, $trace, $tools);
            } else {
                $answer = "AI model not configured. Go to Admin → Integrations and set api_endpoint, ai_api_key, and agent_model.";
            }
            $evidence=$tools->modelResults();
            $failed = empty($modelConfig['apiKey']) || \App\Services\AiClient::isError($answer)
                || !(new \App\Services\AiReplyCleaner())->usableAnswer($answer,$message)
                || !$tools->acceptsEvidence($message,$answer,$trace)
                || !\App\Services\SupportBotService::matchesRetrievedFacts($message,$answer,$evidence);
            if ($failed) $trace->markProviderResponseUnusable();
            (new AuditLogService())->agentRun('admin', $failed ? 'error' : 'model', $startedAt, $trace);
            if ($failed) {
                $this->jsonResponse(['error'=>'The model could not answer. Try again or use a clinic workflow from the sidebar.', 'missing'=>$attachments['missing']],502);
                return;
            }
            $response = ['answer' => $answer, 'missing' => $attachments['missing'],'response_mode'=>$trace->hasNativeToolAnswer()?'model_with_retrieval':'model_direct','sources'=>$trace->hasNativeToolAnswer()?$trace->sources():[]];
            if ($turnId !== null) $chat->remember($turnId, $message, $response, $trace->id);
            $this->jsonResponse($response);
        } catch (\Throwable $e) {
            (new AuditLogService())->agentRun('admin', 'error', $startedAt, $trace);
            error_log('Owner agent failed: ' . get_class($e));
            $this->jsonResponse(['error'=>'The agent could not complete the request. Please try again.'],500);
        }
    }
    /**
     * One-shot model call for the blog Enhance buttons. Reuses the configured agent
     * model rather than adding a second provider path. Returns null when no key is set
     * so the caller can give the owner a specific message.
     */
    /** Shared with the support bot so both agents strip scaffold the same way. */
    private function cleanAnswer(string $text): string {
        $cleaner = new \App\Services\AiReplyCleaner();
        $clean = $cleaner->clean($text, '');
        if ($clean === '' || $cleaner->looksInternal($clean)) {
            return '';
        }
        return $clean;
    }

    /**
     * The provider call now lives in AiClient, shared with the customer support agent,
     * which used to hardcode its own endpoint and model and so ignored whatever the
     * admin had configured here. This method keeps the admin's prompt and its
     * error-to-screen behaviour.
     */
    private function callAiApi(array $config, string $message, string $context, ?\App\Services\AgentRunTrace $trace = null, ?\App\Services\AgentToolRegistry $tools = null): string {
        // "Answer directly / give only the final answer" produced a bare "5" for
        // "how many enquiries came for orders" — and that 5 was the order count from
        // the statistics blob, not enquiries, of which there are none. A number with no
        // stated source is unverifiable, so the persona now asks for the figure, how it
        // was arrived at, and what was checked.
        $tools ??= new \App\Services\AgentToolRegistry(question:$message);
        $prompt = $tools->instructions($message) . "\nYou are the admin analyst for Nebo Lifestyle Clinic, talking to its owner. "
            . "For operational questions be brief and concrete. For drafting, rewriting or explanations, follow the requested format and length.\n"
            . "When you give a number, say what it counts, where it came from, and any judgement you made "
            . "to arrive at it. If a word in the question is open to interpretation, say how you read it.\n"
            . "If the data shows none of something, say so plainly. Never substitute a different number that "
            . "happens to be available.\n"
            . "End with the single most useful next step, when there is one.\n"
            . "Do not restate your role, the context, the constraints or the question. Do not show your reasoning "
            . "or a plan.\n"
            . "You can look things up. Use the tools for current service operations, "
            . "support enquiry, article or campaign page, instead of saying you do not have the "
            . "information. Only report what a tool returned; never invent a booking, payment or a "
            . "figure.\n"
            . "\n" . \App\Services\AgentSessionService::redact($context) . "\n\nQuestion: " . \App\Services\AgentSessionService::redact($message);
        // With tools the agent can answer "where is order 9426" — the data was always
        // there, but nothing let the model ask for it.
        $client = new \App\Services\AiClient(trace: $trace);
        $answer = $client->completeWithTools($prompt, $tools, requireTool:false);
        if (\App\Services\AiClient::isError($answer)) return $answer;

        // A bare unsupported figure is unusable; don't launch a second full loop
        // outside the shared execution budget.
        if (self::isBareFigure($answer) && !self::wantsNumberOnly($message)) {
            $trace?->markProviderResponseUnusable();
            return '';
        }
        return $this->cleanAnswer($answer);
    }

    /** A reply that is only a number, with nothing saying what it counts. */
    private static function isBareFigure(string $answer): bool {
        $stripped = trim(preg_replace('/[\s*_`#.,:₹%-]+/u', '', $answer) ?? '');
        return $stripped !== '' && mb_strlen($stripped) <= 12 && preg_match('/^\d+$/', $stripped) === 1;
    }

    /** The owner explicitly asked for just the figure. */
    private static function wantsNumberOnly(string $question): bool {
        return (bool)preg_match(
            '/\b(?:just|only)\s+(?:the\s+)?(?:number|count|figure|total)\b'   // "just the number"
            . '|\b(?:number|count|figure|total)\s+only\b'                     // "the count only"
            . '|\bno\s+explanation\b/i',
            $question
        );
    }
    public function appearance(): void{
        $s=(new SettingsService())->public();
        $d = ['#4472C4','#70AD47','#F7F9FA','#222222','#4472C4'];
        $this->render('admin/appearance',['pageTitle'=>'Logo & Favicon','logo_url'=>$s['logo_url']??'','favicon_url'=>$s['favicon_url']??'','palette_primary'=>$s['palette_primary']??$d[0],'palette_secondary'=>$s['palette_secondary']??$d[1],'palette_canvas'=>$s['palette_canvas']??$d[2],'palette_text'=>$s['palette_text']??$d[3],'palette_link'=>$s['palette_link']??$d[4]]);
    }
    public function saveAppearance(): void{
        $s=(new SettingsService())->public(); $d=app_path('assets/images/brand'); if(!is_dir($d)) mkdir($d,0775,true); $e='';
        if(!empty($_POST['logo_remove'])){$s['logo_url']='';}
        if(!empty($_FILES['logo_file']['name'])&&$_FILES['logo_file']['error']===UPLOAD_ERR_OK){
            $i=getimagesize($_FILES['logo_file']['tmp_name']);$w=$i[0]??0;$h=$i[1]??0;$sz=$_FILES['logo_file']['size'];
            if($w>512||$h>512)$e='Logo exceeds 512×512 px.';
            elseif($sz>102400)$e='Logo exceeds 100 KB.';
            else{$x=strtolower(pathinfo($_FILES['logo_file']['name'],PATHINFO_EXTENSION));move_uploaded_file($_FILES['logo_file']['tmp_name'],$d.'/logo.'.$x);$s['logo_url']='/assets/images/brand/logo.'.$x;}
        }
        if(!empty($_POST['favicon_remove'])){$s['favicon_url']='';}
        if(!empty($_FILES['favicon_file']['name'])&&$_FILES['favicon_file']['error']===UPLOAD_ERR_OK){
            $i=getimagesize($_FILES['favicon_file']['tmp_name']);$w=$i[0]??0;$h=$i[1]??0;$sz=$_FILES['favicon_file']['size'];
            if($w>64||$h>64)$e='Favicon exceeds 64×64 px.';
            elseif($sz>51200)$e='Favicon exceeds 50 KB.';
            else{$x=strtolower(pathinfo($_FILES['favicon_file']['name'],PATHINFO_EXTENSION));move_uploaded_file($_FILES['favicon_file']['tmp_name'],$d.'/favicon.'.$x);$s['favicon_url']='/assets/images/brand/favicon.'.$x;}
        }
        $paletteBefore = array_intersect_key($s, array_flip(['palette_primary','palette_secondary','palette_canvas','palette_text','palette_link']));
        if (!empty($_POST['reset_palette'])) {
            foreach (['palette_primary','palette_secondary','palette_canvas','palette_text','palette_link'] as $k) unset($s[$k]);
        } else {
            $defaults = ['#4472C4','#70AD47','#F7F9FA','#222222','#4472C4'];
            $keys = ['palette_primary','palette_secondary','palette_canvas','palette_text','palette_link'];
            $vals = [];
            $errs = [];
            foreach ($keys as $i => $k) {
                $v = strtoupper(trim((string)($_POST[$k] ?? '')));
                if ($v === '') continue;
                if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v)) {
                    $errs[] = 'Invalid hex color for ' . str_replace('_', ' ', $k) . '.';
                    continue;
                }
                if (strlen($v) === 4) $v = '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
                $vals[$k] = $v;
            }
            if (empty($errs) && !empty($vals)) {
                $canvas = $vals['palette_canvas'] ?? $s['palette_canvas'] ?? $defaults[2];
                $text = $vals['palette_text'] ?? $s['palette_text'] ?? $defaults[3];
                $link = $vals['palette_link'] ?? $s['palette_link'] ?? $defaults[4];
                $tc = self::contrast($text, $canvas);
                if ($tc < 4.5) $errs[] = 'Text contrast ratio ' . number_format($tc,2) . ':1 is below 4.5:1 minimum against canvas.';
                else { $lc = self::contrast($link, $canvas); if ($lc < 4.5) $errs[] = 'Link contrast ratio ' . number_format($lc,2) . ':1 is below 4.5:1 minimum against canvas.'; }
            }
            if ($errs) { $e = implode(' ', $errs); }
            else { foreach ($vals as $k => $v) $s[$k] = $v; }
        }
        $paletteAfter = array_intersect_key($s, array_flip(['palette_primary','palette_secondary','palette_canvas','palette_text','palette_link']));
        $changed = array_keys(array_diff_assoc($paletteAfter, $paletteBefore));
        (new SettingsService())->savePublic($s);
        if ($changed) (new AuditLogService())->record('save','appearance','palette',['changed_fields'=>$changed,'reset'=>!empty($_POST['reset_palette'])]);
        $this->flash($e ?: 'Appearance saved.','success'); $this->redirect('/admin/appearance');
    }
    public function contactSubmissions(): void{
        // Campaign enquiries store their custom answers in form_data, which the generic
        // field list never showed. Pass the column flag so the saved answers appear in
        // the same review table the owner already uses; output is escaped in the view.
        $this->resource('Contact Submissions','contact_submissions',['name','email','phone','subject','message','status'],['formDataColumn'=>true]);
    }
    public function saveContactSubmission(): void{$this->save('contact_submissions');}
    public function deleteContactSubmission(): void{$this->delete('contact_submissions');}
    public function supportTickets(): void {
        header('Cache-Control: private, no-store');
        $service = new \App\Services\SupportTicketService();
        $tickets = array_values(array_filter($service->all(), static fn(array $row): bool => trim((string)($row['message'] ?? '')) !== '' && preg_match('/^(?:[a-f0-9]{16}|[a-f0-9]{32})$/D',(string)($row['id'] ?? ''))));
        usort($tickets,static fn(array $a,array $b): int => strcmp((string)($b['updated_at'] ?? $b['created_at'] ?? ''),(string)($a['updated_at'] ?? $a['created_at'] ?? '')));
        $filter = is_string($_GET['status'] ?? null) && in_array($_GET['status'],['open','answered','closed'],true) ? $_GET['status'] : 'all';
        $search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,120) : '';
        $selected = is_string($_GET['ticket'] ?? null) ? $service->find($_GET['ticket']) : null;
        $counts = ['all'=>count($tickets),'open'=>0,'answered'=>0,'closed'=>0];
        foreach ($tickets as $row) if (in_array($row['status'] ?? 'open',['open','answered','closed'],true)) $counts[$row['status'] ?? 'open']++;
        $tickets = array_values(array_filter($tickets,static fn(array $row): bool => ($filter==='all'||($row['status']??'open')===$filter) && ($search===''||str_contains(mb_strtolower(($row['customer_email']??'').' '.($row['message']??'').' '.($row['id']??'')),mb_strtolower($search)))));
        $selected ??= $tickets[0] ?? null;
        $timeline = []; $session = null; $archiveError = false;
        if ($selected) {
            try { $timeline = (new \App\Services\CustomerCrmService())->timeline((string)($selected['customer_email']??'')); } catch (\Throwable) {}
            if (!empty($selected['session_id'])) {
                try { $session = (new \App\Services\AgentSessionService())->get((string)$selected['session_id']); } catch (\Throwable) { $archiveError=true; }
            }
        }
        $this->render('admin/support-inbox',['pageTitle'=>'Support inbox','tickets'=>array_slice($tickets,0,100),'selected'=>$selected,'counts'=>$counts,'filter'=>$filter,'search'=>$search,'timeline'=>$timeline,'session'=>$session,'archiveError'=>$archiveError]);
    }
    public function saveSupportTicket(): void {
        $id = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'reply';
        $value = $_POST['value'] ?? $_POST['reply'] ?? '';
        try {
            if (!is_string($value) || !is_string($_POST['revision'] ?? null)) throw new \InvalidArgumentException('Reload the ticket before saving.');
            (new \App\Services\SupportTicketService())->update($id,$_POST['revision'],$action,$value);
            (new AuditLogService())->record('save','support_tickets',$id,['action'=>$action]);
            $this->flash($action==='reply'?'Reply saved for the customer to track.':($action==='note'?'Internal note added.':'Status updated.'),'success');
        } catch (\InvalidArgumentException | \DomainException $error) { $this->flash($error->getMessage(),'error'); }
        catch (\Throwable) { $this->flash('Unable to save ticket.','error'); }
        $this->redirect('/admin/support-tickets?ticket='.rawurlencode($id));
    }

    public function agentSessions(): void {
        header('Cache-Control: private, no-store');
        $service = new \App\Services\AgentSessionService();
        $items = []; $selected = null; $unavailable = false;
        try {
            $items = $service->recent();
            if (is_string($_GET['session'] ?? null)) $selected=$service->get($_GET['session']);
        } catch (\Throwable) { $unavailable=true; }
        $this->render('admin/agent-sessions',['pageTitle'=>'Agent sessions','items'=>$items,'selected'=>$selected,'unavailable'=>$unavailable]);
    }

    public function agentSessionJson(string $id): void {
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        try { $record=(new \App\Services\AgentSessionService())->get($id); }
        catch (\Throwable) { $this->jsonResponse(['error'=>'Archive unavailable.'],503); }
        if (!$record) $this->jsonResponse(['error'=>'Session not found or expired.'],404);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="nebo-agent-'.$id.'.json"');
        (new AuditLogService())->record('export','agent_sessions',$id);
        echo json_encode($record, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
        exit;
    }

    public function purgeAgentSessions(): void {
        try {
            $count=(new \App\Services\AgentSessionService())->purgeExpired();
            (new AuditLogService())->record('purge','agent_sessions','expired',['count'=>$count]);
            $this->flash($count.' expired session records removed.','success');
        } catch (\Throwable) { $this->flash('Could not remove expired sessions.','error'); }
        $this->redirect('/admin/agent-sessions');
    }

    public function customers(): void {
        header('Cache-Control: private, no-store');
        $service = new \App\Services\CustomerCrmService();
        $search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,120) : '';
        $email = is_string($_GET['email'] ?? null) && filter_var($_GET['email'],FILTER_VALIDATE_EMAIL) ? strtolower($_GET['email']) : '';
        $items = []; $timeline=[]; $unavailable=false;
        try { $items=$service->directory($search); if ($email!=='') $timeline=$service->timeline($email); }
        catch (\Throwable) { $unavailable=true; }
        $this->render('admin/customers',['pageTitle'=>'Customers','items'=>$items,'search'=>$search,'email'=>$email,'timeline'=>$timeline,'unavailable'=>$unavailable]);
    }
    public function emailInbox(): void{$this->render('admin/mailbox',['pageTitle'=>'Email Inbox','title'=>'Email Inbox','box'=>'inbox','items'=>(new MailStorageService())->inbox()]);}
    public function emailOutbox(): void{$this->render('admin/mailbox',['pageTitle'=>'Email Outbox','title'=>'Email Outbox','box'=>'outbox','items'=>(new MailStorageService())->outbox()]);}
    public function media(): void {
        $archived = ($_GET['view'] ?? '') === 'archive';
        $this->render('admin/media', [
            'pageTitle'=>'Media Library', 'archived'=>$archived, 'items'=>(new MediaService())->library($archived)
        ]);
    }

    public function archiveMedia(): void {
        $action = $_POST['action'] ?? '';
        $returnTo = $action === 'restore' ? '/admin/media?view=archive' : '/admin/media';
        if (($_POST['confirm_change'] ?? '') !== '1' || !in_array($action, ['archive', 'restore'], true)) {
            $this->flash('Confirm the selected action first.', 'error');
            $this->redirect($returnTo);
        }
        try {
            $result = (new MediaService())->setArchived(is_array($_POST['media_ids'] ?? null) ? $_POST['media_ids'] : [], $action === 'archive');
            foreach ($result['changed'] as $id) (new AuditLogService())->record($action, 'media', $id);
            $message = count($result['changed']) . ($action === 'archive' ? ' archived.' : ' restored.');
            if ($result['blocked']) $message .= ' ' . $result['blocked'] . ' protected or already changed.';
            if ($result['failed']) $message .= ' ' . $result['failed'] . ' could not be updated. Try again.';
            $this->flash($message, $result['failed'] || $result['blocked'] ? 'warning' : 'success');
        } catch (\InvalidArgumentException $error) {
            $this->flash($error->getMessage(), 'error');
        } catch (\Throwable) {
            $this->flash('Media usage could not be verified. Try again when the library is available.', 'error');
        }
        $this->redirect($returnTo);
    }
    /**
     * Every upload in the admin lands in the media library, whichever screen sent it.
     *
     * The agent chat uploads here too, and comes back to the chat rather than dumping
     * the owner on the media page mid-conversation. The return path is restricted to
     * this admin so a crafted form cannot bounce anyone off-site.
     */
    public function uploadMedia(): void{
        $uploaded=(new MediaService())->upload($_FILES['media_files'] ?? [], $_POST['context'] ?? 'shared', $_POST['description'] ?? null);
        (new AuditLogService())->record('upload','media','',['count'=>count($uploaded),'context'=>$_POST['context'] ?? 'shared']);
        $names = array_filter(array_map(fn($f) => basename((string)($f['url'] ?? $f['path'] ?? '')), $uploaded));
        $this->flash(
            count($uploaded).' media file'.(count($uploaded) === 1 ? '' : 's').' uploaded.'
            . ($names ? ' Attach with @'.implode(' or @', $names).'.' : ''),
            'success'
        );
        $redirect = (string)($_POST['redirect'] ?? '');
        $this->redirect(preg_match('#^/admin/[a-z0-9/-]*$#i', $redirect) ? $redirect : '/admin/media');
    }
    public function fixPermissions(): void{(new StoragePermissionService())->fix(); (new AuditLogService())->record('fix','permissions','storage'); $this->flash('Storage permissions checked and updated where PHP is allowed.','success'); $this->redirect('/admin/settings');}
    /** Test a pinned provider/model without changing production routing state. */
    public function testAi(): void {
        $this->validateCsrf();
        $mode=$_POST['probe_mode']??'text';
        $target=$_POST['probe_model']??'';
        if (!is_string($mode)||!is_string($target)||strlen($mode)>16||strlen($target)>200) {
            $result=['ok'=>false,'reason'=>'invalid_target','message'=>'Select a valid diagnostic mode and model.'];
        } else {
            try { $result=(new \App\Services\AiClient())->probe($mode,$target); }
            catch (\Throwable) { $result=['ok'=>false,'reason'=>'diagnostic_unavailable','message'=>'Unable to read diagnostic configuration.']; }
        }
        $_SESSION['ai_test_result']=$result;
        (new AuditLogService())->record('test','ai',(string)($result['model']??''),[
            'result'=>$result['ok']?'ok':'failed','mode'=>$result['mode']??'invalid',
            'reason'=>$result['reason'],'trace_id'=>$result['trace_id']??null,
            'duration_ms'=>$result['duration_ms']??null,'tool_calls'=>$result['tool_calls']??0,
            'steps'=>$result['steps']??[],
        ]);
        $this->redirect('/admin/integrations');
    }

    public function testEmail(): void{
        $to = trim((string)($_POST['test_email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['mail_test_result'] = ['ok'=>false,'transport'=>'','message'=>'Enter a valid email address.'];
            $this->redirect('/admin/integrations');
        }
        $mailer = new \App\Services\SmtpMailer((new SecretService())->all());
        if (!$mailer->configured()) {
            $_SESSION['mail_test_result'] = ['ok'=>false,'transport'=>'','message'=>'SMTP is not fully configured. Save the host, port, mailbox username, password and From Email above first.'];
            $this->redirect('/admin/integrations');
        }
        $transport = $mailer->transport();
        $sentAt = date('c');
        try {
            $mailer->send(
                $to,
                'Test email from Nebo Lifestyle Clinic',
                '<p>This is a test message sent from Admin &rarr; Integrations.</p>'
                . '<p>Transport: <strong>' . e($transport) . '</strong><br>From: <strong>' . e($mailer->fromEmail()) . '</strong><br>Sent: ' . e($sentAt) . '</p>'
                . '<p>If you received this, transactional email is working.</p>'
            );
            $_SESSION['mail_test_result'] = ['ok'=>true,'transport'=>$transport,'message'=>'Test email sent to ' . $to . ' from ' . $mailer->fromEmail() . '. Check the inbox, and the spam folder.'];
            (new AuditLogService())->record('test','email',$to,['transport'=>$transport,'result'=>'sent']);
        } catch (\Throwable $e) {
            $_SESSION['mail_test_result'] = ['ok'=>false,'transport'=>$transport,'message'=>$e->getMessage()];
            (new AuditLogService())->record('test','email',$to,['transport'=>$transport,'result'=>'failed','error'=>$e->getMessage()]);
        }
        $this->redirect('/admin/integrations');
    }
    public function blog(): void{
        $this->requireModule('blog');
        $blog = new \App\Services\BlogService();
        // The editor's "Choose from uploads" picker needs the library, otherwise it
        // renders empty and the only way to set an image is to type a path.
        $this->render('admin/blog',[
            'pageTitle'=>'Blog','title'=>'Blog Posts',
            'posts'=>$blog->all(true),'categories'=>$blog->categories(),
            'mediaFiles'=>(new MediaService())->all('blog'),
            'currentAuthor'=>$this->currentAuthorName(),
        ]);
    }
    /** The signed-in admin's own name, falling back to a generic byline. */
    private function currentAuthorName(): string {
        $user = $_SESSION['user'] ?? [];
        foreach (['name', 'username'] as $key) {
            $value = trim((string)($user[$key] ?? ''));
            if ($value !== '') return $value;
        }
        $email = trim((string)($user['email'] ?? ''));
        if ($email !== '') return explode('@', $email)[0];
        return 'Admin';
    }

    public function saveBlog(): void{
        $blog = new \App\Services\BlogService();
        $slug = trim((string)($_POST['slug'] ?? ''));
        // Only a post that did not exist before triggers the newsletter, so editing an
        // article never re-mails everyone who already received it.
        $isNew = $slug === '' || $blog->find($slug) === null;

        // The date and the author are facts the site already knows, so it should not be
        // asking for them. Left blank they were saved blank, which is why a new post
        // showed no date and sorted to the bottom of the list. Both stay editable for
        // the case where a post is backdated or written by someone else.
        $post = $_POST;
        if (trim((string)($post['published_at'] ?? '')) === '') {
            $existing = $slug !== '' ? $blog->find($slug) : null;
            $post['published_at'] = (string)($existing['published_at'] ?? '') ?: date('Y-m-d');
        }
        if (trim((string)($post['author'] ?? '')) === '') {
            $post['author'] = $this->currentAuthorName();
        }
        $post['updated_at'] = date('Y-m-d');
        $blog->save($post);
        (new AuditLogService())->record('save','blog',$_POST['slug'] ?? '');
        $sent = 0;
        if ($isNew && ($_POST['notify_subscribers'] ?? '') === '1') {
            $sent = $this->sendBlogNewsletter($_POST);
        }
        $this->flash($sent > 0 ? "Blog post saved. Newsletter sent to {$sent} subscriber(s)." : 'Blog post saved.','success');
        $this->redirect('/admin/blog');
    }
    /** Registered customers only — the owner and consultants are not subscribers. */
    private function sendBlogNewsletter(array $post): int {
        try {
            $recipients = [];
            foreach ((new \App\Services\DatabaseService())->read('users') as $user) {
                $role = (string)($user['role'] ?? 'customer');
                if ($role !== 'customer') continue;
                $email = trim((string)($user['email'] ?? ''));
                if ($email !== '') $recipients[] = $email;
            }
            return (new MailQueueService())->enqueueBlogNewsletter($post, array_unique($recipients));
        } catch (\Throwable $e) {
            error_log('Blog newsletter failed: ' . $e->getMessage());
            return 0;
        }
    }
    /** Flip a post between published and hidden without opening the editor. */
    public function toggleBlog(): void{
        $slug = trim((string)($_POST['slug'] ?? ''));
        $blog = new \App\Services\BlogService();
        $post = $slug !== '' ? $blog->find($slug) : null;
        if (!$post) { $this->flash('Post not found.','error'); $this->redirect('/admin/blog'); }
        $post['published'] = empty($post['published']);
        $blog->save($post);
        (new AuditLogService())->record('save','blog.published',$slug,['published'=>$post['published']]);
        $this->flash($post['published'] ? 'Post is now visible on the site.' : 'Post is now hidden from the site.','success');
        $this->redirect('/admin/blog');
    }
    public function deleteBlog(): void{
        $slug = (string)($_POST['slug'] ?? '');
        if ($slug !== '') {
            (new \App\Services\BlogService())->delete($slug);
            (new AuditLogService())->record('delete','blog',$slug);
        }
        $this->flash('Blog post deleted.','info');
        $this->redirect('/admin/blog');
    }
    public function previewBlog(): void{
        $this->layout = 'app';
        $this->seoKey = 'blog.post';
        $content = (new MarkdownRenderer())->render($_POST['content'] ?? '');
        $meta = [
            'title' => $_POST['title'] ?? 'Preview',
            'slug' => $_POST['slug'] ?? '',
            'category' => $_POST['category'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'summary' => $_POST['summary'] ?? '',
            'published_at' => $_POST['published_at'] ?? date('Y-m-d'),
            'author' => $_POST['author'] ?? 'Admin',
            'og_image' => $_POST['og_image'] ?? '',
            'image_alt' => $_POST['image_alt'] ?? '',
            'source_url' => $_POST['source_url'] ?? '',
            'template' => $_POST['template'] ?? 'editorial',
        ];
        $slug = $_POST['slug'] ?? 'preview';
        $this->render('public/blog-post', [
            'content' => $content,
            'meta' => $meta,
            'slug' => $slug,
        ]);
    }
    public function aiDraftBlog(): void { $this->blogSuggestion('draft'); }
    public function enhanceBlogTitle(): void { $this->blogSuggestion('title'); }
    public function enhanceBlogContent(): void { $this->blogSuggestion('content'); }

    private function blogSuggestion(string $field): void {
        header('Cache-Control: private, no-store');
        try {
            if (!(new SettingsService())->strictModules()['blog']) $this->jsonResponse(['error'=>'Blog is disabled.'],404);
            $service=new BlogDraftService();
            if ($field==='draft') {
                if (BlogDraftService::field($_POST['content']??'', BlogDraftService::MAX_ARTICLE_BYTES)!=='') {
                    $this->jsonResponse(['error'=>'Use Enhance content to preserve an existing article.'],409);
                }
                $result=$service->draft(
                    BlogDraftService::field($_POST['template']??'editorial',32),
                    BlogDraftService::field($_POST['title']??'',600),
                    BlogDraftService::field($_POST['source_url']??'',2048)
                );
            } else {
                $result=$service->enhance($field,BlogDraftService::field($_POST[$field]??'', $field==='title'?600:BlogDraftService::MAX_ARTICLE_BYTES));
            }
            $status=$result['status'];unset($result['status']);
            $this->jsonResponse($result,$status);
        } catch (\InvalidArgumentException $error) {
            $this->jsonResponse(['error'=>$error->getMessage()],422);
        } catch (\Throwable $error) {
            error_log('Editorial suggestion failed: '.get_class($error));
            $this->jsonResponse(['error'=>'Editorial suggestion is unavailable. Original retained.'],503);
        }
    }
    public function taxReport(): void{
        $this->requireModule('shop');
        $orders = (new OrderService())->all();
        $orders = array_values(array_filter($orders, fn($o) => !empty($o['invoice_number'])));
        $from = (string)($_GET['from'] ?? '');
        $to = (string)($_GET['to'] ?? '');
        if ($from !== '') $orders = array_values(array_filter($orders, fn($o) => ($o['invoice_date'] ?? '') >= $from));
        if ($to !== '') $orders = array_values(array_filter($orders, fn($o) => ($o['invoice_date'] ?? '') <= $to . 'T23:59:59'));
        if (($_GET['format'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="gst-tax-report.csv"');
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice','Date','Customer','Place of Supply','Taxable','CGST','SGST','IGST','Total']);
            foreach ($orders as $o) fputcsv($handle, [$o['invoice_number']??'',substr($o['invoice_date']??'',0,10),$o['customer_email']??'',$o['place_of_supply']??'',$o['taxable_value']??0,$o['cgst_total']??0,$o['sgst_total']??0,$o['igst_total']??0,$o['total']??0]);
            fclose($handle); exit;
        }
        $totals = ['taxable'=>0,'cgst'=>0,'sgst'=>0,'igst'=>0,'tax'=>0,'gross'=>0];
        foreach ($orders as $o) {
            $totals['taxable'] += (float)($o['taxable_value'] ?? 0);
            $totals['cgst']    += (float)($o['cgst_total'] ?? 0);
            $totals['sgst']    += (float)($o['sgst_total'] ?? 0);
            $totals['igst']    += (float)($o['igst_total'] ?? 0);
            $totals['tax']     += (float)($o['cgst_total'] ?? 0) + (float)($o['sgst_total'] ?? 0) + (float)($o['igst_total'] ?? 0);
            $totals['gross']   += (float)($o['total'] ?? 0);
        }
        $this->render('admin/tax-report', ['pageTitle'=>'GST Tax Report','title'=>'GST Product Sales Report','orders'=>$orders,'from'=>$from,'to'=>$to,'totals'=>$totals]);
    }
    private function list(string $title, ?string $collection = null): void{$this->render('admin/list',['pageTitle' => $title, 'title' => $title, 'collection' => $collection, 'items'=>$collection ? (new ResourceService($collection))->all() : []]);}
    private function resource(string $title,string $collection,array $fields,array $extra=[]): void{$this->render('admin/resource',array_merge(['pageTitle' => $title, 'title' => $title, 'collection' => $collection, 'fields' => $fields, 'items'=>(new ResourceService($collection))->all(), 'mediaFiles'=>$this->mediaFor($collection)], $extra));}
    private function save(string $collection): void{
        $data=$this->cleanPost();
        // Saving the blank form created an empty record. Every collection here is
        // identified by a name or a code, so refuse when neither is present rather than
        // adding a nameless row the owner then has to hunt down and delete.
        $identifier = trim((string)($data['name'] ?? $data['code'] ?? $data['title'] ?? ''));
        if ($identifier === '') {
            $this->flash('Enter a name before saving.', 'error');
            $this->redirect('/admin/'.$collection);
        }
        $data=$this->mergeExistingRecord($collection, $data);
        if(isset($data['working_days']))$data['working_days']=$this->splitList($data['working_days']);
        if(isset($data['modes']))$data['modes']=$this->splitList($data['modes']);
        if(isset($data['languages']))$data['languages']=$this->splitList($data['languages']);
        $uploaded=$this->uploadedMedia($collection);
        $record=(new ResourceService($collection))->save($data);
        $entityName = (string)($record['name'] ?? $record['slug'] ?? '');
        if ($uploaded) (new MediaService())->recordUsage($uploaded, $collection, (string)($record['id'] ?? ''), $entityName);
        (new AuditLogService())->record('save',$collection,(string)($record['id'] ?? ''),['fields'=>array_keys($data),'uploaded_media'=>count($uploaded)]);
        $this->flash($collection==='consultants'?'Consultant profile saved.':'Saved.','success');
        $this->redirect('/admin/'.$collection);
    }
    private function saveProductRecord(): void{
        $data=$this->cleanPost();
        $data=$this->mergeExistingRecord('products', $data);
        // Product details are structured so the public product page can present them
        // consistently. Empty fields intentionally clear old imported values.
        foreach (['highlights', 'description_points'] as $field) {
            if (array_key_exists($field, $_POST)) $data[$field] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$_POST[$field]) ?: []), static fn($line) => $line !== ''));
        }
        if (array_key_exists('specifications', $_POST)) {
            $data['specifications'] = $this->parseSpecifications((string)$_POST['specifications']);
        }
        // Explicit empty gallery fields clear images; omitted fields preserve them.
        // Existing database galleries are arrays, not strings.
        $gallery = $_POST['image_urls'] ?? $data['image_urls'] ?? [];
        $images = is_array($gallery) ? $gallery : $this->splitList((string)$gallery);
        $featured = trim((string)($_POST['image_url'] ?? $data['image_url'] ?? ''));
        if ($featured !== '') array_unshift($images, $featured);
        $uploaded=$this->uploadedMedia('products');
        $uploadedPaths=array_column($uploaded, 'url');
        $images=array_values(array_unique(array_filter(array_merge($images, $uploadedPaths))));
        $data['image_url']=$images[0] ?? '';
        $data['image_urls']=$images;
        $record=(new ResourceService('products'))->save($data);
        $entityName = (string)($record['name'] ?? $record['slug'] ?? '');
        if ($uploaded) (new MediaService())->recordUsage($uploaded, 'products', (string)($record['id'] ?? ''), $entityName);
        (new AuditLogService())->record('save','products',(string)($record['id'] ?? ''),['fields'=>array_keys($data),'uploaded_media'=>count($uploaded)]);
        $this->flash('Product saved.','success');
        $this->redirect('/admin/products');
    }
    private function delete(string $collection): void{
        $id=(string)($_POST['id']??'');
        (new ResourceService($collection))->delete($id);
        (new AuditLogService())->record('delete',$collection,$id);
        $this->flash('Deleted.','info');
        $this->redirect('/admin/'.$collection);
    }
    private function cleanPost(): array {
        return array_filter($_POST, fn($v) => $v !== '' && $v !== null);
    }
    private function mergeExistingRecord(string $collection, array $data): array {
        $id=(string)($data['id'] ?? '');
        if ($id === '') return $data;
        foreach ((new ResourceService($collection))->all() as $item) {
            if ((string)($item['id'] ?? '') === $id) return array_merge($item, $data);
        }
        return $data;
    }
    private function splitList(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value) ?: [])));
    }
    private function parseSpecifications(string $value): array {
        $specifications = [];
        foreach (preg_split('/\r\n|\r|\n/', $value) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = preg_split('/\s*[:=]\s*/', $line, 2);
            if (count($parts) !== 2) continue;
            [$label, $detail] = array_map('trim', $parts);
            if ($label !== '' && $detail !== '') $specifications[$label] = $detail;
        }
        return $specifications;
    }
    private function uploadedMedia(string $collection): array { return (new MediaService())->upload($_FILES['media_files'] ?? [], $this->mediaContext($collection)); }
    private function mediaFor(string $collection): array { return in_array($collection, ['products','campaign_pages','consultants'], true) ? (new MediaService())->all($this->mediaContext($collection)) : []; }
    private function mediaContext(string $collection): string { return match($collection){'products'=>'products','campaign_pages'=>'campaigns','consultants'=>'consultants',default=>'shared'}; }
    private function schemaFields(string $collection, array $fallback): array { return (new SchemaService())->adminFields($collection, $fallback); }
    private static function contrast(string $hex1, string $hex2): float {
        $l1 = self::luminance($hex1); $l2 = self::luminance($hex2);
        return (max($l1,$l2) + 0.05) / (min($l1,$l2) + 0.05);
    }
    private static function luminance(string $hex): float {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $rgb = [hexdec($hex[0].$hex[1]), hexdec($hex[2].$hex[3]), hexdec($hex[4].$hex[5])];
        $vals = [];
        foreach ($rgb as $c) { $s = $c / 255; $vals[] = $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4; }
        return 0.2126 * $vals[0] + 0.7152 * $vals[1] + 0.0722 * $vals[2];
    }

}
