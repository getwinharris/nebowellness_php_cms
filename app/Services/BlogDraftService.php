<?php
namespace App\Services;
final class BlogDraftService
{
    private array $links;
    public function __construct()
    {
        $this->links = [
            'register' => '/register',
            'sign in' => '/login',
            'account' => '/account',
            'orders' => '/account/orders',
            'cart' => '/cart',
            'checkout' => '/checkout',
            'products' => '/shop',
            'contact' => '/contact',
            'blog' => '/blog',
        ];
    }
    public function draft(string $template, string $title, string $sourceUrl): string
    {
        $method = 'draft' . ucfirst($template);
        if (method_exists($this, $method)) {
            return $this->$method($title, $sourceUrl);
        }
        return $this->draftEditorial($title, $sourceUrl);
    }
    private function link(string $text): string
    {
        $href = $this->links[$text] ?? $text;
        if (str_starts_with($href, '/')) {
            return "[{$text}]({$href})";
        }
        return $text;
    }
    private function draftEditorial(string $title, string $sourceUrl): string
    {
        return "## Overview\n\nThis article introduces {$title}. Confirm all health claims with a qualified clinician before publishing.\n\n## What to explore\n\n- Explain the topic in plain language\n- Describe which Nebo program or service is relevant\n- State what a reader can ask at a consultation\n\n## Next steps\n\nExplore [Nebo programs](/#programs) or [contact the clinic]({$this->links['contact']}).\n";
    }
    private function draftProduct(string $title, string $sourceUrl): string
    {
        return "## About this product\n\nThis guide covers [{$title}]({$sourceUrl}) — what it is, how it is used, and what to expect.\n\n## Features\n\n- List verified materials or ingredients\n- Explain instructions and suitability without unsupported medical claims\n- Link to the current [{$this->link('products')}] page\n\n## How to order\n\n1. Add the item to your [{$this->link('cart')}]\n2. Proceed to [{$this->link('checkout')}] and enter your delivery address\n3. Complete payment and track your [{$this->link('orders')}]\n\n## Questions\n\n[{$this->link('contact')}] for product information.\n";
    }
    private function draftTool(string $title, string $sourceUrl): string
    {
        return "## Overview\n\nThis guide explains how to use the {$title} feature. The page shown below demonstrates the interface and key controls.\n\n## Accessing the feature\n\n1. [{$this->link('sign in')}] to your [{$this->link('account')}]\n2. Navigate using the on-page controls shown in the screenshot\n3. Follow the on-screen prompts to complete your task\n\n## Need help?\n\n[{$this->link('contact')}] can help with your Nebo account or clinic enquiry.\n";
    }
    private function draftHelp(string $title, string $sourceUrl): string
    {
        return "## What this guide covers\n\nFollow these steps to {$title}. The screenshot above shows the page you will be working with.\n\n## Step-by-step instructions\n\n1. **Open the page** — [Visit the page]({$sourceUrl}) shown in the screenshot above\n2. **Enter your details** — Fill in the required fields as shown\n3. **Confirm and continue** — Select the confirmation button to proceed\n\n## Still stuck?\n\n[{$this->link('contact')}] can help with your Nebo account or clinic enquiry.\n";
    }
}
