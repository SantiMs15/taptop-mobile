<?php

declare(strict_types=1);

namespace Taptop\Catalog;

/**
 * Convierte un producto del repositorio en lo que necesitan las vistas.
 *
 * Calcula en el servidor la matriz color × opción (capacidad/nota) con precios, envío,
 * disponibilidad y el enlace de WhatsApp ya armado. El JavaScript solo elige entre
 * esas entradas: nunca calcula un precio (sección 8 y 10.7 del brief).
 */
final class ProductPresenter
{
    public const LABEL_CASH = 'Consignación o transferencia';
    public const LABEL_GATEWAY = 'Con tarjeta / pasarela de pago';
    public const CTA_CONFIRM = 'Confirmar disponibilidad';
    public const CTA_ASK = 'Preguntar por disponibilidad';

    public function __construct(
        private readonly string $soldOutMode = 'mark',
        private readonly bool $showPlaceholders = true,
    ) {
    }

    /** Tarjeta del listado. null si el modo 'hide' deja el producto sin nada disponible. */
    public function card(array $product, string $page): ?array
    {
        return $this->build($product, 'tarjeta', $page, $this->soldOutMode);
    }

    /** Detalle: nunca se oculta (un enlace compartido no debe romperse), a lo sumo se marca agotado. */
    public function detail(array $product): array
    {
        $view = $this->build($product, 'detalle', 'detalle', $this->soldOutMode)
            ?? $this->build($product, 'detalle', 'detalle', 'mark');
        $view['slides'] = $this->slides($product, $view);
        // Fotos de los slides por color, para que color-swatches.js las cambie junto con la principal.
        $view['client']['slides'] = array_map(
            static fn (array $s): array => ['title' => $s['title'], 'byColor' => $s['byColor']],
            $view['slides']
        );
        $view['specs'] = $product['specs'];
        $view['specs_status'] = $product['specs_status'];
        $view['question_wa'] = whatsapp_link(wa_message_question($product));
        return $view;
    }

    public function showPlaceholders(): bool
    {
        return $this->showPlaceholders;
    }

    private function build(array $product, string $placement, string $page, string $mode): ?array
    {
        $kind = placeholder_kind($product);
        $hasColors = $product['colors'] !== [];
        $colors = $hasColors ? $product['colors'] : [null];

        $options = [];
        foreach ($product['variants'] as $variant) {
            $key = ($variant['storage'] ?? '') . '|' . ($variant['note'] ?? '');
            $options[$key] ??= ['storage' => $variant['storage'], 'note' => $variant['note']];
        }
        $options = array_values($options);

        // 1) Matriz completa con disponibilidad.
        $raw = [];
        foreach ($colors as $ci => $color) {
            foreach ($options as $oi => $option) {
                $variant = $this->resolveVariant($product['variants'], $option, $color['name'] ?? null);
                if ($variant === null) {
                    continue;
                }
                $available = $mode === 'show'
                    || ($product['available'] && ($color['available'] ?? true) && $variant['available']);
                if ($mode === 'hide' && !$available) {
                    continue;
                }
                $raw[$ci][$oi] = ['variant' => $variant, 'available' => $available];
            }
        }
        if ($raw === []) {
            return null;
        }

        // 2) Colores y opciones que quedan (en 'hide' se podan los vacíos) con índices nuevos.
        $keptColors = array_keys($raw);
        $keptOptions = [];
        foreach ($raw as $row) {
            foreach (array_keys($row) as $oi) {
                $keptOptions[$oi] = true;
            }
        }
        ksort($keptOptions);
        $keptOptions = array_keys($keptOptions);
        $optionMap = array_flip($keptOptions);

        $viewOptions = [];
        foreach ($keptOptions as $newOi => $oi) {
            $option = $options[$oi];
            $viewOptions[] = [
                'index' => $newOi,
                'storage' => $option['storage'],
                'note' => $option['note'],
                'label' => $option['storage'] ?? $option['note'] ?? 'Única',
                'sub' => $option['storage'] !== null ? $option['note'] : null,
            ];
        }

        $trackBase = track_product_params($product, $placement, $page);
        $entries = [];
        $viewColors = [];
        $best = null;
        $bestAny = null;
        $availablePrices = [];
        $allPrices = [];
        foreach ($keptColors as $newCi => $ci) {
            $color = $colors[$ci];
            $colorName = $color['name'] ?? null;
            $colorHasStock = false;
            foreach ($raw[$ci] as $oi => $cell) {
                $newOi = $optionMap[$oi];
                $variant = $cell['variant'];
                $available = $cell['available'];
                $shipping = shipping_info($variant['price_cash'], $variant['price_gateway']);
                $entry = [
                    'sku' => $variant['sku'],
                    'available' => $available,
                    'cash' => $variant['price_cash'],
                    'gw' => $variant['price_gateway'],
                    'cash_f' => format_price($variant['price_cash']),
                    'gw_f' => format_price($variant['price_gateway']),
                    'ship' => $shipping['label'],
                    'ship_free' => $shipping['free'] || $shipping['partial'],
                    'wa' => whatsapp_link($available
                        ? wa_message_confirm($product, $colorName, $variant)
                        : wa_message_sold_out($product, $colorName, $variant)),
                    'cta' => $available ? self::CTA_CONFIRM : self::CTA_ASK,
                    'buy' => $available ? buy_path($product['slug'], $variant['sku'], $colorName) : null,
                    'color' => $colorName,
                    'storage' => $variant['storage'] ?? $variant['note'],
                ];
                $key = ($hasColors ? (string) $newCi : '_') . '|' . $newOi;
                $entries[$key] = $entry;
                $allPrices[$variant['price_cash']] = true;
                // Inicial: el más barato; si empatan, primero un color con foto propia.
                $candidate = [$variant['price_cash'], ($color['image'] ?? null) === null ? 1 : 0, $newCi, $newOi];
                if ($bestAny === null || $candidate < $bestAny) {
                    $bestAny = $candidate;
                }
                if ($available) {
                    $colorHasStock = true;
                    $availablePrices[$variant['price_cash']] = true;
                    if ($best === null || $candidate < $best) {
                        $best = $candidate;
                    }
                }
            }
            if ($color !== null) {
                $viewColors[] = [
                    'index' => $newCi,
                    'name' => $color['name'],
                    'hex' => $color['hex'],
                    'image' => $color['image'],
                    'available' => $colorHasStock,
                ];
            }
        }

        $allSoldOut = $best === null;
        [, , $selColor, $selOption] = $best ?? $bestAny;
        $selectedKey = ($hasColors ? (string) $selColor : '_') . '|' . $selOption;
        $current = $entries[$selectedKey];
        $from = count($allSoldOut ? $allPrices : $availablePrices) > 1;

        // Opciones disponibles para el color inicial (el JS las recalcula al cambiar de color).
        foreach ($viewOptions as &$option) {
            $entry = $entries[($hasColors ? (string) $selColor : '_') . '|' . $option['index']] ?? null;
            $option['exists'] = $entry !== null;
            $option['available'] = $entry !== null && $entry['available'];
        }
        unset($option);

        $selectedColor = $hasColors ? $viewColors[$selColor] : null;
        $hex = $selectedColor['hex'] ?? placeholder_default_hex($kind);
        $image = $selectedColor['image'] ?? null;

        $singleOption = count($viewOptions) === 1 ? $viewOptions[0] : null;

        return [
            'slug' => $product['slug'],
            'name' => $product['name'],
            'brand' => $product['brand'],
            'brand_label' => brand_label($product['brand']),
            'category' => $product['category'],
            'category_label' => category_label($product['category']),
            'url' => url('/productos/' . $product['slug']),
            'placement' => $placement,
            'kind' => $kind,
            'colors' => $viewColors,
            'options' => $viewOptions,
            'single_option' => $singleOption !== null && ($singleOption['storage'] !== null || $singleOption['note'] !== null)
                ? trim($singleOption['label'] . ($singleOption['sub'] !== null ? ', ' . $singleOption['sub'] : ''))
                : null,
            'selected' => ['color' => $hasColors ? $selColor : null, 'option' => $selOption],
            'current' => $current,
            'from' => $from,
            'all_sold_out' => $allSoldOut,
            'media' => [
                'image' => $image,
                'hex' => $hex,
                'placeholder' => $image === null && $this->showPlaceholders,
                'alt' => $this->altText($product['name'], $selectedColor['name'] ?? null, $image === null),
            ],
            'track' => $trackBase,
            'client' => [
                'colors' => array_map(static fn (array $c): array => [
                    'name' => $c['name'],
                    'hex' => $c['hex'],
                    'image' => $c['image'],
                    'available' => $c['available'],
                ], $viewColors),
                'options' => array_map(static fn (array $o): array => ['label' => $o['label'], 'sub' => $o['sub']], $viewOptions),
                'm' => $entries,
                'sel' => ['c' => $hasColors ? $selColor : null, 'o' => $selOption],
                'from' => $from,
                'name' => $product['name'],
                'placeholderAlt' => 'Imagen de referencia de ' . $product['name'],
            ],
        ];
    }

    /** Variante para una opción y un color: primero la de precio propio del color, luego la general. */
    private function resolveVariant(array $variants, array $option, ?string $colorName): ?array
    {
        $generic = null;
        foreach ($variants as $variant) {
            if ($variant['storage'] !== $option['storage'] || $variant['note'] !== $option['note']) {
                continue;
            }
            if ($colorName !== null && $variant['color'] === $colorName) {
                return $variant;
            }
            if ($variant['color'] === null) {
                $generic ??= $variant;
            }
        }
        return $generic;
    }

    /**
     * Slides de descripción (6.2): foto propia + titular de la ficha. Cada slide puede tener una
     * foto por color ("images"); el color sin foto usa "image" y, si tampoco hay, la silueta teñida.
     * Sin ninguna foto propia el slider se omite, salvo en la Etapa 0 con placeholders activos.
     * 'byColor' (una ruta o null por color, en el orden de 'colors') lo usa color-swatches.js.
     */
    private function slides(array $product, array $view): array
    {
        $slides = [];
        $colors = $view['colors'];
        $selected = $view['selected']['color'];
        $selectedColor = $selected !== null ? $colors[$selected] : null;
        foreach ($product['slides'] as $slide) {
            $byColor = array_map(
                static fn (array $c): ?string => $slide['images'][$c['name']] ?? $slide['image'],
                $colors
            );
            $image = $selected !== null ? $byColor[$selected] : $slide['image'];
            $hasPhoto = $slide['image'] !== null || $slide['images'] !== [];
            if (!$hasPhoto && !$this->showPlaceholders) {
                continue;
            }
            $subject = $selectedColor !== null ? $product['name'] . ' en ' . $selectedColor['name'] : $product['name'];
            $slides[] = [
                'title' => $slide['title'],
                'image' => $image,
                'byColor' => $byColor,
                'hex' => $selectedColor['hex'] ?? placeholder_default_hex($view['kind']),
                'alt' => $image !== null
                    ? $subject . ': ' . $slide['title']
                    : 'Imagen de referencia de ' . $subject,
            ];
        }
        return count($slides) >= 3 ? $slides : [];
    }

    private function altText(string $name, ?string $color, bool $placeholder): string
    {
        $subject = $color !== null ? $name . ' en ' . $color : $name;
        return $placeholder ? 'Imagen de referencia de ' . $subject : $subject;
    }
}
