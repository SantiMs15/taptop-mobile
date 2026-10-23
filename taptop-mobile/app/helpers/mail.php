<?php
/**
 * Correo de pedidos: envío (SMTP propio o archivo en local) y texto del aviso de pago aprobado.
 */

declare(strict_types=1);

use Taptop\Mail\SmtpMailer;

/**
 * Envía un correo de texto plano. transport=file guarda el .eml en app/logs/mail/ (o mail.dir);
 * transport=smtp usa las credenciales de config.local.php. Nunca lanza: devuelve true/false.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $from = (string) config('mail.from', '');
    $fromName = (string) config('mail.from_name', '');
    $transport = (string) config('mail.transport', 'smtp');
    $user = trim((string) config('mail.username', ''));
    $pass = (string) config('mail.password', '');

    if ($transport !== 'file' && ($user === '' || $pass === '')) {
        app_log('warning', 'Correo sin configurar');
        return false;
    }

    try {
        $message = SmtpMailer::buildMessage($from, $fromName, $to, $subject, $body, $replyTo);
        if ($transport === 'file') {
            $dir = (string) config('mail.dir', APP_PATH . '/logs/mail');
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo crear la carpeta de correos');
            }
            $path = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
            if (file_put_contents($path, $message) === false) {
                throw new RuntimeException('No se pudo guardar el correo');
            }
            return true;
        }
        (new SmtpMailer((string) config('mail.host'), (int) config('mail.port', 465), $user, $pass))
            ->send($from, $to, $message);
        return true;
    } catch (Throwable $e) {
        app_log('error', 'Correo no enviado', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Asunto y cuerpo del aviso interno de un pedido pagado.
 * @return array{subject: string, body: string}
 */
function order_email(string $reference, array $order, array $transaction): array
{
    $cents = (int) ($transaction['amount_in_cents'] ?? 0);
    $charged = intdiv($cents, 100);
    $expected = (int) ($order['gateway'] ?? 0);
    $matches = $cents === $expected * 100;

    $subject = $matches
        ? 'Pedido pagado ' . $reference . ' · ' . format_price($charged)
        : '⚠ Revisar monto · Pedido ' . $reference;

    $out = [
        'Pedido: ' . $reference,
        'Wompi ID: ' . (string) ($transaction['id'] ?? ''),
        'Método de pago: ' . (string) ($transaction['payment_method_type'] ?? ''),
        'Total cobrado: ' . format_price($charged),
        'Total del pedido: ' . format_price($expected),
    ];
    if (!$matches) {
        $out[] = 'ATENCIÓN: el monto cobrado no coincide con el total del pedido.';
    }
    $out[] = 'Origen: ' . (($order['source'] ?? '') === 'carrito' ? 'carrito' : 'Comprar ahora');
    $out[] = '';
    $out[] = 'DATOS DE ENVÍO';
    $shipping = (array) ($order['shipping'] ?? []);
    foreach (shipping_form_fields() as $name => $field) {
        if ($name === 'acepta') {
            continue;
        }
        $out[] = (string) $field['label'] . ': ' . (string) ($shipping[$name] ?? '');
    }
    $consent = (string) ($order['acepto_datos_at'] ?? $shipping['acepto_datos_at'] ?? '');
    if ($consent !== '') {
        $out[] = 'Aceptó tratamiento de datos: ' . $consent;
    }
    $out[] = '';
    $out[] = 'PRODUCTOS';
    foreach ((array) ($order['lines'] ?? []) as $line) {
        $out[] = (int) ($line['qty'] ?? 1) . ' × ' . (string) ($line['label'] ?? '') . ' — ' . format_price((int) ($line['line_gw'] ?? 0));
    }

    return ['subject' => $subject, 'body' => implode("\n", $out) . "\n"];
}

/**
 * Si el pago quedó APROBADO y hay pedido guardado, manda el aviso una sola vez
 * (order_claim_mail evita duplicados entre el webhook y la página de resultado).
 * Si el envío falla, el pedido se libera para reintentar.
 */
function order_notify_if_approved(string $reference, array $transaction, ?string $file = null): void
{
    if (($transaction['status'] ?? null) !== 'APPROVED') {
        return;
    }
    $order = order_find($reference, $file);
    if ($order === null || !order_claim_mail($reference, $file)) {
        return;
    }
    $mail = order_email($reference, $order, $transaction);
    $customer = (string) ($order['shipping']['correo'] ?? '');
    $ok = false;
    try {
        $ok = send_mail((string) config('orders_email'), $mail['subject'], $mail['body'], $customer !== '' ? $customer : null);
    } finally {
        order_finish_mail($reference, $ok, $file);
    }
}
