<?php
/**
 * Valida el catálogo antes de subirlo (sección 10.4 del brief).
 *
 *   php tools/validate-products.php
 *   php tools/validate-products.php ruta/a/products.json [ruta/a/specs.json]
 *
 * Errores: ese producto no se mostraría en el sitio. Advertencias: se muestra, pero hay que
 * revisarlo (por ejemplo price_gateway menor que price_cash). Sale con código 1 si hay errores.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Taptop\Catalog\ProductValidator;

$catalogFile = $argv[1] ?? APP_PATH . '/data/products.json';
$specsFile = $argv[2] ?? APP_PATH . '/data/specs.json';

echo "Catálogo: {$catalogFile}\n";
$catalog = load_json_file($catalogFile);
if ($catalog === null) {
    echo "[ERROR] No se pudo leer el archivo o el JSON está roto (revisa comas y comillas).\n";
    exit(1);
}

$validator = new ProductValidator((array) config('categories', []), (array) config('brands', []));
$report = $validator->validateCatalog($catalog);

$errors = $report['errors'];
$warnings = $report['warnings'];
$valid = [];
foreach ($report['products'] as $entry) {
    array_push($errors, ...$entry['errors']);
    array_push($warnings, ...$entry['warnings']);
    if ($entry['product'] !== null) {
        $valid[$entry['product']['slug']] = $entry['product'];
    }
}

if (is_file($specsFile)) {
    $specs = load_json_file($specsFile);
    if ($specs === null) {
        $errors[] = 'specs.json no se pudo leer o está roto.';
    } else {
        $colorNames = array_map(static fn (array $p): array => array_column($p['colors'], 'name'), $valid);
        $specReport = $validator->validateSpecs($specs, array_keys($valid), $colorNames);
        array_push($errors, ...$specReport['errors']);
        array_push($warnings, ...$specReport['warnings']);
        $pending = [];
        $draft = [];
        foreach ($specReport['specs'] as $slug => $entry) {
            if ($entry['status'] === 'pendiente') {
                $pending[] = $slug;
            } elseif ($entry['status'] === 'borrador') {
                $draft[] = $slug;
            }
        }
        $missing = array_diff(array_keys($valid), array_keys($specReport['specs']));
        if ($draft !== []) {
            echo sprintf("[AVISO] Fichas en borrador (el cliente debe revisarlas): %d\n", count($draft));
        }
        if ($pending !== [] || $missing !== []) {
            echo '[AVISO] Sin ficha publicada: ' . implode(', ', array_merge($pending, $missing)) . "\n";
        }
    }
}

$variants = 0;
$colors = 0;
$minCash = PHP_INT_MAX;
$maxCash = 0;
foreach ($valid as $product) {
    $variants += count($product['variants']);
    $colors += count($product['colors']);
    foreach ($product['variants'] as $variant) {
        $minCash = min($minCash, $variant['price_cash']);
        $maxCash = max($maxCash, $variant['price_cash']);
    }
}

foreach ($warnings as $warning) {
    echo "[AVISO] {$warning}\n";
}
foreach ($errors as $error) {
    echo "[ERROR] {$error}\n";
}

echo "\n";
echo sprintf("Productos válidos: %d de %d\n", count($valid), count($report['products']));
echo sprintf("Variantes: %d. Colores: %d.\n", $variants, $colors);
if ($valid !== []) {
    echo sprintf("Precios por consignación o transferencia: de %s a %s\n", format_price($minCash), format_price($maxCash));
}
echo sprintf("Advertencias: %d. Errores: %d.\n", count($warnings), count($errors));

exit($errors === [] ? 0 : 1);
