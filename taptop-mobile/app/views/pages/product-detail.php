<?php
/**
 * Detalle (6.2): solo especificaciones, sin texto comercial. Foto + selectores + dos precios +
 * "Confirmar disponibilidad" y "Resolver una duda", bloque de confianza, slides y acordeón.
 * @var array $product vista de ProductPresenter::detail()
 * @var string $nav_category
 */
$uid = ui_id('pdp');
$current = $product['current'];
$business = (string) config('business_name');
$ctaTrack = $product['track'] + [
    'color' => $current['color'],
    'storage' => $current['storage'],
    'price_method' => 'efectivo',
    'price_shown' => $current['cash'],
];
?>
<div class="container">
  <nav class="breadcrumb" aria-label="Ruta de navegación">
    <ol>
      <li><a href="/productos">Productos</a></li>
      <li><a href="<?= e(url('/productos', ['categoria' => $nav_category])) ?>"><?= e(category_label($nav_category)) ?></a></li>
      <li aria-current="page"><?= e($product['name']) ?></li>
    </ol>
  </nav>

  <section class="pdp<?= $product['all_sold_out'] ? ' is-sold-out' : '' ?>" aria-labelledby="pdp-title" data-product-card>
    <div class="pdp__media">
      <div class="pdp__plinth plinth" data-media>
        <?php partial('product-media', ['card' => $product, 'sizes' => '(max-width: 960px) 92vw, 640px']); ?>
      </div>
    </div>

    <div class="pdp__info">
      <div class="pdp__head">
        <p class="pdp__brand"><?= e($product['brand_label']) ?></p>
        <h1 class="pdp__title" id="pdp-title"><?= e($product['name']) ?></h1>
        <?php if ($product['single_option'] !== null): ?>
        <p class="pdp__spec"><?= e($product['single_option']) ?></p>
        <?php endif; ?>
      </div>

      <?php partial('color-swatches', ['card' => $product, 'uid' => $uid, 'size' => 'lg']); ?>
      <?php partial('price-block', ['card' => $product, 'size' => 'lg']); ?>

      <div class="pdp__actions">
        <a class="btn btn--wa btn--lg btn--block" href="<?= e($current['wa']) ?>" target="_blank" rel="noopener noreferrer" data-cta<?= track_attrs('whatsapp_click', $ctaTrack) ?>>
          <?= icon('whatsapp', 'icon') ?><span data-cta-label><?= e($current['cta']) ?></span>
        </a>
        <form method="post" action="<?= e(url('/carrito/agregar')) ?>" data-add-to-cart<?= $current['available'] ? '' : ' hidden' ?>>
          <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
          <input type="hidden" name="sku" value="<?= e((string) $current['sku']) ?>" data-add-sku>
          <input type="hidden" name="color" value="<?= e((string) $current['color']) ?>" data-add-color>
          <button class="btn btn--outline btn--lg btn--block" type="submit"<?= track_attrs('add_to_cart', $ctaTrack) ?>><?= icon('cart', 'icon') ?><span>Agregar al carrito</span></button>
        </form>
        <?php if (wompi() !== null): // SPIKE Wompi: pago con pasarela al precio con tarjeta. ?>
        <a class="btn btn--primary btn--lg btn--block" href="<?= e((string) $current['buy']) ?>" data-buy<?= $current['buy'] === null ? ' hidden' : '' ?>>
          <span>Comprar ahora</span>
        </a>
        <p class="pdp__note" data-buy-note<?= $current['buy'] === null ? ' hidden' : '' ?>>Pago con tarjeta, PSE o Nequi en Wompi, al precio con pasarela.<?= wompi()->isSandbox() ? ' <strong>Modo de pruebas: no se cobra dinero real.</strong>' : '' ?></p>
        <?php endif; ?>
      </div>

      <p class="pdp__note">El precio con pasarela incluye el costo de la plataforma de pago. Tiempo y costo de envío: se confirman por WhatsApp según tu ciudad.</p>

      <ul class="trust" aria-label="Por qué comprar aquí">
        <li><?= icon('seal', 'icon') ?><span>Producto 100 % original.</span></li>
        <li><?= icon('shield', 'icon') ?><span>Garantía del fabricante y <?= (int) config('warranty_months') ?> meses de garantía <?= e($business) ?>: si presenta un fallo, te lo cambiamos.</span></li>
        <li><?= icon('truck', 'icon') ?><span>Envío a toda Colombia, gratis desde <?= e(format_price((int) config('free_shipping_min'))) ?>.</span></li>
      </ul>
      <a class="text-link" href="/garantia">Garantía, cambios y envíos</a>
    </div>

    <script type="application/json" data-variants><?= json_for_script($product['client']) ?></script>
  </section>
</div>

<?php partial('description-slides', ['product' => $product]); ?>

<section class="container section specs-section" aria-labelledby="specs-title">
  <div class="specs-section__head">
    <h2 class="section-title" id="specs-title">Características</h2>
    <p class="specs-section__lede">Toca cada característica para ver el detalle.</p>
  </div>
  <div class="specs-section__body">
    <?php partial('specs-accordion', ['specs' => $product['specs'], 'status' => $product['specs_status']]); ?>
  </div>
</section>

<span hidden data-track-onload<?= track_attrs('view_item', $product['track'] + [
    'color' => $current['color'],
    'storage' => $current['storage'],
    'price_shown' => $current['cash'],
]) ?>></span>
