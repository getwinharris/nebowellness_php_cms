<section class="campaign-index">
  <div class="container">
    <header class="campaign-index__intro">
      <h1>Therapy Services</h1>
      <p>Explore Nebo’s clinic-led programs and enquire about the one that fits your needs.</p>
    </header>
    <?php if (empty($pages)): ?>
      <div class="campaign-empty">
        <h2>Explore your care options</h2>
        <p>Contact our team to ask which programs are currently available.</p>
        <a class="btn btn-primary" href="/contact#contact-form">Contact the clinic</a>
      </div>
    <?php else: ?>
      <?php $therapyProducts = $pages; $therapyCatalogPage = true; require __DIR__ . '/../partials/therapy-products.php'; ?>
    <?php endif; ?>
  </div>
</section>
