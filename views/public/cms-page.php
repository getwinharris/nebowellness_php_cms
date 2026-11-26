<div class="container cms-public">
<?php if ($document===null): ?>
    <h1>Page temporarily unavailable</h1><p>Please try again shortly.</p>
<?php else: $preview=false; require __DIR__.'/../partials/cms-page-document.php'; endif; ?>
</div>
