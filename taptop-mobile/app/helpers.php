<?php
/**
 * Funciones globales: configuración, escape, precios, WhatsApp, URLs, vistas y cabeceras.
 */

declare(strict_types=1);

use Taptop\Catalog\ProductPresenter;
use Taptop\Catalog\ProductValidator;
use Taptop\Repositories\JsonProductRepository;
use Taptop\Repositories\ProductRepository;

// ---------------------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------------------

/**
 * Lee config.php con notación de puntos: config('db.host').
 * Si existe app/config.local.php (no va a Git), sus valores reemplazan a los de config.php:
 * ahí van las credenciales del Paso 2 (base de datos, cuenta de servicio de Google Drive).
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require APP_PATH . '/config.php';
        $local = APP_PATH . '/config.local.php';
        if (is_file($local)) {
            $config = array_replace_recursive($config, (array) require $local);
        }
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/** Vacío o marcado "[por completar]". */
function is_placeholder(mixed $value): bool
{
    if ($value === null) {
        return true;
    }
    $text = trim((string) $value);
    return $text === '' || str_contains($text, '[por completar]');
}

/** Valor de configuración para mostrar en pantalla; "[por completar]" si falta. */
function config_text(string $key): string
{
    $value = config($key);
    return is_placeholder($value) ? '[por completar]' : (string) $value;
}

function instagram_is_provisional(): bool
{
    return rtrim((string) config('instagram_url'), '/') === rtrim((string) config('instagram_placeholder_url'), '/');
}

// ---------------------------------------------------------------------------
// Escape y formato
// ---------------------------------------------------------------------------

/** Escapa cualquier valor para HTML (texto y atributos). Toda salida pasa por aquí. */
function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Precio en COP: 4650000 → "$4.650.000". */
function format_price(int|float $amount): string
{
    return '$' . number_format((int) round($amount), 0, ',', '.');
}

/** JSON seguro para incrustar en <script type="application/json">. */
function json_for_script(mixed $data): string
{
    return json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
}

/** "2026-10-15" → "15 de octubre de 2026". */
function format_date_es(string $isoDate): string
{
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $time = strtotime($isoDate);
    if ($time === false) {
        return $isoDate;
    }
    return sprintf('%d de %s de %s', (int) date('j', $time), $months[(int) date('n', $time) - 1], date('Y', $time));
}

/**
 * Escapa y resalta "[por completar]" para que en la Etapa 0 se vea qué dato falta.
 * Devuelve HTML seguro: el texto se escapa antes de insertar la marca.
 */
function e_todo(mixed $value): string
{
    return str_replace('[por completar]', '<mark class="todo">[por completar]</mark>', e($value));
}

/** Identificador único por petición para ids y names de formularios repetidos. */
function ui_id(string $prefix): string
{
    static $counter = 0;
    $counter++;
    return $prefix . '-' . $counter;
}

/** Divide un titular en palabras para el revelado escalonado. */
function title_words(string $title): array
{
    $words = preg_split('/\s+/u', trim($title)) ?: [];
    return array_values(array_filter($words, static fn (string $w): bool => $w !== ''));
}

// ---------------------------------------------------------------------------
// WhatsApp (sección 8 del brief). Los precios siempre salen del catálogo del servidor.
// ---------------------------------------------------------------------------

function whatsapp_link(string $message = ''): string
{
    $number = preg_replace('/\D+/', '', (string) config('whatsapp_number', '')) ?? '';
    $url = 'https://wa.me/' . $number;
    $message = trim($message);
    if ($message !== '') {
        if (mb_strlen($message) > 500) {
            $message = rtrim(mb_substr($message, 0, 497)) . '...';
        }
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/** "del" / "de los" según el artículo del producto ("el" por defecto). */
function product_de_article(array $product): string
{
    $article = $product['article'] ?? 'el';
    return $article === 'el' ? 'del' : 'de ' . $article;
}

/** "iPhone 18 Pro Borgoña 256 GB" (+ ", SIM física" si la variante tiene nota). */
function product_variant_label(array $product, ?string $color, ?array $variant): string
{
    $parts = [$product['name']];
    if ($color !== null && $color !== '') {
        $parts[] = $color;
    }
    if ($variant !== null && !empty($variant['storage'])) {
        $parts[] = $variant['storage'];
    }
    $label = implode(' ', $parts);
    if ($variant !== null && !empty($variant['note'])) {
        $label .= ', ' . $variant['note'];
    }
    return $label;
}

function wa_message_general(): string
{
    return sprintf('Hola %s, quiero información sobre sus productos.', config('business_name'));
}

/** "Azul Glaciar" -> "azul-glaciar" (para ?color= en los enlaces de producto). */
function color_slug(string $name): string
{
    $name = strtr(mb_strtolower($name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $name), '-');
}

/**
 * Enlace absoluto al producto (con el color elegido). Va al final del mensaje de WhatsApp:
 * WhatsApp arma la vista previa con el og:image de esa página, así llega la foto del equipo.
 */
function product_share_url(array $product, ?string $color): string
{
    return absolute_url(url('/productos/' . $product['slug'], ['color' => $color !== null ? color_slug($color) : null]));
}

function wa_message_confirm(array $product, ?string $color, array $variant): string
{
    return sprintf(
        "Hola %s, quiero confirmar disponibilidad %s %s. Vi el precio de %s por consignación o transferencia, y %s con tarjeta o pasarela.
%s",
        config('business_name'),
        product_de_article($product),
        product_variant_label($product, $color, $variant),
        format_price($variant['price_cash']),
        format_price($variant['price_gateway']),
        product_share_url($product, $color)
    );
}

function wa_message_sold_out(array $product, ?string $color, ?array $variant): string
{
    return sprintf(
        "Hola %s, quiero saber cuándo habrá disponibilidad %s %s.
%s",
        config('business_name'),
        product_de_article($product),
        product_variant_label($product, $color, $variant),
        product_share_url($product, $color)
    );
}

/** Pedido del carrito para pagar por transferencia; $priced = ['lines' => [...], 'cash' => int]. */
function wa_message_cart(array $priced): string
{
    $rows = [];
    foreach ($priced['lines'] as $line) {
        if ($line['available']) {
            $rows[] = sprintf('• %d × %s — %s', $line['qty'], $line['label'], format_price($line['line_cash']));
        }
    }
    return sprintf(
        "Hola %s, quiero pagar por transferencia este pedido:\n%s\nTotal por transferencia: %s",
        config('business_name'),
        implode("\n", $rows),
        format_price($priced['cash'])
    );
}

function wa_message_question(array $product): string
{
    return sprintf('Hola %s, tengo una duda sobre %s %s.', config('business_name'), $product['article'] ?? 'el', $product['name']);
}

// ---------------------------------------------------------------------------
// Envíos
// ---------------------------------------------------------------------------

/**
 * El umbral se compara con el precio que realmente se paga en cada método (6.1 del brief).
 * @return array{free: bool, partial: bool, label: string}
 */
function shipping_info(int $cash, int $gateway): array
{
    $min = (int) config('free_shipping_min', 150000);
    $freeCash = $cash >= $min;
    $freeGateway = $gateway >= $min;
    if ($freeCash && $freeGateway) {
        return ['free' => true, 'partial' => false, 'label' => 'Envío gratis'];
    }
    if ($freeGateway) {
        return ['free' => false, 'partial' => true, 'label' => 'Envío gratis pagando con tarjeta o pasarela'];
    }
    if ($freeCash) {
        return ['free' => false, 'partial' => true, 'label' => 'Envío gratis pagando por consignación o transferencia'];
    }
    return ['free' => false, 'partial' => false, 'label' => 'Costo de envío por confirmar'];
}

// ---------------------------------------------------------------------------
// URLs y assets
// ---------------------------------------------------------------------------

/**
 * Ruta pública de un asset con versión por fecha de modificación (caché larga segura).
 * Si tools/build-assets.php generó un .min más nuevo que el original, se sirve ese.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_PATH . '/assets/' . $path;
    if (preg_match('/^(.+)\.(css|js)$/', $path, $match) === 1 && !str_ends_with($match[1], '.min')) {
        $minPath = $match[1] . '.min.' . $match[2];
        $minFile = PUBLIC_PATH . '/assets/' . $minPath;
        if (is_file($minFile) && is_file($file) && filemtime($minFile) >= filemtime($file)) {
            $path = $minPath;
            $file = $minFile;
        }
    }
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return '/assets/' . $path . '?v=' . $version;
}

/** URL relativa a la raíz con query opcional (los valores vacíos se omiten). */
function url(string $path = '/', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    $query = array_filter($query, static fn ($v): bool => $v !== null && $v !== '');
    return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    return rtrim((string) config('site_url', ''), '/') . '/' . ltrim($path, '/');
}

function current_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : '/';
}

function is_https_request(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

/** noindex mientras site_url sea el dominio temporal o el entorno no sea producción. */
function is_noindex(): bool
{
    if (config('force_noindex', false) || config('environment') !== 'production') {
        return true;
    }
    $host = strtolower((string) parse_url((string) config('site_url', ''), PHP_URL_HOST));
    foreach ((array) config('temporary_hosts', []) as $temporary) {
        $temporary = strtolower((string) $temporary);
        if ($host === $temporary || str_ends_with($host, '.' . $temporary)) {
            return true;
        }
    }
    return false;
}

/** JSON-LD de la organización: tienda en línea sin local, con cobertura nacional (7 y 11 del brief). */
function organization_jsonld(): array
{
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => (string) config('business_name'),
        'url' => absolute_url('/'),
        'logo' => absolute_url(asset('images/brand/logo-mark-512.png')),
        'areaServed' => ['@type' => 'Country', 'name' => 'Colombia'],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => '+' . preg_replace('/\D+/', '', (string) config('whatsapp_number')),
            'contactType' => 'customer service',
            'areaServed' => 'CO',
            'availableLanguage' => 'es',
        ],
    ];
    if (!is_placeholder(config('business_legal_name'))) {
        $data['legalName'] = (string) config('business_legal_name');
    }
    if (!instagram_is_provisional()) {
        $data['sameAs'] = [(string) config('instagram_url')];
    }
    return $data;
}

// ---------------------------------------------------------------------------
// Catálogo
// ---------------------------------------------------------------------------

function product_repository(): ProductRepository
{
    static $repository = null;
    if ($repository === null) {
        $validator = new ProductValidator((array) config('categories', []), (array) config('brands', []));
        $repository = match (config('catalog_source', 'json')) {
            'json' => new JsonProductRepository(
                APP_PATH . '/data/products.json',
                APP_PATH . '/data/specs.json',
                $validator
            ),
            default => throw new RuntimeException('Fuente de catálogo no soportada: ' . (string) config('catalog_source')),
        };
    }
    return $repository;
}

function product_presenter(): ProductPresenter
{
    static $presenter = null;
    return $presenter ??= new ProductPresenter(
        (string) config('sold_out_display', 'mark'),
        (bool) config('show_placeholders', true)
    );
}

function category_label(string $key): string
{
    return (string) (config('categories.' . $key . '.label') ?? $key);
}

function brand_label(string $key): string
{
    return (string) (config('brands.' . $key) ?? $key);
}

/** Lee un JSON de app/data. Devuelve null (y registra) si falta o está roto. */
function load_json_file(string $file): ?array
{
    if (!is_file($file)) {
        app_log('error', 'Archivo de datos no encontrado', ['file' => basename($file)]);
        return null;
    }
    try {
        $data = json_decode((string) file_get_contents($file), true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        app_log('error', 'JSON inválido', ['file' => basename($file), 'error' => $e->getMessage()]);
        return null;
    }
    return is_array($data) ? $data : null;
}

// ---------------------------------------------------------------------------
// Vistas
// ---------------------------------------------------------------------------

/** Renderiza una plantilla de app/views con variables aisladas y devuelve el HTML. */
function view(string $template, array $data = []): string
{
    $file = APP_PATH . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Vista no encontrada: ' . $template);
    }
    return (static function (string $__file, array $__data): string {
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    })($file, $data);
}

function partial(string $name, array $data = []): void
{
    echo view('partials/' . $name, $data);
}

/**
 * Página completa: la vista se renderiza dentro de layout.php.
 * $data['meta'] = ['id', 'title', 'description', 'path', 'og_image', 'og_type', 'scripts'].
 */
function render_page(string $page, array $data, int $status = 200): void
{
    $content = view('pages/' . $page, $data);
    $html = view('layout', $data + ['content' => $content]);
    http_response_code($status);
    send_page_headers();
    echo $html;
}

/** Página de error 500 mínima, sin depender de las plantillas (que pueden ser la causa). */
function render_fatal_error(Throwable $e): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $detail = config('debug', false)
        ? '<pre style="white-space:pre-wrap">' . e($e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>'
        : '';
    echo '<!doctype html><html lang="es-CO"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error | Taptop Mobile</title></head><body style="font-family:system-ui,sans-serif;color:#0D2655;padding:48px 24px;max-width:640px;margin:auto">'
        . '<h1 style="letter-spacing:-0.03em">No pudimos cargar esta página</h1>'
        . '<p>Intenta de nuevo en unos minutos. Si necesitas ayuda ahora, escríbenos por WhatsApp.</p>'
        . '<p><a href="' . e(whatsapp_link(wa_message_general())) . '" style="color:#0D2655">Escribir por WhatsApp</a></p>'
        . $detail . '</body></html>';
}

// ---------------------------------------------------------------------------
// Cabeceras HTTP
// ---------------------------------------------------------------------------

function csp_nonce(): string
{
    static $nonce = null;
    return $nonce ??= base64_encode(random_bytes(16));
}

function content_security_policy(): string
{
    // 'wasm-unsafe-eval' solo permite compilar WebAssembly (lo usa model-viewer); no habilita eval() de JS.
    $script = ["'self'", "'nonce-" . csp_nonce() . "'", "'wasm-unsafe-eval'"];
    $connect = ["'self'"];
    $image = ["'self'", 'data:', 'blob:'];
    if (analytics_ga4_id() !== '') {
        $script[] = 'https://www.googletagmanager.com';
        array_push($connect, 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com');
        array_push($image, 'https://*.google-analytics.com', 'https://*.googletagmanager.com');
    }
    if (analytics_meta_pixel_id() !== '') {
        $script[] = 'https://connect.facebook.net';
        $connect[] = 'https://www.facebook.com';
        $image[] = 'https://www.facebook.com';
    }
    $directives = [
        "default-src 'self'",
        'script-src ' . implode(' ', $script),
        "style-src 'self' 'unsafe-inline'",
        'img-src ' . implode(' ', $image),
        "font-src 'self'",
        'connect-src ' . implode(' ', $connect),
        "media-src 'self' blob:",
        "worker-src 'self' blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ];
    if (is_https_request()) {
        $directives[] = 'upgrade-insecure-requests';
    }
    return implode('; ', $directives);
}

function send_page_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Security-Policy: ' . content_security_policy());
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    if (is_https_request()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    if (is_noindex()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

function redirect(string $location, int $status = 302): never
{
    header('Location: ' . $location, true, $status);
    exit;
}

// ---------------------------------------------------------------------------
// Despliegue y registro
// ---------------------------------------------------------------------------

/** Datos sin los que el sitio no puede salir a producción (4 y 7 del brief). */
function deploy_blockers(): array
{
    $required = [
        'business_legal_name' => 'razón social',
        'business_nit' => 'NIT',
        'business_email' => 'correo para reclamos',
        'support_hours_label' => 'horario de atención',
    ];
    $missing = [];
    foreach ($required as $key => $label) {
        if (is_placeholder(config($key))) {
            $missing[] = sprintf('%s (%s)', $label, $key);
        }
    }
    return $missing;
}

/** En producción, el sitio no arranca con datos legales u horario sin completar. */
function assert_deployable(): void
{
    if (PHP_SAPI === 'cli' || config('environment') !== 'production') {
        return;
    }
    $missing = deploy_blockers();
    if ($missing === []) {
        return;
    }
    app_log('error', 'Despliegue bloqueado: configuración incompleta', ['faltan' => $missing]);
    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="es-CO"><head><meta charset="utf-8"><meta name="robots" content="noindex">'
        . '<title>Sitio en configuración</title></head><body style="font-family:system-ui,sans-serif;padding:48px 24px">'
        . '<h1>Sitio en configuración</h1><p>Faltan datos obligatorios en config.php. Ejecuta tools/preflight.php para ver la lista.</p>'
        . '</body></html>';
    exit;
}

/** Log propio en app/logs (fuera de la carpeta pública). Nunca registra datos personales. */
function app_log(string $level, string $message, array $context = []): void
{
    $line = sprintf(
        '[%s] %s: %s%s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $directory = APP_PATH . '/logs';
    if (is_dir($directory) && is_writable($directory)) {
        error_log($line . PHP_EOL, 3, $directory . '/app-' . date('Y-m') . '.log');
        return;
    }
    error_log($line);
}
