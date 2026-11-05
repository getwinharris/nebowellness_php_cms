<?php
namespace App\Controllers;

use App\Services\{ProductService,CategoryService};

final class ApiController extends BaseController {
    public function index(): void {
        $shopEnabled = module_on('shop');
        $endpoints = [];
        if ($shopEnabled) $endpoints = ['/api/shop', '/api/categories', '/api/product/{slug}'];
        $this->jsonResponse(['success' => true, 'endpoints' => [
            ...$endpoints,
        ]]);
    }

    public function shop(): void {
        $this->requireModule('shop');
        $service = new ProductService();
        // The public API is a storefront surface: hidden products stay out of it.
        $products = $service->visible();
        $categories = (new CategoryService())->all();
        $this->jsonResponse(['success' => true, 'products' => $products, 'categories' => $categories]);
    }

    public function categories(): void {
        $this->requireModule('shop');
        $categories = (new CategoryService())->all();
        $this->jsonResponse(['success' => true, 'categories' => $categories]);
    }

    public function product(string $slug): void {
        $this->requireModule('shop');
        $service = new ProductService();
        $products = $service->visible();
        $product = null;
        foreach ($products as $p) {
            if (($p['slug'] ?? '') === $slug) { $product = $p; break; }
        }
        if ($product === null) {
            $this->jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
            return;
        }
        $this->jsonResponse(['success' => true, 'product' => $product]);
    }

    public function temples(): void {
        $this->jsonResponse(['success' => false, 'error' => 'This endpoint has been retired.'], 410);
    }
}
