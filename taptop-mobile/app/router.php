<?php
/**
 * Enrutador: resuelve la ruta y prepara los datos de cada vista.
 * Rutas: /, /productos, /productos/{slug}, /contacto, /garantia, /terminos, /privacidad,
 * /robots.txt, /sitemap.xml y /llms.txt.
 * SPIKE Wompi: /pago/envio, /pago/resultado y /webhooks/wompi (ver helpers/payments.php). Todo lo demás responde 404 real.
 */

declare(strict_types=1);

const POLICY_PAGES = ['garantia', 'terminos', 'privacidad'];

function dispatch(string $method, string $uri): void
{
    $path = normalize_request_path($uri);
    if ($path === null) {
        page_not_found();
        return;
    }
    if ($path === '/index.php') {
        redirect('/', 301);
    }
    if ($path !== '/' && str_ends_with($path, '/')) {
        $query = parse_url($uri, PHP_URL_QUERY);
        redirect(rtrim($path, '/') . (is_string($query) && $query !== '' ? '?' . $query : ''), 301);
    }
    $method = strtoupper($method);
    // SPIKE Wompi: único endpoint que recibe POST (eventos firmados de la pasarela).
    if ($path === '/webhooks/wompi') {
        webhook_wompi($method);
        return;
    }
    // Carrito y envío: POST solo desde este mismo sitio.
    if (in_array($path, ['/carrito/agregar', '/carrito/actualizar', '/pago/envio'], true) && $method === 'POST') {
        if (!request_is_same_origin($_SERVER)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Solicitud no permitida.';
            return;
        }
        if ($path === '/carrito/agregar') {
            cart_post_add();
            return;
        }
        if ($path === '/carrito/actualizar') {
            cart_post_update();
            return;
        }
        checkout_post();
        return;
    }
    if ($method !== 'GET' && $method !== 'HEAD') {
        http_response_code(405);
        header('Allow: GET, HEAD');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Método no permitido.';
        return;
    }

    if ($path === '/') {
        page_home();
    } elseif ($path === '/productos') {
        page_products();
    } elseif (preg_match('#^/productos/([a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $match) === 1) {
        $legacy = legacy_product_slug($match[1]);
        if ($legacy !== null) {
            redirect('/productos/' . $legacy, 301);
        }
        page_product($match[1]);
    } elseif ($path === '/carrito') {
        page_cart();
    } elseif ($path === '/contacto') {
        page_contact();
    } elseif (in_array(ltrim($path, '/'), POLICY_PAGES, true)) {
        page_policy(ltrim($path, '/'));
    } elseif ($path === '/pago/envio') {
        page_checkout();
    } elseif ($path === '/pago/resultado') {
        page_pay_result();
    } elseif ($path === '/robots.txt') {
        page_robots();
    } elseif ($path === '/sitemap.xml') {
        page_sitemap();
    } elseif ($path === '/llms.txt') {
        page_llms();
    } else {
        page_not_found();
    }
}

/** Ruta decodificada y limpia, o null si trae algo sospechoso. */
function normalize_request_path(string $uri): ?string
{
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }
    $path = rawurldecode($path);
    // Sin caracteres de control ni espacios: el navegador borra tabs y saltos de una URL, y
    // "/%09/otro-sitio.com/" terminaría redirigiendo a "//otro-sitio.com".
    if (preg_match('/[\x00-\x20\x7F]/', $path) === 1 || str_contains($path, '\\') || str_contains($path, '..')) {
        return null;
    }
    $path = preg_replace('#/{2,}#', '/', $path) ?? $path;
    return $path === '' ? '/' : $path;
}

/** Metadatos de página para head.php. */
function page_meta(string $id, ?string $title, string $path, array $scripts = [], array $extra = []): array
{
    $brand = (string) config('business_name', 'Taptop Mobile');
    return $extra + [
        'id' => $id,
        'title' => $title === null ? (string) config('seo_title') : $title . ' | ' . $brand,
        'description' => (string) config('seo_description'),
        'path' => $path,
        'og_image' => absolute_url(asset('images/brand/og-default.jpg')),
        'og_type' => 'website',
        'scripts' => $scripts,
    ];
}

// ---------------------------------------------------------------------------
// Páginas
// ---------------------------------------------------------------------------

function page_home(): void
{
    $presenter = product_presenter();
    $featured = [];
    foreach (product_repository()->findMany((array) config('home_featured', [])) as $product) {
        $card = $presenter->card($product, 'inicio');
        // Destacados: solo lo que se puede comprar hoy.
        if ($card !== null && !$card['all_sold_out']) {
            $featured[] = $card;
        }
    }
    render_page('home', [
        'meta' => page_meta('home', null, '/', ['logo-animation.js', 'carousel.js', 'color-swatches.js', 'dock.js', 'aurora-bg.js']),
        'hero' => load_hero_slides(),
        'featured' => array_slice($featured, 0, (int) config('home_featured_limit', 8)),
        'categories' => category_nav(null, null),
    ]);
}

function page_products(): void
{
    $category = request_query('categoria');
    if ($category !== null && !in_array($category, nav_category_keys(), true)) {
        $category = null;
    }
    $brand = request_query('marca');
    if ($brand !== null && !in_array($brand, (array) config('brand_filters', []), true)) {
        $brand = null;
    }

    $presenter = product_presenter();
    $cards = [];
    foreach (product_repository()->filter($category !== null ? category_data_keys($category) : [], $brand) as $product) {
        $card = $presenter->card($product, 'productos');
        if ($card !== null) {
            $cards[] = $card;
        }
    }

    $heading = $category !== null ? category_label($category) : 'Productos';
    if ($brand !== null) {
        $heading .= ' de ' . brand_label($brand);
    }
    $lede = catalog_lede($category, $brand);
    // SEO: el filtro de marca no es una página propia; su canonical es la misma vista sin marca.
    $seoTitle = $brand !== null ? $heading
        : ($category !== null ? category_label($category) . ' originales en Colombia' : 'Celulares y tecnología Apple y Samsung originales');
    $crumbs = [['Inicio', '/'], ['Productos', '/productos']];
    if ($category !== null) {
        $crumbs[] = [category_label($category), url('/productos', ['categoria' => $category])];
    }
    render_page('products', [
        'meta' => page_meta('products', $seoTitle, url('/productos', ['categoria' => $category, 'marca' => $brand]), ['color-swatches.js'], [
            'description' => seo_clip($lede),
            'canonical' => url('/productos', ['categoria' => $category]),
            'jsonld' => [breadcrumb_jsonld($crumbs)],
        ]),
        'heading' => $heading,
        'lede' => catalog_lede($category, $brand),
        'cards' => sold_out_last($cards),
        'categories' => category_nav($category, $brand),
        'active_category' => $category,
        'active_brand' => $brand,
    ]);
}

/** Productos que se juntaron o renombraron: slug viejo → slug actual (los enlaces viejos no se rompen). */
function legacy_product_slug(string $slug): ?string
{
    return [
        'apple-watch-series-12-42mm' => 'apple-watch-series-12',
        'apple-watch-series-12-46mm' => 'apple-watch-series-12',
    ][$slug] ?? null;
}

function page_product(string $slug): void
{
    $product = product_repository()->findBySlug($slug);
    if ($product === null) {
        page_not_found();
        return;
    }
    $detail = product_presenter()->detail($product, is_string($_GET['config'] ?? null) ? $_GET['config'] : null);
    // Vista previa (WhatsApp, redes): foto del color pedido en ?color=, si no la del color inicial.
    $ogImage = $detail['media']['image'];
    $colorParam = is_string($_GET['color'] ?? null) ? $_GET['color'] : '';
    foreach ($product['colors'] ?? [] as $color) {
        if ($colorParam !== '' && color_slug((string) $color['name']) === $colorParam && !empty($color['image'])) {
            $ogImage = $color['image'];
            break;
        }
    }
    $ogImage = $ogImage !== null ? absolute_url($ogImage) : null;
    $navCategory = nav_category_for($product['category']);
    $description = product_seo_description($product);
    $prices = product_cash_prices($product);
    render_page('product-detail', [
        'meta' => page_meta('product', $product['name'], url('/productos/' . $slug), ['color-swatches.js', 'slider.js', 'cart.js'], array_filter([
            'og_image' => $ogImage,
            'title' => product_seo_title($product),
            'description' => $description,
            'og_type' => 'product',
            'og_price' => $prices !== [] ? min($prices) : null,
            'jsonld' => [
                product_jsonld($product, $detail, $description),
                breadcrumb_jsonld([
                    ['Inicio', '/'],
                    ['Productos', '/productos'],
                    [category_label($navCategory), url('/productos', ['categoria' => $navCategory])],
                    [(string) $product['name'], '/productos/' . $slug],
                ]),
            ],
        ])),
        'product' => $detail,
        'nav_category' => $navCategory,
        'changed' => isset($_GET['cambio']),
    ]);
}

function page_contact(): void
{
    render_page('contact', [
        'meta' => page_meta('contact', 'Contáctanos', '/contacto', [], [
            'description' => seo_clip('Escríbenos por WhatsApp al ' . config('whatsapp_display') . ', '
                . mb_strtolower((string) config('support_days_label')) . ' de ' . rtrim((string) config('support_hours_label'), '.')
                . '. Te asesoramos para comprar Apple y Samsung originales.'),
        ]),
    ]);
}

function page_cart(): void
{
    header('Cache-Control: no-store');
    $lines = cart_read();
    $cart = cart_priced($lines);
    if (count($cart['lines']) < count($lines)) {
        // Hay SKU que salieron del catálogo: la cookie se reescribe sin ellos (antes de pintar nada).
        $kept = array_map(static fn (array $l): array => ['sku' => $l['sku'], 'color' => $l['color'], 'qty' => $l['qty']], $cart['lines']);
        cart_write($kept);
        $cart = cart_priced($kept);
    }
    render_page('cart', [
        'meta' => page_meta('cart', 'Carrito', '/carrito', ['cart.js'], ['noindex' => true]),
        'cart' => $cart,
        'changed' => isset($_GET['cambio']),
    ]);
}

function page_policy(string $key): void
{
    require_once APP_PATH . '/content/policies.php';
    $policy = policy_pages()[$key];
    render_page('policy', [
        'meta' => page_meta('policy', $policy['title'], '/' . $key, [], [
            'description' => seo_clip((string) ($policy['intro'] ?? '')) ?: (string) config('seo_description'),
        ]),
        'policy' => $policy,
        'policy_key' => $key,
    ]);
}

function page_not_found(): void
{
    render_page('404', [
        'meta' => page_meta('404', 'Página no encontrada', current_path(), [], ['noindex' => true]),
    ], 404);
}

function page_robots(): void
{
    header('Content-Type: text/plain; charset=UTF-8');
    $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /pago/', 'Disallow: /carrito', 'Disallow: /webhooks/'];
    if (!is_noindex()) {
        $lines[] = '';
        $lines[] = 'Sitemap: ' . absolute_url('/sitemap.xml');
    }
    echo implode("\n", $lines) . "\n";
}

function page_sitemap(): void
{
    $catalogFile = APP_PATH . '/data/products.json';
    $lastmod = date('Y-m-d', is_file($catalogFile) ? (int) filemtime($catalogFile) : time());
    $paths = ['/', '/productos'];
    foreach (nav_category_keys() as $key) {
        $paths[] = url('/productos', ['categoria' => $key]);
    }
    array_push($paths, '/contacto', '/garantia', '/terminos', '/privacidad');
    foreach (product_repository()->all() as $product) {
        $paths[] = '/productos/' . $product['slug'];
    }
    header('Content-Type: application/xml; charset=UTF-8');
    if (is_noindex()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($paths as $path) {
        echo '  <url><loc>' . e(absolute_url($path)) . '</loc><lastmod>' . $lastmod . '</lastmod></url>' . "\n";
    }
    echo '</urlset>' . "\n";
}

/**
 * /llms.txt: resumen en Markdown para asistentes de IA (llmstxt.org). Se arma con el catálogo
 * y la config, así no se desactualiza. Sin precios: viven solo en la página de cada producto.
 */
function page_llms(): void
{
    $brand = (string) config('business_name');
    $lines = [
        '# ' . $brand,
        '',
        '> Tienda en línea de Colombia que vende celulares y tecnología Apple y Samsung 100 % originales: '
            . 'iPhone, Galaxy, Mac, iPad, Apple Watch, AirPods y accesorios. Envío a toda Colombia y garantía directa de '
            . $brand . '.',
        '',
        '- Pagos: cada producto tiene dos precios. El más bajo es por consignación, depósito en efectivo o transferencia; '
            . 'el otro es con tarjeta de crédito o débito, PSE o Nequi a través de la pasarela de pago.',
        '- Envío gratis desde ' . format_price((int) config('free_shipping_min', 0)) . '; por debajo, ' . format_price((int) config('shipping_cost', 0)) . '. Entrega en ' . delivery_label() . '.',
        '- Garantía ' . $brand . ': ' . warranty_summary() . '.',
        '- Atención por WhatsApp ' . config('whatsapp_display') . ': ' . mb_strtolower((string) config('support_days_label'))
            . ', ' . config('support_hours_label') . ' (' . config('support_timezone_label') . ').',
        '- Precios en pesos colombianos (COP), siempre actualizados en la página de cada producto.',
        '',
        '## Categorías',
        '',
        '- [Todos los productos](' . absolute_url('/productos') . ')',
    ];
    foreach (nav_category_keys() as $key) {
        $lines[] = '- [' . category_label($key) . '](' . absolute_url(url('/productos', ['categoria' => $key])) . '): '
            . (string) config('categories.' . $key . '.lede');
    }
    foreach (nav_category_keys() as $key) {
        $products = product_repository()->filter(category_data_keys($key));
        if ($products === []) {
            continue;
        }
        $lines[] = '';
        $lines[] = '## ' . category_label($key);
        $lines[] = '';
        foreach ($products as $product) {
            $lines[] = '- [' . $product['name'] . '](' . absolute_url('/productos/' . $product['slug']) . ')'
                . llms_product_summary($product);
        }
    }
    $lines[] = '';
    $lines[] = '## Políticas y contacto';
    $lines[] = '';
    $lines[] = '- [Garantía, cambios y devoluciones](' . absolute_url('/garantia') . ')';
    $lines[] = '- [Términos y condiciones](' . absolute_url('/terminos') . ')';
    $lines[] = '- [Privacidad y datos personales](' . absolute_url('/privacidad') . ')';
    $lines[] = '- [Contacto](' . absolute_url('/contacto') . ')';

    header('Content-Type: text/plain; charset=UTF-8');
    if (is_noindex()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
    echo implode("\n", $lines) . "\n";
}

/** ": 256 GB, 512 GB · colores: Negro, Plata" a partir de las variantes y colores del producto. */
function llms_product_summary(array $product): string
{
    $storage = [];
    foreach ((array) ($product['variants'] ?? []) as $variant) {
        $value = $variant['storage'] ?? null;
        if (is_string($value) && $value !== '' && !in_array($value, $storage, true)) {
            $storage[] = $value;
        }
    }
    $colors = [];
    foreach ((array) ($product['colors'] ?? []) as $color) {
        $name = is_array($color) ? ($color['name'] ?? null) : null;
        if (is_string($name) && $name !== '' && !in_array($name, $colors, true)) {
            $colors[] = $name;
        }
    }
    $parts = [];
    if ($storage !== []) {
        $parts[] = implode(', ', $storage);
    }
    if ($colors !== []) {
        $parts[] = 'colores: ' . implode(', ', $colors);
    }
    return $parts === [] ? '' : ': ' . implode(' · ', $parts);
}

// ---------------------------------------------------------------------------
// SEO: descripciones y datos estructurados
// ---------------------------------------------------------------------------

/** Recorta a ~$max caracteres sin cortar palabras (meta description). */
function seo_clip(string $text, int $max = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, ' ,.;:') . '…';
}

/** Precios por consignación de las variantes disponibles (o de todas, si está agotado). */
function product_cash_prices(array $product): array
{
    $all = [];
    $available = [];
    foreach ((array) ($product['variants'] ?? []) as $variant) {
        $price = (int) ($variant['price_cash'] ?? 0);
        if ($price <= 0) {
            continue;
        }
        $all[] = $price;
        if (!empty($product['available']) && !empty($variant['available'])) {
            $available[] = $price;
        }
    }
    return $available !== [] ? $available : $all;
}

/** "Compra iPhone 18 Pro Max original en Colombia desde $5.400.000. Garantía de 1 año…" */
function product_seo_description(array $product): string
{
    $prices = product_cash_prices($product);
    $text = 'Compra ' . $product['name'] . ' original en Colombia';
    if ($prices !== []) {
        $text .= (count(array_unique($prices)) > 1 ? ' desde ' : ' por ') . format_price(min($prices));
    }
    $text .= '. Garantía de ' . warranty_label(warranty_months_for((string) $product['category']))
        . ' con ' . config('business_name') . ' y envío a toda Colombia.';
    $summary = ltrim(llms_product_summary($product), ': ');
    if ($summary !== '' && mb_strlen($text . ' ' . $summary) <= 160) {
        $text .= ' ' . mb_strtoupper(mb_substr($summary, 0, 1)) . mb_substr($summary, 1) . '.';
    }
    return seo_clip($text);
}

/** Título de la ficha: "{nombre} precio en Colombia | Taptop Mobile", sin la marca si queda muy largo. */
function product_seo_title(array $product): string
{
    $title = $product['name'] . ' precio en Colombia';
    $full = $title . ' | ' . config('business_name');
    return mb_strlen($full) <= 65 ? $full : $title;
}

/** BreadcrumbList a partir de [[nombre, ruta], …]. */
function breadcrumb_jsonld(array $items): array
{
    $list = [];
    foreach (array_values($items) as $i => [$name, $path]) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => absolute_url($path)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

/** Product + AggregateOffer (precio por consignación, COP) para resultados con precio. */
function product_jsonld(array $product, array $detail, string $description): array
{
    $images = [];
    foreach ((array) ($product['colors'] ?? []) as $color) {
        if (!empty($color['image'])) {
            $images[] = absolute_url((string) $color['image']);
        }
    }
    if ($images === [] && !empty($detail['media']['image'])) {
        $images[] = absolute_url((string) $detail['media']['image']);
    }
    $prices = product_cash_prices($product);
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => (string) $product['name'],
        'description' => $description,
        'brand' => ['@type' => 'Brand', 'name' => brand_label((string) $product['brand'])],
        'category' => category_label(nav_category_for((string) $product['category'])),
        'url' => absolute_url('/productos/' . $product['slug']),
    ];
    if ($images !== []) {
        $data['image'] = array_values(array_unique($images));
    }
    if ($prices !== []) {
        $data['offers'] = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'COP',
            'lowPrice' => min($prices),
            'highPrice' => max($prices),
            'offerCount' => count($prices),
            'availability' => $detail['all_sold_out'] ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'url' => absolute_url('/productos/' . $product['slug']),
            'seller' => ['@type' => 'Organization', 'name' => (string) config('business_name')],
            'hasMerchantReturnPolicy' => merchant_return_policy_jsonld(),
        ];
        // Gratis si todo el rango supera el mínimo; shipping_cost si todo está por debajo;
        // un rango que cruza el umbral no publica tarifa (sería falsa para parte de las variantes).
        $min = (int) config('free_shipping_min');
        $rate = min($prices) >= $min ? 0 : (max($prices) < $min ? (int) config('shipping_cost') : null);
        if ($rate !== null) {
            [$daysMin, $daysMax] = array_map('intval', (array) config('delivery_days', [2, 5]));
            $data['offers']['shippingDetails'] = [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => $rate, 'currency' => 'COP'],
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'CO'],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 0, 'unitCode' => 'DAY'],
                    'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => $daysMin, 'maxValue' => $daysMax, 'unitCode' => 'DAY'],
                    'businessDays' => [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => array_map(static fn (string $d): string => 'https://schema.org/' . $d, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                    ],
                ],
            ];
        }
    }
    return $data;
}

/** Retracto del art. 47 Ley 1480: días desde la entrega, devolución por envío (la paga el cliente). */
function merchant_return_policy_jsonld(): array
{
    return [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => 'CO',
        'returnPolicyCountry' => 'CO',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays' => (int) config('retracto_days', 5),
        'returnMethod' => 'https://schema.org/ReturnByMail',
        'merchantReturnLink' => absolute_url('/terminos#retracto'),
    ];
}

// ---------------------------------------------------------------------------
// Apoyo
// ---------------------------------------------------------------------------

function request_query(string $key): ?string
{
    $value = $_GET[$key] ?? null;
    if (!is_string($value)) {
        return null;
    }
    $value = strtolower(trim($value));
    return preg_match('/^[a-z0-9-]{1,40}$/', $value) === 1 ? $value : null;
}

/** Categorías que aparecen en el menú (las de 'nav' => false se agrupan en otra). */
function nav_category_keys(): array
{
    $keys = [];
    foreach ((array) config('categories', []) as $key => $category) {
        if (($category['nav'] ?? true) !== false) {
            $keys[] = (string) $key;
        }
    }
    return $keys;
}

/** Categorías de datos que agrupa una categoría del menú (Accesorios incluye Adaptadores). */
function category_data_keys(string $navKey): array
{
    return (array) (config('categories.' . $navKey . '.includes') ?? [$navKey]);
}

function nav_category_for(string $dataCategory): string
{
    foreach (nav_category_keys() as $key) {
        if (in_array($dataCategory, category_data_keys($key), true)) {
            return $key;
        }
    }
    return $dataCategory;
}

/** Enlaces del filtro de categorías con su conteo. */
function category_nav(?string $current, ?string $brand): array
{
    $repository = product_repository();
    $items = [[
        'key' => null,
        'label' => 'Todos',
        'href' => url('/productos', ['marca' => $brand]),
        'count' => count($repository->filter([], $brand)),
        'current' => $current === null,
    ]];
    foreach (nav_category_keys() as $key) {
        $items[] = [
            'key' => $key,
            'label' => category_label($key),
            'href' => url('/productos', ['categoria' => $key, 'marca' => $brand]),
            'count' => count($repository->filter(category_data_keys($key), $brand)),
            'current' => $current === $key,
        ];
    }
    return $items;
}

/**
 * Slides del hero desde hero-slides.json, validados. Un slide inválido se omite y queda
 * en el log; si no queda ninguno, el hero muestra solo la marca.
 * @return array{autoplay_ms: int, slides: list<array>}
 */
function load_hero_slides(): array
{
    $fallback = [
        'id' => 'marca',
        'type' => 'model',
        'tab' => 'Taptop',
        'title' => (string) config('business_name'),
        'subtitle' => 'Apple y Samsung originales. Envío a toda Colombia.',
        'cta' => ['label' => 'Ver productos', 'href' => '/productos'],
        'duration_ms' => 6000,
    ];
    $data = load_json_file(APP_PATH . '/data/hero-slides.json');
    $autoplay = (int) ($data['autoplay_ms'] ?? 3000);
    $autoplay = max(2000, min(15000, $autoplay));
    $slides = [];
    foreach ((array) ($data['slides'] ?? []) as $i => $slide) {
        $problem = hero_slide_problem($slide);
        if ($problem !== null) {
            app_log('warning', sprintf('Slide #%d del hero omitido: %s', $i + 1, $problem));
            continue;
        }
        $slide['duration_ms'] = max(2000, min(20000, (int) ($slide['duration_ms'] ?? $autoplay)));
        $slide['image'] ??= null;
        $slide['placeholder'] ??= null;
        $slides[] = $slide;
    }
    return ['autoplay_ms' => $autoplay, 'slides' => $slides !== [] ? $slides : [$fallback]];
}

function hero_image_src_ok(mixed $image): bool
{
    return is_array($image) && is_string($image['src'] ?? null)
        && preg_match('#^/assets/images/hero/[A-Za-z0-9._-]+\.(webp|avif|jpe?g|png)$#', $image['src']) === 1;
}

function hero_slide_problem(mixed $slide): ?string
{
    if (!is_array($slide)) {
        return 'no es un objeto';
    }
    foreach (['id', 'type', 'tab', 'title', 'subtitle'] as $field) {
        if (!is_string($slide[$field] ?? null) || trim($slide[$field]) === '') {
            return "falta {$field}";
        }
    }
    if (preg_match('/^[a-z0-9-]{1,40}$/', $slide['id']) !== 1) {
        return 'id inválido';
    }
    if (!in_array($slide['type'], ['model', 'image'], true)) {
        return 'type debe ser model o image';
    }
    if (mb_strlen($slide['tab']) > 12 || mb_strlen($slide['title']) > 60 || mb_strlen($slide['subtitle']) > 140) {
        return 'textos demasiado largos (tab 12, title 60, subtitle 140)';
    }
    $cta = $slide['cta'] ?? null;
    if (!is_array($cta) || !is_string($cta['label'] ?? null) || !is_string($cta['href'] ?? null)
        || ($cta['href'] !== 'whatsapp' && preg_match('#^/[a-z0-9/_-]*(\?categoria=[a-z0-9-]+)?$#', $cta['href']) !== 1)) {
        return 'cta inválido (href debe ser una ruta interna o "whatsapp")';
    }
    $image = $slide['image'] ?? null;
    if ($image !== null) {
        if (!hero_image_src_ok($image)) {
            return 'image debe ser {"src": "/assets/images/hero/archivo.webp", "width", "height"}';
        }
        if (!in_array($image['fit'] ?? 'cover', ['cover', 'cutout'], true)) {
            return 'image.fit debe ser "cover" (foto de fondo completo) o "cutout" (equipo recortado flotando)';
        }
        if (isset($image['mobile']) && !hero_image_src_ok($image['mobile'])) {
            return 'image.mobile debe ser {"src": "/assets/images/hero/archivo-movil.webp", "width", "height"}';
        }
    }
    $placeholder = $slide['placeholder'] ?? null;
    if ($placeholder !== null) {
        $colors = $placeholder['colors'] ?? null;
        if (!is_array($colors) || $colors === [] || count($colors) > 4) {
            return 'placeholder.colors debe tener de 1 a 4 colores';
        }
        foreach ($colors as $hex) {
            if (!is_string($hex) || preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) !== 1) {
                return 'placeholder.colors con un hex inválido';
            }
        }
        // Opcional: una silueta por color (p. ej., iPad de frente y de espaldas).
        $kinds = $placeholder['kinds'] ?? null;
        if ($kinds !== null && (!is_array($kinds) || count($kinds) !== count($colors))) {
            return 'placeholder.kinds debe tener un tipo por color';
        }
        foreach ((array) $kinds as $kind) {
            if (!is_string($kind) || preg_match('/^[a-z0-9-]{1,20}$/', $kind) !== 1) {
                return 'placeholder.kinds con un tipo inválido';
            }
        }
    }
    return null;
}
