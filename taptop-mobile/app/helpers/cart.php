<?php

declare(strict_types=1);

/**
 * Carrito de compras: vive en una cookie (sin base de datos) con [sku, color, qty] por línea.
 * La cookie nunca trae precios: todo precio sale del catálogo en el servidor.
 */

const CART_COOKIE = 'tt_cart';
const CART_MAX_QTY = 5;
const CART_MAX_LINES = 10;

/**
 * Cookie cruda (JSON `[[sku, color|null, qty], ...]`) a líneas válidas. Lo mal formado se descarta.
 * @return list<array{sku: string, color: ?string, qty: int}>
 */
function cart_parse(string $raw): array
{
    $data = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        return [];
    }
    $lines = [];
    foreach ($data as $item) {
        if (!is_array($item) || !array_is_list($item) || count($item) !== 3) {
            continue;
        }
        [$sku, $color, $qty] = $item;
        if (!is_string($sku) || preg_match('/^[A-Za-z0-9-]{1,40}$/', $sku) !== 1) {
            continue;
        }
        if ($color !== null && (!is_string($color) || $color === '' || mb_strlen($color) > 40)) {
            continue;
        }
        if (!is_int($qty)) {
            continue;
        }
        $qty = max(1, min(CART_MAX_QTY, $qty));
        foreach ($lines as &$line) {
            if ($line['sku'] === $sku && $line['color'] === $color) {
                $line['qty'] = min(CART_MAX_QTY, $line['qty'] + $qty);
                unset($line);
                continue 2;
            }
        }
        unset($line);
        $lines[] = ['sku' => $sku, 'color' => $color, 'qty' => $qty];
    }
    return array_slice($lines, 0, CART_MAX_LINES);
}

/** Líneas a texto de cookie. */
function cart_encode(array $lines): string
{
    return json_encode(
        array_map(static fn (array $l): array => [$l['sku'], $l['color'], $l['qty']], array_values($lines)),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

/** @return list<array{sku: string, color: ?string, qty: int}> */
function cart_read(): array
{
    $raw = $_COOKIE[CART_COOKIE] ?? '';
    return is_string($raw) ? cart_parse($raw) : [];
}

/** Guarda el carrito 30 días; con [] borra la cookie. */
function cart_write(array $lines): void
{
    $options = [
        'expires' => $lines === [] ? time() - 3600 : time() + 30 * 86400,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if ($lines === []) {
        unset($_COOKIE[CART_COOKIE]);
        setcookie(CART_COOKIE, '', $options);
        return;
    }
    $value = cart_encode($lines);
    $_COOKIE[CART_COOKIE] = $value;
    setcookie(CART_COOKIE, $value, $options);
}

/** Suma $qty a la línea (sku, color) o la crea; respeta 5 unidades por línea y 10 líneas. */
function cart_add(array $lines, string $sku, ?string $color, int $qty = 1): array
{
    $lines = array_values($lines);
    foreach ($lines as $i => $line) {
        if ($line['sku'] === $sku && $line['color'] === $color) {
            $lines[$i]['qty'] = max(1, min(CART_MAX_QTY, $line['qty'] + $qty));
            return $lines;
        }
    }
    if (count($lines) < CART_MAX_LINES) {
        $lines[] = ['sku' => $sku, 'color' => $color, 'qty' => max(1, min(CART_MAX_QTY, $qty))];
    }
    return $lines;
}

/** Cambia la cantidad de la línea en la posición $index; 0 o menos la quita. */
function cart_set_qty(array $lines, int $index, int $qty): array
{
    $lines = array_values($lines);
    if (!isset($lines[$index])) {
        return $lines;
    }
    if ($qty <= 0) {
        unset($lines[$index]);
        return array_values($lines);
    }
    $lines[$index]['qty'] = min(CART_MAX_QTY, $qty);
    return $lines;
}

/**
 * Líneas con nombre, foto y precios del catálogo. Las que ya no existen se omiten; las agotadas
 * quedan con available=false y no suman. $find permite probar con productos falsos.
 * @return array{lines: list<array<string, mixed>>, cash: int, gateway: int, count: int, has_unavailable: bool, shipping: array}
 */
function cart_priced(array $lines, ?callable $find = null): array
{
    $find ??= 'find_product_by_sku';
    $priced = [];
    $cash = 0;
    $gateway = 0;
    $count = 0;
    $hasUnavailable = false;
    foreach (array_values($lines) as $index => $line) {
        $found = $find($line['sku']);
        if ($found === null) {
            continue;
        }
        $product = $found['product'];
        $variant = $found['variant'];
        $entry = find_catalog_entry($product, $line['sku'], $line['color']);
        if ($entry === null) {
            continue;
        }
        $available = (bool) $entry['available'];
        $image = null;
        $hex = null;
        foreach ($product['colors'] ?? [] as $productColor) {
            if (($productColor['name'] ?? null) === $line['color']) {
                $image = $productColor['image'] ?? null;
                $hex = $productColor['hex'] ?? null;
            }
        }
        $kind = placeholder_kind($product);
        if ($line['color'] === null) {
            // Producto sin colores: la foto por defecto de la tarjeta (o la silueta si no hay).
            $card = product_presenter()->card($product, 'carrito');
            $image = $card['media']['image'] ?? null;
            $hex = $card['media']['hex'] ?? null;
        }
        if (($variant['image'] ?? null) !== null) {
            // Configuración con foto propia (Apple Watch).
            $image = $variant['image'];
        }
        $lineCash = $available ? $entry['cash'] * $line['qty'] : 0;
        $lineGateway = $available ? $entry['gw'] * $line['qty'] : 0;
        if ($available) {
            $cash += $lineCash;
            $gateway += $lineGateway;
            $count += $line['qty'];
        } else {
            $hasUnavailable = true;
        }
        $priced[] = [
            'index' => $index,
            'sku' => $line['sku'],
            'color' => $line['color'],
            'qty' => $line['qty'],
            'available' => $available,
            'slug' => $product['slug'],
            'name' => $product['name'],
            'label' => product_variant_label($product, $line['color'], $variant),
            'image' => $image,
            'kind' => $kind,
            'hex' => $hex ?? placeholder_default_hex($kind),
            'url' => url('/productos/' . $product['slug'], [
                'color' => $line['color'] !== null ? color_slug($line['color']) : null,
                'config' => variant_config_sku($variant),
            ]),
            'cash' => $entry['cash'],
            'gw' => $entry['gw'],
            'line_cash' => $lineCash,
            'line_gw' => $lineGateway,
        ];
    }
    return [
        'lines' => $priced,
        'cash' => $cash,
        'gateway' => $gateway,
        'count' => $count,
        'has_unavailable' => $hasUnavailable,
        'shipping' => shipping_info($cash, $gateway),
    ];
}

/** Unidades disponibles en el carrito (para el ícono); sin cookie no toca el catálogo. */
function cart_count(): int
{
    $lines = cart_read();
    return $lines === [] ? 0 : cart_priced($lines)['count'];
}

/** ¿El cliente pidió JSON (fetch) en vez de una redirección? */
function cart_wants_json(): bool
{
    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

function cart_json(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** POST /carrito/agregar (sku, color, slug): suma una unidad si la variante existe y hay stock. */
function cart_post_add(): void
{
    $field = static fn (string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $sku = $field('sku');
    $slug = $field('slug');
    $color = $field('color') !== '' ? $field('color') : null;
    $product = $slug !== '' ? product_repository()->findBySlug($slug) : null;
    $entry = $product !== null && $sku !== '' ? find_catalog_entry($product, $sku, $color) : null;
    $error = null;
    if ($entry === null || !$entry['available']) {
        $error = 'no_disponible';
    } else {
        $before = cart_read();
        $after = cart_add($before, $sku, $color);
        if ($after === $before && count($before) >= CART_MAX_LINES && !in_array([$sku, $color], array_map(static fn (array $l): array => [$l['sku'], $l['color']], $before), true)) {
            $error = 'lleno';
        } else {
            cart_write($after);
        }
    }
    if (cart_wants_json()) {
        if ($error !== null) {
            cart_json(422, ['ok' => false, 'error' => $error]);
            return;
        }
        cart_json(200, ['ok' => true, 'count' => cart_count()]);
        return;
    }
    redirect(url($error === null ? '/carrito' : ($error === 'lleno' ? '/carrito' : '/productos')));
}

/** POST /carrito/actualizar (index, qty): cambia la cantidad de una línea; qty 0 la quita. */
function cart_post_update(): void
{
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    $qty = filter_var($_POST['qty'] ?? null, FILTER_VALIDATE_INT);
    if ($index !== false && $qty !== false) {
        cart_write(cart_set_qty(cart_read(), $index, $qty));
    }
    if (cart_wants_json()) {
        cart_json(200, ['ok' => true, 'count' => cart_count()]);
        return;
    }
    redirect(url('/carrito'));
}
