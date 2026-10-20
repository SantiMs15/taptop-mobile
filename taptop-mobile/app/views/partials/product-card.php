<?php
/**
 * Tarjeta de producto (6.1): imagen grande, marca, nombre, precio por consignación o transferencia
 * y "Ver producto". Color, capacidad y "Confirmar disponibilidad" viven en la ficha del producto.
 * @var array $card vista de ProductPresenter::card()
 */
?>
<article class="card<?= $card['all_sold_out'] ? ' is-sold-out' : '' ?>">
  <a class="card__media plinth" href="<?= e($card['url']) ?>" tabindex="-1" aria-hidden="true"<?= track_attrs('select_item', $card['track']) ?>>
    <?php partial('product-media', ['card' => $card, 'sizes' => '(max-width: 600px) 92vw, 320px']); ?>
    <?php if ($card['all_sold_out']): ?><span class="badge badge--overlay">Agotado</span><?php endif; ?>
  </a>
  <div class="card__body">
    <div class="card__head">
      <p class="card__brand"><?= e($card['brand_label']) ?></p>
      <h3 class="card__title"><a href="<?= e($card['url']) ?>"<?= track_attrs('select_item', $card['track']) ?>><?= e($card['name']) ?></a></h3>
    </div>
    <?php partial('price-block', ['card' => $card, 'size' => 'sm']); ?>
    <a class="btn btn--outline btn--block card__cta" href="<?= e($card['url']) ?>"<?= track_attrs('select_item', $card['track']) ?>>
      <span>Ver producto<span class="visually-hidden">: <?= e($card['name']) ?></span></span><?= icon('chevron-right', 'icon') ?>
    </a>
  </div>
</article>
