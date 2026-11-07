<?php
/**
 * Hero del Inicio: escenario de estudio + carrusel con controlador de vidrio (5.0 y 5.1).
 * Patrón de carrusel con pestañas (WAI-ARIA APG): botón de rotación, lista de pestañas y
 * paneles. Sin JavaScript se ve el primer slide completo. carousel.js agrega el autoplay,
 * las transiciones sin cortes, el swipe y las pausas.
 * @var array $hero ['autoplay_ms' => int, 'slides' => list<array>]
 */
$slides = $hero['slides'];
$count = count($slides);
?>
<section class="stage" id="destacados" aria-roledescription="carrusel" aria-label="Destacados de <?= e(config('business_name')) ?>" data-carousel>
  <h1 class="visually-hidden"><?= e(config('business_name')) ?>: celulares y tecnología Apple y Samsung originales en Colombia</h1>

  <div class="stage__studio" aria-hidden="true">
    <span class="studio-block studio-block--a"></span>
    <span class="studio-block studio-block--b"></span>
    <span class="studio-block studio-block--c"></span>
  </div>

  <?php if ($count > 1): ?>
  <span class="stage__timer" aria-hidden="true" data-timer></span>
  <?php endif; ?>

  <div class="stage__slides">
    <?php foreach ($slides as $i => $slide):
        $words = title_words($slide['title']);
        $cta = $slide['cta'];
        $ctaIsWhatsapp = $cta['href'] === 'whatsapp';
        $headingTag = 'h2';
    ?>
    <?php
        $image = $slide['type'] === 'image' ? $slide['image'] : null;
        $cutout = $image !== null && ($image['fit'] ?? 'cover') === 'cutout';
        $mediaClass = $image === null ? '' : ($cutout ? ' slide__media--cutout' : ' slide__media--photo');
    ?>
    <div class="slide slide--<?= e($slide['type']) ?><?= $i === 0 ? ' is-active' : '' ?>" id="slide-<?= e($slide['id']) ?>" role="group" aria-roledescription="destacado" aria-label="<?= $i + 1 ?> de <?= $count ?>" data-slide data-duration="<?= (int) $slide['duration_ms'] ?>"<?= $i === 0 ? '' : ' inert' ?>>
      <div class="slide__media<?= $mediaClass ?>">
        <?php if ($slide['type'] === 'model'): ?>
          <?php partial('logo-animation', ['mode' => 'hero']); ?>
        <?php elseif ($image !== null): ?>
          <picture class="slide__picture">
            <?php if (isset($image['mobile'])): ?>
            <source media="(max-aspect-ratio: 3/4)" srcset="<?= e($image['mobile']['src']) ?>"
                    width="<?= (int) ($image['mobile']['width'] ?? 1080) ?>" height="<?= (int) ($image['mobile']['height'] ?? 1920) ?>">
            <?php endif; ?>
            <img class="slide__img<?= $cutout ? ' slide__img--cutout' : '' ?>" src="<?= e($image['src']) ?>"
                 width="<?= (int) ($image['width'] ?? 2400) ?>" height="<?= (int) ($image['height'] ?? 1350) ?>"
                 alt="<?= e($slide['alt'] ?? $slide['title']) ?>"<?= $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?> decoding="async" data-slide-img>
          </picture>
        <?php elseif ($slide['placeholder'] !== null): ?>
          <div class="ph-group ph-group--<?= count($slide['placeholder']['colors']) ?>" role="img" aria-label="<?= e('Imagen de referencia: ' . ($slide['alt'] ?? $slide['title'])) ?>">
            <?php foreach ($slide['placeholder']['colors'] as $p => $hex): ?>
              <?= device_placeholder((string) ($slide['placeholder']['kinds'][$p] ?? $slide['placeholder']['kind'] ?? 'phone'), $hex, ['decorative' => true]) ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="slide__copy" data-hover-hold>
        <<?= $headingTag ?> class="slide__title"><?php foreach ($words as $w => $word): ?><span class="w" style="--i: <?= $w ?>"><?= e($word) ?></span><?= $w < count($words) - 1 ? ' ' : '' ?><?php endforeach; ?></<?= $headingTag ?>>
        <p class="slide__sub"><?= e($slide['subtitle']) ?></p>
        <?php if ($ctaIsWhatsapp): ?>
        <a class="btn btn--metal slide__cta" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'hero', 'page' => 'home']) ?>><?= e($cta['label']) ?></a>
        <?php else: ?>
        <a class="btn btn--metal slide__cta" href="<?= e($cta['href']) ?>"><?= e($cta['label']) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($count > 1): ?>
  <button class="stage__arrow stage__arrow--prev glass-capsule" type="button" aria-label="Destacado anterior" data-prev data-hover-hold><?= icon('chevron-left', 'icon') ?></button>
  <button class="stage__arrow stage__arrow--next glass-capsule" type="button" aria-label="Destacado siguiente" data-next data-hover-hold><?= icon('chevron-right', 'icon') ?></button>
  <p class="visually-hidden" aria-live="polite" aria-atomic="true" data-live></p>
  <?php endif; ?>
</section>
