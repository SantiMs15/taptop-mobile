<?php
/**
 * Inicio: escenario del hero, dock de categorías con su grilla, tres pilares y cierre por WhatsApp.
 * @var array $hero
 * @var list<array> $featured
 * @var list<array> $categories
 */
$business = (string) config('business_name');
$freeShipping = format_price((int) config('free_shipping_min'));
// Solo categorías con productos; sin ninguna (catálogo vacío o dañado) no va la sección.
$indexCategories = array_filter($categories, static fn (array $item): bool => $item['key'] !== null && $item['count'] > 0);
?>
<?php partial('hero-carousel', ['hero' => $hero]); ?>

<?php if ($indexCategories !== [] || $featured !== []): ?>
<section class="section section--shop" aria-labelledby="index-title">
  <div class="container">
    <h2 class="section-title section-title--blur" id="index-title"><?php partial('soft-blur-text', ['text' => '¿Qué estás buscando?']); ?></h2>
    <nav class="dock" aria-label="Categorías de productos" data-dock>
      <div class="dock__frame">
        <span class="dock__bar" aria-hidden="true"></span>
        <ul class="dock__row">
          <?php if ($featured !== []): ?>
          <li class="dock__item">
            <a class="dock__link" href="/productos" aria-current="true" aria-label="Destacados" data-dock-key="" data-dock-label="Destacados"><span class="dock__tile"><?= icon('star', 'icon') ?></span></a>
            <span class="dock__dot" aria-hidden="true"></span>
            <span class="dock__label" aria-hidden="true">Destacados</span>
          </li>
          <?php endif; ?>
          <?php foreach ($indexCategories as $item): ?>
          <li class="dock__item">
            <a class="dock__link" href="<?= e($item['href']) ?>" aria-label="<?= e($item['label']) ?>" data-dock-key="<?= e($item['key']) ?>" data-dock-label="<?= e($item['label']) ?>"><span class="dock__tile"><?= icon('cat-' . $item['key'], 'icon') ?: icon('tag', 'icon') ?></span></a>
            <span class="dock__dot" aria-hidden="true"></span>
            <span class="dock__label" aria-hidden="true"><?= e($item['label']) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
    <div class="shop" data-dock-results>
      <div class="shop__head" aria-live="polite">
        <h3 class="shop__title" data-dock-title>Destacados</h3>
        <p class="shop__count" data-dock-count><?= count($featured) ?> <?= count($featured) === 1 ? 'producto' : 'productos' ?></p>
        <a class="text-link" href="/productos">Ver todos los productos</a>
      </div>
      <div class="grid-cards">
        <?php foreach ($featured as $card): ?>
          <?php partial('product-card', ['card' => $card]); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="why" aria-labelledby="why-title" data-why>
  <div class="why__content">
    <div>
      <div class="why__brand why__step" style="--i: 0">
        <?= logo_mark_3d('why__logo') ?>
        <div>
          <p class="why__name"><?= e($business) ?></p>
          <p class="why__slogan">Celulares y tecnología Apple y Samsung</p>
        </div>
      </div>
      <h2 class="why__title why__step" id="why-title" style="--i: 1">Por qué comprar en <?= e($business) ?></h2>
      <span class="why__rule why__step" style="--i: 2" aria-hidden="true"></span>
      <p class="why__lede why__step" style="--i: 3">Productos Apple y Samsung 100 % originales, con garantía directa y atención por WhatsApp todos los días.</p>
      <a class="btn btn--metal why__cta why__step" href="/productos" style="--i: 4">Ver productos</a>
    </div>
    <ul class="why__list why__step" style="--i: 5">
      <li class="why__item">
        <?= icon('tag', 'icon') ?>
        <h3 class="why__item-title">Precios</h3>
        <p class="why__item-text">Dos precios claros en cada producto, y el mejor es por consignación o transferencia. Envío gratis desde <?= e($freeShipping) ?>.</p>
      </li>
      <li class="why__item">
        <?= icon('shield', 'icon') ?>
        <h3 class="why__item-title">Garantía</h3>
        <p class="why__item-text">Garantía directa con <?= e($business) ?>: <?= e(warranty_summary()) ?>. Si presenta un fallo, te lo cambiamos o te devolvemos tu dinero.</p>
      </li>
      <li class="why__item">
        <?= icon('seal', 'icon') ?>
        <h3 class="why__item-title">Profesionalismo</h3>
        <p class="why__item-text">Productos Apple y Samsung 100 % originales y atención por WhatsApp todos los días.</p>
      </li>
    </ul>
  </div>
  <div class="why__media">
    <canvas class="why__aurora" aria-hidden="true" data-aurora></canvas>
    <img src="<?= e(asset('images/hero/por-que-apple-watch-series-12.webp')) ?>" width="747" height="1200" alt="" loading="lazy" decoding="async">
  </div>
</section>

<section class="section cta-band" aria-labelledby="cta-title">
  <div class="container cta-band__inner">
    <h2 class="cta-band__title" id="cta-title">¿Tienes una duda? Te respondemos por WhatsApp.</h2>
    <p class="cta-band__text"><?= e(config('support_days_label')) ?>, <?= e_todo(config_text('support_hours_label')) ?> (<?= e(config('support_timezone_label')) ?>). Te confirmamos disponibilidad, precio final y envío a tu ciudad.</p>
    <a class="btn btn--metal" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'inicio', 'page' => 'home']) ?>>
      <?= icon('whatsapp', 'icon icon--wa') ?><span>Escribir por WhatsApp</span>
    </a>
  </div>
</section>
