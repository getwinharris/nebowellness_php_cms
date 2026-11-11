<section class="campaign-index">
  <div class="container">
    <header class="campaign-index__intro">
      <h1>Therapy Products</h1>
      <p>Explore Nebo’s clinic-led therapies and request a session with our team.</p>
    </header>
    <?php if (empty($pages)): ?>
      <div class="campaign-empty"><h2>No therapy products are listed yet</h2><p>Our team can help you explore Nebo's current programs.</p><a class="btn btn-primary" href="/contact">Contact the clinic</a></div>
    <?php else: ?>
      <?php $therapyProducts = $pages; $therapyCatalogPage = true; require __DIR__ . '/../partials/therapy-products.php'; ?>
    <?php endif; ?>
  </div>
</section>
