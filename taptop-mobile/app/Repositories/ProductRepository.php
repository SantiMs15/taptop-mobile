<?php

declare(strict_types=1);

namespace Taptop\Repositories;

/**
 * Fuente del catálogo. Las vistas solo hablan con esta interfaz, así pasar de
 * products.json (Etapa 0) a MySQL (panel, Paso 2) no toca ninguna plantilla.
 *
 * Forma de un producto normalizado:
 * [
 *   'slug' => string, 'brand' => string, 'category' => string, 'name' => string,
 *   'article' => 'el'|'la'|'los'|'las', 'currency' => 'COP', 'available' => bool, 'visible' => bool,
 *   'colors' => list<array{name: string, hex: string, image: ?string, available: bool}>,
 *   'options' => list<array{name: string, values: list<string>}>, // ejes de configuración; [] si no tiene
 *   'variants' => list<array{sku: string, storage: ?string, note: ?string, color: ?string,
 *                            attrs: ?array<string, string>, image: ?string, price_cash: int, price_gateway: int, available: bool}>,
 *   'slides' => list<array{title: string, image: ?string, images: array<string, string>}>,
 *   'specs' => list<array{title: string, detail: string}>,
 * ]
 * Nunca contiene costos, márgenes ni ganancias.
 */
interface ProductRepository
{
    /** Productos visibles y válidos, en el orden del catálogo. */
    public function all(): array;

    public function findBySlug(string $slug): ?array;

    /**
     * @param list<string> $categories categorías de datos (vacío = todas)
     */
    public function filter(array $categories = [], ?string $brand = null): array;

    /**
     * @param list<string> $slugs
     * @return list<array> en el orden pedido; los slugs inexistentes se omiten
     */
    public function findMany(array $slugs): array;
}
