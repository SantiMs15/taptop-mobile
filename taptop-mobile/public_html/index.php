<?php
/**
 * Front controller de Taptop Mobile. Única entrada pública de PHP.
 * app/ vive un nivel arriba de public_html (fuera del alcance de una URL). Si el hosting
 * obligara a dejarla dentro, se busca en ./app y su .htaccess niega el acceso.
 */

declare(strict_types=1);

$bootstrap = dirname(__DIR__) . '/app/bootstrap.php';
if (!is_file($bootstrap)) {
    $bootstrap = __DIR__ . '/app/bootstrap.php';
}
require $bootstrap;

dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), (string) ($_SERVER['REQUEST_URI'] ?? '/'));
