<?php
/**
 * Íconos SVG propios en línea (sin librerías). Decorativos: aria-hidden por defecto.
 */

declare(strict_types=1);

function icon(string $name, string $class = 'icon'): string
{
    $stroke = 'fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
    $paths = [
        // Glifo de WhatsApp (Simple Icons, CC0).
        'whatsapp' => '<path fill="currentColor" d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41z"/>',
        'instagram' => '<g ' . $stroke . '><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5.2"/><circle cx="12" cy="12" r="4.1"/></g><circle cx="17.3" cy="6.7" r="1.15" fill="currentColor"/>',
        'chevron-left' => '<path ' . $stroke . ' d="M14.5 5.5 8 12l6.5 6.5"/>',
        'chevron-right' => '<path ' . $stroke . ' d="M9.5 5.5 16 12l-6.5 6.5"/>',
        'pause' => '<g fill="currentColor"><rect x="6.5" y="5" width="3.6" height="14" rx="1.2"/><rect x="13.9" y="5" width="3.6" height="14" rx="1.2"/></g>',
        'play' => '<path fill="currentColor" d="M8 5.6v12.8c0 .8.86 1.28 1.54.86l10.1-6.4a1 1 0 0 0 0-1.72L9.54 4.74C8.86 4.32 8 4.8 8 5.6z"/>',
        'shield' => '<g ' . $stroke . '><path d="M12 3.2 5 6v5.6c0 4.3 2.9 7.7 7 9.2 4.1-1.5 7-4.9 7-9.2V6l-7-2.8z"/><path d="m9 12.2 2.2 2.2L15.2 10"/></g>',
        'seal' => '<g ' . $stroke . '><path d="M12 2.8l2.2 1.6 2.7-.2.9 2.6 2.3 1.5-.8 2.6.8 2.6-2.3 1.5-.9 2.6-2.7-.2L12 19.4l-2.2-1.6-2.7.2-.9-2.6-2.3-1.5.8-2.6-.8-2.6 2.3-1.5.9-2.6 2.7.2z"/><path d="m9.2 11.2 1.9 1.9 3.7-3.7"/></g>',
        'truck' => '<g ' . $stroke . '><path d="M2.8 6.5h10.4v9.2H2.8z"/><path d="M13.2 9.5h4l3 3.3v2.9h-7"/><circle cx="7" cy="17.4" r="1.7"/><circle cx="17" cy="17.4" r="1.7"/></g>',
        'tag' => '<g ' . $stroke . '><path d="M3.5 12.6V4.2a.7.7 0 0 1 .7-.7h8.4l8 8a1 1 0 0 1 0 1.4l-7.4 7.4a1 1 0 0 1-1.4 0z"/><circle cx="8.2" cy="8.2" r="1.4"/></g>',
        'plus' => '<path ' . $stroke . ' d="M12 5.5v13M5.5 12h13"/>',
        'cart' => '<g ' . $stroke . '><path d="M3 4h2.4l2 10.2h9.6l1.9-7.4H6.2"/><circle cx="9" cy="19" r="1.5"/><circle cx="16.6" cy="19" r="1.5"/></g>',
        'menu' => '<path ' . $stroke . ' d="M4 8h16M4 16h16"/>',
        'close' => '<path ' . $stroke . ' d="m6 6 12 12M18 6 6 18"/>',
        'chat' => '<path ' . $stroke . ' d="M20 11.6c0 4.2-3.6 7.4-8 7.4-1.2 0-2.3-.2-3.3-.6L4 19.6l1.2-3.7A7 7 0 0 1 4 11.6C4 7.4 7.6 4.2 12 4.2s8 3.2 8 7.4z"/>',
        'clock' => '<g ' . $stroke . '><circle cx="12" cy="12" r="8.6"/><path d="M12 7.4V12l3 2"/></g>',
        'pin' => '<g ' . $stroke . '><path d="M12 21s-6.5-5.8-6.5-11a6.5 6.5 0 0 1 13 0c0 5.2-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></g>',
    ];
    if (!isset($paths[$name])) {
        return '';
    }
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

/**
 * Logo 3D del header y del footer: imagen del mismo modelo del hero (encuadre 20° / 80°, sin
 * sombra de piso) en 1x, 2x y 3x. Decorativo: el enlace que lo contiene ya tiene nombre accesible.
 * El logo oficial es este 3D: favicon, apple-touch-icon y logo-mark-512.png (datos estructurados)
 * salen del mismo render (Logo/taptop-logo-3d-footer.png).
 */
function logo_mark_3d(string $class = 'logo-mark'): string
{
    $src = static fn (int $height): string => asset('images/brand/logo-3d-mark-' . $height . '.webp');
    return '<img class="' . e($class) . '" src="' . e($src(40)) . '"'
        . ' srcset="' . e($src(40)) . ' 1x, ' . e($src(80)) . ' 2x, ' . e($src(120)) . ' 3x"'
        . ' width="31" height="40" alt="" decoding="async">';
}
