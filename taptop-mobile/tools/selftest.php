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
expect(preg_match('/^TT-[0-9A-F]{12}$/', $ref) === 1 && Wompi::isOwnReference($ref) && Wompi::isOwnReference('TT-APP3-0123456789'), 'Wompi: referencia nueva y vieja reconocidas', $ref);
expect(!Wompi::isOwnReference('otra-tienda-123'), 'Wompi: referencia ajena rechazada (nueva forma)');
$tmpOrders = tempnam(sys_get_temp_dir(), 'tt-orders');
order_save($ref, ['source' => 'carrito', 'lines' => [], 'gateway' => 817000, 'cash' => 790000, 'shipping' => [], 'created_at' => 'x', 'mail' => null], $tmpOrders);
expect(order_find($ref, $tmpOrders)['gateway'] === 817000 && order_find('TT-000000000000', $tmpOrders) === null, 'pedido: se guarda y se encuentra por referencia');
expect(order_claim_mail($ref, $tmpOrders) === true && order_claim_mail($ref, $tmpOrders) === false, 'pedido: el correo solo se reclama una vez');
order_finish_mail($ref, false, $tmpOrders);
expect(order_claim_mail($ref, $tmpOrders) === true, 'pedido: si el envío falla, se puede reintentar');
order_finish_mail($ref, true, $tmpOrders);
expect(order_claim_mail($ref, $tmpOrders) === false && order_find($ref, $tmpOrders)['mail']['status'] === 'sent', 'pedido: enviado no se reenvía');
expect(!Wompi::isOwnReference("TT-0123456789AB
"), 'Wompi: referencia con salto de línea final rechazada');
$keep = Wompi::newReference();
order_save($keep, ['source' => 'carrito', 'gateway' => 1], $tmpOrders);
$threw = false;
try {
    order_save(Wompi::newReference(), ['shipping' => ['name' => "\xB1\x31"]], $tmpOrders);
} catch (Throwable $e) {
    $threw = true;
}
expect($threw && order_find($keep, $tmpOrders)['gateway'] === 1, 'pedido: si falla la codificación, el almacén no se borra');
unlink($tmpOrders);
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
$cartLines = cart_add(cart_add([], 'IP18PM-256-BUR', 'Borgoña'), 'APP3', null);
$priced = cart_priced($cartLines);
expect($priced['gateway'] === 6807000 && $priced['cash'] === 6590000 && $priced['count'] === 2, 'carrito: totales desde el catálogo', $priced);
expect(cart_priced([['sku' => 'NO-EXISTE', 'color' => null, 'qty' => 1]])['lines'] === [], 'carrito: sku fuera del catálogo omitido');
expect(cart_set_qty($cartLines, 0, 0) === [['sku' => 'APP3', 'color' => null, 'qty' => 1]], 'carrito: qty 0 quita la línea');
expect(cart_parse(cart_encode($cartLines)) === $cartLines, 'carrito: cookie ida y vuelta (con tildes)');
$waCart = wa_message_cart($priced);
expect(str_contains($waCart, '• 1 × AirPods Pro 3 — $790.000') && str_ends_with($waCart, 'Total por transferencia: $6.590.000'), 'carrito: mensaje de WhatsApp con detalle y total por transferencia', $waCart);
$fakeFind = static fn (string $sku): ?array => ['TP-128' => ['product' => $normalized, 'variant' => $normalized['variants'][0]], 'TP-256' => ['product' => $normalized, 'variant' => $normalized['variants'][1]]][$sku] ?? null;
$soldOut = cart_priced([['sku' => 'TP-128', 'color' => 'Azul', 'qty' => 1], ['sku' => 'TP-256', 'color' => 'Azul', 'qty' => 2]], $fakeFind);
expect($soldOut['lines'][1]['available'] === false && $soldOut['gateway'] === 1033000 && $soldOut['count'] === 1 && $soldOut['has_unavailable'], 'carrito: agotado se muestra pero no suma', $soldOut);

$site = (string) config('site_url');
expect(request_is_same_origin(['HTTP_ORIGIN' => rtrim($site, '/')]), 'POST: mismo origen aceptado');
expect(!request_is_same_origin(['HTTP_ORIGIN' => 'https://otro-sitio.com']), 'POST: otro origen rechazado');
expect(request_is_same_origin(['HTTP_REFERER' => rtrim($site, '/') . '/carrito']), 'POST: sin Origin, Referer propio aceptado');
expect(!request_is_same_origin([]), 'POST: sin Origin ni Referer rechazado');
expect(!request_is_same_origin(['HTTP_ORIGIN' => rtrim($site, '/') . '.evil.com']), 'POST: host que solo empieza igual rechazado');
$noColorLine = cart_priced([['sku' => 'APP3', 'color' => null, 'qty' => 1]])['lines'][0];
expect(array_key_exists('image', $noColorLine) && is_string($noColorLine['kind'] ?? null) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($noColorLine['hex'] ?? '')) === 1, 'carrito: línea trae kind y hex para la silueta si no hay foto', $noColorLine);

echo "\n{$passed} pasaron, {$failed} fallaron\n";
exit($failed === 0 ? 0 : 1);
