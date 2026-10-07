<?php
namespace App\Services;
final class SeoService {
    private string $siteName;
    private string $defaultOgImage;
    private string $twitterHandle;
    private array $telephone;

    public function __construct(array $secrets = []) {
        $envName = getenv('APP_NAME') ?: 'Nebo Wellness';
        $this->siteName = $secrets['seo_site_name'] ?? $envName;
        $configuredOgImage = (string)($secrets['seo_default_og_image'] ?? '');
        $this->defaultOgImage = $configuredOgImage !== '' && !str_contains(strtolower($configuredOgImage), 'sripanchami')
            ? $configuredOgImage
            : 'https://' . ($_SERVER['HTTP_HOST'] ?? 'nebowellness.com') . '/assets/images/nebo-clinic-hero.png';
        $this->twitterHandle = $secrets['seo_twitter_handle'] ?? '';
        $phone = $secrets['phone'] ?? getenv('CONTACT_PHONE') ?: '';
        $this->telephone = $phone !== '' ? [$phone] : ['+917200182025', '+919585182025'];
    }

    public function page(string $key, array $overrides = []): array {
        $host = $_SERVER['HTTP_HOST'] ?? 'nebowellness.com';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme . '://' . $host . $uri;

        $defaults = $this->defaults($key);
        $meta = array_merge($defaults, $overrides);

        if (!empty($overrides['keywords'])) {
            $meta['keywords'] = $overrides['keywords'];
        } elseif (!isset($meta['keywords'])) {
            $meta['keywords'] = $defaults['keywords'] ?? '';
        }

        $meta['canonical'] ??= $url;
        $meta['og_url'] ??= $url;
        $meta['og_site_name'] = $this->siteName;
        $meta['og_image'] ??= $this->defaultOgImage;
        if (str_starts_with((string)$meta['og_image'], '/')) $meta['og_image'] = $scheme . '://' . $host . $meta['og_image'];
        $meta['twitter_image'] ??= $meta['og_image'];
        $meta['twitter_title'] ??= $meta['og_title'] ?? $meta['title'];
        $meta['twitter_description'] ??= $meta['og_description'] ?? $meta['description'];
        $meta['og_title'] ??= $meta['title'];
        $meta['og_description'] ??= $meta['description'];

        return $meta;
    }

    private function defaults(string $key): array {
        $brand = 'Nebo Lifestyle Clinic';
        $desc = 'Naturopathy, functional medicine, and integrative wellness for sustainable health transformation.';
        $maps = [
            'home' => [
                'title' => $brand . ' – Where Science Meets Nature for Lifelong Wellness',
                'description' => 'Naturopathy, functional medicine, and maternal & fertility wellness at Nebo Lifestyle Clinic. Personalised care for gut health, metabolic diseases, and sustainable lifestyle transformation.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'shop' => [
                'title' => 'Wellness Shop – Natural Products Online',
                'description' => 'Browse natural wellness products online at ' . $brand . '. Curated essentials to support your personalised naturopathy and lifestyle plan.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'product' => [
                'title' => 'Wellness Products Online',
                'description' => 'Browse our collection of natural wellness products.',
                'og_type' => 'product',
                'robots' => 'index, follow',
            ],
            'consult' => [
                'title' => 'Wellness Consultations & Naturopathy Services',
                'description' => 'Discover personalised naturopathy, functional medicine, and wellness consultations at Nebo Lifestyle Clinic.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'consultant' => [
                'title' => 'Our Wellness Consultants',
                'description' => 'Meet our experienced naturopaths, functional medicine specialists, and wellness consultants at Nebo Lifestyle Clinic.',
                'og_type' => 'profile',
                'robots' => 'index, follow',
            ],
            'campaigns' => [
                'title' => 'Wellness Campaigns – ' . $brand,
                'description' => 'Explore focused Nebo Lifestyle Clinic campaigns and find a practical next step for your wellness goals.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'campaign' => [
                'title' => 'Wellness Campaign – ' . $brand,
                'description' => 'Discover a Nebo Lifestyle Clinic wellness campaign and contact our team for personal guidance.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'about' => [
                'title' => 'About ' . $brand . ' – Integrative Wellness Clinic',
                'description' => 'Learn about ' . $brand . ', a naturopathy and functional medicine clinic in Kanyakumari offering root-cause, personalised wellness care.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'contact' => [
                'title' => 'Contact ' . $brand . ' – Book Your Consultation',
                'description' => 'Reach out to ' . $brand . ' to book a consultation or ask about gut health, metabolic, fertility, and lifestyle programs. Call or email us.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'cart' => [
                'title' => 'Shopping Cart – ' . $brand,
                'description' => 'Review your shopping cart at ' . $brand . '. Proceed to checkout for natural wellness products.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'checkout' => [
                'title' => 'Checkout – ' . $brand,
                'description' => 'Complete your purchase at ' . $brand . '. Secure payment for natural wellness products.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'privacy' => [
                'title' => 'Privacy Policy – ' . $brand,
                'description' => 'Read the privacy policy of ' . $brand . '. Learn how we collect, use, and protect your personal information when you use our clinic website and support services.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'terms' => [
                'title' => 'Terms & Conditions – ' . $brand,
                'description' => 'Read the terms and conditions of ' . $brand . '. Understand the guidelines for bookings, purchases, and using our website.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'login' => [
                'title' => 'Sign In – ' . $brand,
                'description' => 'Sign in to your ' . $brand . ' account to manage orders and saved delivery addresses.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'register' => [
                'title' => 'Create Account – ' . $brand,
                'description' => 'Create your ' . $brand . ' account to save delivery addresses and manage bookings and orders.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'forgot-password' => [
                'title' => 'Forgot Password – ' . $brand,
                'description' => 'Reset your ' . $brand . ' account password.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'reset-password' => [
                'title' => 'Reset Password – ' . $brand,
                'description' => 'Reset your ' . $brand . ' account password.',
                'og_type' => 'website',
                'robots' => 'noindex, follow',
            ],
            'account' => [
                'title' => 'My Account – ' . $brand,
                'description' => 'Manage your ' . $brand . ' account, view product orders, and track deliveries.',
                'og_type' => 'website',
                'robots' => 'noindex, nofollow',
            ],
            'blog' => [
                'title' => 'Blog & Updates – ' . $brand,
                'description' => 'Read the latest blog posts, wellness guides, and clinic updates from ' . $brand . '.',
                'og_type' => 'website',
                'robots' => 'index, follow',
                'keywords' => 'gut health, fertility wellness, functional medicine, naturopathy, lifestyle medicine',
            ],
            'blog.post' => [
                'title' => 'Blog Post – ' . $brand,
                'description' => 'Read articles, guides, and updates from ' . $brand . '.',
                'og_type' => 'article',
                'robots' => 'index, follow',
                'keywords' => 'gut health, fertility, naturopathy, wellness',
            ],
            'blog.category' => [
                'title' => 'Blog Category – ' . $brand,
                'description' => 'Browse blog articles by category at ' . $brand . '.',
                'og_type' => 'website',
                'robots' => 'index, follow',
            ],
            'admin' => [
                'title' => 'Admin Panel – ' . $brand,
                'description' => '',
                'robots' => 'noindex, nofollow',
            ],
        ];
        return $maps[$key] ?? [
            'title' => $brand,
            'description' => $desc,
            'og_type' => 'website',
            'robots' => 'index, follow',
        ];
    }

    public function jsonLdScript(array $data): string {
        if (!$data) return '';
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    public function organizationSchema(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => ['Organization', 'MedicalClinic'],
            'name' => $this->siteName,
            'description' => 'Naturopathy, functional medicine, and integrative wellness for sustainable health transformation.',
            'url' => $this->pageUrl(''),
            'telephone' => $this->telephone,
            'email' => 'nebolifestyleclinic@gmail.com',
        ];
    }

    public function breadcrumbSchema(array $items): array {
        $itemList = [];
        $position = 1;
        foreach ($items as $item) {
            $entry = ['@type' => 'ListItem', 'position' => $position++];
            if (is_string($item)) {
                $entry['name'] = $item;
                $entry['item'] = $this->pageUrl($item === 'Home' ? '' : strtolower(str_replace(' ', '-', $item)));
            } else {
                $entry['name'] = $item['name'] ?? '';
                $entry['item'] = $item['url'] ?? '';
            }
            $itemList[] = $entry;
        }
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }

    public function productSchema(array $product): array {
        $price = $product['offer_price'] ?? $product['price'] ?? 0;
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'] ?? '',
            'description' => $product['description'] ?? '',
            'image' => $product['image_url'] ?? $this->defaultOgImage,
            'offers' => [
                '@type' => 'Offer',
                'price' => (float)$price,
                'priceCurrency' => 'INR',
                'availability' => 'https://schema.org/InStock',
                'url' => $this->pageUrl('/product/' . ($product['slug'] ?? '')),
            ],
        ];
    }

    public function personSchema(array $consultant): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $consultant['name'] ?? '',
            'description' => ($consultant['speciality'] ?? '') . ' consultant with ' . ($consultant['experience_years'] ?? '') . ' years of experience.',
            'image' => $consultant['photo_url'] ?? $this->defaultOgImage,
            'knowsLanguage' => $consultant['languages'] ?? [],
        ];
    }

    public function aboutPageSchema(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'name' => 'About ' . $this->siteName,
            'description' => 'Learn about ' . $this->siteName . ', a naturopathy and functional medicine clinic offering root-cause, personalised wellness care.',
            'mainEntity' => $this->organizationSchema(),
        ];
    }

    public function contactPageSchema(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => 'Contact ' . $this->siteName,
            'description' => 'Get in touch with ' . $this->siteName . ' to book a consultation or ask about wellness programs and bookings.',
            'mainEntity' => $this->organizationSchema(),
        ];
    }

    public function faqPageSchema(array $questions): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(function ($q) {
                return [
                    '@type' => 'Question',
                    'name' => $q['question'] ?? '',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $q['answer'] ?? '',
                    ],
                ];
            }, $questions),
        ];
    }

    private function pageUrl(string $path): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'nebowellness.com';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }
}
