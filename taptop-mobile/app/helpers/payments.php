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
    return wompi() !== null ? url('/pago/wompi', ['producto' => $slug, 'sku' => $sku, 'color' => $color]) : null;
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

/** GET /pago/wompi?producto=&sku=&color= → redirección al Web Checkout con el monto firmado. */
function page_pay_wompi(): void
{
    $wompi = wompi();
    $slug = is_string($_GET['producto'] ?? null) ? $_GET['producto'] : '';
    $sku = is_string($_GET['sku'] ?? null) ? $_GET['sku'] : '';
    $color = is_string($_GET['color'] ?? null) && $_GET['color'] !== '' ? $_GET['color'] : null;
    $product = $slug !== '' ? product_repository()->findBySlug($slug) : null;
    if ($wompi === null || $product === null || $sku === '') {
        page_not_found();
        return;
    }
    $entry = find_catalog_entry($product, $sku, $color);
    if ($entry === null || !$entry['available']) {
        redirect(url('/productos/' . $slug));
    }

    $reference = Wompi::newReference($sku);
    $amountInCents = $entry['gw'] * 100;
    app_log('info', 'Wompi: inicio de pago', ['reference' => $reference, 'sku' => $sku, 'color' => $color, 'amount_in_cents' => $amountInCents]);
    header('Cache-Control: no-store');
    redirect($wompi->checkoutUrl($reference, $amountInCents, absolute_url('/pago/resultado')));
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
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        throw new RuntimeException('No se pudo abrir ' . $file);
    }
    try {
        flock($handle, LOCK_EX);
        $state = json_decode((string) stream_get_contents($handle), true) ?: [];
        $previous = $state[$id] ?? null;
        $state[$id] = $update;
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($handle);
        return $previous;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
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
