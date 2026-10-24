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
 * Bloqueo de un almacén JSON: vive en {archivo}.lock (el archivo de datos se reemplaza con
 * rename, así que no sirve para bloquear). Lanza si no se puede abrir o bloquear.
 * @return resource
 */
function json_store_lock(string $file, int $mode)
{
    $handle = fopen($file . '.lock', 'c');
    if ($handle === false) {
        throw new RuntimeException('No se pudo abrir el bloqueo de ' . basename($file));
    }
    if (!flock($handle, $mode)) {
        fclose($handle);
        throw new RuntimeException('No se pudo bloquear ' . basename($file));
    }
    return $handle;
}

/** @param resource $handle */
function json_store_unlock($handle): void
{
    flock($handle, LOCK_UN);
    fclose($handle);
}

/** Contenido decodificado (sin bloqueo propio: quien llama ya lo tiene). */
function json_store_decode(string $file): array
{
    $raw = is_file($file) ? file_get_contents($file) : '';
    $state = json_decode((string) $raw, true);
    return is_array($state) ? $state : [];
}

/** Lee un almacén JSON con bloqueo compartido. Si el archivo no existe, está vacío. */
function json_store_read(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $lock = json_store_lock($file, LOCK_SH);
    try {
        return json_store_decode($file);
    } finally {
        json_store_unlock($lock);
    }
}

/**
 * Lee, modifica y escribe un archivo JSON bajo bloqueo exclusivo. $fn recibe el arreglo
 * actual por referencia; lo que retorne es lo que devuelve esta función.
 * Escritura atómica: el JSON nuevo va a {archivo}.tmp y luego reemplaza al original con
 * rename(); si el disco se llena a mitad de camino, el original queda intacto.
 */
function json_store_update(string $file, callable $fn): mixed
{
    $lock = json_store_lock($file, LOCK_EX);
    try {
        $state = json_store_decode($file);
        $result = $fn($state);
        // Codifica ANTES de escribir: si falla, el archivo queda intacto.
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $tmp = $file . '.tmp';
        $handle = fopen($tmp, 'w');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el temporal de ' . basename($file));
        }
        $written = fwrite($handle, $json);
        $flushed = fflush($handle);
        fclose($handle);
        if ($written !== strlen($json) || !$flushed || !rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('No se pudo escribir ' . basename($file));
        }
        return $result;
    } finally {
        json_store_unlock($lock);
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
        $state = json_store_read($file);
        return $id === null ? $state : ($state[$id] ?? null);
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
    $order = json_store_read($file ?? ORDERS_FILE)[$reference] ?? null;
    return is_array($order) ? $order : null;
}

/**
 * Marca que el carrito de este pedido ya se vació: true solo la primera vez, para que recargar
 * (o abrir de un favorito) la página de resultado no borre un carrito nuevo.
 */
function order_mark_cart_cleared(string $reference, ?string $file = null): bool
{
    $file ??= ORDERS_FILE;
    if (!is_file($file)) {
        return false;
    }
    return (bool) json_store_update($file, function (array &$state) use ($reference) {
        if (!isset($state[$reference]) || !is_array($state[$reference]) || !empty($state[$reference]['cart_cleared_at'])) {
            return false;
        }
        $state[$reference]['cart_cleared_at'] = date('c');
        return true;
    });
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

/** Departamentos de Colombia (32 y Bogotá D.C.), en orden alfabético, para el selector de envío. */
function colombia_departments(): array
{
    return [
        'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas',
        'Caquetá', 'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía',
        'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander',
        'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre',
        'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada',
    ];
}

/** Campos del formulario de envío: fuente única (etiqueta, mínimo/máximo, autocompletado). */
function shipping_form_fields(): array
{
    return [
        'nombre' => ['label' => 'Nombre completo', 'required' => true, 'min' => 3, 'max' => 80, 'autocomplete' => 'name'],
        'cedula' => ['label' => 'Cédula', 'required' => true, 'min' => 5, 'max' => 10, 'autocomplete' => 'off', 'inputmode' => 'numeric'],
        'celular' => ['label' => 'Celular', 'required' => true, 'min' => 10, 'max' => 18, 'autocomplete' => 'tel', 'inputmode' => 'numeric', 'type' => 'tel'],
        'correo' => ['label' => 'Correo electrónico', 'required' => true, 'min' => 5, 'max' => 120, 'autocomplete' => 'email', 'type' => 'email'],
        'departamento' => ['label' => 'Departamento', 'required' => true, 'min' => 2, 'max' => 40, 'autocomplete' => 'address-level1', 'options' => colombia_departments()],
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
        if (!is_string($raw) || !mb_check_encoding($raw, 'UTF-8')) {
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
        if (isset($field['options'])) {
            if (!in_array($value, $field['options'], true)) {
                $errors[$name] = 'Elige una opción de la lista.';
            }
        } elseif ($name === 'cedula') {
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

/**
 * A dónde volver cuando ya no hay nada que cobrar: el carrito, o el producto si el slug es válido
 * y existe. Null si la entrada no sirve. Con $changed, la página avisa que algo cambió (?cambio=1).
 */
function checkout_fallback_url(array $input, bool $changed): ?string
{
    $query = $changed ? ['cambio' => 1] : [];
    if (($input['origen'] ?? null) === 'carrito') {
        return url('/carrito', $query);
    }
    $slug = $input['producto'] ?? null;
    if (is_string($slug) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) === 1 && product_repository()->findBySlug($slug) !== null) {
        return url('/productos/' . $slug, $query);
    }
    return null;
}

/** GET /pago/envio?origen=carrito | ?producto=&sku=&color= → resumen del pedido y formulario de envío. */
function page_checkout(): void
{
    if (wompi() === null) {
        page_not_found();
        return;
    }
    $items = checkout_items($_GET);
    if ($items === null) {
        $back = checkout_fallback_url($_GET, false);
        if ($back === null) {
            page_not_found();
            return;
        }
        header('Cache-Control: no-store');
        redirect($back);
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
        header('Cache-Control: no-store');
        redirect(checkout_fallback_url($_POST, true) ?? url('/carrito', ['cambio' => 1]));
    }
    $result = shipping_form_validate($_POST);
    if ($result['errors'] !== []) {
        // Lo escrito se conserva tal cual (solo textos UTF-8 válidos); la casilla se marca si venía marcada.
        $typed = array_filter($_POST, static fn (mixed $v): bool => is_string($v) && mb_check_encoding($v, 'UTF-8'));
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

/** Monto esperado en centavos: del pedido guardado, o (referencia vieja) del SKU en el catálogo. */
function expected_amount_for(string $reference, ?array $order): ?int
{
    if ($order !== null) {
        return (int) $order['gateway'] * 100;
    }
    $sku = Wompi::skuFromReference($reference);
    $found = $sku !== null ? find_product_by_sku($sku) : null;
    return $found !== null ? $found['variant']['price_gateway'] * 100 : null;
}

function expected_amount_in_cents(string $reference): ?int
{
    try {
        $order = order_find($reference);
    } catch (Throwable) {
        $order = null;
    }
    return expected_amount_for($reference, $order);
}

/**
 * Avisa del pedido pagado sin dejar que un fallo del almacén rompa el webhook ni la página del cliente.
 * False si el aviso quedó pendiente (correo fallido o error del almacén): el webhook pide reintento.
 */
function order_notify_safely(string $reference, array $transaction): bool
{
    try {
        return order_notify_if_approved($reference, $transaction);
    } catch (Throwable) {
        app_log('error', 'Aviso de pedido falló', ['reference' => $reference]);
        return false;
    }
}

/**
 * Lo que muestra la página de resultado (sin efectos secundarios, sin datos de envío).
 * $order es la foto del pedido, o null si la referencia es vieja.
 */
function payment_result_view(string $reference, array $transaction, ?array $order): array
{
    $status = (string) ($transaction['status'] ?? 'UNKNOWN');
    $amount = (int) ($transaction['amount_in_cents'] ?? 0);
    $expected = expected_amount_for($reference, $order);
    $lines = [];
    $source = null;
    $backUrl = null;
    $waLink = null;
    if ($order !== null) {
        $source = (string) ($order['source'] ?? '');
        foreach ($order['lines'] as $line) {
            if (!empty($line['available'])) {
                $lines[] = ['qty' => (int) $line['qty'], 'label' => (string) $line['label'], 'line_gw_f' => format_price($line['line_gw'])];
            }
        }
        if ($source === 'carrito') {
            $backUrl = url('/carrito');
        } else {
            $first = $order['lines'][0] ?? null;
            $backUrl = is_array($first) && !empty($first['url']) ? (string) $first['url'] : (!empty($first['slug']) ? url('/productos/' . $first['slug']) : null);
        }
        if (in_array($status, ['DECLINED', 'ERROR', 'VOIDED'], true)) {
            $waLink = whatsapp_link(wa_message_cart(['lines' => $order['lines'], 'cash' => $order['cash']]));
        }
    } else {
        $sku = Wompi::skuFromReference($reference);
        $found = $sku !== null ? find_product_by_sku($sku) : null;
        if ($found !== null) {
            $lines[] = ['qty' => 1, 'label' => product_variant_label($found['product'], $found['variant']['color'], $found['variant']), 'line_gw_f' => format_price($found['variant']['price_gateway'])];
            $backUrl = url('/productos/' . $found['product']['slug']);
        }
    }
    return [
        'status' => $status,
        'amount_f' => format_price(intdiv($amount, 100)),
        'amount_matches' => $expected !== null && $amount === $expected,
        'method' => (string) ($transaction['payment_method_type'] ?? ''),
        'lines' => $lines,
        'source' => $source,
        'wa_link' => $waLink,
        'back_url' => $backUrl,
        'clear_cart' => $status === 'APPROVED' && $source === 'carrito',
    ];
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
    $expected = expected_amount_in_cents($reference);
    $amountMatches = $expected !== null && $amount === $expected;

    $previous = wompi_webhook_state($id);
    if (($previous['status'] ?? null) === $status) {
        app_log('info', 'Wompi webhook: evento repetido, sin cambios', ['id' => $id, 'status' => $status]);
        // Idempotente (order_claim_mail): si el envío anterior falló, el reintento de Wompi lo repite.
        if (!order_notify_safely($reference, $transaction)) {
            webhook_respond(503, ['error' => 'mail_failed']);
            return;
        }
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
    // Si el correo falla, 503: Wompi reintenta y el reintento entra por la rama de evento repetido.
    if (!order_notify_safely($reference, $transaction)) {
        webhook_respond(503, ['error' => 'mail_failed']);
        return;
    }
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
        try {
            $order = order_find($reference);
        } catch (Throwable) {
            $order = null;
            app_log('error', 'Pedido no se pudo leer', ['reference' => $reference]);
        }
        // El monto cobrado debe coincidir con el pedido guardado (o con el catálogo si la referencia es vieja).
        $result = payment_result_view($reference, $transaction, $order);
        app_log('info', 'Wompi: resultado', [
            'id' => $id, 'reference' => $reference, 'status' => $result['status'],
            'amount_in_cents' => (int) ($transaction['amount_in_cents'] ?? 0), 'amount_matches' => $result['amount_matches'],
        ]);
        order_notify_safely($reference, $transaction); // aquí el resultado no importa: el webhook reintenta
        if ($result['clear_cart']) {
            // Una sola vez por pedido: recargar la página no borra un carrito nuevo.
            try {
                $clear = order_mark_cart_cleared($reference);
            } catch (Throwable) {
                $clear = false;
                app_log('error', 'Pedido: no se pudo marcar el carrito vaciado', ['reference' => $reference]);
            }
            if ($clear) {
                cart_write([]);
            }
        }
        $view += $result + [
            'reference' => $reference,
            'transaction_id' => $id,
        ];
    }

    header('Cache-Control: no-store');
    render_page('payment-result', $view + [
        'meta' => page_meta('payment', 'Resultado del pago', '/pago/resultado', [], ['noindex' => true]),
    ]);
}
