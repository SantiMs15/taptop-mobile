<?php
/**
 * <head>: SEO básico, Open Graph, íconos, fuente autoalojada y CSS.
 * El script en línea (con nonce) decide antes de pintar si se muestra el intro:
 * solo en el Inicio, solo si es la primera página de la sesión y sin "reducir movimiento".
 * @var array $meta
 */
$canonical = absolute_url((string) $meta['path']);
$noindex = is_noindex() || !empty($meta['noindex']);
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($meta['title']) ?></title>
<meta name="description" content="<?= e($meta['description']) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:site_name" content="<?= e(config('business_name')) ?>">
<meta property="og:locale" content="es_CO">
<meta property="og:type" content="<?= e($meta['og_type']) ?>">
<meta property="og:title" content="<?= e($meta['title']) ?>">
<meta property="og:description" content="<?= e($meta['description']) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($meta['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#FFFFFF">
<link rel="icon" href="<?= e(asset('images/brand/favicon-32.png')) ?>" sizes="32x32" type="image/png">
<link rel="icon" href="<?= e(asset('images/brand/favicon-192.png')) ?>" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="<?= e(asset('images/brand/apple-touch-icon.png')) ?>">
<link rel="preload" href="/assets/fonts/inter-latin-opsz-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
<script nonce="<?= e(csp_nonce()) ?>">
(function (d) {
  var root = d.documentElement;
  root.className = root.className.replace('no-js', 'js');
  try {
    var store = window.sessionStorage;
    var firstPage = !store.getItem('tt_visit');
    store.setItem('tt_visit', '1');
    var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches
      || (navigator.connection && navigator.connection.saveData);
    if (firstPage && !calm && root.getAttribute('data-page') === 'home') {
      root.classList.add('intro-on');
      // Barra del navegador del mismo gris del intro; logo-animation.js la devuelve a blanco.
      var theme = d.querySelector('meta[name="theme-color"]');
      if (theme) {
        theme.setAttribute('content', '#F6F6F7');
      }
    }
  } catch (error) { /* sin sessionStorage no hay intro */ }
})(document);
</script>
<?php if ($meta['id'] === 'home'): ?>
<script type="application/ld+json"><?= json_for_script(organization_jsonld()) ?></script>
<?php endif; ?>
