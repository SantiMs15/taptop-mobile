<?php
/**
 * Animación del logo (5.0 del brief).
 *  - 'intro': video corto renderizado del modelo, una vez por sesión (sin botón: se cierra solo
 *    en unos segundos, o con Escape).
 *    Solo existe en el Inicio y solo se ve si head.php marcó <html class="intro-on">.
 *  - 'hero': logo 3D en tiempo real con <model-viewer>. Mientras carga, o si el equipo no
 *    lo soporta (movimiento reducido, ahorro de datos, red lenta, gama baja, sin WebGL),
 *    queda la imagen fija: nunca una pantalla vacía. logo-animation.js decide.
 * @var string $mode
 */
if ($mode === 'intro'): ?>
<div class="intro" data-intro>
  <video class="intro__video" muted playsinline preload="none" aria-hidden="true"
         poster="<?= e(asset('images/brand/intro-poster.webp')) ?>"
         data-src="<?= e(asset('video/taptop-intro.mp4')) ?>"></video>
</div>
<?php else: ?>
<div class="logo3d" data-logo3d
     data-model="<?= e(asset('models/taptop-logo.glb')) ?>"
     data-viewer="<?= e(asset('vendor/model-viewer/model-viewer.min.js')) ?>"
     data-video="<?= e(asset('video/taptop-intro.mp4')) ?>">
  <img class="logo3d__poster"
       src="<?= e(asset('images/brand/logo-3d-640.webp')) ?>"
       srcset="<?= e(asset('images/brand/logo-3d-640.webp')) ?> 640w, <?= e(asset('images/brand/logo-3d-1200.webp')) ?> 1200w"
       sizes="(max-width: 700px) 72vw, 560px"
       width="1200" height="1200"
       alt="Logo de Taptop Mobile en 3D"
       fetchpriority="high" decoding="async">
</div>
<?php endif; ?>
