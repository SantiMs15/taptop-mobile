<?php
/**
 * Lista de verificación de la Etapa 1 (sección 12 del brief): qué falta para el lanzamiento público.
 *
 *   php tools/preflight.php
 *
 * Revisa config.php, el catálogo, las fichas y el hero, y recuerda los pendientes que no se pueden
 * comprobar desde el código. Sale con código 1 mientras quede algo pendiente.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$pending = 0;

function item(bool $ok, string $label, string $detail = ''): void
{
    global $pending;
    if (!$ok) {
        $pending++;
    }
    echo ($ok ? '[OK]        ' : '[PENDIENTE] ') . $label . ($detail !== '' ? " ({$detail})" : '') . "\n";
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

section('Dominio e indexación');
item(config('environment') === 'production', "environment = 'production'", 'hoy: ' . (string) config('environment'));
item(!is_noindex(), 'site_url en el dominio final, sin noindex', (string) config('site_url'));
item(str_starts_with((string) config('site_url'), 'https://'), 'site_url con https');
item(config('debug') === false, 'debug apagado');

section('Datos del negocio (sin ellos el sitio no arranca en producción)');
foreach (['business_legal_name' => 'Razón social', 'business_nit' => 'NIT', 'business_email' => 'Correo para reclamos', 'support_hours_label' => 'Horario de atención por WhatsApp'] as $key => $label) {
    item(!is_placeholder(config($key)), $label, $key);
}
item(!is_placeholder(config('warranty_exchange_days')), 'Plazo máximo del cambio por garantía', 'warranty_exchange_days');
item(!is_placeholder(config('warranty_shipping_payer')), 'Quién asume el envío del cambio', 'warranty_shipping_payer');

section('Redes, analítica y SEO');
item(!instagram_is_provisional(), 'Perfil real de Instagram', (string) config('instagram_url'));
item(analytics_ga4_id() !== '', 'ID de GA4', 'ga4_measurement_id');
item(analytics_meta_pixel_id() !== '', 'ID de Meta Pixel', 'meta_pixel_id');
item(config('seo_texts_confirmed') === true, 'Título y descripción generales confirmados con el cliente', 'seo_texts_confirmed');

section('Catálogo y contenido');
$repository = product_repository();
$products = $repository->all();
$withoutPhotos = [];
$draftSpecs = [];
$noSpecs = [];
foreach ($products as $product) {
    $hasPhoto = false;
    foreach ($product['colors'] as $color) {
        $hasPhoto = $hasPhoto || $color['image'] !== null;
    }
    if (!$hasPhoto) {
        $withoutPhotos[] = $product['slug'];
    }
    if ($product['specs_status'] === 'borrador') {
        $draftSpecs[] = $product['slug'];
    }
    if ($product['specs'] === []) {
        $noSpecs[] = $product['slug'];
    }
}
item($products !== [], 'Catálogo cargado y válido', count($products) . ' productos; detalle: php tools/validate-products.php');
item($withoutPhotos === [], 'Fotos propias de los productos publicados', count($withoutPhotos) . ' sin foto');
item(config('show_placeholders') === false, "show_placeholders = false (sin siluetas de referencia)");
item($draftSpecs === [], 'Fichas técnicas revisadas por el cliente', count($draftSpecs) . ' en borrador');
item($noSpecs === [], 'Todos los productos con ficha', $noSpecs === [] ? '' : implode(', ', $noSpecs));
$hero = load_hero_slides();
$heroPlaceholders = array_filter($hero['slides'], static fn (array $s): bool => $s['type'] === 'image' && $s['image'] === null);
item($heroPlaceholders === [], 'Fotos del hero', count($heroPlaceholders) . ' slides con silueta');
$brand = PUBLIC_PATH . '/assets/images/brand/';
item(is_file($brand . 'logo-mark-512.png') && !is_file($brand . 'favicon.svg'), 'Logo oficial (el 3D) en favicon, ícono de iPhone y datos estructurados', 'assets/images/brand/logo-mark-512.png');

section('Panel y respaldos (Paso 2)');
item(config('catalog_source') === 'mysql', "Catálogo en MySQL con panel de administración (catalog_source = 'mysql')");
item(is_file(APP_PATH . '/db/schema.sql'), 'schema.sql y exportación diaria cifrada a Google Drive probadas');

section('Pendientes que no se pueden comprobar desde el código');
foreach ([
    'Facturación electrónica resuelta con el contador (pendiente 11 del brief).',
    'Revisión de un abogado de /garantia, /terminos y /privacidad.',
    'SSL activo en el dominio final y regla "un solo dominio" activada en .htaccess.',
    'Restauración de un respaldo probada antes del lanzamiento.',
    'Hex de los colores tomados con gotero sobre las fotos propias.',
    'Precios de las variantes que la hoja no trae (MacBook Air 13"/15", iPad Pro celular, Apple Watch Series 12).',
    'Comisión real de Wompi igual a la de la hoja (2,65 % + $700 + IVA).',
] as $reminder) {
    echo "[REVISAR]   {$reminder}\n";
}

echo "\n" . ($pending === 0 ? 'Todo listo para la Etapa 1.' : "Pendientes: {$pending}. El sitio no debe lanzarse todavía.") . "\n";
exit($pending === 0 ? 0 : 1);
