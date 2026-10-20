<?php
/**
 * Inicio: escenario del hero, categorías, destacados, tres pilares y cierre por WhatsApp.
 * @var array $hero
 * @var list<array> $featured
 * @var list<array> $categories
 */
$business = (string) config('business_name');
$freeShipping = format_price((int) config('free_shipping_min'));
$months = (int) config('warranty_months', 3);
?>
<?php partial('hero-carousel', ['hero' => $hero]); ?>

<section class="section section--index" aria-labelledby="index-title">
  <div class="container">
    <h2 class="section-title" id="index-title">¿Qué estás buscando?</h2>
    <ul class="category-index">
      <?php foreach ($categories as $item): ?>
        <?php if ($item['key'] === null || $item['count'] === 0) { continue; } ?>
      <li>
        <a class="category-index__link" href="<?= e($item['href']) ?>">
          <span class="category-index__name"><?= e($item['label']) ?></span><span class="category-index__count" aria-hidden="true"><?= (int) $item['count'] ?></span><span class="visually-hidden">, <?= (int) $item['count'] ?> productos</span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if ($featured !== []): ?>
<section class="section section--featured" aria-labelledby="featured-title">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title" id="featured-title">Destacados</h2>
      <a class="text-link" href="/productos">Ver todos los productos</a>
    </div>
    <div class="grid-cards">
      <?php foreach ($featured as $card): ?>
        <?php partial('product-card', ['card' => $card]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--pillars" aria-labelledby="pillars-title">
  <div class="container">
    <h2 class="section-title" id="pillars-title">Por qué comprar en <?= e($business) ?></h2>
    <div class="pillars">
      <article class="pillar">
        <?= icon('tag', 'icon pillar__icon') ?>
        <h3 class="pillar__title">Precios</h3>
        <p class="pillar__text">Dos precios claros en cada producto, y el mejor es por consignación o transferencia. Envío gratis desde <?= e($freeShipping) ?>.</p>
      </article>
      <article class="pillar">
        <?= icon('shield', 'icon pillar__icon') ?>
        <h3 class="pillar__title">Garantía</h3>
        <p class="pillar__text">Garantía del fabricante y <?= $months ?> meses de garantía <?= e($business) ?>: si presenta un fallo, te lo cambiamos por uno igual o equivalente.</p>
      </article>
      <article class="pillar">
        <?= icon('seal', 'icon pillar__icon') ?>
        <h3 class="pillar__title">Profesionalismo</h3>
        <p class="pillar__text">Productos Apple y Samsung 100 % originales y atención por WhatsApp todos los días.</p>
      </article>
    </div>
  </div>
</section>

<section class="section cta-band" aria-labelledby="cta-title">
  <div class="container cta-band__inner">
    <h2 class="cta-band__title" id="cta-title">¿Tienes una duda? Te respondemos por WhatsApp.</h2>
    <p class="cta-band__text"><?= e(config('support_days_label')) ?>, <?= e_todo(config_text('support_hours_label')) ?> (<?= e(config('support_timezone_label')) ?>). Te confirmamos disponibilidad, precio final y envío a tu ciudad.</p>
    <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'inicio', 'page' => 'home']) ?>>
      <?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span>
    </a>
  </div>
</section>
