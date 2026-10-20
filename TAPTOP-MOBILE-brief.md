# Taptop Mobile: Brief de desarrollo web

> Documento base para desarrollar el sitio en Claude Code.
> Fuente: respuestas del cliente + referencias visuales.
> Estado: **brief cerrado y listo para construir (revisión final del 15-oct-2026)**. Stack: PHP en Hostinger. Archivos que acompañan a este documento: `TAPTOP-MOBILE-specs-borrador.md` (fichas técnicas) y `taptop_3d_01_frontal_0.png` (logo 3D). Los puntos marcados con ⏳ están pendientes del cliente; los marcados con ⚠️ son supuestos o riesgos a confirmar; los marcados con ✔ están decididos.

---

## 1. Resumen del proyecto

| Campo | Detalle |
|---|---|
| **Nombre del negocio** | **Taptop Mobile** (antes Nuvo/Nubo Mobile: ya NO usar esos nombres) |
| **Qué vende** | Celulares y productos **Apple y Samsung**, 100% originales, amplia variedad |
| **Respaldo** | **Distribuidor autorizado de Apple y de Samsung** |
| **Idioma** | Español únicamente (por ahora) |
| **Hosting** | **Hostinger, plan Premium** (hosting compartido) |
| **Tecnología** | **PHP personalizado** (sin framework, sin Node en el servidor) |
| **Dominio** | ⏳ Aún no definido (por ahora se trabaja en el dominio temporal de Hostinger, con `noindex`). Sugerencia: `taptopmobile.com` o `.co` (verificar disponibilidad). El plan Premium de Hostinger incluye un dominio gratis el primer año; si se compra ahí queda enlazado al hosting automáticamente |
| **WhatsApp Business** | **+57 320 808 2188** (enlace: `https://wa.me/573208082188`) |
| **Redes sociales** | El perfil de Instagram aún no existe. El ícono va maquetado en el footer con enlace provisional a `instagram.com` hasta recibir el perfil (ver 4) |
| **Tráfico estimado** | Desconocido. Diseñar para pocas visitas y poder escalar |
| **Referencias** | iphoneshopbogota.com (tarjetas de producto) y celudmovil.com.co (carrusel publicitario). Solo inspiración: **no copiar, construir identidad propia** |

## 2. Objetivo del sitio

1. **Visibilidad:** que más gente conozca la marca y todo lo que ofrece.
2. **Conversión:** que el visitante evalúe los factores a favor y compre.
3. **Acción principal:** escribir por **WhatsApp Business** para **confirmar disponibilidad** y resolver dudas; resuelta la duda, se cierra la compra.

**Flujo:** ver producto y precio → clic en "Confirmar disponibilidad" → conversación en WhatsApp → compra.

## 3. Decisiones confirmadas

| Tema | Decisión |
|---|---|
| Marcas | Apple **y** Samsung entran al catálogo |
| Precios | **Dos precios visibles** en cada tarjeta y en el detalle: **precio en efectivo/transferencia** y **precio con pasarela de pago**. **Sin filtros, rangos ni índices de precio** |
| Rol de WhatsApp | Confirmar **disponibilidad** y cerrar la venta (los precios ya están visibles) |
| Carga de precios | **Cada producto tiene sus dos precios cargados a mano** (`price_cash` y `price_gateway`); no se calcula uno a partir del otro |
| Cobertura | **No hay tienda física.** **Se envía a toda Colombia** |
| Envío | **Envío gratis en compras desde $150.000 COP.** Por debajo de ese valor, el costo se confirma por WhatsApp (⏳ por definir) |
| Garantía | **Garantía del fabricante + garantía propia de Taptop Mobile:** si al equipo o al accesorio se le encuentra algún fallo, **la misma tienda lo cambia inmediatamente**. **Garantía propia: 3 meses.** La del fabricante es la estándar de cada marca (Apple, Samsung) y corre aparte. Condiciones ⏳ por definir |
| Medios de pago | **Consignación o depósito en efectivo, transferencia bancaria, tarjeta de crédito/débito, PSE y Nequi/Daviplata.** **No se recibe efectivo contra entrega:** todo pago debe estar confirmado **antes del envío**. Agrupación propuesta: **precio en efectivo/transferencia** → consignación, depósito en efectivo y transferencia; **precio con pasarela** → tarjeta, PSE y Nequi/Daviplata. Nequi va por la pasarela (Wompi). ⚠️ Daviplata solo se ofrece si Wompi lo confirma |
| Devoluciones por arrepentimiento | **Se aplica el derecho de retracto tal como lo exige la ley** (venta a distancia, Ley 1480 de 2011), sin condiciones adicionales. ⚠️ Un abogado debe validar plazos, excepciones y redacción antes de publicar. Referencia a verificar: 5 días hábiles desde la entrega, producto sin uso y en su empaque original, costo de la devolución a cargo del cliente |
| Flujo de compra | **Opción B: primero WhatsApp, después enlace de pago.** El asesor confirma disponibilidad, el cliente elige cómo pagar (efectivo/transferencia o pasarela) y, si es pasarela, recibe un enlace de pago por WhatsApp |
| Texto del botón | "Confirmar disponibilidad" (no "Comprar") |
| Material de producto | **Las fotos las toma el propio Taptop Mobile** (fotografía propia; ver guía en sección 9). Las especificaciones técnicas se basan en las oficiales de cada marca (es distribuidor autorizado), redactadas sin copiar textos completos. Respetar las guías de uso de marca de Apple y Samsung para logos y afirmaciones |
| Contenido iPhone | Mismas especificaciones de la página oficial de Apple |
| Fondo | Blanco |
| Hosting y código | **Hostinger + PHP personalizado** |
| Pagos en línea | **Se van a agregar pasarelas de pago**, vía enlace de pago enviado por WhatsApp (pasarela por definir; ver 10.7) |
| Citas / agenda | No |
| Formularios | No en el MVP; WhatsApp cubre el contacto |

## 4. Mapa del sitio

```
/                          Inicio
/productos                 Listado de productos
/productos/{slug}          Detalle de producto
/contacto                  Contáctanos
/garantia                  Garantía, cambios y envíos
/terminos                  Términos y condiciones
/privacidad                Política de tratamiento de datos
```
URLs limpias (sin `.php`) mediante `.htaccess` y un enrutador simple en `index.php`.

⚠️ Las tres páginas de políticas (`/garantia`, `/terminos`, `/privacidad`) no las pidió el cliente como secciones, pero son necesarias para vender en línea en Colombia y para respaldar la garantía propia. Se enlazan desde el footer, no desde el menú principal. Usan una sola vista (`policy.php`) con contenido distinto.

✔ **Decisión del cliente: publicar versiones cortas y simples ahora y completarlas después.** Condiciones para que sea aceptable (mínimo obligatorio, no negociable):
- **`/privacidad` (lo más urgente):** la Ley 1581 de 2012 exige tener política de tratamiento de datos y **autorización del titular** desde que se recogen datos personales (nombre, teléfono, documento, correo, dirección por WhatsApp o por formulario). Versión corta mínima: responsable (razón social y NIT ⏳), datos que se recogen y para qué, derechos del titular (conocer, actualizar, rectificar, suprimir, revocar), canal para reclamos (WhatsApp y correo ⏳), y vigencia. Si se agrega un formulario, casilla de autorización sin marcar de antemano.
- **`/terminos`:** precios en COP con IVA incluido, dos precios según medio de pago, disponibilidad sujeta a confirmación por WhatsApp, proceso de compra (confirmación → pago → envío), medios de pago aceptados (sin efectivo contra entrega), datos del vendedor (razón social, NIT, correo, WhatsApp) y derecho de retracto en términos genéricos de la ley.
- **`/garantia`:** garantía del fabricante + 3 meses de Taptop Mobile, cambio por equipo equivalente del mismo valor, proceso de reclamo por WhatsApp y qué no cubre (golpes, humedad, mal uso). ⚠️ Sin la palabra "inmediatamente"; usar un plazo definido.
- Cada página lleva la fecha de última actualización y no promete lo que no esté definido (envíos, plazos, facturación).
- ⚠️ **Riesgo aceptado por el cliente:** publicar textos sin revisión legal puede dejar al negocio expuesto ante reclamos y a la Superintendencia de Industria y Comercio (SIC). **El abogado debe revisarlos antes del primer pedido real o dentro de un plazo fijado** ⏳ (fecha por acordar con el cliente). Claude redactará estas versiones cortas como borrador y las marcará como "versión inicial".
- ⏳ **Datos del negocio pendientes (el cliente aún no los tiene a la mano):** razón social, NIT, correo para reclamos y datos de contacto legal. Se guardan en `config.php` (`business_legal_name`, `business_nit`, `business_email`) y las tres páginas los leen de ahí. Mientras estén vacíos, las páginas muestran "[por completar]" y **el sitio no se debe publicar**: el despliegue falla si `business_nit` o `business_email` están vacíos en producción (comprobación en `config.php`/`helpers.php`).

### Navegación
- **Header fijo:** logo Taptop Mobile · Inicio · Productos · Contáctanos · botón WhatsApp.
- **Botón flotante de WhatsApp** en todas las páginas (esquina inferior derecha).
- **Footer:** logo, enlaces, WhatsApp, **ícono de Instagram ya maquetado**, aviso de datos/cookies.
  - ✔ **Decisión del cliente:** el ícono de Instagram va visible desde el inicio y enlaza a `https://www.instagram.com/` (página oficial de Instagram) **mientras no exista el perfil**. El cliente entregará el perfil después.
  - Implementación: `instagram_url` en `config.php`, con el valor provisional `https://www.instagram.com/`. Un solo cambio de esa línea cuando llegue el perfil (formato `https://www.instagram.com/usuario/`). Abre en pestaña nueva (`target="_blank" rel="noopener noreferrer"`), con `aria-label="Instagram de Taptop Mobile"` y ícono SVG propio (sin librerías de íconos).
  - ⚠️ **Antes del lanzamiento debe reemplazarse por el perfil real:** un enlace a la portada de Instagram, sin el perfil de la marca, le resta confianza a una tienda 100 % en línea. El despliegue debe avisar si `instagram_url` sigue con el valor provisional.

## 5. Inicio

### 5.0 Animación del logo (decidido: intro + hero)
- **Intro de marca:** al entrar por primera vez en una sesión, se muestra la animación del logo (1 a 3 segundos) y luego se revela el Inicio. Solo la primera visita de la sesión (`sessionStorage`), con botón "Saltar" visible y cierre automático. No se repite al navegar por otras páginas ni al volver al Inicio. Si el visitante llega directo a `/productos/{slug}`, no se muestra: no debe interponerse entre él y el producto.
- **Hero:** la animación también aparece en la parte superior del Inicio, como primera diapositiva del carrusel o como pieza junto a él (formato por definir al ver el archivo). Si es la primera diapositiva, dura lo que dure la animación en lugar de los 3 s fijos.
- **Reglas técnicas:** archivo de ~1–2 MB, sin sonido, `preload` mínimo, imagen fija de respaldo (`poster`), respeto de `prefers-reduced-motion` (sin intro, solo imagen fija) y sin retrasar el contenido: el texto, el menú y el botón de WhatsApp cargan antes que la animación. El intro no debe bloquear la indexación ni mover el diseño (CLS).
- ✔ **El cliente tiene un modelo 3D** (.glb) y lo subirá al proyecto. Esto cambia la implementación, que queda así:
  - **Visor 3D:** se usa `<model-viewer>` (componente web de Google, cargado solo en el Inicio y de forma diferida, alojado en `public_html/assets/vendor/`, sin CDN). Es la única librería JS externa permitida y se justifica porque implementar 3D a mano no es viable; no se usa Three.js directo ni un framework.
  - **Peso del modelo:** optimizado para web (compresión Draco o meshopt, texturas WebP/KTX2 de máx. 1024–2048 px) y objetivo de **≤ 3 MB**; hasta 5 MB como límite duro. Si pesa más, el cliente o Claude lo optimizan antes de publicar.
  - **Intro (una vez por sesión):** para que sea rápida y no falle en celulares modestos, la intro puede ser un **video corto renderizado desde el modelo** (MP4/WebM, 1–2 MB) en lugar de 3D en tiempo real. El 3D en tiempo real queda para el hero.
  - **Hero:** el modelo se muestra en 3D en tiempo real (rotación suave automática, que el visitante pueda girarlo con el dedo o el mouse, sin zoom que estorbe el scroll en móvil). Se carga solo cuando el hero es visible y tras el contenido principal.
  - **Plan B obligatorio:** imagen fija (`poster`) mientras carga, y si el visitante tiene "reducir movimiento", ahorro de datos, conexión lenta o dispositivo de gama baja, o WebGL falla, se muestra solo la imagen o el video, nunca una pantalla vacía.
  - ✔ **Licencia y origen:** el modelo fue creado desde cero para Taptop Mobile, con su logo; es propiedad de la marca. Al recibirlo, comprobar solo que no incluya marcas de Apple o Samsung.

### 5.1 Hero: carrusel publicitario
- **Cambia cada 3 segundos** de forma automática.
- Pausa al pasar el cursor o tocar, flechas, puntos indicadores y swipe en móvil.
- Respeta `prefers-reduced-motion` (sin autoplay si el usuario lo desactiva).
- Slides previstos:
  1. **iPhone 18**: vista destacada.
  2. **iPhone 18 en todos sus colores**, incluido **Burgundy** (el cliente escribió "borgondy").
  3. **Samsung Galaxy S26 / S25**.
  4. **MacBook**: fotos **horizontales**, con todos los detalles visibles (teclado, bordes, pantalla), nada plano.
- ⚠️ **Alinear los slides con el catálogo real:** el catálogo trae iPhone 16, 17 y **18 Pro / Pro Max** (no un "iPhone 18" base), el color **Burgundy** (es el "morado" de la hoja, el mismo color; en la web se usa el nombre Burgundy), y **Mac mini / Mac Air M5** (no "MacBook"). Confirmar con el cliente qué se anuncia en cada slide para no mostrar un producto que no se vende.
- Cada slide: título corto, subtítulo y CTA ("Ver producto" o WhatsApp).
- **Rendimiento:** primer slide sin lazy (con `fetchpriority="high"`), el resto `loading="lazy"`; WebP/AVIF; `width`/`height` definidos para evitar saltos de layout.
- Los slides salen de `app/data/hero-slides.json`.

### 5.2 Debajo del hero
- Categorías / productos destacados (tarjetas de la sección 6).
- **"Por qué comprar en Taptop Mobile": tres pilares definidos por el cliente: Precios, Garantía y Profesionalismo.**
  - **Precios:** los dos precios visibles, con mejor precio por consignación/transferencia, y envío gratis desde $150.000. ⚠️ Evitar frases como "los precios más bajos" salvo que se puedan demostrar; usar "precios competitivos" o datos concretos.
  - **Garantía:** garantía del fabricante + 3 meses de garantía Taptop Mobile, con cambio por equipo equivalente.
  - **Profesionalismo:** distribuidor autorizado de Apple y Samsung, productos 100% originales, atención por WhatsApp todos los días, envío asegurado a toda Colombia (⏳ confirmar seguro de envío antes de prometerlo; la facturación electrónica no se menciona en la web hasta resolverla con el contador, ver pendiente 11).
  - ⏳ Textos finales de cada pilar por redactar con el cliente. Cada pilar lleva un ícono simple y una frase corta, sin párrafos largos.
- CTA final a WhatsApp.

## 6. Productos

### 6.1 Listado
- Grilla de **tarjetas** limpias (inspiración: iphoneshopbogota.com).
- Cada tarjeta: imagen grande, marca, nombre, **dos precios**, selector de color y botón "Confirmar disponibilidad".
- **Productos con varias capacidades:** la tarjeta muestra el precio **"Desde"** de la variante más económica; al elegir capacidad, o un color con precio propio (como Burgundy), los dos precios se actualizan.
- **Presentación de los dos precios:** el precio en **efectivo/transferencia** es el principal (más destacado) y el precio con **pasarela de pago** va debajo, con etiqueta clara. ⚠️ Etiquetas propuestas: "Consignación o transferencia" (mejor que "Efectivo", para que nadie entienda que se paga al recibir) y "Con tarjeta / pasarela de pago". Ambos deben ser el **precio final** que paga el cliente (con IVA incluido), sin cargos ocultos. ⚠️ Validar con contador/abogado.
- **Envío gratis:** mostrar una etiqueta "Envío gratis" en los productos que cumplan el umbral de **$150.000 COP** (valor en `config.php`, no escrito en las vistas). Los productos por debajo del umbral indican "Costo de envío por confirmar". ✔ **El umbral se compara con el precio que el cliente realmente paga** (efectivo/transferencia o pasarela, según el método elegido). Consecuencia: un producto puede cumplir el umbral con un método y no con el otro; en la web, la etiqueta "Envío gratis" y el cálculo usan el precio de cada método y el asesor confirma el total del pedido por WhatsApp. En `config.php`: `free_shipping_min = 150000`.
- La diferencia entre los dos precios debe ser fácil de entender de un vistazo (opcional: una nota corta "El precio con pasarela incluye el costo de la plataforma de pago").
- **Los colores se ven en la tarjeta**, no solo en el detalle. Selector **circular** con el **color exacto** de cada modelo; al elegir un color **cambia la imagen**.
- Navegación por categoría (iPhone, Samsung, Mac, iPad, Relojes, AirPods y Accesorios, que incluye los adaptadores) y por marca. **Sin filtro de precio.**

### 6.2 Detalle de producto
- ✔ **Sin texto comercial (decisión del cliente): el detalle muestra solo especificaciones.** **El texto de los slides son las descripciones de las características** (aclarado por el cliente), y **no hay un bloque de "descripción del producto"** aparte; tampoco se redactan descripciones de marketing. El contenido es la ficha técnica oficial (ver `TAPTOP-MOBILE-specs-borrador.md`).
- **Descripción en slides:** slider con imagen + **un titular corto de especificación por slide**, tomado de la ficha (por ejemplo "Pantalla OLED de 6,3 pulgadas", "Chip A19", "Cámara principal de 48 MP"). Cada slide = una foto propia + una línea de dato real, sin adjetivos ni promesas. 3 a 5 slides por producto; si el producto no tiene fotos propias aún, se omite el slider y se muestra solo la foto principal y el acordeón. Los titulares se guardan en `product_slides` (o en el JSON semilla) y se editan en el panel.
- **Características principales por clic, en desglose:** acordeón expandible (cámara, pantalla, chip, batería, almacenamiento, conectividad...).
- Selector de color circular + selector de almacenamiento.
- Los **dos precios** visibles (efectivo/transferencia y pasarela), con el mismo formato que en la tarjeta.
- **Bloque de confianza** junto al botón: producto original de distribuidor autorizado, garantía del fabricante + 3 meses de garantía Taptop Mobile, y envío gratis si aplica. Con enlace a `/garantia`.
- Botón principal: **"Confirmar disponibilidad"** → WhatsApp con mensaje prellenado (producto, color, capacidad).
- Botón secundario: "Resolver una duda".

### 6.3 Catálogo
- **Catálogo inicial recibido** (hoja de Google Sheets "Lista de precios", pestaña `Precios`, estado al 15-oct-2026). Son **34 referencias** en la hoja (**30 para el lanzamiento**: sin la segunda línea del Burgundy y **sin los dos iPad A16 ni el iPad 11 M4, excluidos por ahora por decisión del cliente**) en 8 categorías: iPhone, Samsung, Mac, iPad, Relojes, AirPods, Accesorios y Adaptadores.
- 🔒 **Los costos, márgenes y ganancias de esa hoja son internos y NO deben entrar al sitio, al código, al repositorio ni a la base de datos.** Del archivo solo se importan nombre, categoría, `price_cash` y `price_gateway`.
- La hoja calcula el precio con pasarela con la fórmula `precio_gateway = ⌈(precio_cash + 700 × 1,19) ÷ (1 − 2,65% × 1,19)⌉` redondeado hacia arriba al millar (verificada contra varias filas). Sigue siendo una decisión de carga manual: la fórmula solo sirve de ayuda (ver 10.8).
- ⚠️ La comisión usada en la hoja (2,65% + $700 + IVA) debe coincidir con la que Wompi aplique realmente a la cuenta; si la real es mayor, el precio con pasarela deja menos ganancia de la prevista.
- **Precios de partida (COP):**

| Producto | Categoría | Efectivo/transferencia | Pasarela |
|---|---|---|---|
| Pencil Pro | Accesorios | 430.000 | 445.000 |
| Reloj 12 46 | Relojes | 1.950.000 | 2.015.000 |
| Reloj Ultra 4 | Relojes | 3.200.000 | 3.306.000 |
| AirPods Pro 3 | AirPods | 790.000 | 817.000 |
| AirPods 4 Gen cancelación R | AirPods | 650.000 | 673.000 |
| iPad Pro M5 11 256 | iPad | 3.950.000 | 4.080.000 |
| Mac mini M4 256 | Mac | 3.150.000 | 3.254.000 |
| Mac mini M4 512 24 gigas | Mac | 4.500.000 | 4.648.000 |
| Mac mini M4 512 24 gigas Pro | Mac | 5.800.000 | 5.990.000 |
| Mac Air M5 1 tera, 16 gigas | Mac | 5.200.000 | 5.371.000 |
| Mac Air M5 1 tera, 24 gigas | Mac | 6.100.000 | 6.300.000 |
| iPhone 16 128 | iPhone | 2.850.000 | 2.944.000 |
| iPhone 17 256 | iPhone | 3.320.000 | 3.429.000 |
| iPhone 17 Pro Max 256 GB | iPhone | 4.250.000 | 4.390.000 |
| iPhone 17 Pro Max 256 GB con SIM física | iPhone | 4.330.000 | 4.472.000 |
| iPhone 18 Pro 256 | iPhone | 4.550.000 | 4.700.000 |
| iPhone 18 Pro 256 GB Burgundy (en la hoja: "morado") | iPhone | 4.650.000 | 4.803.000 |
| iPhone 18 Pro Max 256 GB | iPhone | 5.400.000 | 5.577.000 |
| iPhone 18 Pro Max 512 GB | iPhone | 5.950.000 | 6.145.000 |
| S26 Plus 512 | Samsung | 3.150.000 | 3.254.000 |
| S25 Ultra 256 | Samsung | 2.900.000 | 2.996.000 |
| S25 Ultra 512 | Samsung | 3.270.000 | 3.378.000 |
| S26 Ultra 256 | Samsung | 3.330.000 | 3.440.000 |
| S26 Ultra 512 | Samsung | 3.800.000 | 3.925.000 |
| Z Fold 8 Ultra 512 | Samsung | 6.350.000 | 6.558.000 |
| Z Fold 8 512 | Samsung | 5.350.000 | 5.526.000 |
| Z Flip 8 512 | Samsung | 3.850.000 | 3.977.000 |
| Adaptadores Nicols | Adaptadores | 93.000 | 97.000 |
| Adaptador Apple NFC | Adaptadores | 95.000 | 99.000 |
| Adaptador Apple no NFC | Adaptadores | 85.000 | 89.000 |

- ⚠️ **Dudas de los datos por resolver antes de publicar:**
  - ✔ `iPhone 18 Pro 256 morado` es **uno solo y en la web se llama Burgundy** (morado y Burgundy son el mismo color), a $4.650.000 (efectivo) y $4.803.000 (pasarela). La segunda línea del morado ($5.800.000) **queda fuera por ahora** y no se publica.
  - ✔ `iPhone 18 Pro 256 Max` es el **iPhone 18 Pro Max de 256 GB**.
  - ⏳ **El precio cambia con el color:** el iPhone 18 Pro 256 GB vale $4.550.000 y en Burgundy $4.650.000. Confirmar a qué colores aplica la fila de $4.550.000 y si otros modelos también tienen precio distinto por color.
  - ✔ `iPhone 17 Pro 256 Max sim card` es el **iPhone 17 Pro Max de 256 GB con SIM física**. Confirmado también que "Max" significa "Pro Max" en las otras filas (iPhone 17 Pro 256 Max e iPhone 18 Pro 512 Max).
  - Los nombres son de trabajo interno ("Mac Air M5", "Reloj 12 46", "Gen cancelación R"): hay que escribir **nombres comerciales limpios** para la web.
- **Colores oficiales por modelo** (nombres tal cual los publican Apple y Samsung; investigados el 15-oct-2026, a verificar contra el equipo real que llegue):

| Modelo | Colores |
|---|---|
| iPhone 16 | Black, White, Pink, Teal, Ultramarine |
| iPhone 17 | Black, White, Mist Blue, Sage, Lavender |
| iPhone 17 Pro Max | Silver, Cosmic Orange, Deep Blue |
| iPhone 18 Pro / 18 Pro Max | Black, Silver, Glacier, Burgundy |
| Galaxy S26 Plus / S26 Ultra | Cobalt Violet, Sky Blue, Black, White, Silver Shadow, Pink Gold |
| Galaxy S25 Ultra | Titanium Silverblue, Titanium Black, Titanium Gray, Titanium Whitesilver |
| Galaxy Z Fold 8 Ultra | Cream, Graphite, Violet Shadow, Green Shadow (exclusivo Samsung.com) |
| Galaxy Z Fold 8 | Cream, Graphite, Lavender, Pistachio (exclusivo Samsung.com) |
| Galaxy Z Flip 8 | Cream, Graphite, Pink, Mint (exclusivo Samsung.com) |

  - Los códigos hex no son oficiales: se sacan con el gotero desde las fotos que tome el cliente. Los colores "exclusivos de Samsung.com" pueden no llegar a Colombia; confirmar con el distribuidor.
  - ✔ **Taptop Mobile vende todos esos colores.** Todos se cargan al lanzamiento.
  - ✔ **Inventario y disponibilidad:** el cliente irá actualizando el inventario más adelante. Cuando un color o capacidad está agotado **se muestra marcado "Agotado"**: el círculo de color aparece tachado/atenuado y no se puede elegir, y la capacidad agotada aparece deshabilitada. El producto sigue visible. Si todas las variantes están agotadas, la tarjeta muestra "Agotado" y el botón cambia a "Preguntar por disponibilidad" (WhatsApp). Los precios siguen visibles. El comportamiento (mostrar o marcar o ocultar) se controla con una opción en `config.php`. `available` existe por producto, por variante y por color, y se edita desde el panel admin.
- ✔ **Nombres comerciales confirmados** (el nombre de la hoja es solo interno; la web usa el de la derecha). Capacidad y color van como variante, no en el nombre:

| Nombre en la hoja | Nombre en la web | Marca |
|---|---|---|
| Pencil Pro | Apple Pencil Pro | Apple |
| Reloj 12 46 | Apple Watch Series 12 (46 mm) | Apple |
| Reloj Ultra 4 | Apple Watch Ultra 4 | Apple |
| AirPods Pro 3 | AirPods Pro 3 | Apple |
| AirPods 4 Gen cancelación R | AirPods 4 con cancelación activa de ruido | Apple |
| iPad Pro M5 11 256 | iPad Pro 11" (M5) | Apple |
| Mac mini M4 256 | Mac mini M4 | Apple |
| Mac mini M4 512 24 gigas | Mac mini M4, 24 GB / 512 GB | Apple |
| Mac mini M4 512 24 gigas Pro | Mac mini M4 Pro, 24 GB / 512 GB | Apple |
| Mac Air M5 1 tera 16 / 24 gigas | MacBook Air M5, 16 GB o 24 GB / 1 TB | Apple |
| iPhone 16 128 | iPhone 16 | Apple |
| iPhone 17 256 | iPhone 17 | Apple |
| iPhone 17 Pro 256 Max (y SIM física) | iPhone 17 Pro Max (variante "SIM física") | Apple |
| iPhone 18 Pro 256 / Burgundy | iPhone 18 Pro (color Burgundy con precio propio) | Apple |
| iPhone 18 Pro 256 / 512 Max | iPhone 18 Pro Max | Apple |
| S26 Plus 512 | Samsung Galaxy S26+ | Samsung |
| S25 Ultra 256 / 512 | Samsung Galaxy S25 Ultra | Samsung |
| S26 Ultra 256 / 512 | Samsung Galaxy S26 Ultra | Samsung |
| Z Fold 8 Ultra 512 | Samsung Galaxy Z Fold8 Ultra | Samsung |
| Z Fold 8 512 | Samsung Galaxy Z Fold8 | Samsung |
| Z Flip 8 512 | Samsung Galaxy Z Flip8 | Samsung |
| Adaptadores Nicols / Apple NFC / Apple no NFC | Adaptador Nicols; Adaptador Apple (NFC); Adaptador Apple (sin NFC) | Nicols / Apple |

  - Las filas con la misma base (iPhone 18 Pro, S25 Ultra, etc.) se agrupan en **un solo producto con varias variantes** (capacidad), no en tarjetas separadas. El seeder debe agruparlas así.
- ✔ **Especificaciones:** Claude prepara un borrador por producto desde fuentes oficiales y el cliente lo revisa antes de publicar (archivo `TAPTOP-MOBILE-specs-borrador.md`). Ya cubre iPhone 16, 17, 17 Pro Max, 18 Pro/Pro Max (parcial), S25 Ultra, AirPods Pro 3, Apple Watch Ultra 4, Apple Watch Series 12, MacBook Air M5, iPad Pro M5, Mac mini M4/M4 Pro y Apple Pencil Pro, y los Samsung S26+, S26 Ultra, Z Fold8 Ultra, Fold8 y Flip8 (de fuentes de terceros, a verificar; faltan algunos datos del Fold8 y Flip8); faltan solo los adaptadores. ✔ El cliente vende **todas las variantes** de cada producto (MacBook Air 13" y 15", iPad Pro con y sin celular, Apple Watch en todos los materiales y conectividades). ⏳ La hoja trae un solo precio por fila, así que **hay que pedir los precios de las variantes que no aparecen** (ver sección 13). ⏳ Falta saber qué versión del Series 12 (46 mm) es la de la hoja: material (aluminio, titanio o cerámica) y conectividad (GPS o celular), porque cambian el precio y los colores. ✔ Confirmados por el cliente: Apple Watch Ultra 4 existe y se vende, el Mac mini M4 es el equipo correcto, y el iPhone 17 Pro Max se vende en **ambas versiones (eSIM y SIM física)** en Colombia. Si hay duda, el producto sale sin especificaciones hasta confirmarlas.
- **Faltan, y la hoja no los trae:** fotos propias (ver guía en sección 9) y cantidades de stock. (Marca, colores, disponibilidad y nombres comerciales ya están definidos arriba.)
- **Envío gratis:** los tres adaptadores ($85.000 a $99.000) quedan por debajo del umbral de $150.000, así que muestran "Costo de envío por confirmar".
- El catálogo debe poder crecer editando datos, sin tocar las plantillas. Tras la importación inicial, **el panel admin es la fuente de verdad** de los precios, no la hoja.

## 7. Contáctanos

- Botón principal: **WhatsApp Business**.
- Mensaje de contacto general prellenado.
- Datos de contacto: WhatsApp, horario de atención e Instagram (enlace provisional hasta recibir el perfil).
- **Atención por WhatsApp todos los días.** ✔ **Decisión del cliente: se publica un horario fijo** (el mismo todos los días), mostrado en Contáctanos y en el footer, con la zona horaria de Colombia. ⏳ **Las horas reales las debe dar el cliente** (no se inventan; en `config.php`: `support_hours_label`, hoy con el valor "[por completar]" y el despliegue no debe salir con ese valor). Configurar en WhatsApp Business un **mensaje de ausencia** para fuera de esa franja que diga cuándo responderán.
- **No hay tienda física:** no se muestra dirección ni mapa, y no se usan datos estructurados de negocio local. En su lugar, marcar `areaServed: Colombia` en el JSON-LD de la organización.
- **Bloque de envíos** (también visible en el footer y en el detalle de producto): cobertura a toda Colombia, **envío gratis desde $150.000 COP**, ⏳ costo de envío por debajo de ese valor, **tiempos de entrega que varían según la ciudad** (⏳ plazos exactos por zona; propuesta: una tabla simple "ciudades principales / resto del país", y más adelante un selector de ciudad) y ⏳ transportadora (✔ **decisión: aún no definida; el sitio no nombra ninguna transportadora ni promete plazos**. Cada ciudad se muestra como "Tiempo y costo de envío: se confirman por WhatsApp según tu ciudad", con botón al asesor. En `config.php` queda una tabla vacía `shipping_zones` (ciudad o zona, plazo, costo) y un campo `carrier_name`; cuando se definan, se llenan y la web pasa a mostrar la tabla sin tocar las vistas. Criterios para elegirla: cobertura nacional, **seguro por valor declarado** para equipos de alto valor, rastreo en línea y tarifa corporativa), y seguimiento del pedido por WhatsApp.
- **Confianza (al ser tienda 100% en línea):** reforzar en Inicio y Contáctanos los factores que reemplazan a un local: distribuidor autorizado de Apple y Samsung, productos originales, garantía y atención directa por WhatsApp.
- **Garantía Taptop Mobile (propia, además de la del fabricante):** si al equipo o a un accesorio se le encuentra algún fallo, **Taptop Mobile lo cambia directamente**, sin que el cliente tenga que gestionarlo con la marca. Es uno de los mensajes de confianza más fuertes de la web: mostrarlo en Inicio, en el detalle de producto y en el footer. ⚠️ Mensaje propuesto: "3 meses de garantía Taptop Mobile: si presenta un fallo, te lo cambiamos".
  - ⚠️ "Inmediatamente" es una promesa que obliga. Conviene escribirla en los términos con **plazo máximo, qué cubre** (defecto de fábrica) **y qué excluye** (golpes, humedad, mal uso), para evitar reclamos.
  - **Plazo: 3 meses desde la entrega.** La garantía del fabricante es la estándar de cada marca y corre aparte. ⚠️ No escribir en la web cuántos meses dura la del fabricante sin verificarlo con la marca o el proveedor (¿Samsung aplica igual que Apple?).
  - ⏳ Definir: proceso (aviso por WhatsApp, fotos o video del fallo, recolección del equipo), quién asume el envío, y qué cubre exactamente el cambio. **Decidido:** si no hay una unidad igual disponible, se cambia por **un equipo equivalente del mismo valor** (no reembolso). ⚠️ Redactarlo en `/garantia` y `/terminos` para que quede claro qué se considera "equivalente".
- Sin agendamiento de citas.

## 8. WhatsApp Business

- Todos los CTAs → `https://wa.me/573208082188?text=<mensaje codificado con rawurlencode>`.
- ✔ **Mensaje prellenado (decisión del cliente): incluye producto, color, capacidad y los dos precios vistos.** Ejemplo: *"Hola Taptop Mobile, quiero confirmar disponibilidad del iPhone 18 Pro Burgundy 256 GB. Vi el precio de $4.650.000 por consignación o transferencia, y $4.803.000 con tarjeta o pasarela."*
  - Los precios se toman del catálogo en el servidor al generar el enlace (no del navegador), con formato COP y puntos de miles.
  - Si el producto tiene un precio distinto por color o por SIM física, el mensaje usa el de la variante elegida.
  - Botón general (sin producto, como el flotante o el de Contacto): *"Hola Taptop Mobile, quiero información sobre sus productos."*
  - Producto agotado: *"Hola Taptop Mobile, quiero saber cuándo habrá disponibilidad del … (color, capacidad)."* sin precios.
  - ⚠️ El mensaje es una referencia para el asesor, no un compromiso: el precio y la disponibilidad se **confirman por WhatsApp** (ya lo dicen los términos). Longitud máxima razonable (< 500 caracteres) y todo con `rawurlencode`.
- El número vive en un solo lugar: `app/config.php` (cargado desde un archivo fuera de la carpeta pública).
- ✔ **Atención 100 % manual por ahora (decisión del cliente): no se construye ningún asistente virtual.** El sitio solo **redirige** a WhatsApp con el mensaje prellenado, y un asesor responde a mano. No hay chatbot, ni WhatsApp Cloud API, ni webhook, ni integraciones en esta fase.
  - Lo único que se hace dentro de la app WhatsApp Business (configuración manual del cliente, sin código): mensaje de bienvenida, mensaje de ausencia, respuestas rápidas y etiquetas. Es opcional.
  - Un asistente virtual queda como idea para el futuro, fuera del alcance actual; se decidirá con datos reales de uso.
- ⚠️ El número debe estar registrado en **WhatsApp Business**, no en el WhatsApp normal.

## 9. Dirección visual

- **Fondo blanco** en todo el sitio.
- Tipografía **sencilla, no brusca**. ⚠️ Propuesta: Inter (autoalojada, sin depender de Google Fonts) en pesos regular/medium.
- **Buen uso del espacio:** márgenes generosos, jerarquía clara, estilo premium minimalista.
- Imágenes con **profundidad y detalle**, no planas.
- Mobile-first.

### Fotografía de producto (la toma el cliente)

- **Tomas por producto y color (mínimo):** frontal, trasera, ángulo 3/4 con el borde, y un detalle (cámara, pantalla o puertos). Mac e iPad: abiertos y en horizontal, con teclado y bordes visibles. Relojes: caja y correa. Accesorios: producto solo y en uso.
- **Fondo y luz:** fondo claro y uniforme que combine con el sitio blanco, luz suave y difusa, sin reflejos fuertes en pantallas ni huellas. Una **sombra suave** bajo el producto da profundidad (el cliente no quiere imágenes planas).
- **Consistencia:** mismo encuadre, ángulos y márgenes en todo el catálogo, para que las tarjetas se vean ordenadas.
- **Color fiel:** balance de blancos calibrado (carta gris) y sin filtros. El color de la foto debe coincidir con el círculo del selector (código hex).
- **Pantalla encendida** con un fondo neutro, sin datos personales ni notificaciones.
- **Resolución y formato:** lado largo de al menos 2400 px, JPG de alta calidad o PNG. El panel los convierte a WebP y genera los tamaños.
- **Hero del Inicio:** tomas **horizontales 16:9** (mínimo 2400 × 1350 px), una por slide, con espacio libre para el texto.
- **Nombres de archivo:** `marca-modelo-color-toma.jpg` (por ejemplo `apple-iphone-18-pro-burgundy-frontal.jpg`).
- ⚠️ **Limitación:** hay que tener físicamente cada color para fotografiarlo. Un color sin unidad no se muestra hasta tenerla. Con 30 referencias y varios colores serán cientos de fotos: priorizar iPhone y Samsung primero y lanzar con 3 tomas por color; completar el resto después.

### Identidad de marca
- **Logo recibido:** isotipo 3D de bloques redondeados en azul marino con ondas de señal en azul cian.
- ✔ **Colores de marca definidos a partir del render 3D del logo** (muestreados de la imagen `taptop_3d_01_frontal_0.png`; el render tiene iluminación, así que son aproximados y quedan **aprobados por el cliente al sacarlos del modelo**):
  - **Azul marino `#394770`** → color principal: botones, títulos, enlaces y texto de acento. Contraste con blanco ≈ 9:1 (cumple AA y AAA).
  - **Azul cian `#32DBFF`** → color de acento solo para detalles (líneas, íconos, indicadores, ondas del logo). Contraste con blanco ≈ 1,6:1: **nunca para texto ni para botones con texto blanco**.
  - Variante clara del marino para fondos suaves (≈ `#EEF1F8`) y texto secundario gris azulado (≈ `#5B6685`); fondo blanco `#FFFFFF`.
  - El botón de WhatsApp conserva el verde de WhatsApp (`#25D366`), para que se reconozca.
  - Variables CSS: `--color-primary`, `--color-accent`, `--color-surface`, `--color-text-muted`, `--color-whatsapp`.
- ⏳ **Pendiente:** logo en SVG/PNG con fondo transparente, versión plana para header y favicon (el 3D con sombra se ve mal en tamaño pequeño). Al recibir el modelo .glb, leer los colores base del material y sustituir los de arriba si difieren.
- ✔ **Animación 3D del logo:** definida en la sección 5.0 (intro + hero). Dejar el espacio reservado `partials/logo-animation.php`.
- Hasta tener los assets: usar **placeholder de logo** y variables CSS para los colores, fáciles de reemplazar.

## 10. Arquitectura técnica (PHP en Hostinger)

### 10.1 Stack

| Capa | Elección | Motivo |
|---|---|---|
| Lenguaje | **PHP 8.2+** (sin framework) | Es lo que corre en Hostinger sin complicaciones; el sitio es chico |
| Plantillas | PHP nativo con *partials* | Sin dependencias; separa lógica de vista |
| Enrutamiento | `.htaccess` + front controller (`index.php`) | URLs limpias y un solo punto de entrada |
| Estilos | **CSS propio con variables** (sin Tailwind) | No hay Node en Hostinger compartido; evita un paso de build |
| JavaScript | **Vanilla JS** (módulos pequeños) | Carrusel, selector de color y slider: pocas líneas, cero dependencias |
| Acordeón | `<details>` / `<summary>` nativo | Accesible por defecto, sin JS |
| Datos | **MySQL** (incluido en Hostinger) + **panel admin** desde el MVP | Dos personas (Santi y el cliente) actualizarán precios con frecuencia; editar un archivo a mano es riesgoso |
| Acceso a datos | PDO con consultas preparadas, vía `ProductRepository` | Seguridad y vistas independientes de la fuente de datos |
| Analítica | GA4 + Meta Pixel (solo con consentimiento) | Medir clics a WhatsApp y productos más vistos |
| 3D | `<model-viewer>` (única librería JS externa, autoalojada) | Mostrar el modelo 3D del logo en el hero; ver 5.0 |

**Trade-off a tener claro:** sin framework, la disciplina de estructura depende de nosotros. Por eso se define una estructura estricta (abajo) y se evita mezclar lógica con HTML.

### 10.2 Estructura de carpetas
```
taptop-mobile/
├── app/                          # FUERA de la carpeta pública
│   ├── config.php                # WhatsApp, nombre, `site_url`, IDs de analítica, `free_shipping_min` (150000), `support_hours_label`, `instagram_url`, datos legales (`business_*`), `shipping_zones`, credenciales de la base de datos
│   ├── helpers.php               # e() escape, whatsapp_link(), format_price(), asset()
│   ├── router.php                # resuelve la ruta y carga la vista
│   ├── helpers/analytics.php     # eventos GA4 / Meta Pixel (con consentimiento)
│   ├── backups/                  # exportaciones cifradas de MySQL (rotación de 14 días; nunca públicas)
│   ├── data/
│   │   ├── products.json         # catálogo semilla (solo nombre, categoría, marca, colores, variantes y precios)
│   │   └── hero-slides.json
│   └── views/
│       ├── partials/
│       │   ├── head.php
│       │   ├── header.php
│       │   ├── footer.php
│       │   ├── whatsapp-float.php
│       │   ├── product-card.php
│       │   ├── color-swatches.php
│       │   ├── hero-carousel.php
│       │   ├── description-slides.php
│       │   ├── specs-accordion.php
│       │   └── logo-animation.php   # reservado, vacío por ahora
│       └── pages/
│           ├── home.php
│           ├── products.php
│           ├── product-detail.php
│           ├── contact.php
│           ├── policy.php        # /garantia, /terminos y /privacidad
│           └── 404.php
└── public_html/                  # raíz pública en Hostinger
    ├── index.php                 # front controller
    ├── .htaccess                 # HTTPS, URLs limpias, caché, cabeceras de seguridad
    ├── robots.txt
    ├── sitemap.xml               # (generado o estático)
    └── assets/
        ├── css/main.css
        ├── js/
        │   ├── carousel.js
        │   ├── color-swatches.js
        │   ├── slider.js
        │   └── analytics.js
        ├── vendor/               # model-viewer autoalojado
        ├── fonts/
        └── images/
            ├── brand/            # logo, favicon (pendiente)
            ├── hero/
            └── products/
```
📌 **Con el panel admin (ver 10.8), `products.json` pasa a ser solo el archivo semilla** para cargar la base de datos la primera vez. Se agregan `app/db/` (conexión PDO, `schema.sql`, seeders), `app/Repositories/ProductRepository.php` y `app/admin/` (controladores y vistas del panel). `hero-slides.json` sigue como archivo. También: `tools/` (validador del JSON y utilidades) fuera de `public_html`.

⚠️ En Hostinger, `public_html` es la raíz pública y se puede crear `app/` un nivel arriba para que `config.php` y los datos **no sean accesibles por URL**. Si por alguna razón no se pudiera, bloquear `app/` con `.htaccess` (`Require all denied`).

### 10.3 Enrutamiento (`.htaccess`)
- Forzar **HTTPS** y un solo dominio (con o sin `www`).
- Reescribir todo lo que no sea un archivo existente a `index.php`.
- Rutas: `/`, `/productos`, `/productos/{slug}`, `/contacto`, `/garantia`, `/terminos`, `/privacidad`; cualquier otra → 404 real (código HTTP 404).
- Cabeceras de caché largas para `/assets/*`; compresión activada.
- Bloquear acceso a `.json` de datos y archivos ocultos si quedaran en la raíz pública.

### 10.4 Modelo de dato del producto
```json
{
  "slug": "iphone-18-pro",
  "brand": "apple",
  "category": "iphone",
  "name": "iPhone 18 Pro",
  "currency": "COP",
  "colors": [{ "name": "Burgundy", "hex": "#000000", "image": "/assets/images/products/apple-iphone-18-pro-burgundy.webp" }],
  "variants": [
    { "sku": "IP18P-256", "storage": "256 GB", "color": null, "price_cash": 0, "price_gateway": 0, "available": true },
    { "sku": "IP18P-256-BUR", "storage": "256 GB", "color": "Burgundy", "price_cash": 0, "price_gateway": 0, "available": true },
    { "sku": "IP18P-512", "storage": "512 GB", "color": null, "price_cash": 0, "price_gateway": 0, "available": true }
  ],
  "slides": [{ "title": "Chip A20 Pro", "image": "" }],
  "specs": [{ "title": "Cámara", "detail": "" }],
  "available": true
}
```
(Los `hex` se toman con el gotero desde las fotos propias; los nombres de color son los oficiales de cada modelo, ver 6.3. Cada slide lleva una foto y un titular de característica, sin texto comercial; ver 6.2.)
- **Los precios van en cada variante (capacidad y, si aplica, color o tipo de SIM), no en el producto**, porque el precio cambia con la capacidad (S25 Ultra 256 y 512), con el color (iPhone 18 Pro 256 en Burgundy) o con el tipo de SIM (iPhone 17 Pro Max con SIM física). `color` vacío (`null`) significa que la variante aplica a todos los colores sin precio propio. `price_cash` es el precio de **consignación/transferencia** (el nombre del campo es solo técnico; en pantalla se muestra con la etiqueta definida en 6.1).
- Validar el JSON al cargar (si falta un campo obligatorio, ese producto no se renderiza y se registra en el log).
- Precio formateado en COP con `number_format` (separador de miles con punto).
- Como los dos precios se cargan a mano, el validador revisa que ambos existan y sean números positivos. Si `price_gateway` es menor que `price_cash`, registra una **advertencia en el log** (probable error de carga) pero igual muestra el producto.

### 10.5 Seguridad (obligatorio desde el MVP)
- **Escapar toda salida** con `htmlspecialchars` (helper `e()`); nunca imprimir datos sin escapar.
- El `slug` de la URL se valida contra los productos existentes (lista blanca), no se usa para leer archivos.
- `config.php` y datos fuera de la raíz pública.
- Cabeceras: `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy` básica, `X-Frame-Options`.
- Errores de PHP **desactivados en pantalla** en producción (`display_errors=0`), registrados en log.
- Panel admin (entra en el MVP; ver 10.8): login con `password_hash`, sesiones seguras, token CSRF, PDO con consultas preparadas, límite de intentos.

✔ **Decisión final sobre el stack (15-oct-2026): PHP puro, sin framework y sin Node, con subida manual por FTP o Administrador de archivos.** Razones: (1) la subida manual es lo más simple (un par de carpetas, sin `vendor` ni Composer); (2) un framework como CodeIgniter 4 ahorraría poco, porque su login completo exige instalar paquetes con Composer; (3) Laravel es inviable con subida manual (miles de archivos). Se descartan WordPress y los sitios estáticos con Node (ver 10.1). Si más adelante se usa Git por SSH, se puede reconsiderar.

**Riesgo a controlar: el panel de administración lo escribimos nosotros.** Para compensar la falta de framework, es obligatorio:
- Usar solo `password_hash`/`password_verify`, tokens CSRF en todos los formularios, consultas PDO preparadas y salida escapada con `e()` (ver 10.5 y 10.8).
- Subida de imágenes con validación del tipo real, renombrado aleatorio y sin ejecución de PHP en `uploads/` (ver 10.8).
- **Auditoría de seguridad antes del lanzamiento:** pedirle a Claude Code una revisión del panel (inyección SQL, XSS, CSRF, sesiones, subida de archivos, control de roles) y corregir los hallazgos. Santi revisa el resultado.
- Ninguna dependencia externa salvo `<model-viewer>` (autoalojado), para que subir y actualizar siga siendo copiar archivos.

### 10.6 Despliegue en Hostinger
- PHP 8.2 o superior seleccionado en hPanel.
- Certificado **SSL gratuito** activado y redirección a HTTPS.
- Subida por Git (si el plan lo permite), FTP o Administrador de archivos.
- Cache de LiteSpeed activada si el plan la incluye.
- **Plan Premium: backups gratuitos semanales** (según la página de Hostinger). Para una tienda con precios que cambian seguido y un historial de cambios, eso es poco: programar una **exportación diaria de MySQL** (cron job, si el plan lo permite) guardada fuera del servidor, y exportar los datos antes de cada despliegue.
- Dominio apuntado a Hostinger (si se compra ahí, se enlaza solo).

**✔ Verificación real del hosting (capturas de hPanel del cliente, 15-oct-2026):**

| Requisito | Resultado | Acción |
|---|---|---|
| PHP | ✔ **8.3 activo**; disponibles 8.2, 8.3, 8.4 y 8.5 | Desarrollar y probar en 8.3. No subir a 8.5 hasta probar. |
| Extensiones | ✔ `pdo`, `pdo_mysql`, `mysqlnd`, `gd`, `imagick`, `fileinfo`, `intl`, `mbstring`, `opcache`, `zip`, `curl`, `openssl`, `exif` activas | Nada que activar. `sodium` no está activa y no hace falta (`password_hash` usa bcrypt integrado). |
| Límites de PHP | ✔ memoria 512 MB, subida 256 MB, POST 256 MB, ejecución 300 s, 20 archivos por subida | Sobra para fotos y el modelo 3D. En el panel limitar las fotos a 5 MB y el 3D a 5 MB. |
| MySQL | ✔ se puede crear una base nueva. Ya existen **3 bases de otros usos** (creadas en 2024 y 2025), sin sitio asignado | **No reutilizarlas.** Crear una base nueva y exclusiva (`..._taptop`) con su propio usuario. Límite de bases del plan: no aparece en la captura; verificar. |
| Backups | ⚠️ **Semanales** (último 12-oct, próximo 19-oct). Manuales cada 24 h. Los **archivos de exportación de la base de datos no entran** en el backup. Diarios: mejora de plan desde **COP 6.900/mes** | ✔ **Decisión del cliente: por ahora se mantienen los backups semanales** (los diarios, ≈ COP 6.900/mes, quedan como mejora recomendada). **Consecuencia obligatoria desde el día uno:** cron diario que exporte la base de datos MySQL (`mysqldump` no está disponible por restricción de funciones: hacerlo con PHP/PDO) a una carpeta fuera de `public_html`, conservando los últimos 14 días, **y una copia fuera del servidor** (descarga periódica por SSH/SFTP o envío cifrado a un almacenamiento externo). Probar la restauración antes del lanzamiento. Además, copia manual (cada 24 h permitida) antes de cada despliegue. Con solo backups semanales se pueden perder hasta 7 días de cambios de precios y productos. |
| SSH | ✔ **Activado por Santi** (15-oct-2026) | Pasar a **clave SSH** y desactivar el acceso por contraseña cuando sea posible. No compartir contraseñas por este chat. |
| Cron | ✔ Disponible (tipo PHP o personalizado). Hay un comando de ejemplo de WordPress (`wp-cron.php`) por defecto | Usar para la exportación diaria y limpieza de intentos de login. |
| Dominio | ⏳ **No definido.** El sitio temporal es `ivory-fly-931463.hostingersite.com` | Construir y probar en el dominio temporal con `noindex`. `site_url` en `config.php` para canonical, sitemap y GA4, y cambiarlo al definir el dominio. |

**✔ Ajustes de PHP: Santi indica que ya los reconfiguró** (no verificado por captura; confirmar con una nueva captura o con `phpinfo()` temporal). Lista aplicada o por aplicar (valores iniciales en hPanel → *Opciones de PHP*):
- `exposePhp`: **desactivar** (hoy activo). `logErrors`: **activar** (hoy apagado; sin esto no hay registro de fallos). `displayErrors`: se mantiene apagado.
- `session.cookieHttponly` y `session.cookieSecure`: **activar** (hoy apagados). `session.useStrictMode`: **activar**. `session.cookieSamesite`: cambiar de `None` a **`Lax`**.
- `date.timezone`: hoy UTC. En código usar `date_default_timezone_set('America/Bogota')` para fechas de precios y del historial.
- `disableFunctions` de Hostinger incluye `ini_alter` (alias de `ini_set`) y otras: **no depender de `ini_set` en el código**; usar `session_set_cookie_params()` y la configuración de hPanel. El código tampoco puede usar `exec`, `system`, `shell_exec`, `popen` ni `proc_open` (las miniaturas se generan con GD/Imagick, no con comandos).
- La captura muestra el **panel del plan Premium compartido**: confirmar el límite de procesos, CPU y archivos (inodos) en el panel de inicio.

### 10.7 Pasarelas de pago

**Estado:** ⏳ pasarela y fecha por definir. El MVP se construye **sin pagos**, pero con la arquitectura lista para agregarlos sin rehacer nada.

**Pasarela elegida: Wompi.** Ofrece enlaces de pago, tarjeta, PSE y Nequi. Daviplata no se confirmó en la fuente consultada: no prometerlo en la web hasta verificarlo con Wompi. Alternativa si Wompi no sirve: ePayco. ⚠️ Las comisiones de una fuente de terceros rondan 2,5% a 3% + IVA + un valor fijo por transacción con tarjeta; **confirmar tarifas, medios soportados y requisitos de apertura directamente con cada proveedor** antes de decidir. Al fijar el `price_gateway`, conviene que la diferencia con `price_cash` cubra esa comisión (más IVA). **Banco de la empresa: Bancolombia** → **Wompi** (de Bancolombia) es la opción que más encaja. ⚠️ Confirmar con Wompi tarifas, requisitos y tiempos de desembolso con cuenta Bancolombia. Criterios de elección: comisión por transacción, medios disponibles (deben cubrir tarjetas, PSE y Nequi/Daviplata si van por pasarela), tiempo de desembolso, calidad de su documentación/SDK de PHP, y soporte de enlaces de pago.

**Principios de diseño (no negociables):**
- **Checkout alojado o por redirección:** los datos de la tarjeta **nunca pasan por nuestro servidor**. Reduce el alcance de cumplimiento PCI y el riesgo.
- **El pago se confirma solo por webhook verificado** (validando la firma de la pasarela), nunca porque el navegador volvió a la página de "gracias".
- **El precio lo calcula siempre el servidor** a partir de `products.json`/MySQL, nunca viene del navegador.
- **Idempotencia:** si la pasarela envía el mismo webhook dos veces, el pedido no se duplica ni se marca dos veces.
- **Llaves de la pasarela** en `app/config.php` fuera de la raíz pública, con entornos separados (pruebas y producción).

**Qué implica técnicamente:**
- **Nivel 1 (flujo elegido, sin código de pagos):** el asesor crea el enlace de pago en el panel de la pasarela y lo envía por WhatsApp. **No requiere desarrollo de pagos en la web.** El sitio solo muestra los dos precios y el botón de WhatsApp. Riesgo a tener presente: el monto del enlace lo escribe una persona, así que puede haber errores; conviene un procedimiento de verificación contra `price_gateway`.
- **Nivel 2 (automatización, opcional y futuro):** generar el enlace por API de la pasarela y registrar el pago por webhook. Requiere:
- Tablas nuevas en MySQL (`orders`, `order_items`, `payments`, y `customers` si aplica); la base de datos ya existe desde el MVP por el panel admin.
- Estados del pedido: `pendiente → pagado → enviado → entregado` (y `fallido` / `cancelado`).
- Páginas nuevas: `/carrito`, `/checkout`, `/pedido/{id}` (estado), y el endpoint `/webhooks/{pasarela}`.
- Control de **stock** al confirmar el pago.
- Correos transaccionales (confirmación de pedido).
- Panel admin para ver y gestionar pedidos.

**Cumplimiento (⚠️ validar con contador/abogado):**
- Facturación electrónica ante la DIAN.
- Derecho de retracto en ventas a distancia (Estatuto del Consumidor, Ley 1480 de 2011) y política de devoluciones.
- Política de tratamiento de datos personales (Ley 1581 de 2012); se recogen nombre, teléfono, dirección y posiblemente documento.
- Términos y condiciones, garantía y política de envíos visibles en el checkout.

**Flujo elegido: B) WhatsApp primero, luego enlace de pago.**
1. El visitante ve los dos precios y pulsa "Confirmar disponibilidad".
2. WhatsApp se abre con mensaje prellenado (producto, color, capacidad y los dos precios vistos; ver 8).
3. El asesor confirma disponibilidad.
4. Si el cliente paga por consignación, depósito en efectivo o transferencia → se le dan los datos de la cuenta. **El envío sale solo después de confirmar el pago.**
5. Si paga con pasarela → el asesor envía el enlace de pago por WhatsApp.
6. **Pago confirmado en la cuenta** (verificar que el dinero haya entrado, no solo el comprobante: los comprobantes falsos son un fraude frecuente) → despacho y seguimiento por WhatsApp.

Alternativas evaluadas y descartadas por ahora:
- **A) Compra directa:** carrito → checkout → pago en línea; WhatsApp queda para dudas y soporte. Requiere stock confiable en la web.
- **B) WhatsApp primero:** el asesor confirma disponibilidad y envía un **enlace de pago** de la pasarela. Más simple y sin riesgo de vender lo que no hay.
- **C) Híbrido:** los dos caminos conviven; productos con stock confirmado se compran directo, el resto se consulta por WhatsApp.

### 10.8 Base de datos y panel de administración (entra en el MVP)

> ✔ **Decisión del cliente (15-oct-2026): se necesita el panel de administración, así que la base de datos MySQL entra en el MVP** (se había pospuesto por unos minutos y se revirtió). Se mantiene `ProductRepository` como interfaz y `products.json` como archivo semilla para la primera carga y como respaldo exportable.
> - ⏳ **Para empezar:** crear en hPanel una base de datos **nueva y exclusiva** (no reutilizar las tres existentes), con su propio usuario. Santi la crea; las credenciales van solo en `config.php` fuera de `public_html` (nunca en Git ni en el chat).
> - **Respaldo de la base:** con backups semanales de Hostinger, la **exportación diaria de MySQL por cron (PHP/PDO) y una copia fuera del servidor son obligatorias** (ver 10.6). ✔ **La copia externa va a Google Drive** (carpeta del cliente o de Santi, decisión del cliente). Cada exportación diaria se comprime y **se cifra** antes de salir del servidor, porque contiene datos del negocio. Implementación en dos pasos: (1) el cron deja la exportación cifrada en `app/backups/` (fuera de `public_html`, con rotación de 14 días); (2) subida a Drive con una **cuenta de servicio de Google** que solo tenga acceso a esa carpeta compartida (⏳ Santi crea el proyecto en Google Cloud y comparte la carpeta). Si el paso 2 no está listo al lanzar, plan provisional: descargar por SSH/SFTP cada semana a la carpeta sincronizada de Drive. La clave de cifrado se guarda aparte, nunca en Git ni junto a la copia.

**Decisión:** los precios los actualizarán **Santi y el cliente, y con frecuencia**. Por eso el panel admin y MySQL pasan de la Fase 2 al **MVP**. Razón: con dos precios por producto, editar un archivo a mano es fácil de romper (una coma mal puesta tumba la página) y un error de precio en una tienda en línea sale caro.

**Funciones del panel en el MVP:**
- Login para Santi y para las personas que designe el cliente.
- Listado de productos con búsqueda y filtro por marca/categoría.
- **Edición rápida de precios en tabla** (`price_cash` y `price_gateway` por variante/capacidad), sin entrar a cada ficha.
- Cambiar **disponibilidad** (disponible / agotado) y **visibilidad** (mostrar / ocultar).
- **Importador CSV** para la carga inicial desde la hoja de precios: lee solo nombre, categoría, `price_cash` y `price_gateway`, ignora cualquier columna de costos o ganancias, muestra una vista previa y avisa de duplicados antes de guardar.
- **Ayuda para el precio de pasarela (opcional):** botón "sugerir" que calcula el precio con pasarela con la fórmula de 6.3 a partir de `price_cash`. Es solo una sugerencia editable; el valor guardado es el que escribe la persona. Los parámetros de comisión (2,65%, $700, IVA) viven en `config.php`.
- **Historial de cambios de precio:** quién lo cambió, cuándo, valor anterior y nuevo. Protege ante errores y permite revertir.
- **Validaciones anti-error de digitación:** ambos precios obligatorios y numéricos; advertencia si `price_gateway` < `price_cash`; **confirmación extra si un cambio supera ±30%** (típico error de agregar o quitar un cero).
- **Alta y edición completa de productos (decidido: entra en el MVP):** el cliente podrá crear y editar productos sin ayuda técnica.
  - Datos básicos: nombre, marca, categoría y `slug` (generado y único). No hay descripción comercial del producto (ver 6.2).
  - **Colores:** nombre + selector de color con código hex exacto + imagen por color. Reordenables.
  - **Almacenamientos/capacidades** disponibles.
  - **Slides de descripción:** foto + titular de característica (dato de la ficha), reordenables.
  - **Características por desglose:** pares título/detalle para el acordeón, reordenables.
  - **Subida de imágenes** con vista previa; el sistema las convierte a WebP, genera tamaños responsivos y limita el peso.
  - **Vista previa** del producto antes de publicar y estado borrador/publicado.
  - Borrado "suave" (se oculta y se puede restaurar); el borrado definitivo solo lo hace `admin`.
  - ⚠️ Esto **amplía bastante el alcance del MVP**. Recomendado construirlo **por etapas**: (a) login + edición de precios, disponibilidad y visibilidad; (b) alta/edición de datos básicos, colores y capacidades; (c) slides, características y subida de imágenes. El sitio público puede salir cuando (a) y (b) estén listos, con el catálogo cargado por Santi mientras se termina (c).

**Tablas base (`schema.sql`):** `products`, `product_colors`, `product_variants` (capacidad, color opcional, nota opcional como "SIM física", SKU, `price_cash`, `price_gateway`, disponibilidad), `product_specs`, `product_slides`, `price_history`, `admin_users`, `login_attempts`.

**Roles:**
- `admin` (**Santi y el cliente**, ✔ decisión del cliente): acceso total, incluido gestionar usuarios.
- `editor` (disponible para futuros asesores, sin cuentas por ahora): crear y editar productos, precios, disponibilidad y visibilidad; no gestiona usuarios ni borra definitivamente.
- **Salvaguardas por tener dos administradores con acceso total:**
  - Borrado siempre lógico (papelera con restauración); el borrado definitivo pide confirmar con la contraseña.
  - Registro de actividad: quién cambió qué y cuándo (precios, disponibilidad, productos y usuarios), junto al historial de precios.
  - No se puede eliminar ni degradar al último administrador, ni a uno mismo.
  - Contraseñas con `password_hash`, bloqueo tras intentos fallidos y recomendación de verificación en dos pasos (TOTP) en una fase posterior.
  - ⏳ Nombre y correo de cada administrador para crear las cuentas.

**Seguridad del panel:**
- Ruta `/admin` con `noindex` y fuera del `sitemap.xml`.
- Contraseñas con `password_hash` (bcrypt o Argon2id), sesiones con cookie `HttpOnly`, `Secure` y `SameSite`, token CSRF en cada formulario y límite de intentos de login.
- Todas las consultas con PDO preparado; toda salida escapada.
- Recomendado: verificación en dos pasos (⚠️ puede ir en una fase siguiente).
- **Subida de imágenes (punto de riesgo en PHP):** validar el tipo real del archivo (no solo la extensión), aceptar solo JPG/PNG/WebP, límite de peso, renombrar con nombre aleatorio y regenerar la imagen (GD/Imagick) para eliminar contenido oculto. Guardar en `public_html/uploads/products/` con un `.htaccess` que **impide ejecutar PHP** en esa carpeta.
- Backups de la base de datos: los de Hostinger Premium son semanales; añadir una **exportación diaria** guardada fuera del servidor (ver 10.6).

## 11. Requisitos no funcionales

- **Rendimiento:** LCP < 2.5 s en móvil; imágenes WebP responsivas (`srcset`), lazy loading, fuentes autoalojadas con `font-display: swap`, CSS/JS minificados.
- **Accesibilidad (WCAG):** contraste, navegación por teclado, `alt`, nombres accesibles en los círculos de color, carrusel pausable.
- ✔ **SEO (decisión del cliente: solo lo básico ahora; el trabajo de SEO se hace más adelante).** Alcance de la Fase 1:
  - `<title>` y `<meta description>` **generales del sitio** (⏳ textos por definir con el cliente) y los de las páginas fijas (Inicio, Productos, Contacto, políticas).
  - `robots.txt`, `sitemap.xml` básico (páginas fijas y productos publicados), URLs limpias y HTML renderizado en servidor (ya incluido en la arquitectura).
  - `noindex` en el dominio temporal y en `/admin`; se quita al publicar en el dominio final.
  - JSON-LD de la organización con `areaServed: Colombia` (ver 7).
  - ⚠️ **Mínimo sin costo que se mantiene, aunque no se haga SEO por producto:** el título de cada ficha usa automáticamente `{nombre del producto} | Taptop Mobile` y las etiquetas Open Graph usan el nombre y la foto del producto, para que el enlace compartido por WhatsApp no salga igual en todos los productos. No requiere trabajo manual.
  - **Fuera del alcance ahora (queda para una etapa posterior):** descripciones meta por producto, datos estructurados `Product`, palabras clave, contenido para posicionar, Search Console y edición de SEO desde el panel.
- **Seguridad:** ver 10.5.
- **Datos de visitantes:** medir con GA4 + Meta Pixel y evento de clic en WhatsApp; sin formularios en el MVP; aviso de cookies y política de datos (Ley 1581 de 2012).
- ✔ **Analítica (decisión del cliente: contactos por WhatsApp + productos más vistos):**
  - **Evento principal `whatsapp_click`** (GA4) y `Contact` (Meta Pixel) al pulsar "Confirmar disponibilidad", el botón flotante o el enlace del pie. Parámetros: `product_slug`, `product_name`, `brand`, `category`, `color`, `storage`, `price_method` (efectivo o pasarela), `price_shown`, `placement` (tarjeta, detalle, flotante, contacto) y `page`.
  - **Productos vistos:** evento `view_item` al abrir `/productos/{slug}` (mismos parámetros) y `select_item` al hacer clic en una tarjeta del listado. Evento `select_color` y `select_storage` al elegir color o capacidad, para saber qué variantes interesan.
  - **Cálculo en el informe:** tasa de contacto = `whatsapp_click` ÷ `view_item` por producto. Sirve para decidir qué fotos y precios priorizar. (Aclaración: un clic en WhatsApp no es una venta; las ventas reales se cruzan a mano con las conversaciones y la facturación.)
  - **Consentimiento:** banner simple de cookies (aceptar / rechazar / más información) que **solo carga GA4 y Meta Pixel si el visitante acepta**; la decisión se guarda en una cookie propia y se puede cambiar desde el pie de página. El texto debe estar en `/privacidad` (qué datos, para qué, terceros: Google y Meta).
  - **Sin datos personales:** nunca enviar nombre, teléfono, correo ni el contenido del mensaje a GA4 ni a Meta. Solo los parámetros de producto de arriba.
  - **Estructura:** `app/helpers/analytics.php` y un único `assets/js/analytics.js`; los eventos se declaran con atributos `data-track-*` en el HTML, sin lógica repartida por las vistas. IDs (`ga4_measurement_id`, `meta_pixel_id`) en `config.php`; si están vacíos no se carga nada (así el sitio temporal no contamina las métricas).
  - ⏳ Pendiente: el cliente crea la propiedad GA4 y el Pixel de Meta (o da acceso a Santi) y define el dominio final.

## 12. Fases

✔ **Decisión del cliente: se publica primero una versión de prueba y el resto se completa por etapas.** Alcance confirmado por el cliente (la primera versión solo muestra el sitio):

**Etapa 0: versión de prueba (dominio temporal, con `noindex`, sin ventas reales)**
- Inicio (intro del logo, hero y los tres pilares), listado de productos con tarjetas y dos precios, detalle de producto y Contáctanos.
- Botones de WhatsApp funcionando con el mensaje prellenado (se pueden probar con el equipo del cliente).
- Catálogo cargado con precios de prueba o reales, pero **no se comparte públicamente ni se anuncia**; solo lo ven Santi, el cliente y quienes ellos inviten.
- Textos legales y datos del negocio con "[por completar]" permitidos aquí.
- Sin analítica real (IDs vacíos) para no ensuciar las métricas.
- Objetivo: revisar diseño, precios, nombres, colores y fotos en móvil con el cliente, y corregir.
- ✔ **Confirmado por el cliente: la Etapa 0 solo muestra el sitio; el panel de administración se construye después, antes de la Etapa 1.** Mientras tanto, el catálogo se carga desde `app/data/products.json` con el seeder (en la base de datos nueva, o leído directamente del archivo si aún no está creada), siempre detrás de `ProductRepository`. Los cambios de precio en la Etapa 0 los hace Santi.

**Etapa 1: lanzamiento público (bloqueado hasta cumplir todo esto)**
- Dominio definido, SSL activo y `noindex` retirado.
- Razón social, NIT, correo y horario reales cargados en `config.php`; textos legales publicados (versión inicial).
- Facturación electrónica resuelta con el contador (ver pendiente 11).
- Fotos propias de los productos que se publican (se puede lanzar con los iPhone y Samsung primero).
- Panel de administración con login y edición de precios funcionando; backups: exportación diaria de MySQL y copia en Google Drive probadas.
- Instagram real en `instagram_url`; GA4 y Meta Pixel con banner de consentimiento.
- Transportadora: no es obligatoria para lanzar (el sitio dice que se confirma por WhatsApp).

**Etapa 2:** resto de productos y fotos, gestión completa de productos desde el panel, SEO por producto, y mejoras.

**Fase 1: MVP.** Equivalencia: los puntos 1 a 6 y el 8 forman la **Etapa 0**; el punto 7 (MySQL y panel) se construye después de la Etapa 0 y antes de la **Etapa 1**.
1. Estructura de carpetas, `config.php`, helpers, `.htaccess` y enrutador.
2. `main.css` con variables de color y tipografía; header, footer, botón flotante de WhatsApp.
3. Hero carrusel (3 s, pausa, swipe) con datos de `hero-slides.json`.
4. Listado con tarjetas, **dos precios** (efectivo/transferencia y pasarela) y selector de color circular.
5. Detalle: slides de descripción + acordeón de características.
6. Contáctanos.
7. **MySQL y panel de administración** (ver 10.8): esquema, repositorio, login, edición de precios con historial y gestión completa de productos (colores, capacidades, slides, características e imágenes), por etapas.
8. SEO, analítica, pruebas en móvil y despliegue en Hostinger.

**Pagos Nivel 1 (puede salir con el MVP):** enlaces de pago de la pasarela enviados por WhatsApp; no requiere código en la web (ver 10.7).

**Fase 2:** **automatización de pagos** (generación del enlace por API y registro por webhook; estados de pedido; ver 10.7). (El asistente virtual de WhatsApp queda fuera por decisión del cliente; la atención es manual.)

**Fase 3:** perfil real de Instagram enlazado (el ícono ya existe en Fase 1 con enlace provisional) y animación 3D del logo (la animación entra en la Fase 1 según 5.0).

## 13. Pendientes del cliente ⏳

1. Del catálogo ya tenemos productos, precios, nombres comerciales, marca y colores (ver 6.3). Faltan: **precios de las variantes que la hoja no trae** (MacBook Air 13" y 15", iPad Pro con y sin celular y otras capacidades, Apple Watch Series 12 en aluminio/titanio/cerámica y GPS/celular, Apple Watch Ultra 4 si hay más versiones, y la versión de cada fila), fotos propias (ver guía en sección 9), datos faltantes de especificaciones (procesador y peso del Z Fold8 y Z Flip8, adaptadores) y verificación de las fichas con el equipo real, y stock.
2. Logo transparente y colores oficiales de marca. **Además, el cliente subirá al proyecto la animación del logo/3D** que quiere mostrar en el sitio: se usará como **intro de marca (una vez por sesión) y en el hero del Inicio** (ver 5.0); el cliente tiene un **modelo 3D** (.glb); falta recibirlo y revisar su peso (ver 5.0). Regla técnica: máximo ~1–2 MB, sin sonido, carga diferida, con imagen fija de respaldo y respetando "reducir movimiento"; no debe frenar la carga de la página.
3. Dominio.
4. Envíos (✔ por ahora el sitio dice que tiempo y costo se confirman por WhatsApp; la tabla queda vacía en `config.php`): transportadora, costo por debajo de $150.000, tiempos de entrega por zona (ciudades principales vs. resto del país) y seguro; (✔ el umbral se compara con el precio realmente pagado); horas exactas de atención por WhatsApp (se atiende todos los días, horario fijo; ⏳ falta que el cliente indique desde qué hora y hasta cuál).
5. **Garantía propia (3 meses):** condiciones (defecto de fábrica vs. mal uso), proceso de cambio y quién asume el envío; datos de la cuenta Bancolombia para consignación/transferencia.
6. Textos finales de los tres pilares de "por qué comprar" (precios, garantía, profesionalismo).
7. Cuentas del panel admin: quiénes entran por parte del cliente (nombres y correos) (podrán crear y editar productos completos).
8. ✔ **Hosting verificado en hPanel (ver 10.6):** PHP 8.3, extensiones, límites, cron y SSH activos. Falta confirmar: límite de bases de datos MySQL, acceso por Git, límites de CPU/RAM/inodos y (con una captura nueva) los ajustes de PHP que Santi indica haber hecho.
9. **Wompi (elegida):** abrir la cuenta (confirmar requisitos de apertura y quién la tramita), verificar tarifas, tiempos de desembolso, soporte de Daviplata y cómo crear los enlaces de pago desde su panel. Confirmar que la comisión real coincida con la de la hoja de precios (2,65% + $700 + IVA).
10. **Precio destacado:** confirmar que el de efectivo/transferencia es el principal y el de pasarela va debajo.
11. **Facturación electrónica (crítico antes del lanzamiento):** ✔ el cliente **no sabe si la necesita ni si ya la tiene**; queda como **tarea previa al lanzamiento: consultar con un contador**. Preguntas para el contador: (a) ¿Taptop Mobile está obligado a facturar electrónicamente (por actividad, ingresos y por vender a personas naturales y empresas)? (b) ¿Ya hay resolución de facturación y proveedor tecnológico habilitado por la DIAN? (c) ¿Qué datos se piden al comprador (nombre completo o razón social, documento o NIT, correo, ciudad y dirección)? (d) ¿Cómo se factura una venta por pasarela de pago (Wompi) frente a consignación? (e) ¿IVA: los precios publicados incluyen IVA y cómo se discrimina en la factura?
    - **Impacto en el sitio y en WhatsApp:** el mensaje prellenado y el guion del asesor deben poder pedir nombre, documento y correo para la factura antes del pago. El sitio no genera facturas (Fase 1); si más adelante se integra un proveedor de facturación, es una fase aparte.
    - **No se debe publicar el sitio vendiendo hasta resolver esto.**
12. Fecha esperada para activar los pagos (¿en el lanzamiento o después?).
13. **Datos legales y del negocio:** razón social, NIT, correo para reclamos y horas reales de atención por WhatsApp (sin ellos el sitio no se publica; ver 4 y 7).
14. **Revisión de un abogado** de `/garantia`, `/terminos` y `/privacidad`, con fecha acordada (ver 4).
15. **Instagram:** perfil real para reemplazar el enlace provisional (ver 4). **Analítica:** crear la propiedad GA4 y el Pixel de Meta (ver 11).
16. **Copia de seguridad en Google Drive:** Santi crea el proyecto en Google Cloud, la cuenta de servicio y comparte la carpeta (ver 10.8). Crear la base de datos MySQL nueva y exclusiva.
17. **Apple Watch Series 12:** qué versión es la de la hoja (material y conectividad), y precios del resto de variantes (ver 6.3).
18. **Textos del título y la descripción generales del sitio (SEO básico)** y de los tres pilares (ver 11 y 5.2).

## 14. Prompt de arranque para Claude Code

> Lee `TAPTOP-MOBILE-brief.md` completo y también `TAPTOP-MOBILE-specs-borrador.md`. Construye en **PHP 8.2+ puro (sin framework y sin Node)** (el hosting usa 8.3) siguiendo la estructura de la sección 10.2: `app/` fuera de `public_html/`, front controller en `public_html/index.php`, `.htaccess` con HTTPS y URLs limpias, plantillas con partials, CSS propio con variables (colores de la sección 9) y JavaScript vanilla. **Trabaja en este orden:**
>
> **1) Etapa 0 (solo el sitio público, sin panel):** scaffolding, `config.php` (con los campos de 10.2 y valores "[por completar]" donde falten datos), `helpers.php` (`e()`, `whatsapp_link()`, `format_price()`), `ProductRepository` como interfaz con `app/data/products.json` generado desde el catálogo de la sección 6.3 (solo nombre, categoría, marca, colores, variantes, `price_cash` y `price_gateway`; **nunca costos ni ganancias**; precios por variante; sin los dos iPad A16, sin el iPad 11 M4 y sin la segunda línea Burgundy (el iPad Pro M5 sí entra)), `tools/validate-products.php`, y los partials `header`, `footer` (con Instagram provisional y horario), `whatsapp-float`, `hero-carousel`, `product-card`, `color-swatches`, `description-slides`, `specs-accordion` y `logo-animation`. Páginas: Inicio (intro + hero con el modelo 3D según 5.0, con plan B, y los tres pilares), `/productos`, `/productos/{slug}`, `/contacto`, `/garantia`, `/terminos`, `/privacidad` (versión inicial según 4) y 404. Dos precios en cada tarjeta y en el detalle (`price_cash` destacado, `price_gateway` debajo, formato COP), disponibilidad por color y capacidad con el estado "Agotado" (ver 6.3), y el botón "Confirmar disponibilidad" con el mensaje prellenado de la sección 8 (precios calculados en el servidor). Sin carrito ni checkout; los pagos son por enlace enviado por WhatsApp (Nivel 1). SEO básico y analítica con consentimiento (sección 11), con IDs vacíos. Usa placeholders para logo e imágenes. Escapa toda salida. No construyas asistente virtual. El sitio debe quedar con `noindex` mientras `site_url` sea el dominio temporal.
>
> **2) Después, y solo cuando el cliente confirme la Etapa 0:** MySQL (`schema.sql`, seeder desde `products.json`, `MysqlProductRepository`), exportación diaria cifrada con subida a Google Drive y el panel de administración de 10.8, por etapas: primero login y precios, luego datos básicos, colores y capacidades, y al final slides, características e imágenes. Respeta las restricciones de 10.6 (funciones bloqueadas, sin `ini_set`, sin `exec`).
>
> **Antes de proponer el lanzamiento público**, verifica la lista de la Etapa 1 (sección 12) y avisa de todo lo que siga con "[por completar]" o con valores provisionales. No implementes nada de la Fase 2 ni 3, pero no tomes decisiones que bloqueen los pagos futuros (ver 10.7).
