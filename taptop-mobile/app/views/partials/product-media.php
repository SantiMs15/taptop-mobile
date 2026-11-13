<?php
/**
 * Imagen del producto sobre el pedestal. Con foto propia del color se muestra la foto;
 * si no, la silueta de referencia teñida con el color elegido (Etapa 0).
 * color-swatches.js cambia una u otra al elegir otro color.
 * @var array $card
 * @var string $sizes atributo sizes de la foto
 */
$media = $card['media'];
?>
<?php if ($media['image'] !== null || $media['placeholder']): ?>
  <?= device_placeholder($card['kind'], $media['hex'], [
      'label' => $media['alt'],
      'class' => $media['image'] !== null ? 'is-covered' : '',
  ]) ?>
  <?php if ($media['image'] !== null): ?>
  <img class="media__img" src="<?= e($media['image']) ?>" width="1200" height="1500" sizes="<?= e($sizes) ?>"
       alt="<?= e($media['alt']) ?>"<?= !empty($eager) ? ' fetchpriority="high"' : ' loading="lazy"' ?> decoding="async" data-media-img>
  <?php endif; ?>
<?php else: ?>
  <p class="media__pending">Foto próximamente</p>
<?php endif; ?>
