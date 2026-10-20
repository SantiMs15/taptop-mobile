<?php
/**
 * Esqueleto común. Recibe $meta (ver page_meta) y $content (HTML de la página ya renderizado).
 * @var array $meta
 * @var string $content
 */
$pageId = (string) $meta['id'];
$isHome = $pageId === 'home';
?><!doctype html>
<html lang="es-CO" class="no-js" data-page="<?= e($pageId) ?>">
<head>
<?php partial('head', ['meta' => $meta]); ?>
</head>
<body class="page page--<?= e($pageId) ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?php if ($isHome) { partial('logo-animation', ['mode' => 'intro']); } ?>
<?php partial('header', ['overlay' => $isHome, 'page' => $pageId]); ?>
<main id="contenido" tabindex="-1">
<?= $content ?>
</main>
<?php partial('footer', ['page' => $pageId]); ?>
<?php partial('whatsapp-float', ['page' => $pageId]); ?>
<?php if (analytics_enabled()) { partial('consent-banner'); } ?>
<script type="application/json" id="tt-config"><?= json_for_script(analytics_client_config()) ?></script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<script src="<?= e(asset('js/analytics.js')) ?>" defer></script>
<?php foreach ((array) ($meta['scripts'] ?? []) as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
