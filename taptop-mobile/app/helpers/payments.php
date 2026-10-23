<?php
/**
 * SPIKE Wompi (sandbox): botón "Comprar ahora", redirección al checkout y página de resultado.
 * Solo se activa si config('wompi') tiene llave pública y secreto de integridad.
 */

declare(strict_types=1);

use Taptop\Payments\Wompi;

function wompi(): ?Wompi
{
    static $wompi = false;
    if ($wompi === false) {
        $wompi = Wompi::fromConfig((array) config('wompi', []));
    }
    return $wompi;
}

/** Ruta de "Comprar ahora" para una variante disponible, o null si Wompi no está configurado. */
function buy_path(string $slug, string $sku, ?string $color): ?string
{
    return wompi() !== null ? url('/pago/envio', ['producto' => $slug, 'sku' => $sku, 'color' => $color]) : null;
}

/**
 * Entrada de la matriz del presentador para (sku, color), tal como la ve el cliente.
 * El precio sale siempre del catálogo, nunca de la URL.
 */
function find_catalog_entry(array $product, string $sku, ?string $color): ?array
{
    foreach (product_presenter()->detail($product)['client']['m'] as $entry) {
        if ($entry['sku'] === $sku && $entry['color'] === $color) {
            return $entry;
        }
    }
    return null;
}

/** Busca el producto que tiene un SKU (para la página de resultado). */
function find_product_by_sku(string $sku): ?array
{
    foreach (product_repository()->all() as $product) {
        foreach ($product['variants'] as $variant) {
            if ($variant['sku'] === $sku) {
                return ['product' => $product, 'variant' => $variant];
            }
        }
    }
    return null;
}

const ORDERS_FILE = APP_PATH . '/logs/wompi-orders.json';

/**
 * Lee, modifica y escribe un archivo JSON bajo bloqueo exclusivo. $fn recibe el arreglo
 * actual por referencia; lo que retorne es lo que devuelve esta función.
 */
function json_store_update(string $file, callable $fn): mixed
{
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        throw new RuntimeException('No se pudo abrir ' . $file);
    }
    try {
        flock($handle, LOCK_EX);
        $state = json_decode((string) stream_get_contents($handle), true);
        if (!is_array($state)) {
            $state = [];
        }
        $result = $fn($state);
        // Codifica ANTES de truncar: si falla, el archivo queda intacto.
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        ftruncate($handle, 0);
        rewind($handle);
        if (fwrite($handle, $json) !== strlen($json)) {
            throw new RuntimeException('No se pudo escribir ' . $file);
        }
        fflush($handle);
        return $result;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

/**
 * Estado de las transacciones recibidas por webhook (id → estado), en app/logs.
 * SPIKE: reemplaza a la tabla payments que tendría MySQL. Con $update, escribe bajo bloqueo
 * y devuelve el estado anterior (null si es nueva).
 */
function wompi_webhook_state(?string $id = null, ?array $update = null): mixed
{
    $file = APP_PATH . '/logs/wompi-transactions.json';
    if ($update === null) {
        $state = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        return $id === null ? (array) $state : ($state[$id] ?? null);
    }
    return json_store_update($file, function (array &$state) use ($id, $update) {
        $previous = $state[$id] ?? null;
        $state[$id] = $update;
        return $previous;
    });
}

/** Guarda el pedido bajo su referencia. Ojo: contiene datos personales; no pasarlo a app_log(). */
function order_save(string $reference, array $order, ?string $file = null): void
{
    json_store_update($file ?? ORDERS_FILE, function (array &$state) use ($reference, $order) {
        $state[$reference] = $order;
    });
}

function order_find(string $reference, ?string $file = null): ?array
{
    $file ??= ORDERS_FILE;
    if (!is_file($file)) {
        return null;
    }
    $state = json_decode((string) file_get_contents($file), true);
    $order = is_array($state) ? ($state[$reference] ?? null) : null;
    return is_array($order) ? $order : null;
}

/** Reclama el envío del correo: true solo para quien lo gana (sin reclamo previo, o uno de más de 10 min). */
function order_claim_mail(string $reference, ?string $file = null): bool
{
    $file ??= ORDERS_FILE;
    if (!is_file($file)) {
        return false;
    }
    return (bool) json_store_update($file, function (array &$state) use ($reference) {
        if (!isset($state[$reference]) || !is_array($state[$reference])) {
            return false;
        }
        $mail = $state[$reference]['mail'] ?? null;
        $free = $mail === null
            || (($mail['status'] ?? null) === 'sending' && strtotime((string) ($mail['at'] ?? '')) < time() - 600);
        if (!$free) {
            return false;
        }
        $state[$reference]['mail'] = ['status' => 'sending', 'at' => date('c')];
        return true;
    });
}

/** Cierra el envío: enviado queda marcado; si falló, se libera para reintentar. */
function order_finish_mail(string $reference, bool $sent, ?string $file = null): void
{
    $file ??= ORDERS_FILE;
    if (!is_file($file)) {
        return;
    }
    json_store_update($file, function (array &$state) use ($reference, $sent) {
        if (isset($state[$reference]) && is_array($state[$reference])) {
            $state[$reference]['mail'] = $sent ? ['status' => 'sent', 'at' => date('c')] : null;
        }
    });
}

/**
 * Lo que se va a cobrar: el carrito (origen=carrito) o un solo producto (producto+sku+color,
 * "Comprar ahora"). Precios siempre del catálogo. Null si no hay nada disponible que cobrar.
 * @return array{source: string, priced: array, back: string}|null
 */
function checkout_items(array $query): ?array
{
    $str = static fn (string $key): string => is_string($query[$key] ?? null) ? $query[$key] : '';
    if ($str('origen') === 'carrito') {
        $source = 'carrito';
        $lines = cart_read();
        $back = '/carrito';
    } else {
        $slug = $str('producto');
        $sku = $str('sku');
        $color = $str('color') !== '' ? $str('color') : null;
        $product = $slug !== '' && $sku !== '' ? product_repository()->findBySlug($slug) : null;
        if ($product === null || find_catalog_entry($product, $sku, $color) === null) {
            return null;
        }
        $source = 'producto';
        $lines = [['sku' => $sku, 'color' => $color, 'qty' => 1]];
        $back = '/productos/' . $slug;
    }
    $priced = cart_priced($lines);
    if ($priced['count'] < 1) {
        return null;
    }
    $priced['lines'] = array_values(array_filter($priced['lines'], static fn (array $l): bool => $l['available']));
    return ['source' => $source, 'priced' => $priced, 'back' => $back];
}

/** Campos del formulario de envío: fuente única (etiqueta, mínimo/máximo, autocompletado). */
function shipping_form_fields(): array
{
    return [
        'nombre' => ['label' => 'Nombre completo', 'required' => true, 'min' => 3, 'max' => 80, 'autocomplete' => 'name'],
        'cedula' => ['label' => 'Cédula', 'required' => true, 'min' => 5, 'max' => 10, 'autocomplete' => 'off', 'inputmode' => 'numeric'],
        'celular' => ['label' => 'Celular', 'required' => true, 'min' => 10, 'max' => 18, 'autocomplete' => 'tel', 'inputmode' => 'numeric', 'type' => 'tel'],
        'correo' => ['label' => 'Correo electrónico', 'required' => true, 'min' => 5, 'max' => 120, 'autocomplete' => 'email', 'type' => 'email'],
        'departamento' => ['label' => 'Departamento', 'required' => true, 'min' => 2, 'max' => 40, 'autocomplete' => 'address-level1'],
        'ciudad' => ['label' => 'Ciudad', 'required' => true, 'min' => 2, 'max' => 60, 'autocomplete' => 'address-level2'],
        'direccion' => ['label' => 'Dirección', 'required' => true, 'min' => 5, 'max' => 120, 'autocomplete' => 'street-address'],
        'barrio' => ['label' => 'Barrio', 'required' => true, 'min' => 2, 'max' => 60, 'autocomplete' => 'off'],
        'notas' => ['label' => 'Notas de entrega (opcional)', 'required' => false, 'min' => 0, 'max' => 300, 'autocomplete' => 'off'],
        'acepta' => ['label' => 'Acepto la política de tratamiento de datos', 'required' => true],
    ];
}

/**
 * Valida y normaliza el formulario de envío. Todo campo debe ser texto (un arreglo es error).
 * @return array{data: array<string, string>, errors: array<string, string>}
 */
function shipping_form_validate(array $post): array
{
    $data = [];
    $errors = [];
    foreach (shipping_form_fields() as $name => $field) {
        if ($name === 'acepta') {
            if (!is_string($post['acepta'] ?? null) || $post['acepta'] === '') {
                $errors['acepta'] = 'Debes aceptar la política para continuar.';
            }
            continue;
        }
        $raw = $post[$name] ?? '';
        if (!is_string($raw)) {
            $errors[$name] = 'Revisa este campo.';
            $data[$name] = '';
            continue;
        }
        $value = trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw);
        $data[$name] = $value;
        $len = mb_strlen($value);
        if ($value === '') {
            if ($field['required']) {
                $errors[$name] = 'Este dato es obligatorio.';
            }
            continue;
        }
        if ($name === 'cedula') {
            if (preg_match('/^\d{5,10}$/', $value) !== 1) {
                $errors[$name] = 'Escribe solo números, entre 5 y 10 dígitos.';
            }
        } elseif ($name === 'celular') {
            $digits = preg_replace('/[\s\-().]/', '', $value) ?? '';
            $digits = preg_replace('/^\+?57(?=\d{10}$)/', '', $digits) ?? $digits;
            if (preg_match('/^3\d{9}$/', $digits) !== 1) {
                $errors[$name] = 'Escribe un celular de 10 dígitos que empiece por 3.';
            } else {
                $data[$name] = $digits;
            }
        } elseif ($name === 'correo') {
            if ($len > $field['max'] || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$name] = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
            }
        } elseif ($len < $field['min'] || $len > $field['max']) {
            $errors[$name] = $len < $field['min']
                ? 'Escribe al menos ' . $field['min'] . ' caracteres.'
                : 'Usa máximo ' . $field['max'] . ' caracteres.';
        }
    }
    return ['data' => $data, 'errors' => $errors];
}

/** Pinta la página de datos de envío (GET, o POST con errores). */
function checkout_render(array $items, array $values, array $errors, int $status): void
{
    $wompi = wompi();
    header('Cache-Control: no-store');
    render_page('checkout', [
        'meta' => page_meta('checkout', 'Datos de envío', '/pago/envio', [], ['noindex' => true]),
        'items' => $items,
        'fields' => shipping_form_fields(),
        'values' => $values,
        'errors' => $errors,
        'sandbox' => $wompi !== null && $wompi->isSandbox(),
    ], $status);
}

/** GET /pago/envio?origen=carrito | ?producto=&sku=&color= → resumen del pedido y formulario de envío. */
function page_checkout(): void
{
    $items = wompi() !== null ? checkout_items($_GET) : null;
    if ($items === null) {
        page_not_found();
        return;
    }
    checkout_render($items, [], [], 200);
}

/** POST /pago/envio → valida, guarda el pedido y manda al Web Checkout de Wompi con el monto firmado. */
function checkout_post(): void
{
    $wompi = wompi();
    if ($wompi === null) {
        page_not_found();
        return;
    }
    $items = checkout_items($_POST);
    $seen = is_string($_POST['total_visto'] ?? null) ? $_POST['total_visto'] : '';
    if ($items === null || $items['priced']['gateway'] !== (int) $seen) {
        $slug = is_string($_POST['producto'] ?? null) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $_POST['producto']) === 1 ? $_POST['producto'] : null;
        $toProduct = ($_POST['origen'] ?? null) !== 'carrito' && $slug !== null;
        header('Cache-Control: no-store');
        redirect($toProduct ? url('/productos/' . $slug) : url('/carrito', ['cambio' => 1]));
    }
    $result = shipping_form_validate($_POST);
    if ($result['errors'] !== []) {
        // Lo escrito se conserva tal cual (solo textos); la casilla se marca si venía marcada.
        $typed = array_filter($_POST, 'is_string');
        checkout_render($items, array_intersect_key($typed, shipping_form_fields()), $result['errors'], 422);
        return;
    }

    $priced = $items['priced'];
    $reference = Wompi::newReference();
    $amountInCents = $priced['gateway'] * 100;
    order_save($reference, [
        'source' => $items['source'],
        'lines' => $priced['lines'],
        'gateway' => (int) $priced['gateway'],
        'cash' => (int) $priced['cash'],
        'shipping' => $result['data'],
        'acepto_datos_at' => date('c'),
        'created_at' => date('c'),
        'mail' => null,
    ]);
    app_log('info', 'Wompi: inicio de pago', ['reference' => $reference, 'source' => $items['source'], 'amount_in_cents' => $amountInCents]);
    header('Cache-Control: no-store');
    redirect($wompi->checkoutUrl($reference, $amountInCents, absolute_url('/pago/resultado')));
}

function webhook_respond(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($body);
}

/**
 * POST /webhooks/wompi: Wompi avisa los cambios de estado de una transacción.
 * Solo cuenta si la firma es válida; un mismo estado repetido no se procesa dos veces.
 */
function webhook_wompi(string $method): void
{
    if ($method !== 'POST') {
        header('Allow: POST');
        webhook_respond(405, ['error' => 'method_not_allowed']);
        return;
    }
    $raw = (string) file_get_contents('php://input', false, null, 0, 65536);
    $event = json_decode($raw, true);
    $secret = trim((string) config('wompi.events_secret', ''));
    if (!is_array($event) || !Wompi::eventIsAuthentic($event, $secret)) {
        app_log('warning', 'Wompi webhook: firma inválida o evento mal formado', ['bytes' => strlen($raw)]);
        webhook_respond(401, ['error' => 'invalid_signature']);
        return;
    }
    if (($event['event'] ?? '') !== 'transaction.updated' || !is_array($event['data']['transaction'] ?? null)) {
        webhook_respond(200, ['ok' => true, 'ignored' => $event['event'] ?? null]);
        return;
    }

    $transaction = $event['data']['transaction'];
    $id = (string) ($transaction['id'] ?? '');
    $status = (string) ($transaction['status'] ?? '');
    $reference = (string) ($transaction['reference'] ?? '');
    $amount = (int) ($transaction['amount_in_cents'] ?? 0);
    $sku = Wompi::skuFromReference($reference);
    $found = $sku !== null ? find_product_by_sku($sku) : null;
    $amountMatches = $found !== null && $amount === $found['variant']['price_gateway'] * 100;

    $previous = wompi_webhook_state($id);
    if (($previous['status'] ?? null) === $status) {
        app_log('info', 'Wompi webhook: evento repetido, sin cambios', ['id' => $id, 'status' => $status]);
        webhook_respond(200, ['ok' => true, 'duplicate' => true]);
        return;
    }
    wompi_webhook_state($id, [
        'status' => $status,
        'reference' => $reference,
        'amount_in_cents' => $amount,
        'amount_matches' => $amountMatches,
        'environment' => $event['environment'] ?? null,
        'received_at' => date('c'),
    ]);
    app_log($amountMatches ? 'info' : 'warning', 'Wompi webhook: ' . ($status === 'APPROVED' ? 'PAGO CONFIRMADO' : 'estado ' . $status), [
        'id' => $id, 'reference' => $reference, 'amount_in_cents' => $amount, 'amount_matches' => $amountMatches,
    ]);
    order_notify_if_approved($reference, $transaction);
    webhook_respond(200, ['ok' => true]);
}

/** GET /pago/resultado?id= → Wompi devuelve aquí al cliente; el estado se consulta a la API. */
function page_pay_result(): void
{
    $wompi = wompi();
    $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
    if ($wompi === null || $id === '') {
        page_not_found();
        return;
    }

    $error = null;
    $transaction = null;
    try {
        $transaction = $wompi->fetchTransaction($id);
    } catch (RuntimeException $e) {
        app_log('error', 'Wompi: consulta fallida', ['id' => $id, 'error' => $e->getMessage()]);
        $error = 'No pudimos consultar el estado del pago. Intenta recargar la página en unos segundos.';
    }
    if ($transaction === null && $error === null) {
        page_not_found();
        return;
    }

    $view = ['error' => $error, 'sandbox' => $wompi->isSandbox()];
    if ($transaction !== null) {
        $reference = (string) ($transaction['reference'] ?? '');
        $amount = (int) ($transaction['amount_in_cents'] ?? 0);
        $sku = Wompi::skuFromReference($reference);
        $found = $sku !== null ? find_product_by_sku($sku) : null;
        // El monto cobrado debe coincidir con el precio con pasarela del catálogo.
        $amountMatches = $found !== null && $amount === $found['variant']['price_gateway'] * 100;
        app_log('info', 'Wompi: resultado', [
            'id' => $id, 'reference' => $reference, 'status' => $transaction['status'] ?? null,
            'amount_in_cents' => $amount, 'amount_matches' => $amountMatches,
        ]);
        order_notify_if_approved($reference, $transaction);
        $view += [
            'status' => (string) ($transaction['status'] ?? 'UNKNOWN'),
            'reference' => $reference,
            'amount_f' => format_price(intdiv($amount, 100)),
            'method' => (string) ($transaction['payment_method_type'] ?? ''),
            'product_name' => $found !== null ? product_variant_label($found['product'], $found['variant']['color'], $found['variant']) : null,
            'product_url' => $found !== null ? url('/productos/' . $found['product']['slug']) : null,
            'amount_matches' => $amountMatches,
            'transaction_id' => $id,
            'webhook' => wompi_webhook_state($id),
        ];
    }

    header('Cache-Control: no-store');
    render_page('payment-result', $view + [
        'meta' => page_meta('payment', 'Resultado del pago', '/pago/resultado', [], ['noindex' => true]),
    ]);
}
