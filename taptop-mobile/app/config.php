<?php
/**
 * Configuración de Taptop Mobile.
 *
 * Vive FUERA de public_html: nada de esto es accesible por URL.
 * Los valores "[por completar]" son datos que el cliente aún no entrega. Con
 * 'environment' => 'production' el sitio se niega a arrancar mientras falten los
 * datos legales o el horario (ver assert_deployable() en helpers.php).
 * Lista completa de pendientes: `php tools/preflight.php`.
 */

declare(strict_types=1);

return [
    // 'staging' = dominio temporal (Etapa 0), 'production' = lanzamiento (Etapa 1), 'local' = desarrollo.
    'environment' => 'production',
    // true solo en local: muestra el detalle de los errores en pantalla.
    'debug' => false,

    // --- Negocio -----------------------------------------------------------------
    'business_name' => 'Taptop Mobile',
    'business_legal_name' => 'Sara Garzón', // razón social
    'business_nit' => '1029661063-6',
    'business_email' => 'servicioalcliente@taptopmobile.com',     // correo para reclamos y datos personales

    // --- Sitio ---------------------------------------------------------------------
    // Sin barra final. Se usa para canonical, sitemap, Open Graph y JSON-LD.
    'site_url' => 'https://taptopmobile.com',
    // Mientras site_url esté en uno de estos dominios, todo el sitio sale con noindex.
    'temporary_hosts' => ['hostingersite.com'],
    'force_noindex' => false,
    'timezone' => 'America/Bogota',

    // --- SEO básico (textos generales ⏳ por confirmar con el cliente) -------------
    'seo_title' => 'Taptop Mobile | Celulares Apple y Samsung originales en Colombia',
    'seo_description' => 'Celulares y tecnología Apple y Samsung 100 % originales. Dos precios claros, garantía Taptop Mobile y envío a toda Colombia.',
    'seo_texts_confirmed' => false,

    // --- WhatsApp Business (único lugar donde vive el número) ----------------------
    'whatsapp_number' => '573208082188',
    'whatsapp_display' => '+57 320 808 2188',
    // Horario fijo, el mismo todos los días. Ejemplo: '8:00 a. m. a 8:00 p. m.'
    'support_hours_label' => '8:00 a. m. a 8:00 p. m.',
    'support_days_label' => 'Todos los días',
    'support_timezone_label' => 'hora de Colombia',

    // --- Redes -------------------------------------------------------------------
    // Provisional hasta que exista el perfil. Formato final: https://www.instagram.com/usuario/
    'instagram_url' => 'https://www.instagram.com/',
    'instagram_placeholder_url' => 'https://www.instagram.com/',

    // --- Envíos ------------------------------------------------------------------
    'free_shipping_min' => 150000,
    'carrier_name' => '', // aún no definida: el sitio no nombra transportadora ni promete plazos
    // Cuando se definan: [['zone' => 'Ciudades principales', 'time' => '1 a 2 días hábiles', 'cost' => 'Gratis desde $150.000'], ...]
    'shipping_zones' => [],

    // --- Garantía ------------------------------------------------------------------
    'warranty_months' => 3,
    'warranty_exchange_days' => '30', // plazo máximo del cambio, en días hábiles
    'warranty_shipping_payer' => 'el cliente', // quién asume el envío del cambio

    // --- Pagos (Nivel 1: enlace de pago enviado por WhatsApp; sin código de pagos) ---
    // Parámetros de la comisión, solo para el botón "sugerir" del panel (Paso 2).
    'gateway_fee' => ['percent' => 2.65, 'fixed' => 700, 'vat' => 0.19, 'round_to' => 1000],
    // SPIKE: Wompi Web Checkout. Las llaves van SOLO en config.local.php (no va a Git).
    // Vacías = sin botón "Comprar ahora".
    'wompi' => ['public_key' => '', 'integrity_secret' => '', 'events_secret' => ''],

    // --- Correo de pedidos -------------------------------------------------------
    // Aviso interno cuando un pago queda aprobado. 'username' y 'password' van SOLO en
    // config.local.php (no va a Git). 'transport' => 'file' guarda los .eml en app/logs/mail/.
    'orders_email' => 'pedidos@taptopmobile.com',
    'mail' => [
        'transport' => 'smtp',
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'username' => '',
        'password' => '',
        'from' => 'pedidos@taptopmobile.com',
        'from_name' => 'Taptop Mobile',
    ],

    // --- Catálogo ------------------------------------------------------------------
    'catalog_source' => 'json', // 'json' en la Etapa 0; 'mysql' cuando exista el panel
    // Qué hacer con colores y capacidades agotados: 'mark' (tachado y no seleccionable),
    // 'hide' (se ocultan) o 'show' (se ignora la disponibilidad).
    'sold_out_display' => 'mark',
    // true en la Etapa 0: dibuja siluetas de referencia donde aún no hay fotos propias.
    'show_placeholders' => true,
    'home_featured' => [
        'iphone-18-pro',
        'iphone-18-pro-max',
        'samsung-galaxy-s26-ultra',
        'samsung-galaxy-z-fold8-ultra',
        'macbook-air-m5',
        'iphone-17',
        'apple-watch-ultra-4',
        'airpods-pro-3',
    ],
    // El orden es el orden en la web. 'includes': categorías de datos que agrupa el menú.
    'categories' => [
        'iphone' => ['label' => 'iPhone'],
        'samsung' => ['label' => 'Samsung'],
        'mac' => ['label' => 'Mac'],
        'ipad' => ['label' => 'iPad'],
        'relojes' => ['label' => 'Apple Watch'],
        'airpods' => ['label' => 'AirPods'],
        'accesorios' => ['label' => 'Accesorios', 'includes' => ['accesorios', 'adaptadores']],
        'adaptadores' => ['label' => 'Adaptadores', 'nav' => false],
    ],
    'brands' => [
        'apple' => 'Apple',
        'samsung' => 'Samsung',
        'nicols' => 'Nicols',
    ],
    'brand_filters' => ['apple', 'samsung'],

    // --- Analítica (vacíos = no se carga nada, ni se muestra el banner de cookies) ---
    'ga4_measurement_id' => '',
    'meta_pixel_id' => '',
    'consent_cookie' => 'tt_consent',
    'consent_days' => 180,

    // --- Textos legales --------------------------------------------------------------
    'legal_updated_at' => '2026-10-15',
    'legal_version_label' => 'Versión inicial',

    // --- Base de datos (Paso 2: aún sin uso). Nunca subir credenciales a Git. ---------
    'db' => [
        'host' => 'localhost',
        'name' => '',
        'user' => '',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
