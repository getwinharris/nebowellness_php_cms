<?php
namespace App\Controllers;
use App\Services\{AuthService,BlogService,CampaignPageService,ConsultantService,ProductService,CategoryService,SecretService,SeoService,ContactService,ReviewService,MarkdownRenderer};
final class PublicController extends BaseController {
    
    public function home(): void {
        $this->detectApiRequest();
        $this->seoKey = 'home';
        $consultants = [];
        try { $consultants = (new ConsultantService())->all(); }
        catch (\Throwable $error) { error_log('Home consultant profiles unavailable: ' . $error->getMessage()); }
        if ($consultants === []) $consultants = $this->defaultConsultants();
        $this->render('public/home', ['consultants' => $consultants]);
    }

    /**
     * Honest temporary cards for the launch. The silhouettes make it clear that these
     * are placeholders; saving a consultant in Admin replaces the fallback list and
     * its photo_url can be changed from the media picker.
     */
    private function defaultConsultants(): array {
        $female = '/assets/images/consultants/default-female.webp';
        $male = '/assets/images/consultants/default-male.webp';
        return [
            ['id'=>'dr-bablin-torah','slug'=>'dr-bablin-torah','name'=>'Dr Bablin Torah','speciality'=>'MD Naturopathic Consultant','photo_url'=>$male,'description'=>'Integrative naturopathy and personalised lifestyle guidance.','languages'=>['English','Tamil']],
            ['id'=>'dr-sathyajothi','slug'=>'dr-sathyajothi','name'=>'Dr Sathyajothi','speciality'=>'BNYS · Yoga Mentor & Guide','photo_url'=>$male,'description'=>'Therapeutic yoga and practical movement guidance for sustainable wellbeing.','languages'=>['English','Tamil']],
            ['id'=>'dr-berslin-fency','slug'=>'dr-berslin-fency','name'=>'Dr Berslin Fency','speciality'=>'BNYS · Consultant Physician','photo_url'=>$female,'description'=>'Root-cause assessment and evidence-informed naturopathic care.','languages'=>['English','Tamil']],
            ['id'=>'dr-karthik-raj','slug'=>'dr-karthik-raj','name'=>'Dr Karthik Raj','speciality'=>'BNYS · Lifestyle Physician','photo_url'=>$male,'description'=>'Personalised nutrition, movement, sleep and metabolic health planning.','languages'=>['English','Tamil']],
            ['id'=>'dr-padmashree','slug'=>'dr-padmashree','name'=>'Dr Padmashree','speciality'=>'BNYS, FFAC, CCBE · Maternity Wellness Consultant','photo_url'=>$female,'description'=>'Preconception, pregnancy and postpartum wellness support.','languages'=>['English','Tamil']],
        ];
    }
    
    public function about(): void { 
        $this->detectApiRequest();
        $this->seoKey = 'about';
        $this->render('public/about'); 
    }

    public function spiritual(): void {
        $this->redirect('/about', 301);
    }
    
    public function terms(): void { 
        $this->detectApiRequest();
        $this->seoKey = 'terms';
        $this->render('public/terms', ['document' => $this->markdownDocument('content/legal/terms.md')]);
    }
    
    public function privacy(): void { 
        $this->detectApiRequest();
        $this->seoKey = 'privacy';
        $this->render('public/privacy', ['document' => $this->markdownDocument('content/legal/privacy.md')]);
    }
    
    public function consult(): void {
        $this->redirect('/shop');
    }
    
    public function consultant(string $slug): void {
        $this->redirect('/shop');
    }
    
    public function temples(): void { 
        $this->redirect('/about', 301);
    }
    
    public function temple(string $slug): void { 
        $this->redirect('/about', 301);
    }

    public function campaigns(): void {
        $this->requireModule('campaigns');
        $this->seoKey = 'campaigns';
        $this->render('public/campaigns', ['pages' => (new CampaignPageService())->published()]);
    }

    public function campaign(string $slug): void {
        $this->requireModule('campaigns');
        $page = (new CampaignPageService())->findPublished($slug);
        if ($page === null) $this->renderNotFound();
        $this->seoKey = 'campaign';
        $this->seoOverrides = [
            'title' => trim((string)($page['seo_title'] ?? '')) ?: $page['title'] . ' – Nebo Lifestyle Clinic',
            'description' => trim((string)($page['seo_description'] ?? '')) ?: (string)($page['summary'] ?? ''),
            'og_image' => trim((string)($page['image_url'] ?? '')) ?: '/assets/images/nebo-programs.png',
            'canonical' => $this->siteUrl('/campaigns/' . $page['slug']),
        ];
        $this->render('public/campaign', ['campaign' => $page]);
    }
    
    public function campaignEnquire(string $slug): void {
        $this->requireModule('campaigns');
        $page = (new CampaignPageService())->findPublished($slug);
        if ($page === null) $this->renderNotFound();
        $this->validateCsrf();
        $token = is_string($_POST['submission_token'] ?? null) ? $_POST['submission_token'] : '';
        if (!preg_match('/^[a-f0-9]{32}$/', $token) || !isset($_SESSION['campaign_tokens'][$slug][$token])) {
            $this->flash('This form has expired. Please try again.', 'error');
            $this->redirect('/campaigns/' . rawurlencode($slug) . '#campaign-enquiry');
        }
        if ($_SESSION['campaign_tokens'][$slug][$token] === 'done') {
            $_SESSION['campaign_form'] = ['slug'=>$slug, 'success'=>true];
            $this->redirect('/campaigns/' . rawurlencode($slug) . '#campaign-enquiry');
        }
        $this->checkRateLimit('campaign-enquiry', 3, 120);
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 40000) {
            $this->flash('The enquiry is too long. Please shorten it.', 'error');
            $this->redirect('/campaigns/' . rawurlencode($slug) . '#campaign-enquiry');
        }
        $length = static fn(string $value): int => preg_match_all('/./us', $value) ?: 0;
        $submission = [];
        foreach (['name','email','phone','message'] as $field) {
            $value = $_POST[$field] ?? '';
            $submission[$field] = is_string($value) ? trim($value) : '';
        }
        $errors = [];
        if ($submission['name'] === '' || $length($submission['name']) > 150) $errors[] = 'Enter your name (up to 150 characters).';
        if (!filter_var($submission['email'], FILTER_VALIDATE_EMAIL) || strlen($submission['email']) > 254) $errors[] = 'Enter a valid email address.';
        foreach (['phone','message'] as $field) {
            $mode = $page['form_' . $field] ?? 'optional';
            if ($mode === 'hidden') $submission[$field] = '';
            if ($mode === 'required' && $submission[$field] === '') $errors[] = 'Please enter your ' . $field . '.';
        }
        if ($length($submission['phone']) > 40 || $length($submission['message']) > 5000) $errors[] = 'Keep your phone number under 40 characters and message under 5,000 characters.';
        if (!empty($page['form_require_otp'])) {
            $otp = trim((string)($_POST['email_otp'] ?? ''));
            if (!(new \App\Services\EmailOtpService())->verify('campaign:' . $slug, $submission['email'], $otp)) $errors[] = 'Enter the valid 6-digit code sent to your email.';
        }
        $customValues = [];
        foreach (is_array($page['form_fields'] ?? null) ? $page['form_fields'] : [] as $field) {
            if (!is_array($field)) continue;
            $key = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($field['key'] ?? '')));
            if ($key === '') continue;
            $type = (string)($field['type'] ?? 'text');
            $value = $_POST['custom'][$key] ?? '';
            $value = is_string($value) ? trim($value) : '';
            if ($type === 'checkbox') $value = $value === 'Yes' ? 'Yes' : '';
            if ($type === 'select' && $value !== '') {
                $options = array_map('strval', is_array($field['options'] ?? null) ? $field['options'] : []);
                if (!in_array($value, $options, true)) {
                    $errors[] = 'Choose a valid option for ' . (string)($field['label'] ?? $key) . '.';
                    $value = '';
                }
            }
            if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email for ' . (string)($field['label'] ?? $key) . '.';
                $value = '';
            }
            if ($type === 'tel' && $value !== '' && !preg_match('/^[0-9+().\-\s]{5,40}$/', $value)) {
                $errors[] = 'Enter a valid phone number for ' . (string)($field['label'] ?? $key) . '.';
                $value = '';
            }
            if (!empty($field['required']) && $value === '') $errors[] = 'Please enter ' . (string)($field['label'] ?? $key) . '.';
            $customValues[$key] = mb_substr($value, 0, 2000);
        }
        if ($errors !== []) {
            foreach (['name'=>150,'email'=>254,'phone'=>40,'message'=>5000] as $field=>$limit) {
                preg_match_all('/./us', $submission[$field], $characters);
                $submission[$field] = implode('', array_slice($characters[0] ?? [], 0, $limit));
            }
            $submission['custom'] = $customValues;
            $_SESSION['campaign_form'] = ['slug'=>$slug, 'errors'=>$errors, 'values'=>$submission];
            $this->redirect('/campaigns/' . rawurlencode($slug) . '#campaign-enquiry');
        }
        $submission['campaign_slug'] = $slug;
        $submission['campaign_title'] = $page['title'];
        $submission['subject'] = 'Campaign enquiry: ' . $page['title'];
        $submission['form_data'] = $customValues;
        $submission['id'] = 'campaign_' . substr(hash('sha256', session_id() . $slug . $token), 0, 24);
        $submission['id'] = (new ContactService())->save($submission);
        try {
            $queue = new \App\Services\MailQueueService();
            $queue->enqueueContactConfirmation($submission);
        } catch (\Throwable $error) { error_log('Campaign receipt queue failed: ' . $error->getMessage()); }
        try {
            $queue = new \App\Services\MailQueueService();
            $customHtml = '';
            if ($customValues !== []) {
                $customHtml = '<h3>Campaign answers</h3><dl>';
                foreach ($customValues as $key => $value) {
                    $customHtml .= '<dt>' . e(ucwords(str_replace('_', ' ', $key))) . '</dt><dd>' . e($value) . '</dd>';
                }
                $customHtml .= '</dl>';
            }
            $queue->notifyAdmin($submission['subject'], '<p>New campaign enquiry.</p><p>' . e($submission['name']) . ' · ' . e($submission['email']) . '</p><p>' . e($submission['phone']) . '</p><p>' . nl2br(e($submission['message'])) . '</p>' . $customHtml, [
                'contact_id' => $submission['id'],
                'idempotency_key' => 'campaign_admin:' . $submission['id'],
            ]);
        } catch (\Throwable $error) { error_log('Campaign enquiry email failed: ' . $error->getMessage()); }
        $_SESSION['campaign_tokens'][$slug][$token] = 'done';
        if (!empty($page['form_require_otp'])) (new \App\Services\EmailOtpService())->consume('campaign:' . $slug, $submission['email']);
        $_SESSION['campaign_form'] = ['slug'=>$slug, 'success'=>true];
        $this->redirect('/campaigns/' . rawurlencode($slug) . '#campaign-enquiry');
    }

    public function shop(): void {
        $this->requireModule('shop');
        $this->detectApiRequest();
        $category = $_GET['category'] ?? '';
        $categories = (new CategoryService())->all();
        $items = (new ProductService())->visible();
        $this->seoKey = 'shop';
        if ($category) {
            $items = array_values(array_filter($items, function ($item) use ($category) {
                $categoryList = $item['categories'] ?? [$item['category'] ?? ''];
                if (!is_array($categoryList)) {
                    $categoryList = preg_split('/[\r\n,]+/', (string)$categoryList) ?: [];
                }
                $categoryList[] = $item['category'] ?? '';
                return in_array($category, array_filter(array_map('trim', $categoryList)), true);
            }));
            $catName = '';
            foreach ($categories as $c) {
                if (($c['slug'] ?? '') === $category || ($c['name'] ?? '') === $category) {
                    $catName = $c['name'];
                    break;
                }
            }
            if ($catName) {
                $this->seoOverrides = [
                    'title' => 'Buy ' . $catName . ' Online – Wellness Products at Nebo Lifestyle Clinic',
                    'description' => 'Shop ' . $catName . ' online at Nebo Lifestyle Clinic. Browse natural wellness essentials supporting your personalised plan.',
                ];
            }
        }
        $this->render('public/shop', compact('items', 'categories', 'category'));
    }

    public function categories(): void {
        $this->requireModule('shop');
        $this->detectApiRequest();
        $categories = (new CategoryService())->all();
        if ($this->isApiRequest) {
            $this->jsonResponse($categories);
            return;
        }
        $this->seoKey = 'shop';
        $this->render('public/categories', ['items' => (new ProductService())->visible(), 'categories' => $categories, 'category' => '']);
    }
    
    public function product(string $slug): void {
        $this->requireModule('shop');
        $this->detectApiRequest();
        $adminPreview = ($_GET['preview'] ?? '') === '1';
        if ($adminPreview) {
            (new AuthService())->requireAdmin();
            header('X-Robots-Tag: noindex, nofollow');
        }
        $product = (new ProductService())->findBySlug($slug, $adminPreview);
        // A missing product used to render the template with a null record and return
        // HTTP 200 — a soft 404. Search engines index those as real pages and keep
        // crawling dead URLs.
        if (!$product) $this->renderNotFound();
        $related = [];
        if ($product) {
            $all = (new ProductService())->visible();
            $related = array_values(array_filter($all, fn($p) => ($p['slug'] ?? '') !== $slug));
            $this->seoKey = 'product';
            $price = $product['offer_price'] ?? $product['price'] ?? 0;
            $schema = (new SeoService((new SecretService())->all()))->productSchema($product);
            $this->seoOverrides = [
                'title' => ($product['name'] ?? 'Product') . ' – Buy Online at Nebo Lifestyle Clinic',
                'description' => 'Buy ' . ($product['name'] ?? 'this product') . ' online at Nebo Lifestyle Clinic. ' . ($product['description'] ?? '') . ' Price: ₹' . $price . '. Natural wellness product supporting your plan.',
                'og_image' => $product['image_url'] ?? '',
                'json_ld' => '<script type="application/ld+json">' . json_encode($schema) . '</script>',
            ];
            if ($adminPreview) {
                $related = [];
                $this->seoOverrides['robots'] = 'noindex, nofollow';
                $this->seoOverrides['json_ld'] = '';
                $this->seoOverrides['title'] = 'Admin preview – ' . ($product['name'] ?? 'Product');
            }
        }
        $reviewSummary = (new ReviewService())->summary('product', $slug);
        $this->render('public/product', compact('product', 'related', 'reviewSummary', 'adminPreview'));
    }
    
    public function cart(): void {
        $this->requireModule('shop');
        $this->detectApiRequest();
        $this->seoKey = 'cart';
        $items = $this->resolveCartItems();
        $total = $this->cartTotal($items);
        // Prices are GST-inclusive, so show the tax already contained in the total.
        $settings = (new \App\Services\SettingsService())->public();
        $summary = \App\Services\TaxService::cartSummary($items, $settings);
        $this->render('public/cart', [
            'items' => $items,
            'total' => $total,
            'gstAmount' => $summary['gst_amount'],
            'itemCount' => $summary['item_count'],
        ]);
    }
    
    public function checkout(): void {
        $this->requireModule('shop');
        $this->detectApiRequest();
        $this->seoKey = 'checkout';
        $items = [];
        try {
            $items = $this->resolveCartItems();
        } catch (\Throwable $e) {
            error_log('Checkout resolveCartItems error: ' . $e->getMessage());
        }
        $secrets = [];
        $razorpayReady = false;
        $settings = ['shipping_mode' => 'free', 'flat_rate' => 0];
        try {
            $secretService = new SecretService();
            $secrets = $secretService->all();
            $razorpayReady = $secretService->razorpayReadyForCurrentHost($secrets);
            $settings = (new \App\Services\SettingsService())->public();
        } catch (\Throwable $e) {
            error_log('Checkout secrets/settings error: ' . $e->getMessage());
        }
        $addresses = [];
        if (($items !== []) && ($_SESSION['user']['email'] ?? '') !== '') {
            try {
                $addresses = (new \App\Services\AddressService())->forCustomer($_SESSION['user']['email']);
            } catch (\Throwable $e) {
                error_log('Checkout addresses error: ' . $e->getMessage());
            }
        }
        $this->render('public/checkout', ['items' => $items, 'total' => $this->cartTotal($items), 'secrets' => $secrets, 'addresses' => $addresses, 'razorpayReady' => $razorpayReady, 'settings' => $settings]);
    }
    
    public function sitemap(): void {
        header('Content-Type: application/xml; charset=utf-8');
        $host = $_SERVER['HTTP_HOST'] ?? 'nebowellness.com';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . $host;

        $pages = ['/', '/about', '/contact', '/blog', '/terms', '/privacy'];
        if (module_on('shop')) $pages[] = '/shop';
        $products = [];
        if (module_on('shop')) {
            try { $products = (new ProductService())->visible(); } catch (\Throwable) {}
        }
        $blogPosts = [];
        try { $blogPosts = (new BlogService())->all(); } catch (\Throwable) {}
        $campaignPages = module_on('campaigns') ? (new CampaignPageService())->published() : [];
        if (module_on('campaigns')) $pages[] = '/campaigns';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as $path) {
            $xml .= '  <url><loc>' . $base . $path . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' . "\n";
        }

        foreach ($products as $p) {
            if (!empty($p['slug'])) {
                $xml .= '  <url><loc>' . $base . '/product/' . e($p['slug']) . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
            }
        }

        foreach ($blogPosts as $post) {
            if (!empty($post['slug']) && !empty($post['published'])) {
                $xml .= '  <url><loc>' . $base . '/blog/' . e($post['slug']) . '</loc><lastmod>' . e(substr((string)($post['updated_at'] ?? $post['published_at'] ?? ''), 0, 10)) . '</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>' . "\n";
            }
        }

        foreach ($campaignPages as $campaignPage) {
            $xml .= '  <url><loc>' . $base . '/campaigns/' . e($campaignPage['slug']) . '</loc><changefreq>monthly</changefreq><priority>0.7</priority></url>' . "\n";
        }

        $xml .= '</urlset>';
        echo $xml;
        exit;
    }

    public function contact(): void {
        $this->detectApiRequest();
        $this->seoKey = 'contact';
        $success = false;
        $subject = $_GET['subject'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $this->checkRateLimit('contact', 3, 120);
            $token = trim((string)($_POST['submission_token'] ?? ''));
            if (!preg_match('/^[a-f0-9]{32}$/', $token) || (($_SESSION['contact_tokens'][$token] ?? null) !== 'pending')) {
                $this->flash('This contact form has expired. Please try again.', 'error');
                $this->redirect('/contact#contact-form');
            }
            $contactService = new ContactService();
            $submission = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'subject' => $_POST['subject'] ?? '',
                'message' => $_POST['message'] ?? '',
            ];
            $otp = trim((string)($_POST['email_otp'] ?? ''));
            if (!(new \App\Services\EmailOtpService())->verify('contact', (string)$submission['email'], $otp)) {
                $this->flash('Enter the valid 6-digit code sent to your email.', 'error');
                $this->redirect('/contact#contact-form');
            }
            $submission['id'] = 'contact_' . substr(hash('sha256', session_id() . $token), 0, 24);
            $submission['id'] = $contactService->save($submission);
            // The owner was never told a contact form had been submitted; it only
            // appeared under Admin -> Contacts if someone thought to look.
            try {
                (new \App\Services\MailQueueService())->enqueueContactConfirmation($submission);
            } catch (\Throwable $e) { error_log('Contact receipt failed: ' . $e->getMessage()); }
            try {
                (new \App\Services\MailQueueService())->notifyAdmin(
                    'New contact form submission',
                    '<p>A new message came in through the contact form.</p><dl>'
                    . '<dt>Name</dt><dd>' . e((string)($_POST['name'] ?? '')) . '</dd>'
                    . '<dt>Email</dt><dd>' . e((string)($_POST['email'] ?? '')) . '</dd>'
                    . '<dt>Phone</dt><dd>' . e((string)($_POST['phone'] ?? '')) . '</dd>'
                    . '<dt>Subject</dt><dd>' . e((string)($_POST['subject'] ?? '')) . '</dd>'
                    . '<dt>Message</dt><dd>' . nl2br(e((string)($_POST['message'] ?? '')), false) . '</dd></dl>',
                    [
                        'contact_id' => $submission['id'],
                        'idempotency_key' => 'contact_admin:' . $submission['id'],
                    ]
                );
            } catch (\Throwable $e) { error_log('Contact notification failed: ' . $e->getMessage()); }
            $_SESSION['contact_tokens'][$token] = 'done';
            (new \App\Services\EmailOtpService())->consume('contact', (string)$submission['email']);
            $success = true;
        }
        $this->render('public/contact', ['success' => $success, 'subject' => $subject]);
    }

    public function sendEmailOtp(): void {
        $this->detectApiRequest();
        $this->isApiRequest = true;
        $this->validateCsrf();
        $this->checkRateLimit('email-otp', 4, 600);
        $email = trim((string)($_POST['email'] ?? ''));
        $context = trim((string)($_POST['context'] ?? 'contact'));
        if ($context !== 'contact') {
            if (!preg_match('/^campaign:([a-z0-9-]+)$/', $context, $match)) $this->jsonResponse(['error'=>'Invalid form context.'],422);
            $this->requireModule('campaigns');
            $page = (new CampaignPageService())->findPublished($match[1]);
            if ($page === null || empty($page['form_require_otp'])) $this->jsonResponse(['error'=>'Email verification is not enabled for this form.'],422);
        }
        try {
            (new \App\Services\EmailOtpService())->send($context, $email);
            $this->jsonResponse(['success'=>true,'message'=>'A 6-digit code was sent. Check your inbox and spam folder.']);
        } catch (\DomainException|\InvalidArgumentException $error) {
            $this->jsonResponse(['error'=>$error->getMessage()],422);
        } catch (\Throwable) {
            $this->jsonResponse(['error'=>'The verification email could not be sent. Please try again, call +91 72001 82025, or email nebolifestyleclinic@gmail.com.'],503);
        }
    }
    
    public function login(): void { 
        $this->detectApiRequest();
        // Someone already signed in has no business on the sign-in page; send them to
        // their dashboard instead of offering a second login.
        if (!empty($_SESSION['user'])) $this->redirect((($_SESSION['user']['role'] ?? '') === 'admin') ? '/admin' : '/account/dashboard');
        $this->seoKey = 'login';
        $secrets = (new \App\Services\SecretService())->all();
        $this->render('public/login', [
            'googleAuthEnabled' => !empty($secrets['google_client_id']) && !empty($secrets['google_client_secret']),
        ]); 
    }

    public function docs(): void {
        $this->redirect('/blog');
    }

    public function doc(string $slug): void {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        $replacements = [
            'create-account' => '/register',
            'order-products' => '/shop',
            'payments-and-orders' => '/blog/category/help',
            'book-consultant' => '/contact',
        ];
        if (isset($replacements[$slug])) $this->redirect($replacements[$slug], 301);
        $this->redirect('/blog/' . $slug);
    }

    private function parseContentDocument(string $raw, string $fallbackSlug): array
    {
        $meta = [];
        $body = $raw;
        if (str_starts_with($raw, '---')) {
            $parts = explode('---', $raw, 3);
            if (count($parts) === 3) {
                foreach (explode("\n", trim($parts[1])) as $line) {
                    if (!str_contains($line, ':')) continue;
                    [$key, $value] = explode(':', $line, 2);
                    $meta[trim($key)] = trim(trim($value), "\"'");
                }
                $body = trim($parts[2]);
            }
        }
        preg_match('/^#\s+(.+)$/m', $body, $heading);
        $title = trim((string)($meta['title'] ?? $heading[1] ?? ucfirst(str_replace('-', ' ', $fallbackSlug))));
        $body = trim((string)preg_replace('/^#\s+.+\R?/m', '', $body, 1));
        return [
            'title' => $title,
            'slug' => (string)($meta['slug'] ?? $fallbackSlug),
            'summary' => (string)($meta['summary'] ?? ''),
            'order' => (int)($meta['order'] ?? 100),
            'icon' => (string)($meta['icon'] ?? 'guide'),
            'html' => (new MarkdownRenderer())->render($body),
        ];
    }

    /**
     * Markdown page with YAML frontmatter, the same file shape blogs use.
     *
     * This previously stripped only a leading "# Heading" and rendered everything else,
     * so the frontmatter block was published as body text — Terms and Privacy opened
     * with "title: Terms & Conditions description: ... category: legal". Frontmatter is
     * metadata and must never reach the page.
     */
    private function markdownDocument(string $relativePath): array
    {
        $raw = (string)@file_get_contents(app_path($relativePath));
        $meta = [];
        $body = $raw;

        if (preg_match('/\A\x{FEFF}?\s*---\R(.*?)\R---\R?(.*)\z/su', $raw, $m)) {
            foreach (preg_split('/\R/', $m[1]) as $line) {
                if (!str_contains($line, ':')) continue;
                [$key, $value] = explode(':', $line, 2);
                $meta[trim($key)] = trim(trim($value), " \"'");
            }
            $body = $m[2];
        }

        // Frontmatter title wins; fall back to a leading H1, which is then removed so it
        // is not printed twice under the page heading.
        $title = trim((string)($meta['title'] ?? ''));
        if ($title === '') {
            preg_match('/^#\s+(.+)$/m', $body, $heading);
            $title = trim($heading[1] ?? 'Document');
        }
        $body = trim((string)preg_replace('/^#\s+.+\R?/m', '', $body, 1));

        return [
            'title' => $title,
            'description' => (string)($meta['description'] ?? ''),
            'html' => (new MarkdownRenderer())->render($body),
        ];
    }
}
