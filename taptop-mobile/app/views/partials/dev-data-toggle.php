<?php
/**
 * Interruptor de datos de prueba (break-ui). Solo se incluye si dev_data_set() no es null,
 * es decir en `php -S` local; nunca en producción. Estilos en línea: no es parte del diseño.
 * @var string $current
 */
$path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
?>
<nav aria-label="Datos de prueba" style="position:fixed;left:50%;bottom:12px;z-index:9999;transform:translateX(-50%);display:flex;gap:2px;padding:3px;border-radius:999px;background:#e5e5e5;box-shadow:0 2px 8px rgb(0 0 0 / .15);font:500 12px/1 system-ui,sans-serif;">
  <?php foreach (DEV_DATA_SETS as $key => $label): ?>
  <a href="<?= e($path . '?data=' . $key) ?>" style="padding:7px 12px;border-radius:999px;color:#222;text-decoration:none;white-space:nowrap;<?= $key === $current ? 'background:#fff;box-shadow:0 1px 2px rgb(0 0 0 / .2);' : '' ?>"<?= $key === $current ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
