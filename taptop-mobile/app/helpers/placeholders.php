<?php
/**
 * Siluetas SVG de referencia para la Etapa 0, mientras llegan las fotos propias.
 * El color sale de la variable CSS --dv del contenedor, así el selector de color
 * "cambia la imagen" sin recargar nada. Sin logos de Apple ni de Samsung.
 */

declare(strict_types=1);

/** Tipo de silueta según el producto. */
function placeholder_kind(array $product): string
{
    $slug = $product['slug'];
    return match ($product['category']) {
        'iphone' => str_contains($slug, 'pro') ? 'phone-pro' : 'phone',
        'samsung' => match (true) {
            str_contains($slug, 'fold') => 'fold',
            str_contains($slug, 'flip') => 'flip',
            default => 'phone-galaxy',
        },
        'mac' => str_contains($slug, 'mini') ? 'mini' : 'laptop',
        'ipad' => 'tablet',
        'relojes' => str_contains($slug, 'ultra') ? 'watch-ultra' : 'watch',
        'airpods' => 'earbuds',
        'accesorios' => str_contains($slug, 'pencil') ? 'pencil' : 'adapter',
        default => 'adapter',
    };
}

/** Color neutro para productos sin selector de color. */
function placeholder_default_hex(string $kind): string
{
    return match ($kind) {
        'mini', 'laptop', 'tablet', 'tablet-front' => '#DCDEE2',
        'earbuds', 'earbuds-pair', 'pencil', 'pencil-flat', 'adapter', 'adapter-3q' => '#F4F4F2',
        default => '#C9CFDC',
    };
}

/**
 * Silueta lista para insertar. $hex se aplica como --dv en el contenedor.
 * Opciones: 'label' (texto accesible), 'class' (clases extra).
 */
function device_placeholder(string $kind, ?string $hex = null, array $options = []): string
{
    static $counter = 0;
    $counter++;
    $id = 'ph' . $counter;
    $hex = $hex !== null && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) === 1 ? $hex : placeholder_default_hex($kind);
    $label = (string) ($options['label'] ?? 'Imagen de referencia');
    $class = trim('ph ph--' . $kind . ' ' . (string) ($options['class'] ?? ''));

    $a11y = !empty($options['decorative'])
        ? 'aria-hidden="true"'
        : 'role="img" aria-label="' . e($label) . '"';

    [$viewBox, $body] = placeholder_shape($kind, $id);
    $defs = '<defs>'
        . '<linearGradient id="' . $id . 'h" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0" stop-color="#fff" stop-opacity=".55"/><stop offset=".38" stop-color="#fff" stop-opacity="0"/>'
        . '<stop offset="1" stop-color="#0D2655" stop-opacity=".16"/></linearGradient>'
        . '<radialGradient id="' . $id . 's" cx=".5" cy=".5" r=".5">'
        . '<stop offset="0" stop-color="#0D2655" stop-opacity=".30"/><stop offset="1" stop-color="#0D2655" stop-opacity="0"/></radialGradient>'
        . '<linearGradient id="' . $id . 'd" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0" stop-color="#2B4C8C"/><stop offset=".55" stop-color="#0D2655"/><stop offset="1" stop-color="#071634"/></linearGradient>'
        . '</defs>';

    return '<div class="' . e($class) . '" style="--dv:' . e($hex) . '" ' . $a11y . '>'
        . '<svg viewBox="' . $viewBox . '" aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMax meet">'
        . $defs . $body . '</svg></div>';
}

/** @return array{0: string, 1: string} viewBox y contenido. */
function placeholder_shape(string $kind, string $id): array
{
    $hi = 'url(#' . $id . 'h)';
    $shadow = 'url(#' . $id . 's)';
    $display = 'url(#' . $id . 'd)';
    $lens = static function (float $cx, float $cy, float $r): string {
        return '<circle class="dv-ring" cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '"/>'
            . '<circle class="dv-glass" cx="' . $cx . '" cy="' . $cy . '" r="' . round($r * 0.68, 1) . '"/>'
            . '<circle cx="' . ($cx - $r * 0.22) . '" cy="' . ($cy - $r * 0.22) . '" r="' . round($r * 0.16, 1) . '" fill="#fff" opacity=".45"/>';
    };

    switch ($kind) {
        case 'phone':
        case 'phone-pro':
        case 'phone-galaxy':
            $camera = match ($kind) {
                'phone-pro' => '<rect class="dv-shade" x="36" y="26" width="104" height="110" rx="30"/>'
                    . $lens(66, 57, 20) . $lens(66, 105, 20) . $lens(110, 81, 20)
                    . '<circle cx="112" cy="44" r="6" fill="#F3EAD2" opacity=".9"/><circle class="dv-glass" cx="112" cy="118" r="5"/>',
                'phone-galaxy' => $lens(60, 60, 19) . $lens(60, 108, 19) . $lens(60, 156, 19)
                    . '<circle cx="100" cy="60" r="6" fill="#F3EAD2" opacity=".9"/>',
                default => '<rect class="dv-shade" x="36" y="26" width="58" height="108" rx="29"/>'
                    . $lens(65, 55, 19) . $lens(65, 105, 19),
            };
            $radius = $kind === 'phone-galaxy' ? 26 : 40;
            return ['0 0 240 470',
                '<ellipse cx="120" cy="446" rx="98" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="20" y="8" width="200" height="428" rx="' . $radius . '"/>'
                . $camera
                . '<rect x="20" y="8" width="200" height="428" rx="' . $radius . '" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="21" y="9" width="198" height="426" rx="' . ($radius - 1) . '"/>'];

        case 'fold':
            return ['0 0 240 470',
                '<ellipse cx="120" cy="446" rx="88" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="34" y="8" width="172" height="428" rx="22"/>'
                . '<rect class="dv-shade" x="48" y="26" width="52" height="152" rx="26"/>'
                . $lens(74, 56, 17) . $lens(74, 102, 17) . $lens(74, 148, 17)
                . '<rect x="34" y="8" width="172" height="428" rx="22" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="35" y="9" width="170" height="426" rx="21"/>'];

        case 'flip':
            return ['0 0 240 470',
                '<ellipse cx="120" cy="446" rx="98" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="20" y="196" width="200" height="240" rx="42"/>'
                . '<rect x="34" y="210" width="172" height="142" rx="30" fill="' . $display . '"/>'
                . $lens(64, 238, 14) . $lens(104, 238, 14)
                . '<rect class="dv-shade" x="20" y="368" width="200" height="8"/>'
                . '<rect x="20" y="196" width="200" height="240" rx="42" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="21" y="197" width="198" height="238" rx="41"/>'];

        case 'tablet':
            return ['0 0 340 470',
                '<ellipse cx="170" cy="446" rx="140" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="20" y="8" width="300" height="428" rx="30"/>'
                . '<rect class="dv-shade" x="38" y="26" width="58" height="58" rx="18"/>'
                . $lens(59, 47, 13) . '<circle class="dv-glass" cx="80" cy="68" r="6"/>'
                . '<rect x="20" y="8" width="300" height="428" rx="30" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="21" y="9" width="298" height="426" rx="29"/>'];

        case 'tablet-front':
            // De frente: marco del color del equipo, bisel negro fino y cámara frontal en el lado largo (M5).
            return ['0 0 340 470',
                '<ellipse cx="170" cy="446" rx="140" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="20" y="8" width="300" height="428" rx="30"/>'
                . '<rect x="25" y="13" width="290" height="418" rx="25" fill="#0B0F17"/>'
                . '<rect x="37" y="25" width="266" height="394" rx="14" fill="' . $display . '"/>'
                . '<circle class="dv-glass" cx="309" cy="222" r="3"/>'
                . '<rect x="20" y="8" width="300" height="428" rx="30" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="21" y="9" width="298" height="426" rx="29"/>'];

        case 'laptop':
            return ['0 0 640 440',
                '<ellipse cx="320" cy="414" rx="290" ry="16" fill="' . $shadow . '"/>'
                . '<rect x="96" y="22" width="448" height="306" rx="18" fill="#14171D"/>'
                . '<rect x="108" y="34" width="424" height="282" rx="8" fill="' . $display . '"/>'
                . '<rect x="296" y="34" width="48" height="11" rx="5" fill="#14171D"/>'
                . '<rect class="dv-shade" x="96" y="322" width="448" height="12" rx="4"/>'
                . '<path class="dv-body" d="M42 334h556a12 12 0 0 1 12 12v6a14 14 0 0 1-14 14H44a14 14 0 0 1-14-14v-6a12 12 0 0 1 12-12z"/>'
                . '<rect class="dv-shade" x="282" y="334" width="76" height="7" rx="3.5"/>'
                . '<path d="M42 334h556a12 12 0 0 1 12 12v6a14 14 0 0 1-14 14H44a14 14 0 0 1-14-14v-6a12 12 0 0 1 12-12z" fill="' . $hi . '"/>'
                . '<path class="dv-edge" d="M42 334.8h556a11.2 11.2 0 0 1 11.2 11.2v6a13.2 13.2 0 0 1-13.2 13.2H44A13.2 13.2 0 0 1 30.8 352v-6A11.2 11.2 0 0 1 42 334.8z"/>'];

        case 'mini':
            return ['0 0 480 300',
                '<ellipse cx="240" cy="262" rx="200" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-light" x="44" y="118" width="392" height="44" rx="22"/>'
                . '<rect class="dv-body" x="44" y="138" width="392" height="112" rx="30"/>'
                . '<rect class="dv-glass" x="118" y="196" width="20" height="9" rx="4.5"/>'
                . '<rect class="dv-glass" x="150" y="196" width="20" height="9" rx="4.5"/>'
                . '<circle class="dv-glass" cx="190" cy="200.5" r="5"/>'
                . '<circle cx="372" cy="200.5" r="3.5" fill="#fff" opacity=".9"/>'
                . '<rect x="44" y="138" width="392" height="112" rx="30" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="45" y="139" width="390" height="110" rx="29"/>'];

        case 'watch':
        case 'watch-ultra':
            $ultra = $kind === 'watch-ultra';
            $caseRadius = $ultra ? 36 : 52;
            return ['0 0 240 470',
                '<ellipse cx="120" cy="452" rx="70" ry="10" fill="' . $shadow . '"/>'
                . '<rect class="dv-band" x="66" y="6" width="108" height="150" rx="22"/>'
                . '<rect class="dv-band" x="66" y="304" width="108" height="140" rx="22"/>'
                . '<rect class="dv-body" x="34" y="112" width="172" height="240" rx="' . $caseRadius . '"/>'
                . '<rect x="50" y="128" width="140" height="208" rx="' . ($caseRadius - 12) . '" fill="#0B0F17"/>'
                . '<rect x="50" y="128" width="140" height="208" rx="' . ($caseRadius - 12) . '" fill="' . $hi . '" opacity=".5"/>'
                . '<rect class="dv-shade" x="204" y="176" width="14" height="46" rx="6"/>'
                . '<rect class="dv-shade" x="206" y="242" width="8" height="40" rx="4"/>'
                . ($ultra ? '<rect x="24" y="172" width="11" height="46" rx="4" fill="#F06A23"/>' : '')
                . '<rect x="34" y="112" width="172" height="240" rx="' . $caseRadius . '" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="35" y="113" width="170" height="238" rx="' . ($caseRadius - 1) . '"/>'];

        case 'earbuds':
            return ['0 0 300 300',
                '<ellipse cx="150" cy="262" rx="120" ry="14" fill="' . $shadow . '"/>'
                . '<rect class="dv-body" x="34" y="66" width="232" height="184" rx="74"/>'
                . '<path d="M36 122h228" stroke="#0D2655" stroke-opacity=".13" stroke-width="2"/>'
                . '<circle cx="150" cy="184" r="4.5" fill="#0D2655" opacity=".22"/>'
                . '<rect x="34" y="66" width="232" height="184" rx="74" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="35" y="67" width="230" height="182" rx="73"/>'];

        case 'earbuds-pair':
            // Solo los AirPods, de pie y rectos, con la almohadilla hacia afuera (el izquierdo es el espejo).
            $bud = static function (string $transform) use ($hi): string {
                // Pivote en la base del palito; la almohadilla mira hacia +x.
                return '<g transform="' . $transform . '">'
                    . '<rect class="dv-body" x="-10" y="-140" width="20" height="140" rx="10"/>'
                    . '<rect x="-10" y="-140" width="20" height="140" rx="10" fill="' . $hi . '"/>'
                    . '<rect class="dv-shade" x="-10" y="-16" width="20" height="16" rx="8"/>'
                    . '<ellipse class="dv-body" cx="4" cy="-160" rx="30" ry="32"/>'
                    . '<ellipse class="dv-glass" cx="-17" cy="-168" rx="3.5" ry="6" opacity=".75"/>'
                    . '<ellipse cx="4" cy="-160" rx="30" ry="32" fill="' . $hi . '"/>'
                    . '<ellipse class="dv-edge" cx="4" cy="-160" rx="29.2" ry="31.2"/>'
                    . '<ellipse class="dv-shade" cx="34" cy="-162" rx="12" ry="17"/>'
                    . '<ellipse cx="40" cy="-162" rx="4.5" ry="8" fill="#0D2655" opacity=".22"/>'
                    . '</g>';
            };
            return ['60 110 280 260',
                '<ellipse cx="200" cy="350" rx="110" ry="12" fill="' . $shadow . '"/>'
                . $bud('translate(150 336) scale(-1 1)')
                . $bud('translate(250 336)')];

        case 'pencil':
            return ['0 0 480 200',
                '<ellipse cx="240" cy="172" rx="200" ry="10" fill="' . $shadow . '"/>'
                . '<g transform="rotate(-14 240 100)">'
                . '<rect class="dv-body" x="40" y="82" width="344" height="36" rx="18"/>'
                . '<path class="dv-body" d="M380 82l62 18-62 18z"/>'
                . '<path d="M430 96.5l12 3.5-12 3.5z" fill="#8A93A6"/>'
                . '<rect x="70" y="82" width="290" height="7" fill="#0D2655" opacity=".05"/>'
                . '<rect x="40" y="82" width="344" height="36" rx="18" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="40.8" y="82.8" width="342.4" height="34.4" rx="17.2"/>'
                . '</g>'];

        case 'pencil-flat':
            // Recto y sin sombra, para el carrusel (la tarjeta de producto usa 'pencil').
            return ['34 76 414 48',
                '<rect class="dv-body" x="40" y="82" width="344" height="36" rx="18"/>'
                . '<path class="dv-body" d="M380 82l62 18-62 18z"/>'
                . '<path d="M430 96.5l12 3.5-12 3.5z" fill="#8A93A6"/>'
                . '<rect x="70" y="82" width="290" height="7" fill="#0D2655" opacity=".05"/>'
                . '<rect x="40" y="82" width="344" height="36" rx="18" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="40.8" y="82.8" width="342.4" height="34.4" rx="17.2"/>'];

        case 'adapter-3q':
            // Cargador en 3/4: frente, costado derecho y cara superior con las patas.
            // Las caras laterales se redondean con un trazo grueso del mismo color (.dv-round).
            return ['50 44 200 236',
                '<ellipse cx="150" cy="266" rx="92" ry="12" fill="' . $shadow . '"/>'
                . '<polygon class="dv-light dv-round" points="84,122 172,122 212,102 124,102"/>'
                . '<polygon class="dv-shade dv-round" points="172,126 212,102 212,222 172,246"/>'
                . '<rect x="124" y="56" width="10" height="52" rx="3" fill="#B9BFCB"/>'
                . '<rect x="132" y="58" width="4" height="49" rx="2" fill="#9AA1AE"/>'
                . '<rect x="156" y="56" width="10" height="52" rx="3" fill="#B9BFCB"/>'
                . '<rect x="164" y="58" width="4" height="49" rx="2" fill="#9AA1AE"/>'
                . '<rect class="dv-body" x="74" y="120" width="100" height="136" rx="20"/>'
                . '<rect class="dv-glass" x="112" y="222" width="24" height="9" rx="4.5"/>'
                . '<rect x="74" y="120" width="100" height="136" rx="20" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="74.8" y="120.8" width="98.4" height="134.4" rx="19.2"/>'];

        case 'adapter':
        default:
            return ['0 0 300 300',
                '<ellipse cx="150" cy="262" rx="90" ry="12" fill="' . $shadow . '"/>'
                . '<rect x="120" y="52" width="14" height="52" rx="4" fill="#B9BFCB"/>'
                . '<rect x="166" y="52" width="14" height="52" rx="4" fill="#B9BFCB"/>'
                . '<rect class="dv-body" x="84" y="96" width="132" height="152" rx="32"/>'
                . '<rect class="dv-glass" x="138" y="214" width="24" height="9" rx="4.5"/>'
                . '<rect x="84" y="96" width="132" height="152" rx="32" fill="' . $hi . '"/>'
                . '<rect class="dv-edge" x="85" y="97" width="130" height="150" rx="31"/>'];
    }
}
