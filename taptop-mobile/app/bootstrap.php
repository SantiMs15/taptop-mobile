<?php
/**
 * Arranque de la aplicación: rutas base, autoload, zona horaria y manejo de errores.
 * Sin ini_set (bloqueado en Hostinger): display_errors y la sesión se configuran en hPanel.
 */

declare(strict_types=1);

define('APP_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', is_dir(ROOT_PATH . '/public_html') ? ROOT_PATH . '/public_html' : ROOT_PATH);

require APP_PATH . '/helpers.php';
require APP_PATH . '/helpers/analytics.php';
require APP_PATH . '/helpers/icons.php';
require APP_PATH . '/helpers/placeholders.php';
require APP_PATH . '/router.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Taptop\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set((string) config('timezone', 'America/Bogota'));
error_reporting(E_ALL);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (config('debug', false)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    app_log('warning', $message, ['file' => $file, 'line' => $line]);
    return true;
});

set_exception_handler(static function (Throwable $e): void {
    app_log('error', $e->getMessage(), [
        'type' => $e::class,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    render_fatal_error($e);
});

assert_deployable();
