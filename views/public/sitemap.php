<?php
use App\Services\SeoService;
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $path => $modified): ?>
  <url><loc><?= htmlspecialchars(SeoService::publicUrl($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc><?php if ($modified !== null): ?><lastmod><?= e($modified) ?></lastmod><?php endif; ?></url>
<?php endforeach; ?>
</urlset>
