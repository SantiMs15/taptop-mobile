<?php
/**
 * Los dos precios (6.1): consignación/transferencia destacado y pasarela debajo, en COP.
 * También el estado de envío y "Agotado". color-swatches.js reemplaza los textos con
 * valores ya calculados en el servidor.
 * @var array $card
 * @var string $size 'sm' (tarjeta) o 'lg' (detalle)
 */
$current = $card['current'];
?>
<div class="price price--<?= e($size) ?>" data-price>
  <p class="price__from" data-price-from<?= $card['from'] ? '' : ' hidden' ?>>Desde</p>
  <p class="price__row price__row--cash">
    <span class="price__value" data-price-cash><?= e($current['cash_f']) ?></span>
    <?php if ($size !== 'sm'): ?>
    <span class="price__label"><?= e(\Taptop\Catalog\ProductPresenter::LABEL_CASH) ?></span>
    <?php endif; ?>
  </p>
  <?php if ($size !== 'sm'): // Las tarjetas del listado muestran solo el precio, sin etiqueta ni el de pasarela. ?>
  <p class="price__row price__row--gw">
    <span class="price__value" data-price-gw><?= e($current['gw_f']) ?></span>
    <span class="price__label"><?= e(\Taptop\Catalog\ProductPresenter::LABEL_GATEWAY) ?></span>
  </p>
  <?php endif; ?>
  <p class="price__meta">
    <span class="price__ship<?= $current['ship_free'] ? ' is-free' : '' ?>" data-price-ship><?= icon('truck', 'icon') ?><span data-price-ship-text><?= e($current['ship']) ?></span></span>
    <span class="badge badge--sold" data-price-stock<?= $current['available'] ? ' hidden' : '' ?>>Agotado</span>
  </p>
  <p class="visually-hidden" aria-live="polite" data-price-live></p>
</div>
