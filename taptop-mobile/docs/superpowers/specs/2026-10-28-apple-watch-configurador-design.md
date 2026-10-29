# Apple Watch: configurador por opciones

Fecha: 2026-10-28. Estado: aprobado por Santi.

## Para qué

Un Apple Watch se arma con muchas opciones (material, acabado de caja, correa, tamaño, conectividad, talla). Hoy el catálogo solo maneja color + almacenamiento, y el Series 12 está partido en dos productos por tamaño. Santi quiere un solo producto con todas las opciones a la vista, pero **solo se vende lo que él dice que hay en inventario**. Él irá pasando configuraciones disponibles una a una; todo lo demás se ve pero no se puede comprar.

## Metas

- Un producto `apple-watch-series-12` ("Apple Watch Series 12") con 6 selectores, en este orden:
  1. Acabado de la caja: Bronce oscuro, Oro claro, Negro, Gris espacial, Blanco perla, Azul noche, Natural, Oro radiante
  2. Color de la correa: Borgoña, Oliva, Azul marino, Negro, Arena
  3. Material de la caja: Aluminio, Titanio, Cerámica
  4. Tamaño de la caja: 42 mm, 46 mm
  5. Conectividad: GPS, GPS + celular
  6. Tamaño de la banda: S/M, M/L
- Cada configuración disponible es una variante con **SKU y precio propios**. El carrito, el checkout Wompi, los pedidos y los correos siguen funcionando por SKU, sin cambios de fondo.
- Opciones que no forman parte de ninguna configuración disponible con lo ya elegido → en gris (como un color agotado hoy).
- Si el cliente elige una combinación no disponible → la ficha dice "No disponible", sin precio, y el botón pasa a "Consultar por WhatsApp" con la configuración exacta en el mensaje.
- La ficha abre en la configuración disponible más barata.
- Las etiquetas del carrito, el pedido, el correo y WhatsApp nombran la configuración completa: "Apple Watch Series 12 42 mm, Aluminio Oro claro, correa Arena S/M, GPS".

## No metas

- Reglas de qué acabado existe en qué material: no se modelan. Una combinación que Apple no fabrica se ve igual que una agotada ("Consultar").
- Fotos por configuración: hoy no hay fotos del reloj; se usa la silueta de siempre. Cuando lleguen fotos se decide la llave (probablemente acabado de caja + correa).
- Ultra 4 queda como está (oculto, sin opciones) hasta que Santi pase sus configuraciones.
- Los iPhone, MacBook, AirPods, etc. no cambian: siguen con color + almacenamiento.

## Anti-metas

- Que se pueda comprar o pagar una configuración que Santi no dio.
- Inventar precios: cada configuración entra con el precio de la hoja de Santi.
- Romper los carritos o enlaces existentes de otros productos.

## Datos (products.json)

Producto nuevo con un bloque `options` (todas las opciones visibles) y variantes con `attrs`:

```json
{
  "slug": "apple-watch-series-12",
  "name": "Apple Watch Series 12",
  "category": "relojes",
  "colors": [],
  "options": [
    { "name": "Acabado de la caja", "values": ["Bronce oscuro", "Oro claro", "Negro", "Gris espacial", "Blanco perla", "Azul noche", "Natural", "Oro radiante"] },
    { "name": "Color de la correa", "values": ["Borgoña", "Oliva", "Azul marino", "Negro", "Arena"] },
    { "name": "Material de la caja", "values": ["Aluminio", "Titanio", "Cerámica"] },
    { "name": "Tamaño de la caja", "values": ["42 mm", "46 mm"] },
    { "name": "Conectividad", "values": ["GPS", "GPS + celular"] },
    { "name": "Tamaño de la banda", "values": ["S/M", "M/L"] }
  ],
  "variants": [
    { "sku": "AW-S12-42-AL-OROCL-ARENA-SM-GPS", "attrs": { "Acabado de la caja": "Oro claro", "Color de la correa": "Arena", "Material de la caja": "Aluminio", "Tamaño de la caja": "42 mm", "Conectividad": "GPS", "Tamaño de la banda": "S/M" }, "price_cash": 1490000, "price_gateway": 1540000 }
  ]
}
```

- El validador exige: cada variante de un producto con `options` trae `attrs` con exactamente esas llaves y valores de la lista; no hay dos variantes con los mismos `attrs`; SKU válido para el carrito (`[A-Za-z0-9-]{1,40}`).
- Agregar una configuración nueva = agregar una línea a `variants`. Nada más.

## Ficha, carrito y mensajes

- `ProductPresenter` arma, para productos con `options`, un modo "configurador": lista de selectores, mapa SKU → attrs/precio, y la configuración inicial. Los productos sin `options` siguen por el camino actual.
- JS del configurador (nuevo o extensión de `color-swatches.js`): al tocar una opción, busca la variante con esos attrs; si existe, muestra precio y botón de compra; si no, "No disponible" + WhatsApp. Gris = valor que no aparece en ninguna variante compatible con las demás opciones elegidas.
- Carrito: la línea guarda `[sku, null, qty]`; la etiqueta sale de `attrs`. `product_variant_label` aprende a leer `attrs`.
- Enlace compartible: `?config=<sku>` abre la ficha en esa configuración.
- Tarjeta en el listado: "Desde $X" si hay varios precios; si no, el precio.

## Migración

- Se ocultan `apple-watch-series-12-42mm` y `apple-watch-series-12-46mm`; sus URLs redirigen 301 a `/productos/apple-watch-series-12`.
- `specs.json`: la ficha técnica del 46 mm (que es la más completa) pasa al producto nuevo; tamaños y conectividad se describen en ambas versiones.
- Los SKU viejos `AW-S12-42` y `AW-S12-46` se eliminan; un carrito que los tenga los descarta (comportamiento actual con SKU desconocido, ya cubierto por el selftest).

## Pruebas

- Selftest: validador (attrs incompletos, valor fuera de lista, attrs duplicados), presenter (inicial, gris, combinación no disponible), etiqueta del carrito y mensaje de WhatsApp, redirecciones.
- Revisión en local (`localhost:8080`) antes de publicar, como siempre.

## Decisiones mías (revisar)

- Formato de SKU largo y legible.
- Combinaciones imposibles se muestran como "Consultar", no se ocultan.

## Confirmado por Santi

- Orden y nombres de los selectores (lista de Metas).
- Precio de la configuración disponible: $1.490.000 consignación/transferencia, $1.540.000 pasarela.
