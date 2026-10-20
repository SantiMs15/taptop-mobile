<?php

declare(strict_types=1);

namespace Taptop\Catalog;

/**
 * Valida y normaliza el catálogo (products.json) y las fichas (specs.json).
 * Lo usan JsonProductRepository al cargar y tools/validate-products.php antes de subir.
 *
 * Errores: el producto no se muestra. Advertencias: se muestra, pero queda en el log
 * (por ejemplo price_gateway < price_cash, probable error de digitación).
 */
final class ProductValidator
{
    /** Claves que delatan costos o márgenes: nunca deben llegar al sitio. */
    private const FORBIDDEN_KEY = '/(cost|margen|margin|ganancia|profit|utilidad|precio_?compra|purchase)/i';
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const SKU = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';
    private const IMAGE_PATH = '#^/(assets/images|uploads)/[A-Za-z0-9/._-]+\.(webp|avif|jpe?g|png)$#';
    private const ARTICLES = ['el', 'la', 'los', 'las'];
    private const SPEC_STATUSES = ['borrador', 'confirmado', 'pendiente'];

    public function __construct(
        private readonly array $categories,
        private readonly array $brands,
    ) {
    }

    /**
     * @return array{
     *   products: list<array{index: int, slug: ?string, product: ?array, errors: list<string>, warnings: list<string>}>,
     *   errors: list<string>, warnings: list<string>
     * }
     */
    public function validateCatalog(mixed $catalog): array
    {
        $result = ['products' => [], 'errors' => [], 'warnings' => []];
        if (!is_array($catalog) || !isset($catalog['products']) || !is_array($catalog['products']) || !array_is_list($catalog['products'])) {
            $result['errors'][] = 'El catálogo debe tener una lista "products".';
            return $result;
        }
        $root = $catalog;
        unset($root['products']);
        foreach ($this->forbiddenKeys($root, '') as $path) {
            $result['errors'][] = "Campo prohibido en la raíz del catálogo: {$path} (costos o márgenes no entran al sitio).";
        }
        if ($result['errors'] !== []) {
            return $result;
        }

        $slugs = [];
        $skus = [];
        foreach ($catalog['products'] as $index => $raw) {
            $entry = $this->validateProduct($raw, $index);
            if ($entry['product'] !== null) {
                $slug = $entry['product']['slug'];
                if (isset($slugs[$slug])) {
                    $entry['errors'][] = sprintf('"%s": slug duplicado (ya lo usa el producto #%d).', $slug, $slugs[$slug]);
                } else {
                    $slugs[$slug] = $index + 1;
                }
                foreach ($entry['product']['variants'] as $variant) {
                    if (isset($skus[$variant['sku']])) {
                        $entry['errors'][] = sprintf('"%s": SKU duplicado %s (ya lo usa "%s").', $slug, $variant['sku'], $skus[$variant['sku']]);
                    } else {
                        $skus[$variant['sku']] = $slug;
                    }
                }
                if ($entry['errors'] !== []) {
                    $entry['product'] = null;
                }
            }
            $result['products'][] = $entry;
        }
        return $result;
    }

    /**
     * Fichas por slug. Estado 'pendiente' = no se publica hasta confirmar los datos.
     * @param list<string> $knownSlugs
     * @param array<string, list<string>> $colorNames colores de cada producto, para revisar las fotos por color de los slides
     * @return array{specs: array<string, array{status: string, slides: list<array>, specs: list<array>}>, errors: list<string>, warnings: list<string>}
     */
    public function validateSpecs(mixed $data, array $knownSlugs, array $colorNames = []): array
    {
        $result = ['specs' => [], 'errors' => [], 'warnings' => []];
        if (!is_array($data) || !isset($data['products']) || !is_array($data['products']) || array_is_list($data['products'])) {
            $result['errors'][] = 'specs.json debe tener un objeto "products" indexado por slug.';
            return $result;
        }
        $known = array_flip($knownSlugs);
        foreach ($data['products'] as $slug => $entry) {
            $label = '"' . $slug . '" (specs)';
            if (!isset($known[$slug])) {
                $result['warnings'][] = "{$label}: no existe en products.json.";
                continue;
            }
            if (!is_array($entry)) {
                $result['errors'][] = "{$label}: debe ser un objeto.";
                continue;
            }
            $status = $entry['status'] ?? 'borrador';
            if (!in_array($status, self::SPEC_STATUSES, true)) {
                $result['errors'][] = "{$label}: estado desconocido \"{$status}\".";
                continue;
            }
            $slides = [];
            foreach ((array) ($entry['slides'] ?? []) as $i => $slide) {
                $title = is_array($slide) ? ($slide['title'] ?? null) : null;
                $image = is_array($slide) ? ($slide['image'] ?? null) : null;
                if (!is_string($title) || trim($title) === '' || mb_strlen($title) > 80) {
                    $result['errors'][] = sprintf('%s: slide #%d sin titular válido (máx. 80 caracteres).', $label, $i + 1);
                    continue;
                }
                if ($image !== null && (!is_string($image) || preg_match(self::IMAGE_PATH, $image) !== 1)) {
                    $result['errors'][] = sprintf('%s: slide #%d con ruta de imagen inválida.', $label, $i + 1);
                    continue;
                }
                // Fotos por color: {"Nombre del color": ruta}. "image" queda para los colores sin foto propia.
                $images = [];
                foreach ((array) ($slide['images'] ?? []) as $colorName => $path) {
                    if (!is_string($path) || str_contains($path, '..') || preg_match(self::IMAGE_PATH, $path) !== 1) {
                        $result['errors'][] = sprintf('%s: slide #%d con ruta de imagen inválida para "%s".', $label, $i + 1, $colorName);
                        continue;
                    }
                    if (isset($colorNames[$slug]) && !in_array((string) $colorName, $colorNames[$slug], true)) {
                        $result['warnings'][] = sprintf('%s: slide #%d tiene foto para "%s", color que el producto no tiene.', $label, $i + 1, $colorName);
                        continue;
                    }
                    $images[(string) $colorName] = $path;
                }
                foreach (array_filter([$image, ...array_values($images)]) as $path) {
                    $nameWarning = $this->photoNameWarning((string) $slug, $path, sprintf('%s: slide #%d', $label, $i + 1));
                    if ($nameWarning !== null) {
                        $result['warnings'][] = $nameWarning;
                    }
                }
                $slides[] = ['title' => trim($title), 'image' => $image, 'images' => $images];
            }
            $specs = [];
            foreach ((array) ($entry['specs'] ?? []) as $i => $spec) {
                $title = is_array($spec) ? ($spec['title'] ?? null) : null;
                $detail = is_array($spec) ? ($spec['detail'] ?? null) : null;
                if (!is_string($title) || trim($title) === '' || !is_string($detail) || trim($detail) === '') {
                    $result['errors'][] = sprintf('%s: característica #%d incompleta (title y detail).', $label, $i + 1);
                    continue;
                }
                $specs[] = ['title' => trim($title), 'detail' => trim($detail)];
            }
            if ($slides !== [] && (count($slides) < 3 || count($slides) > 5)) {
                $result['warnings'][] = "{$label}: el brief pide de 3 a 5 slides por producto.";
            }
            $result['specs'][$slug] = ['status' => $status, 'slides' => $slides, 'specs' => $specs];
        }
        return $result;
    }

    /** @return array{index: int, slug: ?string, product: ?array, errors: list<string>, warnings: list<string>} */
    private function validateProduct(mixed $raw, int $index): array
    {
        $errors = [];
        $warnings = [];
        $label = 'Producto #' . ($index + 1);
        if (!is_array($raw) || ($raw !== [] && array_is_list($raw))) {
            return ['index' => $index, 'slug' => null, 'product' => null, 'errors' => ["{$label}: debe ser un objeto."], 'warnings' => []];
        }

        $slug = $raw['slug'] ?? null;
        if (!is_string($slug) || preg_match(self::SLUG, $slug) !== 1) {
            $errors[] = "{$label}: slug inválido (solo minúsculas, números y guiones).";
            $slug = null;
        } else {
            $label = '"' . $slug . '"';
        }
        foreach ($this->forbiddenKeys($raw, '') as $path) {
            $errors[] = "{$label}: campo prohibido {$path} (costos o márgenes no entran al sitio).";
        }

        $name = $raw['name'] ?? null;
        if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 120) {
            $errors[] = "{$label}: falta el nombre (máx. 120 caracteres).";
        } elseif (preg_match('/\b\d+\s?(GB|TB)\b/i', $name) === 1) {
            $warnings[] = "{$label}: el nombre incluye una capacidad; la capacidad va en la variante.";
        }
        $brand = $raw['brand'] ?? null;
        if (!is_string($brand) || !array_key_exists($brand, $this->brands)) {
            $errors[] = "{$label}: marca desconocida (agrégala en config.php > brands).";
        }
        $category = $raw['category'] ?? null;
        if (!is_string($category) || !array_key_exists($category, $this->categories)) {
            $errors[] = "{$label}: categoría desconocida (agrégala en config.php > categories).";
        }
        $article = $raw['article'] ?? 'el';
        if (!in_array($article, self::ARTICLES, true)) {
            $errors[] = "{$label}: article debe ser el, la, los o las.";
        }
        if (($raw['currency'] ?? 'COP') !== 'COP') {
            $errors[] = "{$label}: la moneda debe ser COP.";
        }
        $available = $this->boolField($raw, 'available', $label, $errors);
        $visible = $this->boolField($raw, 'visible', $label, $errors);

        [$colors, $colorIndex] = $this->validateColors($raw['colors'] ?? [], $label, $errors);
        foreach ($colors as $color) {
            $nameWarning = $color['image'] !== null && is_string($slug)
                ? $this->photoNameWarning($slug, $color['image'], "{$label}, color {$color['name']}")
                : null;
            if ($nameWarning !== null) {
                $warnings[] = $nameWarning;
            }
        }
        $variants = $this->validateVariants($raw['variants'] ?? null, $colorIndex, $label, $errors, $warnings);

        $product = $errors === [] ? [
            'slug' => $slug,
            'brand' => $brand,
            'category' => $category,
            'name' => trim((string) $name),
            'article' => $article,
            'currency' => 'COP',
            'available' => $available,
            'visible' => $visible,
            'colors' => $colors,
            'variants' => $variants,
            'slides' => [],
            'specs' => [],
            'specs_status' => null,
        ] : null;

        return ['index' => $index, 'slug' => $slug, 'product' => $product, 'errors' => $errors, 'warnings' => $warnings];
    }

    /** @return array{0: list<array>, 1: array<string, string>} colores y mapa nombre-en-minúsculas → nombre */
    private function validateColors(mixed $rawColors, string $label, array &$errors): array
    {
        $colors = [];
        $index = [];
        if (!is_array($rawColors) || ($rawColors !== [] && !array_is_list($rawColors))) {
            $errors[] = "{$label}: colors debe ser una lista.";
            return [$colors, $index];
        }
        foreach ($rawColors as $i => $color) {
            $colorLabel = sprintf('%s, color #%d', $label, $i + 1);
            if (!is_array($color)) {
                $errors[] = "{$colorLabel}: debe ser un objeto.";
                continue;
            }
            $name = $color['name'] ?? null;
            if (!is_string($name) || trim($name) === '') {
                $errors[] = "{$colorLabel}: falta el nombre.";
                continue;
            }
            $name = trim($name);
            $key = mb_strtolower($name);
            if (isset($index[$key])) {
                $errors[] = "{$colorLabel}: color repetido \"{$name}\".";
                continue;
            }
            $hex = $color['hex'] ?? null;
            if (!is_string($hex) || preg_match(self::HEX, $hex) !== 1) {
                $errors[] = "{$colorLabel}: hex inválido (formato #RRGGBB).";
                continue;
            }
            $image = $color['image'] ?? null;
            if ($image !== null && (!is_string($image) || str_contains($image, '..') || preg_match(self::IMAGE_PATH, $image) !== 1)) {
                $errors[] = "{$colorLabel}: ruta de imagen inválida.";
                continue;
            }
            $index[$key] = $name;
            $colors[] = [
                'name' => $name,
                'hex' => strtoupper($hex),
                'image' => $image,
                'available' => $this->boolField($color, 'available', $colorLabel, $errors),
            ];
        }
        return [$colors, $index];
    }

    /** @param array<string, string> $colorIndex */
    private function validateVariants(mixed $rawVariants, array $colorIndex, string $label, array &$errors, array &$warnings): array
    {
        $variants = [];
        if (!is_array($rawVariants) || $rawVariants === [] || !array_is_list($rawVariants)) {
            $errors[] = "{$label}: necesita al menos una variante con sus dos precios.";
            return $variants;
        }
        $combinations = [];
        foreach ($rawVariants as $i => $variant) {
            $variantLabel = sprintf('%s, variante #%d', $label, $i + 1);
            if (!is_array($variant)) {
                $errors[] = "{$variantLabel}: debe ser un objeto.";
                continue;
            }
            $sku = $variant['sku'] ?? null;
            if (!is_string($sku) || preg_match(self::SKU, $sku) !== 1) {
                $errors[] = "{$variantLabel}: SKU inválido (mayúsculas, números y guiones).";
                continue;
            }
            $storage = $this->optionalString($variant, 'storage', $variantLabel, $errors);
            $note = $this->optionalString($variant, 'note', $variantLabel, $errors);
            $color = $this->optionalString($variant, 'color', $variantLabel, $errors);
            if ($color !== null) {
                if (!isset($colorIndex[mb_strtolower($color)])) {
                    $errors[] = "{$variantLabel}: el color \"{$color}\" no está en la lista de colores.";
                    continue;
                }
                $color = $colorIndex[mb_strtolower($color)];
            }
            $cash = $variant['price_cash'] ?? null;
            $gateway = $variant['price_gateway'] ?? null;
            if (!is_int($cash) || $cash <= 0) {
                $errors[] = "{$variantLabel}: price_cash debe ser un entero positivo en COP (sin puntos ni decimales).";
                continue;
            }
            if (!is_int($gateway) || $gateway <= 0) {
                $errors[] = "{$variantLabel}: price_gateway debe ser un entero positivo en COP (sin puntos ni decimales).";
                continue;
            }
            if ($gateway < $cash) {
                $warnings[] = sprintf('%s: price_gateway (%d) es menor que price_cash (%d); probable error de carga.', $variantLabel, $gateway, $cash);
            }
            if ($cash < 1000 || $cash > 100000000) {
                $warnings[] = "{$variantLabel}: price_cash fuera de un rango razonable; revisa ceros de más o de menos.";
            }
            $combination = mb_strtolower(($storage ?? '') . '|' . ($note ?? '') . '|' . ($color ?? ''));
            if (isset($combinations[$combination])) {
                $errors[] = "{$variantLabel}: combinación repetida de capacidad, nota y color.";
                continue;
            }
            $combinations[$combination] = true;
            $variants[] = [
                'sku' => $sku,
                'storage' => $storage,
                'note' => $note,
                'color' => $color,
                'price_cash' => $cash,
                'price_gateway' => $gateway,
                'available' => $this->boolField($variant, 'available', $variantLabel, $errors),
            ];
        }
        return $variants;
    }

    /**
     * Nombre SEO de las fotos de producto: {slug}-{color-en-español}[-{toma}].webp, en minúsculas y
     * sin tildes ni ñ (p. ej. iphone-18-pro-max-negro-trasera.webp). Solo avisa: no bloquea la foto.
     */
    private function photoNameWarning(string $slug, string $path, string $label): ?string
    {
        if (!str_starts_with($path, '/assets/images/products/')) {
            return null;
        }
        $pattern = '/^' . preg_quote($slug, '/') . '-[a-z0-9]+(?:-[a-z0-9]+)*\.webp$/';
        return preg_match($pattern, basename($path)) === 1
            ? null
            : "{$label}: la foto \"" . basename($path) . "\" no sigue el formato {$slug}-color-toma.webp (SEO).";
    }

    private function boolField(array $data, string $key, string $label, array &$errors): bool
    {
        if (!array_key_exists($key, $data)) {
            return true;
        }
        if (!is_bool($data[$key])) {
            $errors[] = "{$label}: {$key} debe ser true o false.";
            return true;
        }
        return $data[$key];
    }

    private function optionalString(array $data, string $key, string $label, array &$errors): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > 60) {
            $errors[] = "{$label}: {$key} debe ser texto (máx. 60 caracteres) o null.";
            return null;
        }
        return trim($value);
    }

    /** @return list<string> rutas de las claves prohibidas encontradas */
    private function forbiddenKeys(array $data, string $path): array
    {
        $found = [];
        foreach ($data as $key => $value) {
            $current = $path === '' ? (string) $key : $path . '.' . $key;
            if (is_string($key) && preg_match(self::FORBIDDEN_KEY, $key) === 1) {
                $found[] = $current;
            }
            if (is_array($value)) {
                array_push($found, ...$this->forbiddenKeys($value, $current));
            }
        }
        return $found;
    }
}
