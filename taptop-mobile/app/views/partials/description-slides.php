<?php
/**
 * Descripción en slides (6.2): una foto propia + un titular de la ficha por slide, sin texto
 * comercial. La foto es la del color elegido: color-swatches.js la cambia junto con la principal
 * (sin foto para ese color queda la silueta teñida). Desplazamiento nativo con scroll-snap
 * (swipe sin JS); slider.js agrega flechas, puntos y el titular que entra palabra por palabra.
 * @var array $product vista de ProductPresenter::detail()
 */
$slides = $product['slides'];
if ($slides === []) {
    return;
}
$count = count($slides);
$trackId = ui_id('features');
?>
<section class="features" aria-roledescription="carrusel" aria-label="<?= e($product['name']) ?> en detalle" data-slider>
  <div class="container features__head">
    <h2 class="section-title">En detalle</h2>
    <div class="features__controls" data-slider-controls hidden>
      <button class="round-button glass-capsule" type="button" aria-label="Característica anterior" aria-controls="<?= e($trackId) ?>" data-slider-prev><?= icon('chevron-left', 'icon') ?></button>
      <button class="round-button glass-capsule" type="button" aria-label="Característica siguiente" aria-controls="<?= e($trackId) ?>" data-slider-next><?= icon('chevron-right', 'icon') ?></button>
    </div>
  </div>
  <div class="features__track" id="<?= e($trackId) ?>" tabindex="0" aria-label="Desliza para ver más" data-slider-track>
    <?php foreach ($slides as $i => $slide): ?>
    <figure class="feature" role="group" aria-roledescription="característica" aria-label="<?= $i + 1 ?> de <?= $count ?>" data-slider-item>
      <div class="feature__media plinth" data-slide-media>
        <?= device_placeholder($product['kind'], $slide['hex'], [
            'label' => $slide['alt'],
            'class' => $slide['image'] !== null ? 'is-covered' : '',
        ]) ?>
        <?php if ($slide['image'] !== null): ?>
        <img class="media__img" src="<?= e($slide['image']) ?>" width="1600" height="1200" alt="<?= e($slide['alt']) ?>" loading="lazy" decoding="async" data-media-img>
        <?php endif; ?>
      </div>
      <figcaption class="feature__title"><?php foreach (title_words($slide['title']) as $w => $word): ?><span class="w" style="--i: <?= $w ?>"><?= e($word) ?></span> <?php endforeach; ?></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="container">
    <div class="features__dots" data-slider-dots hidden>
      <?php foreach ($slides as $i => $slide): ?>
      <button class="features__dot" type="button" aria-label="Ver característica <?= $i + 1 ?> de <?= $count ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?> data-slider-dot="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
