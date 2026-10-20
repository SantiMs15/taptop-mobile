<?php

declare(strict_types=1);

namespace Taptop\Repositories;

use Taptop\Catalog\ProductValidator;

/**
 * Catálogo desde app/data/products.json (Etapa 0). Valida al cargar: un producto con
 * errores no se muestra y queda en el log; las advertencias se registran una vez por
 * versión del archivo para no llenar el log en cada visita.
 */
final class JsonProductRepository implements ProductRepository
{
    /** @var array<string, array>|null slug => producto */
    private ?array $products = null;

    public function __construct(
        private readonly string $catalogFile,
        private readonly string $specsFile,
        private readonly ProductValidator $validator,
    ) {
    }

    public function all(): array
    {
        return array_values(array_filter($this->load(), static fn (array $p): bool => $p['visible']));
    }

    public function findBySlug(string $slug): ?array
    {
        $product = $this->load()[$slug] ?? null;
        return $product !== null && $product['visible'] ? $product : null;
    }

    public function filter(array $categories = [], ?string $brand = null): array
    {
        $categories = array_flip($categories);
        return array_values(array_filter(
            $this->all(),
            static fn (array $p): bool => ($categories === [] || isset($categories[$p['category']]))
                && ($brand === null || $p['brand'] === $brand)
        ));
    }

    public function findMany(array $slugs): array
    {
        $found = [];
        foreach ($slugs as $slug) {
            $product = is_string($slug) ? $this->findBySlug($slug) : null;
            if ($product !== null) {
                $found[] = $product;
            }
        }
        return $found;
    }

    /** @return array<string, array> */
    private function load(): array
    {
        if ($this->products !== null) {
            return $this->products;
        }
        $this->products = [];
        $catalog = load_json_file($this->catalogFile);
        if ($catalog === null) {
            return $this->products;
        }

        $report = $this->validator->validateCatalog($catalog);
        $messages = ['errors' => $report['errors'], 'warnings' => $report['warnings']];
        foreach ($report['products'] as $entry) {
            array_push($messages['errors'], ...$entry['errors']);
            array_push($messages['warnings'], ...$entry['warnings']);
            if ($entry['product'] !== null) {
                $this->products[$entry['product']['slug']] = $entry['product'];
            }
        }

        $specs = is_file($this->specsFile) ? load_json_file($this->specsFile) : null;
        if ($specs !== null) {
            $colorNames = array_map(static fn (array $p): array => array_column($p['colors'], 'name'), $this->products);
            $specReport = $this->validator->validateSpecs($specs, array_keys($this->products), $colorNames);
            array_push($messages['errors'], ...$specReport['errors']);
            array_push($messages['warnings'], ...$specReport['warnings']);
            foreach ($specReport['specs'] as $slug => $entry) {
                if ($entry['status'] === 'pendiente') {
                    $this->products[$slug]['specs_status'] = 'pendiente';
                    continue;
                }
                $this->products[$slug]['slides'] = $entry['slides'];
                $this->products[$slug]['specs'] = $entry['specs'];
                $this->products[$slug]['specs_status'] = $entry['status'];
            }
        }

        $this->logOnce($messages);
        return $this->products;
    }

    /** Registra errores y advertencias solo cuando cambia alguno de los archivos de datos. */
    private function logOnce(array $messages): void
    {
        if ($messages['errors'] === [] && $messages['warnings'] === []) {
            return;
        }
        $stamp = md5((string) @filemtime($this->catalogFile) . '|' . (string) @filemtime($this->specsFile));
        $marker = APP_PATH . '/logs/.catalog-' . $stamp;
        if (is_file($marker)) {
            return;
        }
        foreach ($messages['errors'] as $message) {
            app_log('error', 'Catálogo: ' . $message);
        }
        foreach ($messages['warnings'] as $message) {
            app_log('warning', 'Catálogo: ' . $message);
        }
        if (is_dir(APP_PATH . '/logs') && is_writable(APP_PATH . '/logs')) {
            foreach (glob(APP_PATH . '/logs/.catalog-*') ?: [] as $old) {
                @unlink($old);
            }
            @touch($marker);
        }
    }
}
