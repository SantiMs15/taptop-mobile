<?php
/**
 * Animación del logo (5.0 del brief).
 *  - 'intro': escaneo estilo Face ID, una vez por sesión. Decorativo: no usa la cámara ni reconoce
 *    a nadie. Se cierra solo en unos segundos, con un clic o con Escape. Basado en Unlock Face ID
 *    de Smooth UI (Eduardo Calvo, MIT), en CSS sin motion.
 *    Solo existe en el Inicio y solo se ve si head.php marcó <html class="intro-on">.
 *  - 'hero': logo 3D en tiempo real con <model-viewer>. Mientras carga, o si el equipo no
 *    lo soporta (movimiento reducido, ahorro de datos, red lenta, gama baja, sin WebGL),
 *    queda la imagen fija: nunca una pantalla vacía. logo-animation.js decide.
 * @var string $mode
 */
if ($mode === 'intro'): ?>
<div class="intro" data-intro aria-hidden="true">
  <svg class="faceid" viewBox="0 0 100 100" focusable="false">
    <defs>
      <clipPath id="faceid-clip"><rect x="8" y="8" width="84" height="84" rx="22"/></clipPath>
      <linearGradient id="faceid-band" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" stop-color="#00B3F6" stop-opacity="0"/>
        <stop offset="100%" stop-color="#00B3F6" stop-opacity="0.45"/>
      </linearGradient>
    </defs>
    <g class="faceid__glyph">
      <path class="faceid__bracket" style="--i: 0" pathLength="1" d="M8 34 L8 28 A20 20 0 0 1 28 8 L34 8"/>
      <path class="faceid__bracket" style="--i: 1" pathLength="1" d="M66 8 L72 8 A20 20 0 0 1 92 28 L92 34"/>
      <path class="faceid__bracket" style="--i: 2" pathLength="1" d="M92 66 L92 72 A20 20 0 0 1 72 92 L66 92"/>
      <path class="faceid__bracket" style="--i: 3" pathLength="1" d="M34 92 L28 92 A20 20 0 0 1 8 72 L8 66"/>
      <path class="faceid__face" style="--i: 0" pathLength="1" d="M36 38 L36 47"/>
      <path class="faceid__face" style="--i: 1" pathLength="1" d="M64 38 L64 47"/>
      <path class="faceid__face" style="--i: 2" pathLength="1" d="M50 38 L50 54 Q50 57.5 53.5 57.5 L56 57.5"/>
      <path class="faceid__face" style="--i: 3" pathLength="1" d="M36 61 L36 66 Q50 73 64 66 L64 61"/>
      <g clip-path="url(#faceid-clip)">
        <g class="faceid__scan">
          <rect x="8" y="41" width="84" height="18" fill="url(#faceid-band)"/>
          <line x1="10" x2="90" y1="59" y2="59"/>
        </g>
      </g>
    </g>
    <path class="faceid__check" pathLength="1" d="M30 52 L44 66 L72 34"/>
  </svg>
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
