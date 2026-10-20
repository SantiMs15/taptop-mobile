<?php
/**
 * Pruebas rápidas de la lógica del sitio (sin base de datos ni navegador).
 *
 *   php tools/selftest.php
 *
 * Cubre: validador (costos prohibidos, SKU duplicado, colores, pasarela < efectivo), presentador
 * (agotado en modos mark/hide/show, precio por color, "Desde"), envío gratis, mensajes de
 * WhatsApp, escape y rutas. Sale con código 1 si algo falla.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Taptop\Catalog\ProductPresenter;
use Taptop\Catalog\ProductValidator;

$passed = 0;
$failed = 0;

function expect(bool $condition, string $name, mixed $detail = null): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS {$name}\n";
        return;
    }
    $failed++;
    echo "FAIL {$name}" . ($detail !== null ? ' -> ' . var_export($detail, true) : '') . "\n";
}

$validator = new ProductValidator((array) config('categories'), (array) config('brands'));

function product(array $overrides = []): array
{
    return $overrides + [
        'slug' => 'telefono-prueba',
        'brand' => 'apple',
        'category' => 'iphone',
        'name' => 'Teléfono Prueba',
        'colors' => [
            ['name' => 'Azul', 'hex' => '#123456', 'image' => null],
            ['name' => 'Rojo', 'hex' => '#AA2233', 'image' => null, 'available' => false],
        ],
        'variants' => [
            ['sku' => 'TP-128', 'storage' => '128 GB', 'color' => null, 'price_cash' => 1000000, 'price_gateway' => 1033000],
            ['sku' => 'TP-256', 'storage' => '256 GB', 'color' => null, 'price_cash' => 1200000, 'price_gateway' => 1240000, 'available' => false],
        ],
    ];
}

// --- Validador ---------------------------------------------------------------
$report = $validator->validateCatalog(['products' => [product()]]);
expect($report['products'][0]['errors'] === [], 'validador: producto correcto sin errores', $report['products'][0]['errors']);

$withCost = product(['costo' => 900000]);
$report = $validator->validateCatalog(['products' => [$withCost]]);
expect($report['products'][0]['product'] === null, 'validador: rechaza campo de costo');

$nested = product();
$nested['variants'][0]['margen'] = 10;
$report = $validator->validateCatalog(['products' => [$nested]]);
expect($report['products'][0]['product'] === null, 'validador: rechaza margen dentro de una variante');

$report = $validator->validateCatalog(['products' => [product(), product(['slug' => 'otro'])]]);
expect($report['products'][1]['product'] === null, 'validador: SKU duplicado entre productos');

$cheapGateway = product();
$cheapGateway['variants'][0]['price_gateway'] = 900000;
$report = $validator->validateCatalog(['products' => [$cheapGateway]]);
expect($report['products'][0]['product'] !== null && $report['products'][0]['warnings'] !== [], 'validador: pasarela < efectivo = advertencia, se muestra');

$badColor = product();
$badColor['variants'][0]['color'] = 'Verde';
$report = $validator->validateCatalog(['products' => [$badColor]]);
expect($report['products'][0]['product'] === null, 'validador: color de variante inexistente');

$stringPrice = product();
$stringPrice['variants'][0]['price_cash'] = '1.000.000';
$report = $validator->validateCatalog(['products' => [$stringPrice]]);
expect($report['products'][0]['product'] === null, 'validador: precio como texto con puntos');

$report = $validator->validateCatalog(['products' => [product(['name' => 'Teléfono 256 GB'])]]);
expect($report['products'][0]['warnings'] !== [], 'validador: capacidad en el nombre = advertencia');

// Catálogo real
$real = load_json_file(APP_PATH . '/data/products.json');
$report = $validator->validateCatalog($real);
$realErrors = array_merge($report['errors'], ...array_map(static fn ($e) => $e['errors'], $report['products']));
expect($realErrors === [], 'catálogo real: sin errores', $realErrors);
expect(count($report['products']) === 23, 'catálogo real: 23 productos', count($report['products']));
$variantTotal = array_sum(array_map(static fn ($e) => count($e['product']['variants'] ?? []), $report['products']));
expect($variantTotal === 32, 'catálogo real: 32 variantes (filas de la hoja + Borgoña del 18 Pro Max)', $variantTotal);
$bySlug = [];
foreach ($real['products'] as $item) {
    $bySlug[$item['slug']] = $item;
}
$ipads = array_values(array_filter(array_keys($bySlug), static fn (string $slug): bool => str_contains($slug, 'ipad')));
expect($ipads === ['ipad-pro-11-m5'], 'catálogo real: el único iPad es el iPad Pro M5 (sin A16 ni 11 M4)', $ipads);
$burgundy = array_filter($bySlug['iphone-18-pro']['variants'], static fn (array $v): bool => ($v['color'] ?? null) === 'Borgoña');
expect(count($burgundy) === 1 && array_values($burgundy)[0]['price_cash'] === 4650000, 'catálogo real: una sola línea Borgoña ($4.650.000)');

// --- Presentador ---------------------------------------------------------------
$normalized = $validator->validateCatalog(['products' => [product()]])['products'][0]['product'];

$mark = (new ProductPresenter('mark', true))->card($normalized, 'test');
expect($mark !== null && count($mark['colors']) === 2, 'mark: muestra los 2 colores');
expect($mark['colors'][1]['available'] === false, 'mark: color agotado marcado');
expect($mark['options'][1]['available'] === false, 'mark: capacidad agotada deshabilitada');
expect($mark['current']['available'] === true && $mark['current']['cash'] === 1000000, 'mark: selección inicial = variante disponible más barata');
$soldOutEntry = $mark['client']['m']['0|1'];
expect($soldOutEntry['cta'] === ProductPresenter::CTA_ASK, 'mark: agotado cambia el botón', $soldOutEntry['cta']);
expect(str_contains(rawurldecode($soldOutEntry['wa']), 'quiero saber cuándo habrá disponibilidad del Teléfono Prueba Azul 256 GB.'), 'mark: mensaje de agotado sin precios', rawurldecode($soldOutEntry['wa']));
expect(!str_contains(rawurldecode($soldOutEntry['wa']), '$'), 'mark: mensaje de agotado no lleva precios');

$hide = (new ProductPresenter('hide', true))->card($normalized, 'test');
expect($hide !== null && count($hide['colors']) === 1 && count($hide['options']) === 1, 'hide: oculta color y capacidad agotados');

$show = (new ProductPresenter('show', true))->card($normalized, 'test');
expect($show['client']['m']['1|1']['available'] === true, 'show: ignora la disponibilidad');

$allSold = product(['available' => false]);
$allSold = $validator->validateCatalog(['products' => [$allSold]])['products'][0]['product'];
$card = (new ProductPresenter('mark', true))->card($allSold, 'test');
expect($card['all_sold_out'] === true && $card['current']['cta'] === ProductPresenter::CTA_ASK, 'todo agotado: tarjeta "Agotado" y "Preguntar por disponibilidad"');
expect($card['current']['cash_f'] === '$1.000.000', 'todo agotado: los precios siguen visibles');
expect((new ProductPresenter('hide', true))->card($allSold, 'test') === null, 'hide + todo agotado: no aparece en el listado');
$detail = (new ProductPresenter('hide', true))->detail($allSold);
expect($detail['all_sold_out'] === true, 'hide + todo agotado: el detalle sigue respondiendo (enlace compartido)');

$colorPrice = product();
$colorPrice['colors'][1]['available'] = true;
$colorPrice['variants'][] = ['sku' => 'TP-128-R', 'storage' => '128 GB', 'color' => 'Rojo', 'price_cash' => 1100000, 'price_gateway' => 1136000];
$colorPrice = $validator->validateCatalog(['products' => [$colorPrice]])['products'][0]['product'];
$card = (new ProductPresenter('mark', true))->card($colorPrice, 'test');
expect($card['client']['m']['1|0']['cash'] === 1100000 && $card['client']['m']['0|0']['cash'] === 1000000, 'precio propio por color (como Borgoña)');
expect($card['from'] === true, '"Desde" cuando hay precios distintos');

// Slides por color: foto propia del color, si no la general ("image"), si no la silueta.
$specReport = $validator->validateSpecs(['products' => ['telefono-prueba' => ['slides' => [
    ['title' => 'Uno', 'images' => ['Rojo' => '/assets/images/products/telefono-prueba-rojo-uno.webp']],
    ['title' => 'Dos', 'image' => '/assets/images/products/telefono-prueba-dos.webp', 'images' => ['Rojo' => '/assets/images/products/telefono-prueba-rojo-dos.webp']],
    ['title' => 'Tres', 'images' => ['Verde' => '/assets/images/products/telefono-prueba-verde-tres.webp']],
]]]], [$colorPrice['slug']], [$colorPrice['slug'] => ['Azul', 'Rojo']]);
expect(count($specReport['warnings']) === 1, 'slides: foto para un color que el producto no tiene = advertencia', $specReport['warnings']);
$badName = $validator->validateSpecs(['products' => ['telefono-prueba' => ['slides' => [
    ['title' => 'Uno', 'image' => '/assets/images/products/IMG_0042.webp'],
    ['title' => 'Dos', 'images' => ['Rojo' => '/assets/images/products/rojo-trasera.webp']],
    ['title' => 'Tres', 'image' => '/assets/images/products/telefono-prueba-azul-camara.webp'],
]]]], [$colorPrice['slug']], [$colorPrice['slug'] => ['Azul', 'Rojo']]);
expect(count($badName['warnings']) === 2, 'fotos: nombre sin el formato slug-color-toma.webp = advertencia SEO', $badName['warnings']);
$withSlides = $colorPrice + ['specs' => [], 'specs_status' => 'verificado'];
$withSlides['slides'] = $specReport['specs']['telefono-prueba']['slides'] ?? [];
$slides = (new ProductPresenter('mark', true))->detail($withSlides)['client']['slides'];
expect(array_column($slides, 'byColor') === [
    [null, '/assets/images/products/telefono-prueba-rojo-uno.webp'],
    ['/assets/images/products/telefono-prueba-dos.webp', '/assets/images/products/telefono-prueba-rojo-dos.webp'],
    [null, null],
], 'slides: foto por color, con la general como respaldo', $slides);

// --- Envío, formato, WhatsApp, escape, rutas --------------------------------------
expect(shipping_info(93000, 97000)['label'] === 'Costo de envío por confirmar', 'envío: adaptador bajo el umbral');
expect(shipping_info(149000, 152000)['partial'] === true, 'envío: cumple solo con pasarela');
expect(shipping_info(150000, 155000)['free'] === true, 'envío: umbral exacto = gratis');
expect(format_price(4650000) === '$4.650.000', 'formato COP con puntos de miles');
expect(str_starts_with(whatsapp_link('Hola $1'), 'https://wa.me/573208082188?text=Hola%20%241'), 'whatsapp_link con rawurlencode');
expect(mb_strlen(rawurldecode(explode('text=', whatsapp_link(str_repeat('a', 900)))[1])) <= 500, 'mensaje de WhatsApp limitado a 500 caracteres');
expect(color_slug('Azul Glaciar') === 'azul-glaciar' && color_slug('Púrpura Ñandú') === 'purpura-nandu', 'color_slug sin tildes ni espacios');
$waText = rawurldecode($mark['client']['m']['0|0']['wa']);
expect(str_ends_with($waText, '/productos/telefono-prueba?color=azul'), 'WhatsApp: el mensaje termina con el enlace del producto y su color (vista previa con foto)', $waText);
expect(e('<script>"x"</script>') === '&lt;script&gt;&quot;x&quot;&lt;/script&gt;', 'e() escapa HTML y comillas');
expect(e_todo('NIT [por completar]') === 'NIT <mark class="todo">[por completar]</mark>', 'e_todo resalta lo pendiente');
expect(normalize_request_path('/productos/../app/config.php') === null, 'ruta con .. rechazada');
expect(normalize_request_path('/productos//iphone-17') === '/productos/iphone-17', 'barras dobles normalizadas');
expect(normalize_request_path('/%09/otro-sitio.com/') === null, 'ruta con tab rechazada (sin redirección a otro sitio)');
$siteHost = strtolower((string) parse_url((string) config('site_url'), PHP_URL_HOST));
expect(!str_ends_with($siteHost, '.hostingersite.com') || is_noindex(), 'noindex activo mientras site_url sea el dominio temporal');

// --- Logo del header ---------------------------------------------------------------
$headerMark = logo_mark_3d('brand__mark');
preg_match_all('#/assets/(images/brand/logo-3d-mark-\d+\.webp)\?v=[1-9]\d* [123]x#', $headerMark, $found);
$missing = array_filter($found[1], static fn (string $file): bool => !is_file(PUBLIC_PATH . '/assets/' . $file));
expect(count($found[1]) === 3 && $missing === [] && str_contains($headerMark, 'alt=""'), 'logo 3D del header: 1x/2x/3x existen y es decorativo', $found[1]);

// --- Fotos del hero ------------------------------------------------------------------
$heroSlide = static fn (array $image): array => [
    'id' => 'prueba', 'type' => 'image', 'tab' => 'Prueba', 'title' => 'Prueba', 'subtitle' => 'Prueba',
    'cta' => ['label' => 'Ver', 'href' => '/productos'], 'image' => $image,
];
$heroSrc = ['src' => '/assets/images/hero/a.webp', 'width' => 2400, 'height' => 1350];
expect(hero_slide_problem($heroSlide($heroSrc + ['fit' => 'cutout', 'mobile' => ['src' => '/assets/images/hero/a-movil.webp']])) === null, 'hero: recorte con versión móvil aceptado');
expect(hero_slide_problem($heroSlide($heroSrc + ['fit' => 'stretch'])) !== null, 'hero: fit desconocido rechazado');
expect(hero_slide_problem($heroSlide($heroSrc + ['mobile' => ['src' => 'https://otro.com/a.webp']])) !== null, 'hero: imagen móvil externa rechazada');

echo "\n{$passed} pasaron, {$failed} fallaron\n";
exit($failed === 0 ? 0 : 1);
