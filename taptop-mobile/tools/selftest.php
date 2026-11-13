<?php
/**
 * Pruebas rápidas de la lógica del sitio (sin base de datos ni navegador).
 *
 *   php tools/selftest.php
 *
 * Cubre: validador (costos prohibidos, SKU duplicado, colores, pasarela < efectivo), presentador
 * (agotado en modos mark/hide/show, precio por color, "Desde"), envío gratis, mensajes de
 * WhatsApp, escape y rutas, carrito, envío y correo de pedidos. Sale con código 1 si algo falla.
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

/** Reloj con configuraciones: los 6 ejes de la spec y una variante (42 mm, aluminio Oro claro). */
function watch(array $overrides = []): array
{
    return $overrides + [
        'slug' => 'reloj-prueba',
        'brand' => 'apple',
        'category' => 'relojes',
        'name' => 'Apple Watch Series 12',
        'colors' => [],
        'options' => [
            ['name' => 'Acabado de la caja', 'values' => ['Bronce oscuro', 'Oro claro', 'Negro', 'Gris espacial', 'Blanco perla', 'Azul noche', 'Natural', 'Oro radiante']],
            ['name' => 'Color de la correa', 'values' => ['Borgoña', 'Oliva', 'Azul marino', 'Negro', 'Arena']],
            ['name' => 'Material de la caja', 'values' => ['Aluminio', 'Titanio', 'Cerámica']],
            ['name' => 'Tamaño de la caja', 'values' => ['42 mm', '46 mm']],
            ['name' => 'Conectividad', 'values' => ['GPS', 'GPS + celular']],
            ['name' => 'Tamaño de la banda', 'values' => ['S/M', 'M/L']],
        ],
        'variants' => [watch_variant('AW-S12-42-AL-OROCL-ARENA-SM-GPS', [], 1490000, 1540000)],
    ];
}

function watch_variant(string $sku, array $attrs, int $cash, int $gateway): array
{
    return ['sku' => $sku, 'attrs' => $attrs + [
        'Acabado de la caja' => 'Oro claro', 'Color de la correa' => 'Arena', 'Material de la caja' => 'Aluminio',
        'Tamaño de la caja' => '42 mm', 'Conectividad' => 'GPS', 'Tamaño de la banda' => 'S/M',
    ], 'price_cash' => $cash, 'price_gateway' => $gateway];
}

function first_errors(array $report): string
{
    return implode(' | ', $report['products'][0]['errors']);
}

// --- Validador ---------------------------------------------------------------
$report = $validator->validateCatalog(['products' => [watch()]]);
expect($report['products'][0]['errors'] === [], 'validador: reloj con configuración correcta', $report['products'][0]['errors']);
expect(($report['products'][0]['product']['options'][0]['name'] ?? null) === 'Acabado de la caja'
    && ($report['products'][0]['product']['variants'][0]['attrs']['Conectividad'] ?? null) === 'GPS'
    && $report['products'][0]['product']['variants'][0]['storage'] === null, 'validador: normaliza options y attrs');
$plain = $validator->validateCatalog(['products' => [product()]])['products'][0]['product'];
expect($plain['options'] === [] && $plain['variants'][0]['attrs'] === null, 'validador: producto sin options queda con options [] y attrs null');
$bad = watch();
unset($bad['variants'][0]['attrs']['Conectividad']);
expect(str_contains(first_errors($validator->validateCatalog(['products' => [$bad]])), 'Conectividad'), 'validador: attrs sin un eje');
$bad = watch(['variants' => [watch_variant('AW-X', ['Acabado de la caja' => 'Oro rosa'], 1490000, 1540000)]]);
expect(str_contains(first_errors($validator->validateCatalog(['products' => [$bad]])), 'Oro rosa'), 'validador: valor fuera de la lista');
$bad = watch(['variants' => [watch_variant('AW-A', [], 1490000, 1540000), watch_variant('AW-B', [], 1500000, 1550000)]]);
expect(str_contains(first_errors($validator->validateCatalog(['products' => [$bad]])), 'repetida'), 'validador: configuración repetida');
$bad = product();
$bad['variants'][0]['attrs'] = ['Conectividad' => 'GPS'];
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: attrs sin options');
$bad = watch();
$bad['options'][4]['values'] = [];
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: eje sin valores');
$bad = watch();
$bad['options'][5]['values'] = ['S/M', 'S/M'];
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: valor repetido en un eje');
$pic = watch();
$pic['variants'][0]['image'] = '/assets/images/products/reloj-prueba-oro-claro-arena.webp';
$report = $validator->validateCatalog(['products' => [$pic]]);
expect(($report['products'][0]['product']['variants'][0]['image'] ?? null) === '/assets/images/products/reloj-prueba-oro-claro-arena.webp'
    && $validator->validateCatalog(['products' => [watch()]])['products'][0]['product']['variants'][0]['image'] === null, 'validador: foto por configuración', $report['products'][0]['errors']);
$bad = watch();
$bad['variants'][0]['image'] = '../secreto.png';
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: ruta de foto de configuración inválida');
$bad = product();
$bad['variants'][0]['image'] = '/assets/images/products/telefono-prueba-azul.webp';
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: foto por variante solo en configuraciones');
$bad = watch(['colors' => [['name' => 'Azul', 'hex' => '#123456', 'image' => null]]]);
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: options y colors no se mezclan');

$hexed = watch();
$hexed['options'][1]['hex'] = ['Borgoña' => '#6a2433', 'Oliva' => '#6B6B47', 'Azul marino' => '#1F2D4D', 'Negro' => '#1F1F1F', 'Arena' => '#D8C7A6'];
$report = $validator->validateCatalog(['products' => [$hexed]]);
expect(($report['products'][0]['product']['options'][1]['hex']['Borgoña'] ?? null) === '#6A2433' && $report['products'][0]['product']['options'][2]['hex'] === null, 'validador: hex por valor en ejes de color', $report['products'][0]['errors']);
$bad = $hexed;
unset($bad['options'][1]['hex']['Arena']);
expect(str_contains(first_errors($validator->validateCatalog(['products' => [$bad]])), 'Arena'), 'validador: eje de color sin hex de un valor');
$bad = $hexed;
$bad['options'][1]['hex']['Rosa'] = '#FFC0CB';
expect(str_contains(first_errors($validator->validateCatalog(['products' => [$bad]])), 'Rosa'), 'validador: hex de un valor que no existe');
$bad = $hexed;
$bad['options'][1]['hex']['Oliva'] = 'verde';
expect($validator->validateCatalog(['products' => [$bad]])['products'][0]['product'] === null, 'validador: hex inválido en opción');

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

$sized = $validator->validateCatalog(['products' => [product(['sizes' => [['label' => '13"'], ['label' => '15"', 'available' => false]]])]])['products'][0]['product'];
expect($sized !== null && $sized['sizes'] === [['label' => '13"', 'available' => true], ['label' => '15"', 'available' => false]], 'validador: tamaños, disponible por defecto');
$report = $validator->validateCatalog(['products' => [product(['sizes' => [['label' => '15"', 'available' => false]]])]]);
expect($report['products'][0]['product'] === null, 'validador: tamaños sin ninguno disponible = error');

// Catálogo real
$real = load_json_file(APP_PATH . '/data/products.json');
$report = $validator->validateCatalog($real);
$realErrors = array_merge($report['errors'], ...array_map(static fn ($e) => $e['errors'], $report['products']));
expect($realErrors === [], 'catálogo real: sin errores', $realErrors);
expect(count($report['products']) === 26, 'catálogo real: 26 productos (Series 12 en uno solo)', count($report['products']));
$variantTotal = array_sum(array_map(static fn ($e) => count($e['product']['variants'] ?? []), $report['products']));
expect($variantTotal === 39, 'catálogo real: 39 variantes (filas de la hoja + colores con precio propio del 18 Pro Max)', $variantTotal);

// Desde aquí, el catálogo real con todo disponible: lo agotado hoy no debe romper las pruebas.
$inStock = $real;
foreach ($inStock['products'] as &$p) {
    unset($p['available']);
    foreach ($p['colors'] as &$c) { unset($c['available']); }
    foreach ($p['variants'] as &$v) { unset($v['available']); }
    unset($c, $v);
}
unset($p);
$inStockPath = sys_get_temp_dir() . '/taptop-products-' . bin2hex(random_bytes(4)) . '.json';
file_put_contents($inStockPath, json_encode($inStock, JSON_UNESCAPED_UNICODE));
product_repository(new \Taptop\Repositories\JsonProductRepository($inStockPath, APP_PATH . '/data/specs.json', $validator));
register_shutdown_function(static fn () => @unlink($inStockPath));
$bySlug = [];
foreach ($real['products'] as $item) {
    $bySlug[$item['slug']] = $item;
}
$ipads = array_values(array_filter(array_keys($bySlug), static fn (string $slug): bool => str_contains($slug, 'ipad')));
expect($ipads === ['ipad-pro-11-m5', 'ipad-air-11-m4', 'ipad-a16'], 'catálogo real: iPads Pro M5, Air M4 y A16', $ipads);
$burgundy = array_filter($bySlug['iphone-18-pro']['variants'], static fn (array $v): bool => ($v['color'] ?? null) === 'Borgoña');
expect(count($burgundy) === 1, 'catálogo real: una sola línea Borgoña en el iPhone 18 Pro');
$s12 = $bySlug['apple-watch-series-12'] ?? null;
expect($s12 !== null && count($s12['variants']) === 2 && $s12['variants'][0]['sku'] === 'AW-S12-42-AL-OROCL-ARENA-SM-GPS' && $s12['variants'][1]['sku'] === 'AW-S12-42-AL-NEGRO-NEGRO-SM-GPS'
    && $s12['variants'][0]['price_cash'] === 1490000 && $s12['variants'][0]['price_gateway'] === 1540000, 'catálogo real: Series 12 con sus 2 configuraciones', $s12['variants'] ?? null);
$u4 = $bySlug['apple-watch-ultra-4'] ?? null;
expect($u4 !== null && ($u4['available'] ?? true) === true && count($u4['variants']) === 1 && $u4['variants'][0]['sku'] === 'AW-U4-49-TI-NEGRO-NEGRO-SM-CEL'
    && $u4['variants'][0]['price_cash'] === 3040000 && $u4['variants'][0]['price_gateway'] === 3140000
    && ($u4['variants'][0]['attrs']['Conectividad'] ?? null) === 'GPS + celular' && ($u4['variants'][0]['image'] ?? null) === '/assets/images/products/apple-watch-ultra-4-negro-negro.webp', 'catálogo real: Ultra 4 con su única configuración y su foto', $u4['variants'] ?? null);
expect((new ProductPresenter('mark', true))->detail(product_repository()->findBySlug('apple-watch-ultra-4'))['slides'] === [], 'catálogo real: Ultra 4 sin sección En detalle');
expect(!isset($bySlug['apple-watch-series-12-42mm']) && !isset($bySlug['apple-watch-series-12-46mm']), 'catálogo real: sin los Series 12 viejos por tamaño');
expect(legacy_product_slug('apple-watch-series-12-46mm') === 'apple-watch-series-12' && legacy_product_slug('apple-watch-series-12-42mm') === 'apple-watch-series-12'
    && legacy_product_slug('iphone-18-pro') === null, 'rutas: enlaces viejos del Series 12 redirigen');
expect(!in_array('apple-watch-series-12-46mm', (array) config('home_featured'), true) && in_array('apple-watch-series-12', (array) config('home_featured'), true), 'inicio: destacado apunta al Series 12 nuevo');

// --- Presentador ---------------------------------------------------------------
$normalized = $validator->validateCatalog(['products' => [product()]])['products'][0]['product'];

// Configurador (reloj): cada configuración es una opción con SKU propio.
$watchSku = 'AW-S12-42-AL-OROCL-ARENA-SM-GPS';
$watchLabel = 'Apple Watch Series 12 42 mm, Aluminio Oro claro, correa Arena S/M, GPS';
$watchP = $validator->validateCatalog(['products' => [watch()]])['products'][0]['product'];
expect(product_variant_label($watchP, null, $watchP['variants'][0]) === $watchLabel, 'configurador: etiqueta completa', product_variant_label($watchP, null, $watchP['variants'][0]));
$wd = (new ProductPresenter('mark', true))->detail($watchP);
expect(count($wd['options']) === 1 && $wd['single_option'] === null, 'configurador: una opción por configuración y sin texto fijo', [$wd['options'], $wd['single_option']]);
expect(($wd['client']['cfg']['axes'][0]['name'] ?? null) === 'Acabado de la caja' && ($wd['client']['cfg']['opts'][0] ?? null) === [1, 4, 0, 0, 0, 0], 'configurador: ejes e índices de valores', $wd['client']['cfg'] ?? null);
expect($wd['current']['sku'] === $watchSku && $wd['current']['cash'] === 1490000, 'configurador: inicial con su SKU y precio', $wd['current']);
expect(str_contains(rawurldecode($wd['current']['wa']), $watchLabel) && str_contains(rawurldecode($wd['current']['wa']), 'config=' . $watchSku), 'configurador: WhatsApp con etiqueta y enlace ?config=', rawurldecode($wd['current']['wa']));
expect(str_contains($wd['client']['cfg']['ask'] ?? '', '%7BCONFIG%7D'), 'configurador: plantilla de consulta con marcador');
$tpl = $wd['client']['cfg']['label'] ?? '';
foreach ($watchP['variants'][0]['attrs'] as $axis => $value) {
    $tpl = str_replace('{' . $axis . '}', $value, $tpl);
}
expect('Apple Watch Series 12 ' . $tpl === $watchLabel, 'configurador: plantilla de etiqueta para el JS', $tpl);
$two = $validator->validateCatalog(['products' => [watch(['variants' => [
    watch_variant($watchSku, [], 1490000, 1540000),
    watch_variant('AW-S12-46-AL-OROCL-ARENA-SM-GPS', ['Tamaño de la caja' => '46 mm'], 1720000, 1777000),
]])]])['products'][0]['product'];
expect((new ProductPresenter('mark', true))->detail($two, 'AW-S12-46-AL-OROCL-ARENA-SM-GPS')['current']['cash'] === 1720000, 'configurador: ?config= elige esa configuración');
expect((new ProductPresenter('mark', true))->detail($two, 'NO-EXISTE')['current']['cash'] === 1490000, 'configurador: ?config= desconocido abre en la más barata');
$pick46 = (new ProductPresenter('mark', true))->detail($two, 'AW-S12-46-AL-OROCL-ARENA-SM-GPS');
expect($pick46['from'] === false && $pick46['client']['from'] === false && (new ProductPresenter('mark', true))->detail($two)['from'] === true, 'configurador: ?config= elegido no muestra "Desde"');
$twoStorages = product();
unset($twoStorages['variants'][1]['available']);
$twoStorages = $validator->validateCatalog(['products' => [$twoStorages]])['products'][0]['product'];
$ignored = (new ProductPresenter('mark', true))->detail($twoStorages, 'TP-256');
expect($ignored['current']['sku'] === 'TP-128' && $ignored['from'] === true, 'configurador: ?config= no aplica a productos sin opciones', $ignored['current']['sku']);
expect(find_catalog_entry($watchP, $watchSku, null) !== null && find_catalog_entry($watchP, $watchSku, 'Negro') === null, 'configurador: carrito acepta el SKU sin color y rechaza con color');
expect(!isset((new ProductPresenter('mark', true))->detail($normalized)['client']['cfg']), 'configurador: producto con colores no trae cfg');
$pics = watch(['variants' => [
    ['image' => '/assets/images/products/reloj-prueba-oro-claro-arena.webp'] + watch_variant($watchSku, [], 1490000, 1540000),
    ['image' => '/assets/images/products/reloj-prueba-negro-negro.webp'] + watch_variant('AW-S12-42-AL-NEGRO-NEGRO-SM-GPS', ['Acabado de la caja' => 'Negro', 'Color de la correa' => 'Negro'], 1490000, 1540000),
]]);
$picsP = $validator->validateCatalog(['products' => [$pics]])['products'][0]['product'];
$picsD = (new ProductPresenter('mark', true))->detail($picsP, 'AW-S12-42-AL-NEGRO-NEGRO-SM-GPS');
expect($picsD['media']['image'] === '/assets/images/products/reloj-prueba-negro-negro.webp' && $picsD['media']['alt'] === 'Apple Watch Series 12 42 mm, Aluminio Negro, correa Negro S/M, GPS'
    && ($picsD['client']['m']['_|0']['img'] ?? null) === '/assets/images/products/reloj-prueba-oro-claro-arena.webp', 'configurador: foto y texto alternativo de la configuración elegida', [$picsD['media'], $picsD['client']['m']['_|0']['img'] ?? null]);
$picsFind = static fn (string $sku): ?array => ['product' => $picsP, 'variant' => $picsP['variants'][$sku === $watchSku ? 0 : 1]];
$picsCart = cart_priced([['sku' => 'AW-S12-42-AL-NEGRO-NEGRO-SM-GPS', 'color' => null, 'qty' => 1]], $picsFind);
expect(($picsCart['lines'][0]['image'] ?? null) === '/assets/images/products/reloj-prueba-negro-negro.webp', 'carrito: foto de la configuración de la línea', $picsCart['lines'][0] ?? null);
// Galería de miniaturas: foto del color + fotos de "En detalle" del mismo color.
$g18 = (new ProductPresenter('mark', true))->detail(product_repository()->findBySlug('iphone-18-pro'));
$gSel = $g18['client']['gallery'][$g18['selected']['color']] ?? [];
expect(count($g18['gallery']) === 3 && !str_contains(implode(' ', array_column($gSel, 'src')), '-camara') && $g18['gallery'] === $gSel && $gSel[0]['src'] === $g18['media']['image']
    && str_ends_with($gSel[1]['src'], '-frontal.webp') && str_contains($gSel[1]['alt'], 'Chip A20 Pro'), 'galería: foto del color y las de En detalle sin el primer plano de la cámara', $gSel);
expect(count($g18['client']['gallery']) === count($g18['colors']) && count(array_unique(array_column(array_merge(...$g18['client']['gallery']), 'src'))) === 12 && count($g18['slides']) === 3, 'galería: una lista por color, sin repetidas; En detalle conserva la cámara');
expect((new ProductPresenter('mark', true))->detail($normalized)['gallery'] === [] && (new ProductPresenter('mark', true))->detail($watchP)['gallery'] === [], 'galería: sin fotos o con una sola, no hay galería');
$hexedP = $validator->validateCatalog(['products' => [$hexed]])['products'][0]['product'];
$cfgHtml = view('partials/config-options', ['card' => (new ProductPresenter('mark', true))->detail($hexedP), 'uid' => 'pdp-t']);
expect(substr_count($cfgHtml, '<label class="swatch') === 5 && str_contains($cfgHtml, '--swatch: #D8C7A6') && str_contains($cfgHtml, 'swatches swatches--lg')
    && substr_count($cfgHtml, '<label class="option') === 8 + 3 + 2 + 2 + 2, 'configurador: ejes con hex en círculos, el resto en botones', substr_count($cfgHtml, 'class="swatch'));
expect(str_contains($cfgHtml, '<label class="swatch is-sold-out"') && str_contains($cfgHtml, 'title="Oliva (no disponible)"'), 'configurador: círculo sin configuración marcado y con título');
expect(preg_match('/<input[^>]*value="1" data-cfg-axis="1"[^>]* disabled>/', $cfgHtml) === 1 && preg_match('/<input[^>]*data-cfg-axis="0" checked>/', $cfgHtml) === 1,
    'configurador: lo que no está en ninguna configuración no se puede elegir', $cfgHtml);
$hexedTwo = $hexed;
$hexedTwo['variants'][] = watch_variant('AW-S12-42-AL-NEGRO-NEGRO-SM-GPS', ['Acabado de la caja' => 'Negro', 'Color de la correa' => 'Negro'], 1490000, 1540000);
$hexedTwo['options'][0]['hex'] = array_fill_keys($hexedTwo['options'][0]['values'], '#888888');
$twoHtml = view('partials/config-options', ['card' => (new ProductPresenter('mark', true))->detail($validator->validateCatalog(['products' => [$hexedTwo]])['products'][0]['product']), 'uid' => 'pdp-t']);
expect(!str_contains($twoHtml, 'title="Negro (no disponible)"') && str_contains($twoHtml, 'title="Oliva (no disponible)"') && str_contains($twoHtml, 'title="Bronce oscuro (no disponible)"'),
    'configurador: solo se tacha lo que no está en ninguna configuración');

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
$under = shipping_info(93000, 97000);
expect($under['label'] === 'Envío: $15.000' && $under['cost_cash'] === 15000 && $under['cost_gateway'] === 15000, 'envío: adaptador bajo el umbral paga $15.000', $under);
$part = shipping_info(149000, 152000);
expect($part['cost_cash'] === 15000 && $part['cost_gateway'] === 0, 'envío: cobra solo en el método que no llega al umbral', $part);
expect(delivery_label() === '2 a 5 días hábiles', 'envío: plazo de entrega', delivery_label());
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

// --- Texto bajo el título del catálogo --------------------------------------------
expect(str_contains(catalog_lede('iphone', null), 'iPhone') && str_ends_with(catalog_lede('iphone', null), 'Originales, con 1 año de garantía directa y envío a toda Colombia.'), 'catálogo: texto de iPhone con garantía de 1 año', catalog_lede('iphone', null));
expect(str_contains(catalog_lede('airpods', null), '3 meses de garantía'), 'catálogo: AirPods con garantía de accesorios', catalog_lede('airpods', null));
expect(catalog_lede('iphone', 'apple') === catalog_lede('iphone', null) && str_contains(catalog_lede(null, 'samsung'), 'Galaxy') && catalog_lede(null, null) !== '', 'catálogo: categoría, marca y general');
expect(str_ends_with(catalog_lede(null, null), 'Originales, con garantía directa y envío a toda Colombia.') && !str_contains(catalog_lede(null, 'apple'), '1 año'), 'catálogo: sin categoría no promete un plazo de garantía');
$ledes = array_map(static fn (string $c): string => catalog_lede($c, null), nav_category_keys());
expect(count(array_unique($ledes)) === count($ledes) && !str_contains(implode(' ', $ledes), 'IVA'), 'catálogo: un texto propio por categoría', $ledes);

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

// --- SPIKE Wompi ---------------------------------------------------------------------
use Taptop\Payments\Wompi;

expect(
    Wompi::integritySignature('TT-A-1', 459000000, 'COP', 'secreto') === hash('sha256', 'TT-A-1459000000COPsecreto'),
    'Wompi: firma = SHA-256 de referencia + centavos + moneda + secreto'
);
$wompiRef = 'TT-IP18PM-256-BUR-0123456789';
expect(Wompi::skuFromReference($wompiRef) === 'IP18PM-256-BUR', 'Wompi: referencia vieja conserva el SKU', $wompiRef);
expect(Wompi::skuFromReference('otra-tienda-123') === null, 'Wompi: referencia ajena rechazada');
$ref = Wompi::newReference();
expect(preg_match('/^TT-[0-9A-F]{12}$/D', $ref) === 1 && Wompi::skuFromReference($ref) === null, 'Wompi: referencia nueva sin datos del producto', $ref);
$tmpOrders = tempnam(sys_get_temp_dir(), 'tt-orders');
order_save($ref, ['source' => 'carrito', 'lines' => [], 'gateway' => 817000, 'cash' => 790000, 'shipping' => [], 'created_at' => 'x', 'mail' => null], $tmpOrders);
expect(order_find($ref, $tmpOrders)['gateway'] === 817000 && order_find('TT-000000000000', $tmpOrders) === null, 'pedido: se guarda y se encuentra por referencia');
expect(order_claim_mail($ref, $tmpOrders) === true && order_claim_mail($ref, $tmpOrders) === false, 'pedido: el correo solo se reclama una vez');
order_finish_mail($ref, false, $tmpOrders);
expect(order_claim_mail($ref, $tmpOrders) === true, 'pedido: si el envío falla, se puede reintentar');
order_finish_mail($ref, true, $tmpOrders);
expect(order_claim_mail($ref, $tmpOrders) === false && order_find($ref, $tmpOrders)['mail']['status'] === 'sent', 'pedido: enviado no se reenvía');
expect(Wompi::skuFromReference("TT-APP3-0123456789\n") === null, 'Wompi: referencia con salto de línea final rechazada');
expect(order_mark_cart_cleared($ref, $tmpOrders) === true && order_mark_cart_cleared($ref, $tmpOrders) === false && is_string(order_find($ref, $tmpOrders)['cart_cleared_at'] ?? null), 'pedido: el carrito se vacía una sola vez por pedido');
expect(order_mark_cart_cleared('TT-000000000000', $tmpOrders) === false, 'pedido: sin pedido no se vacía el carrito');
expect(!is_file($tmpOrders . '.tmp') && is_file($tmpOrders . '.lock'), 'almacén: escritura atómica sin temporal sobrante, bloqueo en archivo aparte');
$keep = Wompi::newReference();
order_save($keep, ['source' => 'carrito', 'gateway' => 1], $tmpOrders);
$threw = false;
try {
    order_save(Wompi::newReference(), ['shipping' => ['name' => "\xB1\x31"]], $tmpOrders);
} catch (Throwable $e) {
    $threw = true;
}
expect($threw && order_find($keep, $tmpOrders)['gateway'] === 1, 'pedido: si falla la codificación, el almacén no se borra');
$threw = false;
try {
    json_store_update($tmpOrders, static function (array &$state): void { throw new RuntimeException('a mitad'); });
} catch (RuntimeException) {
    $threw = true;
}
expect($threw && order_find($keep, $tmpOrders)['gateway'] === 1 && order_save($keep, ['gateway' => 2], $tmpOrders) === null && order_find($keep, $tmpOrders)['gateway'] === 2, 'almacén: un error libera el bloqueo y se puede volver a escribir (rename sobre el archivo existente)');
$missingStore = sys_get_temp_dir() . '/tt-no-existe-' . bin2hex(random_bytes(4)) . '.json';
expect(order_find($ref, $missingStore) === null && !is_file($missingStore . '.lock'), 'almacén: leer un archivo que no existe = vacío, sin crear nada');
unlink($tmpOrders);
@unlink($tmpOrders . '.lock');
$testWompi = new Wompi('pub_test_x', 'test_integrity_y');
parse_str((string) parse_url($testWompi->checkoutUrl('TT-A-1', 100, 'http://localhost/pago/resultado'), PHP_URL_QUERY), $checkoutQuery);
expect(
    $testWompi->isSandbox() && $checkoutQuery['amount-in-cents'] === '100' && $checkoutQuery['signature:integrity'] === Wompi::integritySignature('TT-A-1', 100, 'COP', 'test_integrity_y'),
    'Wompi: URL del checkout lleva monto y firma',
    $checkoutQuery
);
expect(Wompi::fromConfig(['public_key' => 'pub_test_x', 'integrity_secret' => '']) === null, 'Wompi: sin secreto de integridad no se activa');
$wompiEvent = [
    'event' => 'transaction.updated',
    'data' => ['transaction' => ['id' => '1-2-3', 'status' => 'APPROVED', 'amount_in_cents' => 459000000]],
    'signature' => ['properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'], 'checksum' => hash('sha256', '1-2-3APPROVED4590000001700000000secreto')],
    'timestamp' => 1700000000,
];
expect(Wompi::eventIsAuthentic($wompiEvent, 'secreto'), 'Wompi webhook: evento con firma válida aceptado');
$wompiTampered = $wompiEvent;
$wompiTampered['data']['transaction']['amount_in_cents'] = 100;
expect(!Wompi::eventIsAuthentic($wompiTampered, 'secreto'), 'Wompi webhook: monto alterado rechazado');
expect(!Wompi::eventIsAuthentic($wompiEvent, 'otro') && !Wompi::eventIsAuthentic($wompiEvent, ''), 'Wompi webhook: secreto distinto o vacío rechazado');

// --- Carrito ---------------------------------------------------------------
expect(cart_parse('') === [] && cart_parse('{roto') === [] && cart_parse('"x"') === [], 'carrito: cookie vacía o rota = carrito vacío');
expect(cart_parse('[["IP18PM-256-BUR","Borgoña",99]]') === [['sku' => 'IP18PM-256-BUR', 'color' => 'Borgoña', 'qty' => 5]], 'carrito: qty recortada a 5');
expect(count(cart_parse(json_encode(array_map(static fn ($i) => ["SKU-$i", null, 1], range(1, 50))))) === 10, 'carrito: máximo 10 líneas');
expect(cart_parse('[["<b>x</b>",null,1],["APP3",null,1],["APP3",null,2]]') === [['sku' => 'APP3', 'color' => null, 'qty' => 3]], 'carrito: sku inválido descartado y duplicados fusionados');
$cartLines = cart_add(cart_add([], 'IP18PM-256-BUR', 'Borgoña'), 'APP3', 'Blanco');
$priced = cart_priced($cartLines);
$bur = find_product_by_sku('IP18PM-256-BUR')['variant'];
$app3 = find_product_by_sku('APP3')['variant'];
expect($priced['gateway'] === $bur['price_gateway'] + $app3['price_gateway'] && $priced['cash'] === $bur['price_cash'] + $app3['price_cash'] && $priced['count'] === 2, 'carrito: totales desde el catálogo', $priced);
expect($priced['total_gateway'] === $priced['gateway'] && $priced['total_cash'] === $priced['cash'], 'carrito: sobre el umbral el envío es gratis', $priced);
$cheapPriced = cart_priced([['sku' => 'APL-ADAPT-NONFC', 'color' => null, 'qty' => 1]]);
expect($cheapPriced['total_gateway'] === $cheapPriced['gateway'] + 15000 && $cheapPriced['total_cash'] === $cheapPriced['cash'] + 15000 && $cheapPriced['count'] === 1 && str_contains(wa_message_cart($cheapPriced), 'Envío — $15.000') && str_contains(wa_message_cart($cheapPriced), 'Total por transferencia: ' . format_price($cheapPriced['cash'] + 15000)), 'carrito: bajo el umbral suma $15.000 al total y al mensaje de WhatsApp', $cheapPriced);
$emptyPriced = cart_priced([], static fn (string $sku): ?array => null);
expect($emptyPriced['total_gateway'] === 0 && $emptyPriced['total_cash'] === 0, 'carrito: vacío no cobra envío', $emptyPriced);
$mailFee = order_email('TT-1', ['gateway' => 104000, 'shipping_fee' => 15000, 'lines' => []], ['amount_in_cents' => 10400000]);
expect(str_contains($mailFee['body'], 'Envío: $15.000') && !str_contains($mailFee['subject'], 'Revisar'), 'correo: muestra el envío cobrado', $mailFee);
expect(cart_priced([['sku' => 'NO-EXISTE', 'color' => null, 'qty' => 1]])['lines'] === [], 'carrito: sku fuera del catálogo omitido');
expect(cart_priced([['sku' => 'AW-S12-42', 'color' => null, 'qty' => 1]])['lines'] === [], 'carrito: SKU viejo del Series 12 se descarta');
expect(cart_set_qty($cartLines, 0, 0) === [['sku' => 'APP3', 'color' => 'Blanco', 'qty' => 1]], 'carrito: qty 0 quita la línea');
expect(cart_parse(cart_encode($cartLines)) === $cartLines, 'carrito: cookie ida y vuelta (con tildes)');
$waCart = wa_message_cart($priced);
expect(str_contains($waCart, '• 1 × AirPods Pro 3 Blanco — ' . format_price($app3['price_cash'])) && str_ends_with($waCart, 'Total por transferencia: ' . format_price($priced['cash'])), 'carrito: mensaje de WhatsApp con detalle y total por transferencia', $waCart);
$fakeFind = static fn (string $sku): ?array => ['TP-128' => ['product' => $normalized, 'variant' => $normalized['variants'][0]], 'TP-256' => ['product' => $normalized, 'variant' => $normalized['variants'][1]]][$sku] ?? null;
$soldOut = cart_priced([['sku' => 'TP-128', 'color' => 'Azul', 'qty' => 1], ['sku' => 'TP-256', 'color' => 'Azul', 'qty' => 2]], $fakeFind);
expect($soldOut['lines'][1]['available'] === false && $soldOut['gateway'] === 1033000 && $soldOut['count'] === 1 && $soldOut['has_unavailable'], 'carrito: agotado se muestra pero no suma', $soldOut);
$ordered = array_column(sold_out_last([['slug' => 'a', 'all_sold_out' => true], ['slug' => 'b', 'all_sold_out' => false], ['slug' => 'c', 'all_sold_out' => true], ['slug' => 'd', 'all_sold_out' => false]]), 'slug');
expect($ordered === ['b', 'd', 'a', 'c'], 'listado: agotados al final sin cambiar el orden del resto', $ordered);

$site = (string) config('site_url');
expect(request_is_same_origin(['HTTP_ORIGIN' => rtrim($site, '/')]), 'POST: mismo origen aceptado');
expect(!request_is_same_origin(['HTTP_ORIGIN' => 'https://otro-sitio.com']), 'POST: otro origen rechazado');
expect(request_is_same_origin(['HTTP_REFERER' => rtrim($site, '/') . '/carrito']), 'POST: sin Origin, Referer propio aceptado');
expect(!request_is_same_origin([]), 'POST: sin Origin ni Referer rechazado');
expect(!request_is_same_origin(['HTTP_ORIGIN' => rtrim($site, '/') . '.evil.com']), 'POST: host que solo empieza igual rechazado');
$noColorLine = cart_priced([['sku' => 'APL-PENCIL-PRO', 'color' => null, 'qty' => 1]])['lines'][0];
expect(array_key_exists('image', $noColorLine) && is_string($noColorLine['kind'] ?? null) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($noColorLine['hex'] ?? '')) === 1, 'carrito: línea trae kind y hex para la silueta si no hay foto', $noColorLine);

// --- Datos de envío ----------------------------------------------------------
$okForm = ['nombre' => 'Ana Pérez', 'cedula' => '1020304050', 'celular' => '+57 300 123 4567', 'correo' => 'ana@example.com', 'departamento' => 'Antioquia', 'ciudad' => 'Medellín', 'direccion' => 'Cra 43A # 1-50', 'barrio' => 'El Poblado', 'notas' => '', 'acepta' => '1'];
$v = shipping_form_validate($okForm);
expect($v['errors'] === [] && $v['data']['celular'] === '3001234567' && !isset($v['data']['acepta']), 'envío: formulario válido y celular normalizado', $v);
expect(isset(shipping_form_validate(['celular' => '12345'] + $okForm)['errors']['celular']), 'envío: celular inválido');
expect(isset(shipping_form_validate(['cedula' => '12a'] + $okForm)['errors']['cedula']), 'envío: cédula inválida');
expect(isset(shipping_form_validate(['correo' => 'ana@'] + $okForm)['errors']['correo']), 'envío: correo inválido');
expect(isset(shipping_form_validate(['departamento' => 'Narnia'] + $okForm)['errors']['departamento']) && count(colombia_departments()) === 33, 'envío: departamento fuera de la lista rechazado (32 + Bogotá D.C.)');
expect(isset(shipping_form_validate(array_diff_key($okForm, ['acepta' => 1]))['errors']['acepta']), 'envío: sin aceptar la política no continúa');
expect(isset(shipping_form_validate(['nombre' => ['x']] + $okForm)['errors']['nombre']), 'envío: campo con arreglo en vez de texto rechazado sin error PHP');
$badUtf8 = shipping_form_validate(['barrio' => "El Pobl\xB1do"] + $okForm);
expect(isset($badUtf8['errors']['barrio']) && $badUtf8['data']['barrio'] === '', 'envío: texto con UTF-8 inválido rechazado (no llega al pedido)', $badUtf8['errors']);
expect(checkout_fallback_url(['origen' => 'carrito'], false) === '/carrito' && checkout_fallback_url(['origen' => 'carrito'], true) === '/carrito?cambio=1', 'envío: sin nada que cobrar, el carrito vuelve a /carrito');
expect(checkout_fallback_url(['producto' => 'airpods-pro-3', 'sku' => 'NO'], false) === '/productos/airpods-pro-3' && checkout_fallback_url(['producto' => 'airpods-pro-3'], true) === '/productos/airpods-pro-3?cambio=1', 'envío: Comprar ahora sin nada que cobrar vuelve al producto (con ?cambio=1 si cambió)');
expect(checkout_fallback_url(['producto' => '../x'], false) === null && checkout_fallback_url(['producto' => 'no-existe-zz'], false) === null && checkout_fallback_url(['producto' => ['x']], false) === null && checkout_fallback_url([], false) === null, 'envío: entrada basura = 404');
expect(str_contains(content_security_policy(true), "form-action 'self' https://checkout.wompi.co;") && str_contains(content_security_policy(false), "form-action 'self';"), 'CSP: form-action permite el checkout de Wompi solo con Wompi activo');
expect(wompi() === null || str_contains(content_security_policy(), 'form-action \'self\' https://checkout.wompi.co'), 'CSP: con la configuración actual de Wompi, form-action lo incluye');
$cfgEnv = config('environment');
$cfgWompi = config('wompi');
$cfgMail = config('mail');
config_override('environment', 'production');
config_override('wompi', ['public_key' => 'pub_prod_x', 'integrity_secret' => 'prod_integrity_x']);
config_override('mail', ['transport' => 'file', 'username' => 'u', 'password' => 'p']);
$mailBlocker = static fn (): bool => (bool) array_filter(deploy_blockers(), static fn (string $b): bool => str_contains($b, 'correo SMTP de pedidos'));
$blockFile = $mailBlocker();
config_override('mail', ['transport' => 'smtp', 'username' => 'u', 'password' => '']);
$blockNoPass = $mailBlocker();
config_override('mail', ['transport' => 'smtp', 'username' => 'u', 'password' => 'p']);
$blockOk = $mailBlocker();
config_override('environment', 'staging');
config_override('mail', ['transport' => 'file', 'username' => '', 'password' => '']);
$blockStaging = $mailBlocker();
config_override('environment', 'production');
config_override('wompi', []);
$blockNoWompi = $mailBlocker();
config_override('environment', $cfgEnv);
config_override('wompi', $cfgWompi);
config_override('mail', $cfgMail);
expect($blockFile && $blockNoPass && !$blockOk && !$blockStaging && !$blockNoWompi, 'despliegue: con Wompi en producción exige SMTP con usuario y contraseña', compact('blockFile', 'blockNoPass', 'blockOk', 'blockStaging', 'blockNoWompi'));
$single = checkout_items(['producto' => 'airpods-pro-3', 'sku' => 'APP3', 'color' => 'Blanco']);
expect($single !== null && $single['source'] === 'producto' && $single['priced']['gateway'] === 776000, 'envío: Comprar ahora arma un pedido de un producto', $single);
expect(checkout_items(['producto' => 'airpods-pro-3', 'sku' => 'NO']) === null, 'envío: sku que no es del producto = sin pedido');
expect(checkout_items(['producto' => ['x'], 'sku' => 'APP3']) === null, 'envío: producto con arreglo = sin pedido');
expect(str_starts_with((string) buy_path('airpods-pro-3', 'APP3', 'Blanco'), '/pago/envio?') || wompi() === null, 'Comprar ahora lleva a la página de envío');

// --- Correo del pedido -------------------------------------------------------
use Taptop\Mail\SmtpMailer;

$msg = SmtpMailer::buildMessage('servicioalcliente@taptopmobile.com', 'Taptop Mobile', 'servicioalcliente@taptopmobile.com', 'Pedido pagado TT-1 · $817.000', "Línea 1\n.línea con punto", 'ana@example.com');
expect(str_contains($msg, "Subject: =?UTF-8?B?") && str_contains($msg, "Reply-To: ana@example.com\r\n") && str_contains($msg, "Content-Transfer-Encoding: base64\r\n"), 'correo: cabeceras y asunto UTF-8', $msg);
$threw = false;
try { SmtpMailer::buildMessage('a@b.co', 'X', "a@b.co\r\nBcc: x@y.co", 'Hola', 'x'); } catch (InvalidArgumentException) { $threw = true; }
expect($threw, 'correo: saltos de línea en cabeceras rechazados (sin inyección)');
$mailOrder = ['source' => 'carrito', 'lines' => cart_priced(cart_add([], 'APP3', 'Blanco'))['lines'], 'gateway' => 817000, 'cash' => 790000, 'shipping' => $v['data'], 'created_at' => 'x', 'mail' => null];
$mail = order_email('TT-ABCDEF123456', $mailOrder, ['id' => '1-2-3', 'payment_method_type' => 'CARD', 'amount_in_cents' => 81700000]);
expect(str_contains($mail['subject'], 'TT-ABCDEF123456') && str_contains($mail['body'], 'El Poblado') && str_contains($mail['body'], '1 × AirPods Pro 3'), 'correo: pedido con datos de envío y productos', $mail);
expect(str_starts_with(order_email('TT-ABCDEF123456', $mailOrder, ['id' => '1', 'amount_in_cents' => 100])['subject'], '⚠'), 'correo: monto distinto avisa en el asunto');

expect(str_starts_with(order_email('TT-ABCDEF123456', ['gateway' => 1000] + $mailOrder, ['id' => '1', 'amount_in_cents' => 100099])['subject'], '⚠'), 'correo: centavos sobrantes cuentan como monto distinto');
expect(str_contains(order_email('TT-ABCDEF123456', ['acepto_datos_at' => '2026-10-22T10:00:00-05:00'] + $mailOrder, ['id' => '1', 'amount_in_cents' => 81700000])['body'], 'Aceptó tratamiento de datos: 2026-10-22'), 'correo: fecha de autorización del nivel superior del pedido');

$mailDir = sys_get_temp_dir() . '/taptop-mail-' . bin2hex(random_bytes(4));
$ordersTmp = $mailDir . '-orders.json';
config_override('mail.transport', 'file');
config_override('mail.dir', $mailDir);
order_save('TT-ABCDEF123456', $mailOrder, $ordersTmp);
$approved = ['id' => '1-2-3', 'status' => 'APPROVED', 'payment_method_type' => 'CARD', 'amount_in_cents' => 81700000];
$notify1 = order_notify_if_approved('TT-ABCDEF123456', $approved, $ordersTmp);
$notify2 = order_notify_if_approved('TT-ABCDEF123456', $approved, $ordersTmp);
expect($notify1 && $notify2 && count(glob($mailDir . '/*.eml') ?: []) === 1 && (order_find('TT-ABCDEF123456', $ordersTmp)['mail']['status'] ?? null) === 'sent', 'correo: dos avisos, un solo correo (ambos sin pendientes)', glob($mailDir . '/*'));
order_save('TT-SENDING000001', $mailOrder, $ordersTmp);
order_claim_mail('TT-SENDING000001', $ordersTmp);
expect(order_notify_if_approved('TT-SENDING000001', $approved, $ordersTmp) === false, 'correo: con otro envío en curso queda pendiente (el webhook pide reintento)');
$eml = (string) file_get_contents((glob($mailDir . '/*.eml') ?: [''])[0]);
expect(str_contains($eml, "\r\nReply-To: ana@example.com\r\n") && str_contains($eml, 'To: ' . config('orders_email')), 'correo: archivo con destino y Reply-To al cliente');
order_save('TT-DECLINED00001', $mailOrder, $ordersTmp);
$notifyDeclined = order_notify_if_approved('TT-DECLINED00001', ['status' => 'DECLINED'] + $approved, $ordersTmp);
$notifyMissing = order_notify_if_approved('TT-NOEXISTE0001', $approved, $ordersTmp);
expect($notifyDeclined && $notifyMissing && count(glob($mailDir . '/*.eml') ?: []) === 1, 'correo: no aprobado o sin pedido no envía (y no queda pendiente)');
config_override('mail.transport', 'smtp');
config_override('mail.username', '');
order_save('TT-NOCREDS000001', $mailOrder, $ordersTmp);
expect(send_mail('x@example.com', 'Hola', 'x') === false, 'correo: SMTP sin credenciales devuelve false');
$notifyNoCreds = order_notify_if_approved('TT-NOCREDS000001', $approved, $ordersTmp);
expect($notifyNoCreds === false && order_find('TT-NOCREDS000001', $ordersTmp)['mail'] === null, 'correo: sin credenciales devuelve false y el pedido queda para reintentar');
config_override('mail.transport', 'file');
foreach (glob($mailDir . '/*') ?: [] as $f) { @unlink($f); }
@rmdir($mailDir);
@unlink($ordersTmp);
@unlink($ordersTmp . '.lock');

// --- Resultado del pago --------------------------------------------------------
$resOrder = ['source' => 'carrito', 'lines' => cart_priced(cart_add([], 'APP3', 'Blanco'))['lines'], 'gateway' => 817000, 'cash' => 790000, 'shipping' => $v['data'], 'created_at' => 'x', 'mail' => null];
$resTx = ['status' => 'APPROVED', 'amount_in_cents' => 81700000, 'payment_method_type' => 'CARD'];
$rv = payment_result_view('TT-ABCDEF123456', $resTx, $resOrder);
expect($rv['amount_matches'] === true && $rv['clear_cart'] === true && count($rv['lines']) === 1 && $rv['wa_link'] === null && !str_contains(json_encode($rv), 'El Poblado'), 'resultado: carrito aprobado coincide, vacía carrito y no muestra datos de envío', $rv);
$rv = payment_result_view('TT-ABCDEF123456', ['status' => 'DECLINED'] + $resTx, $resOrder);
expect($rv['clear_cart'] === false && $rv['wa_link'] !== null && str_contains($rv['wa_link'], rawurlencode('Total por transferencia')) && str_contains($rv['back_url'], '/carrito'), 'resultado: rechazado conserva carrito y ofrece WhatsApp', $rv);
$rv = payment_result_view('TT-ABCDEF123456', $resTx, ['source' => 'producto'] + $resOrder);
expect($rv['clear_cart'] === false && $rv['amount_matches'] === true, 'resultado: compra directa no vacía el carrito');
$rv = payment_result_view('TT-APP3-0123456789', ['amount_in_cents' => 77600000] + $resTx, null);
expect($rv['amount_matches'] === true && count($rv['lines']) === 1 && str_contains($rv['lines'][0]['label'], 'AirPods Pro 3') && $rv['clear_cart'] === false, 'resultado: referencia vieja usa el SKU', $rv);
$rv = payment_result_view('TT-ABCDEF123456', ['amount_in_cents' => 81700099] + $resTx, $resOrder);
expect($rv['amount_matches'] === false, 'resultado: centavos distintos no coinciden');
expect(expected_amount_for('TT-ABCDEF123456', $resOrder) === 81700000 && expected_amount_for('TT-APP3-0123456789', null) === 77600000 && expected_amount_for('TT-ABCDEF123456', null) === null, 'monto esperado: pedido, SKU viejo y desconocido');

echo "\n{$passed} pasaron, {$failed} fallaron\n";
exit($failed === 0 ? 0 : 1);
