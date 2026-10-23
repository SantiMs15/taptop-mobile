<?php

declare(strict_types=1);

namespace Taptop\Payments;

use RuntimeException;

/**
 * Wompi Web Checkout (redirección): la tarjeta nunca pasa por este servidor.
 *
 * SPIKE (rama spike/wompi-sandbox): prueba de integración en sandbox. Sin pedidos, stock ni
 * webhooks. El estado del pago se consulta a la API de Wompi, nunca se cree a la URL de regreso.
 */
final class Wompi
{
    public const CHECKOUT_URL = 'https://checkout.wompi.co/p/';
    public const CURRENCY = 'COP';

    public function __construct(
        private readonly string $publicKey,
        private readonly string $integritySecret,
    ) {
    }

    public static function fromConfig(array $config): ?self
    {
        $public = trim((string) ($config['public_key'] ?? ''));
        $integrity = trim((string) ($config['integrity_secret'] ?? ''));
        return $public !== '' && $integrity !== '' ? new self($public, $integrity) : null;
    }

    public function isSandbox(): bool
    {
        return str_starts_with($this->publicKey, 'pub_test_');
    }

    /** Firma de integridad: SHA-256 de referencia + monto en centavos + moneda + secreto. */
    public static function integritySignature(string $reference, int $amountInCents, string $currency, string $secret): string
    {
        return hash('sha256', $reference . $amountInCents . $currency . $secret);
    }

    /** Referencia única: TT- + 12 hex en mayúscula. Solo letras y números, sin datos del producto. */
    public static function newReference(): string
    {
        return 'TT-' . strtoupper(bin2hex(random_bytes(6)));
    }

    /** SKU guardado en una referencia vieja, o null si no tiene el formato propio. */
    public static function skuFromReference(string $reference): ?string
    {
        return preg_match('/^TT-([A-Z0-9-]+)-[0-9A-F]{10}$/D', $reference, $match) === 1 ? $match[1] : null;
    }

    public function checkoutUrl(string $reference, int $amountInCents, string $redirectUrl): string
    {
        // Wompi documenta el parámetro con ":" literal.
        return self::CHECKOUT_URL . '?' . str_replace('signature%3Aintegrity', 'signature:integrity', http_build_query([
            'public-key' => $this->publicKey,
            'currency' => self::CURRENCY,
            'amount-in-cents' => $amountInCents,
            'reference' => $reference,
            'signature:integrity' => self::integritySignature($reference, $amountInCents, self::CURRENCY, $this->integritySecret),
            'redirect-url' => $redirectUrl,
        ]));
    }

    /**
     * Verifica un evento (webhook): SHA-256 de los valores de signature.properties, en orden,
     * + timestamp + secreto de eventos, comparado con signature.checksum.
     */
    public static function eventIsAuthentic(array $event, string $eventsSecret): bool
    {
        $properties = $event['signature']['properties'] ?? null;
        $checksum = $event['signature']['checksum'] ?? null;
        $timestamp = $event['timestamp'] ?? null;
        if ($eventsSecret === '' || !is_array($properties) || $properties === [] || !is_string($checksum) || !is_int($timestamp)) {
            return false;
        }
        $concatenated = '';
        foreach ($properties as $path) {
            $value = $event['data'] ?? null;
            foreach (explode('.', (string) $path) as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return false;
                }
                $value = $value[$segment];
            }
            if (!is_scalar($value)) {
                return false;
            }
            $concatenated .= (string) $value;
        }
        return hash_equals(hash('sha256', $concatenated . $timestamp . $eventsSecret), strtolower($checksum));
    }

    public function apiBase(): string
    {
        return $this->isSandbox() ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
    }

    /**
     * Consulta una transacción a Wompi (endpoint público). Devuelve el objeto "data" o null si no existe.
     * @throws RuntimeException si la API no responde
     */
    public function fetchTransaction(string $id): ?array
    {
        if (preg_match('/^[A-Za-z0-9-]{1,64}$/', $id) !== 1) {
            return null;
        }
        $curl = curl_init($this->apiBase() . '/transactions/' . rawurlencode($id));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false) {
            throw new RuntimeException('Wompi no respondió: ' . $error);
        }
        if ($status === 404) {
            return null;
        }
        $json = json_decode((string) $body, true);
        if ($status !== 200 || !is_array($json['data'] ?? null)) {
            throw new RuntimeException('Respuesta inesperada de Wompi (HTTP ' . $status . ')');
        }
        return $json['data'];
    }
}
