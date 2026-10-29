<?php
/**
 * Miniaturas del detalle: la foto principal y las de "En detalle" del color elegido. Al tocar una
 * pasa a la foto grande. color-swatches.js las cambia al elegir otro color; sin galería queda oculto.
 * Columna a la izquierda de la foto en escritorio; fila debajo en celular.
 * @var array $product vista de ProductPresenter::detail()
 */
$gallery = $product['gallery'];
?>
<div class="thumbs" role="group" aria-label="Fotos del producto" data-gallery<?= $gallery === [] ? ' hidden' : '' ?>>
  <?php foreach ($gallery as $i => $item): ?>
  <button class="thumb" type="button" data-thumb="<?= (int) $i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Ver foto: <?= e($item['alt']) ?>">
    <img src="<?= e($item['src']) ?>" alt="" width="76" height="76" loading="lazy" decoding="async">
  </button>
  <?php endforeach; ?>
</div>
