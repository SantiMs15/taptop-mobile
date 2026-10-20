<?php
/**
 * Router para el servidor embebido de PHP (solo desarrollo local). Imita el .htaccess:
 * sirve los archivos reales de public_html y manda todo lo demás a index.php.
 *
 *   php -S localhost:8080 -t public_html tools/dev-router.php
 */

declare(strict_types=1);

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

if (preg_match('#(^|/)\.(?!well-known/)#', $path) === 1 || preg_match('#\.(json|sql|log|ini|env|md|lock|bak)$#i', $path) === 1) {
    http_response_code(403);
    echo 'Prohibido';
    return true;
}

$file = realpath(__DIR__ . '/../public_html' . $path);
$public = realpath(__DIR__ . '/../public_html');
if ($file !== false && is_file($file) && str_starts_with($file, (string) $public) && !str_ends_with($file, '.php')) {
    $types = ['glb' => 'model/gltf-binary', 'woff2' => 'font/woff2', 'webp' => 'image/webp', 'mp4' => 'video/mp4', 'svg' => 'image/svg+xml'];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (isset($types[$extension])) {
        header('Content-Type: ' . $types[$extension]);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
    return false; // el servidor embebido lo sirve con su tipo MIME
}

require __DIR__ . '/../public_html/index.php';
return true;
