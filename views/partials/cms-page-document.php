<?php
$headingTag=($preview??false)?'h3':'h2';
$titleTag=($preview??false)?'h2':'h1';
$markdown=new \App\Services\MarkdownRenderer();
$dynamicService=null;$itemTag=($preview??false)?'h4':'h3';
?>
<article class="cms-document">
    <<?= $titleTag ?> class="cms-document-title"><?= e($document['title']) ?></<?= $titleTag ?>>
    <?php if ($document['description']!==''): ?><p class="cms-document-description"><?= e($document['description']) ?></p><?php endif; ?>
    <?php foreach ($document['sections'] as $section): if (!$section['visible']) continue; $c=$section['content'];
        $dynamic=null;
        if (\App\Services\CmsDynamicSectionService::supports($section['type'])) {
            $dynamicService??=new \App\Services\CmsDynamicSectionService();
            $dynamic=$dynamicService->resolve($section['type'],(int)$c['display_limit']);
            if ($dynamic['state']==='disabled'&&empty($preview)) continue;
        }
    ?>
        <section class="cms-section cms-section--<?= e($section['type']) ?>" id="section-<?= e($section['id']) ?>">
        <?php if ($section['type']==='faq'): ?>
            <details><summary><?= e($c['heading']?:'Question') ?></summary><div class="cms-markdown"><?= $markdown->render($c['body']) ?></div></details>
        <?php else: ?>
            <?php if ($c['heading']!==''): ?><<?= $headingTag ?>><?= e($c['heading']) ?></<?= $headingTag ?>><?php endif; ?>
            <?php if ($section['type']==='image_text' && $c['image_url']!==''): ?><img loading="lazy" src="<?= e($c['image_url']) ?>" alt="<?= e($c['image_alt']) ?>"><?php endif; ?>
            <?php if (in_array($section['type'],['rich_text','image_text'],true)): ?><div class="cms-markdown"><?= $markdown->render($c['body']) ?></div>
            <?php elseif ($c['body']!==''): ?><p class="cms-section-copy"><?= e($c['body']) ?></p><?php endif; ?>
            <?php if ($dynamic!==null): require __DIR__.'/cms-dynamic-section.php'; endif; ?>
            <?php if ($section['type']==='cta' && $c['link_url']!=='' && $c['link_label']!==''): ?><a class="btn btn-primary" href="<?= e($c['link_url']) ?>"><?= e($c['link_label']) ?></a><?php endif; ?>
        <?php endif; ?>
        </section>
    <?php endforeach; ?>
</article>
