<?php
/**
 * Minifica CSS y JS propios sin Node (sección 11 del brief).
 *
 *   php tools/build-assets.php
 *
 * Genera main.min.css y *.min.js junto a los originales. asset() sirve el .min solo si es más
 * nuevo que el original: si alguien edita main.css y olvida correr esto, se usa el original.
 * La minificación es conservadora (comentarios y espacios); no reescribe código.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$assets = dirname(__DIR__) . '/public_html/assets';

function minify_css(string $css): string
{
    $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
    $css = (string) preg_replace('/\s+/', ' ', $css);
    $css = (string) preg_replace('/\s*([{};,])\s*/', '$1', $css);
    $css = (string) preg_replace('/:\s+/', ':', $css);
    $css = str_replace(';}', '}', $css);
    return trim($css) . "\n";
}

/** Quita comentarios de línea completa, bloques de comentario e indentación. Mantiene los saltos de línea. */
function minify_js(string $js): string
{
    $lines = [];
    $inComment = false;
    foreach (preg_split('/\R/', $js) ?: [] as $line) {
        $trimmed = trim($line);
        if ($inComment) {
            if (str_contains($trimmed, '*/')) {
                $inComment = false;
            }
            continue;
        }
        if (str_starts_with($trimmed, '/*')) {
            $inComment = !str_contains($trimmed, '*/');
            continue;
        }
        if ($trimmed === '' || str_starts_with($trimmed, '//')) {
            continue;
        }
        $lines[] = $trimmed;
    }
    return implode("\n", $lines) . "\n";
}

$jobs = [];
foreach (glob($assets . '/css/*.css') ?: [] as $file) {
    if (!str_ends_with($file, '.min.css')) {
        $jobs[] = [$file, substr($file, 0, -4) . '.min.css', 'minify_css'];
    }
}
foreach (glob($assets . '/js/*.js') ?: [] as $file) {
    if (!str_ends_with($file, '.min.js')) {
        $jobs[] = [$file, substr($file, 0, -3) . '.min.js', 'minify_js'];
    }
}

foreach ($jobs as [$source, $target, $minifier]) {
    $original = (string) file_get_contents($source);
    $minified = $minifier($original);
    file_put_contents($target, $minified);
    echo sprintf("%-28s %6d -> %6d bytes\n", basename($target), strlen($original), strlen($minified));
}
