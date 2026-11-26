<?php
namespace App\Services;

final class SupportBotService {
    public const PROGRAMS_PROMPT = 'What wellness programs are available?';
    public const CONSULTATION_PROMPT = 'How do I request a consultation?';

    public function __construct(
        private SecretService $secrets = new SecretService(),
        private DatabaseService $store = new DatabaseService(),
        private AgentContextService $agentContext = new AgentContextService(),
        private ?AgentRunTrace $trace = null
    ) {}

    public function answer(string $message, ?array $user, string $previous = ''): array {
        $startedAt = microtime(true);
        $this->trace ??= new AgentRunTrace();
        $message = trim($message);
        if ($message === '') throw new \InvalidArgumentException('Message is required.');

        // Ticket tracking and explicit navigation are application operations, not AI answers.
        $ticketReply = $this->ticketReply($message, (string)($user['email'] ?? ''));
        if ($ticketReply !== null) {
            if (preg_match('/\btickets?\b/i',$message) && !empty($user['email'])) $this->trace->tool('track_support_ticket','ok',$startedAt);
            $result=$this->finishReply($ticketReply,'fallback',$startedAt);
            $result['response_mode']='application_action';
            $result['memory']='none';
            if (preg_match('/^(?:human|talk to|speak to|contact support)/i',$message)) {
                $handoff=(new ClinicContactService())->handoff($message);
                if ($handoff) $result['actions'][]=$handoff;
            }
            return $result;
        }
        $tools=new AgentToolRegistry($this->store,scope:'support',contextLoader:fn()=> $this->customerContext($user,$message),question:$message);
        $context=$tools->retrieve('clinic_context',[],$this->trace);
        if (isset($context['error'])) return $this->unavailable($message,$startedAt,'Clinic information is temporarily unavailable.');
        if (!$context['signed_in'] && $this->isPrivateAccountQuestion($message)) {
            return array_replace($this->finishReply('Sign in at /login to access your own account.','private_account',$startedAt,$context),['response_mode'=>'application_action','memory'=>'none']);
        }
        $destination=$this->destination($message,$context);
        if ($destination !== null) {
            $result=$this->finishReply('Open '.$destination['label'].'.','fallback',$startedAt,$context);
            $result['response_mode']='application_action'; $result['open']=$destination['path'];
            $result['actions']=[['type'=>'navigate','label'=>$destination['label'],'path'=>$destination['path']]];
            return $result;
        }
        // Pre-execute relevant read-only lookups so plain-compatible providers receive
        // actual retrieved evidence too. Model-selected function calls remain available.
        $evidence=[];
        if (preg_match('/\b(doctor|dr\.?|practitioner|qualification|credentials?|registration|specialit\w*|team)\b/i',$message)) {
            $evidence['published_practitioners']=$tools->retrieve('published_practitioners',[],$this->trace);
        }
        if ($tools->has('published_programmes') && preg_match('/\b(programmes?|programs?|duration|fertility|gut|metabolic|maternal|lifestyle)\b/i',$message)) {
            $evidence['published_programmes']=$tools->retrieve('published_programmes',[],$this->trace);
            foreach ($context['site']['programmes']??[] as $row) {
                if (!empty($row['title']) && str_contains(mb_strtolower($message),mb_strtolower($row['title']))) {
                    $slug=basename((string)$row['url']);
                    $evidence['published_programme_detail']=$tools->retrieve('published_programme_detail',['slug'=>$slug],$this->trace);
                    break;
                }
            }
        }
        $context['retrieved_evidence']=$evidence;
        $answer=$this->modelReply($message,$context,$previous,$tools);
        $cleaner=new AiReplyCleaner();
        $candidate=$answer===null?'':$cleaner->clean($answer,'');
        $rightTool=true;
        if (isset($evidence['published_practitioners']) && preg_match('/\b(registration|qualifications?|credentials?|specialit\w*)\b/i',$message))
            $rightTool=$this->trace->usedNativeTool(['published_practitioners','search_clinic_knowledge']);
        if (isset($evidence['published_programme_detail'])) $rightTool=$rightTool && $this->trace->usedNativeTool(['published_programme_detail','search_clinic_knowledge']);
        if ($answer===null || !$cleaner->usableAnswer($candidate,$message) || !$this->trace->hasNativeToolAnswer() || !$rightTool || !self::matchesRetrievedFacts($message,$candidate,$evidence)) {
            if ($answer!==null) $this->trace->markProviderResponseUnusable();
            return $this->unavailable($message,$startedAt,'The AI could not produce a verified response right now. Please retry or contact the clinic.');
        }
        $result=$this->finishReply($candidate,'model',$startedAt,$context);
        $result['response_mode']='model_with_retrieval';
        $result['sources']=$this->trace->sources();
        if ($this->isPrivateAccountQuestion($message)) $result['memory']='none';
        return $result;
    }

    public static function matchesRetrievedFacts(string $question,string $answer,array $evidence): bool {
        $lower=mb_strtolower($question);
        foreach ($evidence['published_practitioners']['practitioners']??[] as $row) {
            $name=trim((string)preg_replace('/^dr\.?\s+/i','',(string)($row['name']??'')));
            if ($name==='' || !str_contains($lower,mb_strtolower($name))) continue;
            if (preg_match('/\b(registration|reg\s*(?:no|number))\b/i',$question) && !empty($row['registration_number'])
                && !preg_match('/(?<![a-z0-9])'.preg_quote((string)$row['registration_number'],'/').'(?![a-z0-9])/i',$answer)) return false;
            if (preg_match('/\b(qualification|qualifications|credentials?)\b/i',$question) && !empty($row['credentials'])
                && !str_contains(mb_strtolower($answer),mb_strtolower((string)$row['credentials']))) return false;
        }
        $detail=$evidence['published_programme_detail']??[];
        if (preg_match('/\b(duration|how long)\b/i',$question) && !empty($detail['duration']) && !str_contains(mb_strtolower($answer),mb_strtolower((string)$detail['duration']))) return false;
        return true;
    }

    private function unavailable(string $message,float $startedAt,string $error): array {
        (new AuditLogService($this->store))->agentRun('support','error',$startedAt,$this->trace);
        $actions=[['type'=>'navigate','label'=>'Contact the clinic','path'=>'/contact']];
        $handoff=(new ClinicContactService())->handoff($message);
        if ($handoff) $actions[]=$handoff;
        return ['error'=>$error,'memory'=>'none','response_mode'=>'unavailable','actions'=>$actions];
    }

    private function finishReply(string $reply, string $outcome, float $started, array $context = []): array {
        (new AuditLogService($this->store))->agentRun('support', $outcome, $started, $this->trace);
        $allowed = array_column($this->destinations($context), 'path');
        $actions = array_values(array_filter($this->extractActions($reply), static fn(array $action): bool => in_array($action['path'], $allowed, true)));
        return ['reply' => $reply, 'ticket_id' => null, 'memory' => 'session', 'actions' => $actions, 'response_mode'=>$outcome];
    }

    private function ticketReply(string $message, string $email): ?string {
        if (preg_match('/^(?:human|talk to (?:a person|the clinic)|contact support|speak to (?:a person|the clinic))[?.!]*$/i', $message)) {
            return $email === '' ? 'Contact the clinic at /contact.' : 'To send a support ticket, enter your message and select Send to clinic. You can also use /contact.';
        }
        $list = preg_match('/^(?:show |list )?(?:my )?(?:support )?tickets[?.!]*$/i', $message);
        if (!$list && !preg_match('/^(?:track|check|status of) (?:support )?ticket\s+([a-f0-9]{16,32})[?.!]*$/i', $message, $match)) return null;
        if ($email === '') return 'Sign in at /login to see your support tickets, or contact the clinic at /contact.';
        $tickets = new SupportTicketService($this->store);
        if (!$list) {
            $ticket = $tickets->track($email, strtolower($match[1]));
            if ($ticket === null) return 'No ticket with that reference was found for this account.';
            return 'Ticket ' . $ticket['id'] . ' is ' . ($ticket['status'] ?? 'open') . '.'
                . (empty($ticket['reply']) ? '' : ' Clinic reply: ' . $ticket['reply']);
        }
        $rows = $tickets->forUser($email);
        usort($rows, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        $recent = array_map(static fn(array $row): string => $row['id'] . ' (' . ($row['status'] ?? 'open') . ')', array_slice($rows, 0, 5));
        return $recent === [] ? 'You have no support tickets.' : 'Recent tickets: ' . implode(', ', $recent) . '. Type “track ticket” followed by a reference to check its status and clinic reply.';
    }

    private function destination(string $message, array $context): ?array {
        if (!preg_match('/^(?:please )?(?:open|go to|take me to|navigate to)\s+(?:the\s+)?(.+?)\s*[?.!]*$/i', $message, $match)) return null;
        $target = mb_strtolower(trim($match[1]));
        return $this->destinations($context)[$target] ?? null;
    }

    private function destinations(array $context): array {
        $pages = [
            'home' => ['path' => '/', 'label' => 'Nebo home'],
            'team' => ['path' => '/#team', 'label' => 'Meet the team'],
            'programs' => ['path' => '/#programs', 'label' => 'Nebo programs'],
            'about' => ['path' => '/about', 'label' => 'About Nebo'],
            'about nebo' => ['path' => '/about', 'label' => 'About Nebo'],
            'sign in' => ['path' => '/login', 'label' => 'Sign in'],
            'contact' => ['path' => '/contact', 'label' => 'Contact the clinic'],
            'support' => ['path' => '/support', 'label' => 'Support'],
        ];
        foreach ($context['site']['pages'] ?? [] as $name => $path) {
            $pages[$name] ??= ['path' => $path, 'label' => ucfirst($name)];
        }
        $aliases = ['campaigns' => ['therapy products', 'campaign pages'], 'blog' => ['journal'], 'orders' => ['my orders'], 'therapy requests' => ['my therapy requests', 'therapy status']];
        foreach ($aliases as $key => $names) {
            if (isset($pages[$key])) foreach ($names as $name) $pages[$name] = $pages[$key];
        }
        $items = array_merge($context['articles'] ?? [], $context['site']['campaigns'] ?? [], $context['site']['products'] ?? []);
        foreach ($items as $item) {
            $title = trim((string)($item['title'] ?? $item['name'] ?? ''));
            $path = (string)($item['url'] ?? '');
            if ($title !== '' && preg_match('#^/(?:blog|campaigns|product)/[a-z0-9-]+$#D', $path)) {
                $pages[mb_strtolower($title)] ??= [
                    'path' => $path, 'label' => $title,
                    'summary' => mb_substr(trim(strip_tags((string)($item['summary'] ?? $item['description'] ?? ''))), 0, 400),
                ];
            }
        }
        return $pages;
    }

    private function customerContext(?array $user,string $message=''): array {
        $cart = array_map(fn($item) => [
            'slug' => $item['slug'] ?? '',
            'qty' => (int)($item['qty'] ?? 0),
            'name' => $item['name'] ?? '',
        ], array_values($_SESSION['cart'] ?? []));
        $accountQuestion=module_on('shop') && $this->isPrivateAccountQuestion($message) && ($user['role']??'')==='customer';
        $base=$this->agentContext->forUserEmail($accountQuestion?(string)($user['email']??''):'');
        unset($base['user']);
        $base['orders']=array_map(static fn(array $row):array=>array_intersect_key($row,array_flip(['id','status','total','shipped_at','invoice_number','invoice_date'])),array_slice($base['orders']??[],0,10));
        if (($user['role'] ?? '') === 'customer') $base['site']['pages']['therapy requests'] = '/account/therapy';
        if (!$accountQuestion && empty($user['email'])) unset($base['site']['pages']['orders']);
        // Articles let the agent answer "what is this app?" from real content instead of
        // falling back to a navigation list. BlogService::all() already withholds
        // unpublished posts and posts whose module is off.
        return [
            'signed_in' => !empty($user['email']),
            'cart' => module_on('shop')?$cart:[],
            'articles' => module_on('blog')?$this->siteArticles():[],
            'policies' => module_on('shop') ? $this->policies() : [],
        ] + $base;
    }

    /**
     * The provider call goes through AiClient, which resolves the endpoint and model
     * from Admin → Integrations. This method used to build its own provider URL with a
     * hardcoded host and model default, so changing the model in the admin fixed the
     * admin agent and left this one calling a model that answered 404 — every customer
     * reply silently fell back to a canned menu.
     */
    private function modelReply(string $message, array $context, string $previous, AgentToolRegistry $tools): ?string {
        $prompt=$tools->instructions($message)."\nAnswer directly and briefly in the customer's language. "
            ."Use the returned tool evidence, do not introduce yourself or cite an unrelated article. "
            ."No diagnoses, promised results, or invented prices. If the requested data is missing, say so. "
            ."Use actual returned source paths.\n"
            .(module_on('shop')?'Commerce is enabled.':'Commerce is disabled.')
            ."\nCustomer context JSON: ".json_encode(['signed_in'=>$context['signed_in']??false],JSON_THROW_ON_ERROR)
            .$previous."\nCustomer question: ".AgentSessionService::redact($message);
        $reply=(new AiClient($this->secrets, routingScope: 'support', trace: $this->trace))->completeWithTools($prompt,$tools,700,0.2,4);
        return AiClient::isError($reply)?null:$reply;
    }

    /** Capabilities the agent may offer, derived from the active public modules. */
    private function allowedHelp(): string {
        $help = ['wellness programs', 'consultation enquiries', 'navigation details from the JSON'];
        if (module_on('campaigns')) $help[] = 'published clinic campaigns';
        // 'shop' is the key in SettingsService::MODULES. 'ecommerce' is not, and
        // moduleEnabled() returns its `?? true` default for an unknown key — so this
        // read as "on" whatever the owner had set, and the bot kept offering cart and
        // checkout help for a shop that was switched off.
        if (module_on('shop')) array_unshift($help, 'product', 'cart', 'checkout', 'delivery address', 'order');
        if (module_on('blog')) $help[] = 'articles and help guides';
        return implode(', ', $help);
    }

    /**
     * The shop's own rules, so the agent can answer them instead of deflecting.
     *
     * Asked "how long does delivery take to Singapore" the agent replied that it had no
     * information — while OrderService has held the answer all along and the shipment
     * email already quotes it. The constants are read rather than restated, so the
     * promise the agent makes and the promise the email makes cannot drift apart.
     */
    private function policies(): array
    {
        return [
            'delivery_within_india' => OrderService::DELIVERY_DAYS_DOMESTIC . ' after dispatch',
            'delivery_outside_india' => OrderService::DELIVERY_DAYS_INTERNATIONAL . ' after dispatch',
            'shipping_cost' => 'Calculated at checkout once the delivery address is entered, and shown before payment.',
            'payment_methods' => 'Card and UPI, taken securely at checkout.',
            'tracking' => 'A courier name, tracking ID and tracking link are emailed when an order ships, and shown at /account/dashboard/orders.',
            'returns_or_problems' => 'Ask the customer to use the /contact form.',
        ];
    }

    /** Published articles the agent may quote, filtered exactly as the public site is. */
    private function siteArticles(int $limit = 12): array {
        try {
            $out = [];
            foreach ((new BlogService())->all() as $post) {
                $out[] = [
                    'title' => (string)($post['title'] ?? ''),
                    'url' => '/blog/' . (string)($post['slug'] ?? ''),
                    'category' => (string)($post['category'] ?? ''),
                    'summary' => mb_substr(trim((string)($post['excerpt'] ?? $post['summary'] ?? '')), 0, 180),
                ];
                if (count($out) >= $limit) break;
            }
            return $out;
        } catch (\Throwable $e) {
            error_log('Article context failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * The published article that best answers the question, or null.
     *
     * Scored on how many of the question's own words appear in the title, so a stray
     * word like "the" cannot pull up an unrelated post. Only titles are matched:
     * summaries are broad enough that almost anything would score.
     */
    private function matchArticle(string $message, array $context): ?array {
        $articles = $context['articles'] ?? [];
        if ($articles === []) return null;
        $words = array_filter(
            preg_split('/[^a-z0-9]+/i', strtolower($message)) ?: [],
            fn(string $w): bool => mb_strlen($w) >= 4 && !in_array($w, [
                'what', 'when', 'where', 'which', 'does', 'have', 'your', 'this',
                'that', 'with', 'about', 'there', 'their', 'please', 'tell',
                'could', 'would', 'should', 'some', 'more', 'also', 'size',
            ], true)
        );
        if ($words === []) return null;
        $best = null;
        $bestScore = 0;
        foreach ($articles as $article) {
            $title = strtolower((string)($article['title'] ?? ''));
            if ($title === '') continue;
            $score = 0;
            foreach ($words as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $title)) $score++;
            }
            if ($score > $bestScore) { $bestScore = $score; $best = $article; }
        }
        return $bestScore > 0 ? $best : null;
    }

    /**
     * True only when the question is about this customer's own records.
     *
     * "delivery", "track" and "shipped" used to match on their own, so "how long does
     * delivery take to Singapore" — a question anyone can be told the answer to — was
     * turned away with "please sign in to ask about your personal orders". A possessive
     * word is now required, and the shipping policy is answered for everyone.
     */
    private function isPrivateAccountQuestion(string $message): bool {
        return (bool)preg_match(
            '/\b(?:my|our|this)\s+(?:order|orders|delivery|shipment|package|parcel|payment|refund)\b/i',
            $message
        ) || (bool)preg_match(
            '/\border\s+(?:history|status|number|id)\b|\btrack\s+(?:my|the|this)\b|\border\s+#?\d+/i',
            $message
        );
    }

    private function extractActions(string $reply): array {
        preg_match_all('/\/(?:#programs|#team|shop|cart|checkout|contact|login|about|support|campaigns(?:\/[a-z0-9-]+)?|blog(?:\/category\/[a-z0-9-]+|\/[a-z0-9-]+)?|product\/[a-z0-9-]+|account\/therapy|account\/dashboard(?:\/orders|\/install)?)(?=[\s.,)\/  ]|$)/i', $reply, $matches);
        $seen = [];
        $actions = [];
        foreach ($matches[0] as $path) {
            $path = strtolower($path);
            if (str_starts_with($path, '/campaigns') && !module_on('campaigns')) continue;
            if (preg_match('#^/(shop|cart|checkout|product/|account/dashboard)#', $path) && !module_on('shop')) continue;
            if (str_starts_with($path, '/blog') && !module_on('blog')) continue;
            if (in_array($path, $seen, true)) continue;
            $seen[] = $path;
            $label = match (true) {
                $path === '/account/therapy' => 'My therapy requests',
                $path === '/shop' => 'View Shop',
                $path === '/cart' => 'View Cart',
                $path === '/checkout' => 'Go to Checkout',
                $path === '/contact' => 'Contact Us',
                $path === '/#programs' => 'Explore Programs',
                $path === '/blog' => 'Read Blog',
                default => 'Open ' . trim(preg_replace('/^\/+/', '', str_replace(['-', '/'], ' ', $path)))
            };
            $actions[] = ['type' => 'navigate', 'label' => $label, 'path' => $path];
        }
        return $actions;
    }
}
