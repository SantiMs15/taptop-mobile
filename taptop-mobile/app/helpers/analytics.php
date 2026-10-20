<?php
/**
 * Analítica con consentimiento (sección 11 del brief).
 * GA4 y Meta Pixel solo se cargan si hay ID configurado y el visitante acepta.
 * Los eventos se declaran en el HTML con atributos data-track-*; nunca datos personales.
 */

declare(strict_types=1);

function analytics_ga4_id(): string
{
    $id = strtoupper(trim((string) config('ga4_measurement_id', '')));
    return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) === 1 ? $id : '';
}

function analytics_meta_pixel_id(): string
{
    $id = trim((string) config('meta_pixel_id', ''));
    return preg_match('/^\d{6,20}$/', $id) === 1 ? $id : '';
}

function analytics_enabled(): bool
{
    return analytics_ga4_id() !== '' || analytics_meta_pixel_id() !== '';
}

/** Configuración que lee assets/js/analytics.js. */
function analytics_client_config(): array
{
    return [
        'ga4' => analytics_ga4_id(),
        'pixel' => analytics_meta_pixel_id(),
        'cookie' => (string) config('consent_cookie', 'tt_consent'),
        'days' => (int) config('consent_days', 180),
    ];
}

/**
 * Atributos data-track para un elemento: track_attrs('whatsapp_click', ['placement' => 'tarjeta']).
 * Las claves se escriben en kebab-case (product_slug → data-track-product-slug) y el JS
 * las devuelve a snake_case al enviar el evento.
 */
function track_attrs(string $event, array $params = []): string
{
    $html = ' data-track="' . e($event) . '"';
    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === false) {
            continue;
        }
        $name = trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $key)), '-');
        if ($name === '') {
            continue;
        }
        $html .= ' data-track-' . e($name) . '="' . e(is_bool($value) ? 'true' : (string) $value) . '"';
    }
    return $html;
}

/** Parámetros comunes de producto para los eventos (sin datos personales). */
function track_product_params(array $product, string $placement, string $page): array
{
    return [
        'product_slug' => $product['slug'],
        'product_name' => $product['name'],
        'brand' => $product['brand'],
        'category' => $product['category'],
        'placement' => $placement,
        'page' => $page,
    ];
}
