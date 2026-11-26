<?php
namespace App\Controllers;

use App\Services\BlogService;
use App\Services\MarkdownRenderer;

final class BlogController extends BaseController
{
    public function __construct(private BlogService $blogService=new BlogService()) {}

    public function index(): void
    {
        $this->requireModule('blog');
        $this->detectApiRequest();
        $this->seoKey = 'blog';

        $blogService = $this->blogService;
        $posts = $blogService->all();
        $categories = $blogService->categories();

        $this->render('public/blog', [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => null,
        ]);
    }

    public function show(string $slug): void
    {
        $this->requireModule('blog');
        $this->detectApiRequest();
        $blogService = $this->blogService;
        $post = $blogService->find($slug);

        // `find()` also serves the editor, so an unpublished post must be rejected
        // here as well as omitted from the index. Otherwise a retired article stays
        // publicly available to anyone with its old URL.
        if ($post === null || !$blogService->isPublic($post)) {
            $this->renderNotFound();
            return;
        }

        $content = $post['html'] ?? '';

        $title = $post['seo_title'] ?? $post['title'] ?? ucfirst(str_replace('-', ' ', $slug));
        $this->seoKey = 'blog.post';
        $keywords = $post['keywords'] ?? '';
        $this->seoOverrides = [
            'title' => $title,
            'description' => $post['seo_description'] ?? $post['excerpt'] ?? 'Read ' . $title,
            'og_image' => $post['og_image'] ?? $post['image'] ?? null,
            'keywords' => $keywords,
        ];

        $this->render('public/blog-post', [
            'content' => $content,
            'meta' => $post,
            'slug' => $slug,
        ]);
    }

    public function category(string $slug): void
    {
        $this->requireModule('blog');
        $this->detectApiRequest();
        $this->seoKey = 'blog.category';

        $blogService = $this->blogService;
        $posts = $blogService->all();
        $categories = $blogService->categories();

        $filtered = array_values(array_filter($posts, fn($p) => ($p['category'] ?? '') === $slug));

        $categoryName = null;
        foreach ($categories as $cat) {
            if (($cat['slug'] ?? '') === $slug) {
                $categoryName = $cat['name'] ?? $slug;
                break;
            }
        }

        if ($categoryName === null || $blogService->categoryIsDisabled($slug)) $this->renderNotFound();

        $this->seoOverrides = [
            'title' => $categoryName . ' — Blog',
            'description' => 'Browse ' . $categoryName . ' articles and updates.',
        ];

        $this->render('public/blog', [
            'posts' => $filtered,
            'categories' => $categories,
            'activeCategory' => $slug,
            'categoryName' => $categoryName,
        ]);
    }
}
