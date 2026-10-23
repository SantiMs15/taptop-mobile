# Carrito de compras — diseño

Fecha: 2026-10-22 · Rama base: `spike/wompi-sandbox` (botón "Comprar ahora" con Wompi)

## Para qué y por qué

Hoy, quien quiere llevar más de un equipo (p. ej. un iPhone y unos AirPods) tiene que pagar cada
uno por separado con "Comprar ahora". El carrito deja juntar varios productos y pagarlos **en un
solo pago**.

Medios de pago (decisión de Santi):

- **Principal: pagar en la web con Wompi** (tarjeta, PSE, Nequi), al precio con pasarela.
- **Secundario: transferencia por WhatsApp.** El botón abre el chat y por ahí el asesor da los
  datos de pago. También se puede mandar un enlace de pago personalizado por WhatsApp a quien
  tenga dudas (eso ya existe, no lo toca el carrito).

## Metas

1. En la página de producto, botón **"Agregar al carrito"** al lado de "Comprar ahora". Los dos
   se quedan (decisión de Santi).
2. Página propia **`/carrito`** (opción B del boceto, elegida por Santi): lista de productos con
   foto, nombre, color, capacidad, cantidad (− / +), "Quitar" y precio; totales; dos botones de pago;
   enlace "‹ Seguir comprando".
3. El carrito muestra **los dos totales**: pagando en línea (pasarela) y por transferencia
   (efectivo). Decisión de Santi, aunque la transferencia salga más barata.
4. **"Pagar ahora"** cobra todo el carrito en un solo pago de Wompi.
5. **"Pagar por transferencia (WhatsApp)"** abre WhatsApp con un mensaje ya escrito con el
   detalle del carrito y el total por transferencia.
6. Ícono de carrito con el número de productos en la cabecera, en todas las páginas.
7. **Página de datos de envío** antes de Wompi, tanto para el carrito como para "Comprar ahora"
   (decisión de Santi).
8. **Correo con el pedido** (datos de envío + productos) a `servicioalcliente@taptopmobile.com`
   cuando el pago queda aprobado (decisión de Santi: correo con datos y lista de productos).

## Etapas de salida

- **Ahora: modo de pruebas (sandbox)**, en la rama `spike/wompi-sandbox`, con vista previa local.
- **Después, paso aparte: llaves reales** (decisión de Santi: "sigue en pruebas, pero vamos a hacer
  la migración"). Ese paso incluye llaves reales y SMTP en el servidor, llevar Wompi a `main` y el
  manejo de fallos de la API o del webhook. Este carrito se construye sin atajos de sandbox para que
  ese paso no obligue a rehacerlo.

## No metas

- Cuentas de usuario, carrito guardado entre dispositivos, cupones, cálculo de envío.
- Base de datos o panel de pedidos (eso es el Paso 2 / MySQL).
- Botón de agregar en las tarjetas del listado o del inicio: solo en la página de producto, que es
  donde se elige color y capacidad.
- Cambiar "Comprar ahora" más allá de hacerlo pasar por la página de datos de envío.
- Correo de confirmación propio al cliente (Wompi ya le manda comprobante).

## Anti-metas (sería un fracaso aunque funcione)

- Que el precio cobrado salga del navegador. **Siempre** se recalcula en el servidor desde el
  catálogo, igual que hoy con "Comprar ahora".
- Cobrar un producto agotado o un precio viejo.
- Romper el sitio cuando Wompi no está configurado: sin llaves, el carrito sigue funcionando con
  el botón de WhatsApp y sin "Pagar ahora" (igual que hoy desaparece "Comprar ahora").
- Que el carrito haga más lento el sitio o cargue scripts en páginas que no lo usan más allá del
  contador de la cabecera.

## Restricciones

- PHP 8.3 sin framework ni base de datos, hosting compartido en Hostinger.
- Las llaves de Wompi viven solo en `app/config.local.php` (fuera de Git).
- Mismo estilo de código, tokens de color y componentes (`btn--primary`, `btn--wa`) del sitio.
- Flujo de trabajo: vista previa local → OK de Santi → deploy.

## Cómo funciona (decisiones mías, Santi puede corregirlas)

### Dónde vive el carrito: cookie, no `localStorage`

El carrito se guarda en una cookie (`tt_cart`, ~30 días, `SameSite=Lax`, `Path=/`) con solo
`[sku, color, cantidad]` por línea, sin precios. Así `/carrito` se arma en el servidor con PHP,
igual que el resto del sitio, con precios y disponibilidad del catálogo en cada carga, y funciona
aunque el JavaScript falle. El servidor ignora líneas que no existan en el catálogo.

### Agregar, cambiar y quitar

- Formularios `POST` (`/carrito/agregar`, `/carrito/actualizar`) que escriben la cookie y redirigen
  (patrón POST → redirect → GET). Hoy el router solo acepta POST en el webhook; se abren estas
  rutas con verificación de origen (`Origin`/`Referer` del propio sitio) para evitar envíos desde
  otros sitios.
- Con JavaScript, "Agregar al carrito" envía el formulario sin recargar y muestra el aviso
  "Agregado ✓ · Ver carrito" y actualiza el contador. Sin JavaScript, el formulario funciona igual
  y lleva a `/carrito`.
- El botón usa la variante elegida en la página (los mismos datos que ya alimentan "Comprar
  ahora" en `color-swatches.js`). Solo aparece si esa variante está disponible.
- Límites: hasta 5 unidades por línea y 10 líneas. Agregar algo que ya está suma 1 a esa línea.

### Página `/carrito`

- `noindex`, `Cache-Control: no-store`, fuera del sitemap y con `Disallow` en `robots.txt`.
- Si una línea quedó agotada o desapareció del catálogo, se muestra como "Agotado" y no suma en
  los totales ni se cobra. Si el precio cambió, se muestra el precio actual.
- Totales: "Total pagando en línea" (suma de `price_gateway`) en grande y "Total por
  transferencia" (suma de `price_cash`) debajo.
- Envío: misma regla de `shipping_info()` aplicada a los totales del carrito.
- Carrito vacío: mensaje y botón "Ver productos".

### Datos de envío (`/pago/envio`)

Una sola página para los dos caminos de pago en línea:

- **Carrito:** "Pagar ahora" en `/carrito` lleva aquí con todo el carrito.
- **Comprar ahora:** el botón de la página de producto ya no va directo a Wompi; lleva aquí con ese
  único producto (slug, sku, color en la URL, como hoy).

Arriba, resumen del pedido (productos y total con pasarela). Debajo, el formulario *(campos
propuestos por mí)*: nombre completo, cédula, celular, correo, departamento, ciudad, dirección,
barrio y notas de entrega (opcional). Casilla obligatoria de aceptación de la política de datos con
enlace a `/privacidad`.

`POST` → validación en el servidor (si falla, vuelve con los errores y lo ya escrito) → se
recalculan productos y total desde el catálogo → se guarda la **foto del pedido** (líneas, precios,
total, datos de envío, fecha) en `app/logs/wompi-orders.json` bajo bloqueo, igual que hoy
`wompi-transactions.json` → redirección al checkout de Wompi con el monto firmado.

Si entre `/carrito` y este envío algo se agotó o cambió de precio, no se cobra: vuelve a `/carrito`
con el aviso "Tu carrito cambió, revísalo" *(mi decisión)*. Para eso el formulario envía el total que
el cliente vio y el servidor lo compara.

`app/logs/` ya está fuera de `public_html` y fuera de Git; ahí quedan los pedidos. Los datos
personales solo se usan para el despacho.

### Correo del pedido

- Destino: `servicioalcliente@taptopmobile.com` (de Santi), en `config('orders_email')`.
- Se envía **solo cuando el pago queda APPROVED** *(mi decisión: no llegan pedidos sin pagar)*.
- Lo dispara lo primero que vea el pago aprobado: el webhook o la página `/pago/resultado`, que ya
  consulta el estado a la API de Wompi. Se marca en la foto del pedido que el correo salió, bajo
  bloqueo, para que nunca salga dos veces.
- Contenido: referencia y ID de Wompi, método de pago, total cobrado, datos de envío, lista de
  productos (cantidad, nombre, color, capacidad, precio) y `amount_matches`. Si el monto no
  coincide, el asunto lo dice ("⚠ Revisar monto").
- Envío por SMTP de Hostinger con la cuenta del dominio. Servidor, usuario y contraseña solo en
  `config.local.php`. Sin datos de SMTP, no se envía y queda en el log. Hace falta una librería de
  SMTP pequeña (p. ej. PHPMailer) o un cliente SMTP mínimo propio; lo decide quien construya.
- Al cliente no se le manda correo propio en esta etapa: Wompi ya le envía su comprobante.
  *(mi decisión)*
- En local, el correo se escribe en `app/logs/mail/` en vez de enviarse, para poder probarlo.

### Pagar ahora (Wompi)

- El paso a Wompi sale de la página de datos de envío (arriba).
- Como ahora todo pedido tiene foto con datos de envío, la referencia pasa a ser una sola forma
  para los dos caminos: `TT-{aleatorio}` *(mi decisión)*. Se sigue aceptando la forma vieja
  `TT-{sku}-{aleatorio}` al leer, por los pagos de prueba ya hechos.
- `/pago/resultado` y `/webhooks/wompi` buscan la foto del pedido por referencia y comparan el
  monto cobrado con el total guardado. El resultado muestra la lista de productos y "Te
  escribiremos para coordinar la entrega".
- Sin tope de monto *(mi decisión)*. Si Wompi o el banco rechazan, la página de resultado ofrece
  pagar por transferencia por WhatsApp con el pedido ya escrito.
- Cuando Wompi confirma el pago (APPROVED), el carrito se vacía en ese navegador (al cargar
  `/pago/resultado`).

### Pagar por transferencia (WhatsApp)

Enlace `wa.me` con un mensaje del estilo de `wa_message_confirm()`:

```
Hola Taptop Mobile, quiero pagar por transferencia este pedido:
• 1 × iPhone 18 Pro Max Borgoña 256 GB — $5.800.000
• 1 × AirPods Pro 3 — $790.000
Total por transferencia: $6.590.000
```

No vacía el carrito: el pedido se cierra en el chat.

### Medición

Eventos con `track_attrs()`: `add_to_cart`, `view_cart`, `begin_checkout` (Pagar ahora) y
`whatsapp_click` (transferencia), con los mismos parámetros que ya se usan.

### Pruebas

Ampliar `tools/selftest.php`: lectura y validación de la cookie (líneas inválidas, límites),
cálculo de totales desde el catálogo, referencia nueva y lectura de la vieja, mensaje de WhatsApp,
validación del formulario de envío, armado del correo y que no salga dos veces.

### Privacidad

Revisar `/privacidad` (`app/content/policies.php`) y ajustar si no cubre la recolección de cédula,
dirección, celular y correo para despachar pedidos pagados en línea.

## Lo que queda a criterio de quien lo construya

Nombres de funciones y archivos, el marcado exacto de la página, la animación del aviso, el ícono
del carrito y cómo se reparte el código entre `helpers/` y `app/`.
