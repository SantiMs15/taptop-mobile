# Configurador de Apple Watch: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Un producto `apple-watch-series-12` con 6 selectores donde solo se venden las configuraciones listadas como variantes; el resto se ve y lleva a WhatsApp.

**Architecture:** Cada variante con `attrs` se presenta como una "opción" propia del camino actual (sin colores), con la llave `'_|<índice>'` en `client.m`. Así precio, CTA, carrito y Wompi siguen sin cambios. Encima, `client.cfg` describe los 6 ejes y qué valores tiene cada opción. El JS traduce lo elegido en los ejes a un índice de opción, o a "no disponible".

**Tech Stack:** PHP 8.3 sin framework, datos JSON, JS ES5 en `public_html/assets/js` (minificado con `php tools/build-assets.php`), pruebas en `tools/selftest.php`.

**Spec:** `docs/superpowers/specs/2026-10-28-apple-watch-configurador-design.md`

## Global Constraints

- Ejes, en este orden y con estos nombres exactos: "Acabado de la caja", "Color de la correa", "Material de la caja", "Tamaño de la caja", "Conectividad", "Tamaño de la banda".
- Única configuración hoy: SKU `AW-S12-42-AL-OROCL-ARENA-SM-GPS`, Oro claro / Arena / Aluminio / 42 mm / GPS / S/M, `price_cash` 1490000, `price_gateway` 1540000.
- SKU válido para el carrito: `^[A-Za-z0-9-]{1,40}$`.
- Productos sin `options` (iPhone, MacBook, AirPods…) se comportan exactamente igual que hoy: el selftest actual (124) sigue pasando.
- Etiqueta de configuración: `"{name} {Tamaño de la caja}, {Material de la caja} {Acabado de la caja}, correa {Color de la correa} {Tamaño de la banda}, {Conectividad}"` → "Apple Watch Series 12 42 mm, Aluminio Oro claro, correa Arena S/M, GPS".
- Archivos JSON: editar como bytes con reemplazos exactos (texto no ASCII, CRLF en partes).
- Flujo de publicación: preview local → OK de Santi → `tools/deploy.sh --dry-run` → deploy.

## Review Focus

- Combinación sin variante: sin precio, sin "Agregar al carrito" ni "Comprar ahora"; el botón de WhatsApp nombra la combinación elegida (Task 3, prueba de JS manual + Task 2 plantilla).
- POST `/carrito/agregar` con SKU de config válido pero `color` no vacío → rechazado (Task 2, prueba con `find_catalog_entry`).
- Enlace viejo `/productos/apple-watch-series-12-46mm` → 301 al nuevo, no 404 (Task 4).
- `?config=` con SKU inexistente → la ficha abre en la inicial sin error (Task 2).
- Tarjeta del listado con varias configs: no muestra la lista "Capacidad" con etiquetas largas (Task 3).

---

### Task 1: Datos — `options` y `attrs` en repositorio y validador

**Files:**
- Modify: `app/Repositories/JsonProductRepository.php` (normalización de producto/variante)
- Modify: `app/Repositories/ProductRepository.php` (docblock de forma)
- Modify: `app/Catalog/ProductValidator.php`
- Test: `tools/selftest.php`

**Interfaces:**
- Produces: producto normalizado con `options: list<array{name: string, values: list<string>}>` (`[]` si no tiene) y cada variante con `attrs: ?array<string,string>` (`null` si no tiene). En variantes con `attrs`, `storage`, `note` y `color` quedan `null`.

- [ ] **Step 1: Pruebas que fallan** en `tools/selftest.php`, sección "Validador", con un helper `watch(array $overrides = [])` que arma el producto de la spec (los 6 ejes, una variante):
  - `validateCatalog` de `watch()` → 0 errores.
  - variante sin la llave "Conectividad" → error que contenga `Conectividad`.
  - valor `"Oro rosa"` en "Acabado de la caja" → error que contenga `Oro rosa`.
  - dos variantes con los mismos `attrs` (SKU distintos) → error de combinación repetida.
  - variante con `attrs` en un producto sin `options` → error.
  - `options` con un eje sin valores o valor repetido → error.
  - producto con `options` y `colors` no vacío → error (no se mezclan).
- [ ] **Step 2:** `php tools/selftest.php` → esas pruebas FAIL, el resto PASS.
- [ ] **Step 3:** Implementar validación y normalización. La combinación repetida se detecta con la concatenación de los valores en el orden de `options`.
- [ ] **Step 4:** `php tools/selftest.php` → 0 fallaron. `php tools/validate-products.php` → "Errores: 0".
- [ ] **Step 5: Commit** `Catálogo: opciones y atributos por variante (validador)`.

### Task 2: Presentador, etiquetas, WhatsApp y enlace `?config=`

**Files:**
- Modify: `app/Catalog/ProductPresenter.php`
- Modify: `app/helpers.php` (`product_variant_label`, `product_share_url`, `wa_message_*`)
- Modify: `app/router.php` (detalle: leer `?config=`)
- Test: `tools/selftest.php`

**Interfaces:**
- Consumes: Task 1 (`options`, `attrs`).
- Produces:
  - `ProductPresenter::detail(array $product, ?string $preferredSku = null): array`. Si `$preferredSku` es una variante con `attrs`, esa es la seleccionada; si no existe, se ignora.
  - En productos con `options`: una opción de vista por variante (llave interna = SKU), `label` = etiqueta de configuración sin el nombre del producto, `sub` = null; `single_option` = null.
  - `client.cfg = ['axes' => list<{name, values}>, 'opts' => list<list<int>>, 'ask' => string]`. `opts[i]` son los índices de valor por eje de la opción `i`, en el orden de `axes`. `ask` es el enlace `https://wa.me/...?text=` con el marcador literal `%7BCONFIG%7D` (`{CONFIG}` codificado) donde va la combinación.
  - `product_variant_label($product, null, $variant)` con `attrs` → la etiqueta de Global Constraints.
  - `product_share_url(array $product, ?string $color, ?string $sku = null)`: con `$sku` agrega `?config=<sku>`.

- [ ] **Step 1: Pruebas que fallan**:
  - `product_variant_label(watch(), null, <variante>)` === `"Apple Watch Series 12 42 mm, Aluminio Oro claro, correa Arena S/M, GPS"`.
  - `detail(watch())`: `count(options) === 1`, `single_option === null`, `client.cfg.axes[0].name === 'Acabado de la caja'`, `client.cfg.opts[0] === [1, 4, 0, 0, 0, 0]`, `current.sku === 'AW-S12-42-AL-OROCL-ARENA-SM-GPS'`, `current.cash === 1490000`.
  - Con dos variantes (la de la spec + una 46 mm de 1720000/1777000): `detail($p, '<sku 46>')['current']['cash'] === 1720000`; `detail($p, 'NO-EXISTE')['current']['cash'] === 1490000` (más barata).
  - `current.wa` decodificado contiene la etiqueta completa y `config=AW-S12-42-AL-OROCL-ARENA-SM-GPS`.
  - `client.cfg.ask` contiene `%7BCONFIG%7D`.
  - `find_catalog_entry(watch(), 'AW-S12-42-AL-OROCL-ARENA-SM-GPS', null)` !== null y con color `'Negro'` === null.
  - Producto de `product()` (iPhone de prueba): `detail()` idéntico a antes (sin la llave `cfg` en `client`).
- [ ] **Step 2:** selftest → esas FAIL.
- [ ] **Step 3:** Implementar. En `build()`, la llave de opción y `resolveVariant` usan el SKU cuando la variante tiene `attrs`. La selección inicial sigue la regla actual (más barata disponible). El mensaje `ask` sigue la forma de `wa_message_sold_out`: "Hola {negocio}, quiero saber si tienen el Apple Watch Series 12 {CONFIG}.\n{url del producto}". El router pasa `$_GET['config']` (string o null) a `detail()`.
- [ ] **Step 4:** selftest → 0 fallaron.
- [ ] **Step 5: Commit** `Presentador: configuraciones por atributos, etiqueta y enlace ?config=`.

### Task 3: Vista y JS del configurador

**Files:**
- Create: `app/views/partials/config-options.php`
- Modify: `app/views/pages/product-detail.php` (incluir el parcial antes de `color-swatches`)
- Modify: `app/views/partials/color-swatches.php` (no mostrar "Capacidad" si `isset($card['client']['cfg'])`)
- Modify: `public_html/assets/js/color-swatches.js` (+ `.min.js` vía build)
- Modify: CSS de `.option` solo si hace falta la clase `is-unavailable`

**Interfaces:**
- Consumes: `client.cfg` y las opciones de Task 2.
- Produces: radios `data-cfg-axis="<i>"` con `value="<índice de valor>"`; leyenda `"<Eje>: <valor elegido>"` con `data-cfg-value="<i>"`.

- [ ] **Step 1:** Parcial: un `fieldset.options.options--lg` por eje con todos los valores; ninguno `disabled` (deben poder elegirse); los que no aparecen en ninguna opción compatible con los demás ejes elegidos llevan la clase `is-unavailable` y el texto oculto ", no disponible".
- [ ] **Step 2:** JS en `initCard`, si `data.cfg` existe:
  - `state.cfg` = `data.cfg.opts[state.option]` copia.
  - Al cambiar un eje: actualizar `state.cfg`, buscar `i` con `opts[i]` igual a `state.cfg`. Si existe: `state.option = i; render(true)`. Si no: `renderUnavailable()`.
  - `renderUnavailable()`: precio y "Desde" ocultos o reemplazados por "No disponible" en `[data-price-cash]`; oculta `[data-add-to-cart]`, `[data-buy]`, `[data-buy-note]`; CTA = `data.cfg.ask` con `%7BCONFIG%7D` reemplazado por `encodeURIComponent(<etiqueta de la combinación>)`, texto `Preguntar por disponibilidad`; `card` con `is-sold-out`.
  - Grises: valor `v` del eje `a` es gris si ninguna `opts[i]` tiene `v` en `a` y coincide con `state.cfg` en los otros ejes.
  - Evento de analítica `select_option` con `axis` y `value`.
- [ ] **Step 3:** `php tools/build-assets.php`; preview local (`php -S 127.0.0.1:8080 -t public_html tools/dev-router.php`) con un producto de prueba temporal de 2 variantes en `products.json` (no se commitea): abrir la ficha, cambiar Tamaño a 46 mm → precio 46; elegir Titanio → "No disponible", WhatsApp con la combinación; volver a Aluminio → precio y "Agregar al carrito" visibles; agregar al carrito → la línea muestra la etiqueta completa. Comprobar a 375 px de ancho que los 6 selectores no se desbordan.
- [ ] **Step 4:** Quitar el producto de prueba; selftest → 0 fallaron.
- [ ] **Step 5: Commit** `Ficha: selectores del configurador de Apple Watch`.

### Task 4: Datos reales y redirecciones

**Files:**
- Modify: `app/data/products.json` (agregar `apple-watch-series-12`; quitar `apple-watch-series-12-42mm` y `-46mm`)
- Modify: `app/data/specs.json` (`apple-watch-series-12` con la ficha del 46 mm; quitar las dos viejas)
- Modify: `app/router.php` (301)
- Test: `tools/selftest.php`

**Interfaces:**
- Produces: `legacy_product_slug(string $slug): ?string` en `app/router.php` → `'apple-watch-series-12'` para `apple-watch-series-12-42mm` y `apple-watch-series-12-46mm`; null para el resto. La ruta de detalle redirige 301 antes del 404.

- [ ] **Step 1: Pruebas que fallan:** `legacy_product_slug('apple-watch-series-12-46mm') === 'apple-watch-series-12'`, `legacy_product_slug('iphone-18-pro') === null`; el catálogo real tiene `apple-watch-series-12` con 1 variante de SKU `AW-S12-42-AL-OROCL-ARENA-SM-GPS`, y `cart_priced` con una línea `AW-S12-42` la descarta.
- [ ] **Step 2:** selftest → FAIL.
- [ ] **Step 3:** Editar datos (JSON como bytes) y router. En specs, agregar a la ficha: `{ "title": "Tamaños", "detail": "42 mm o 46 mm" }` y `{ "title": "Conectividad", "detail": "GPS o GPS + celular" }`; el slide "Chip S11" etc. se conserva.
- [ ] **Step 4:** selftest → 0 fallaron; `validate-products.php` → 0 errores, 0 advertencias; `curl -sI http://127.0.0.1:8080/productos/apple-watch-series-12-46mm` → `301` con `Location: /productos/apple-watch-series-12`.
- [ ] **Step 5: Commit** `Apple Watch Series 12: un solo producto con configuraciones; 301 de los enlaces viejos`.

### Task 5: Revisión de Santi y publicación

- [ ] **Step 1:** Preview local en `http://127.0.0.1:8080/productos/apple-watch-series-12`; pasar el enlace a Santi.
- [ ] **Step 2:** Con su OK: `git push`, `bash tools/deploy.sh --dry-run`, luego `bash tools/deploy.sh`.
- [ ] **Step 3:** En vivo: la ficha responde 200; la vieja de 46 mm responde 301.
- [ ] **Step 4:** README "Editar el catálogo": párrafo corto sobre cómo agregar una configuración (una línea en `variants` con `attrs`). Commit y push.
