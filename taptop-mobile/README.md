# Taptop Mobile: sitio web (Etapa 0)

Sitio público de Taptop Mobile en PHP 8.2+ puro (probado en 8.3), sin framework y sin Node.
Versión de prueba: dominio temporal con `noindex`, sin panel de administración y sin pagos en línea.
Los pagos son por enlace enviado por WhatsApp (Nivel 1, sección 10.7 del brief).

- Brief y decisiones: `../TAPTOP-MOBILE-brief.md`
- Decisiones visuales de esta etapa: `../docs/superpowers/specs/2026-10-15-taptop-etapa0-design.md`

## Estructura

```
taptop-mobile/
├── app/                      fuera de public_html (no accesible por URL)
│   ├── config.php            negocio, WhatsApp, site_url, envíos, analítica, catálogo
│   ├── config.local.php      (opcional, no va a Git) credenciales del Paso 2
│   ├── bootstrap.php         arranque, errores, autoload
│   ├── router.php            rutas y datos de cada página
│   ├── helpers.php           e(), whatsapp_link(), format_price(), asset(), cabeceras
│   ├── helpers/              analytics.php, icons.php, placeholders.php
│   ├── Catalog/              ProductValidator, ProductPresenter
│   ├── Repositories/         ProductRepository (interfaz) + JsonProductRepository
│   ├── content/policies.php  textos de /garantia, /terminos y /privacidad (versión inicial)
│   ├── data/                 products.json, specs.json, hero-slides.json
│   ├── views/                layout, partials y páginas
│   ├── logs/                 registro propio (sin datos personales)
│   └── backups/              reservado para las exportaciones del Paso 2
├── public_html/              raíz pública en Hostinger
│   ├── index.php             front controller
│   ├── .htaccess             HTTPS, URLs limpias, caché, compresión, cabeceras
│   └── assets/               css, js, fuente Inter, model-viewer, modelo 3D, video, imágenes
└── tools/                    validador, pruebas, lista de lanzamiento, minificador, router local y subida
```

## Probar en el computador

Con PHP 8.2 o superior instalado:

```
php -S localhost:8080 -t public_html tools/dev-router.php
```

y abrir http://localhost:8080. El router local imita el `.htaccess`.

## Subir a Hostinger

Publicado por primera vez el 15-oct-2026 en el dominio temporal ivory-fly-931463.hostingersite.com;
desde el 19-oct-2026 vive en https://taptopmobile.com (sigue con `noindex` mientras `environment` no sea
`production`). La cuenta de Hostinger (`u555341304`) aloja **otros sitios**: solo se toca
`domains/taptopmobile.com/`.

```
domains/taptopmobile.com/
├── app/              fuera de la raíz pública
├── tools/
├── public_html/      raíz pública: index.php, .htaccess y assets/
└── deploy-backups/   copia automática antes de cada subida (las 10 últimas)
```

Para actualizar, desde `taptop-mobile/` en Git Bash:

```
bash tools/deploy.sh --dry-run   # muestra qué cambiaría, sin publicar
bash tools/deploy.sh             # prueba en el servidor, respalda y publica
```

Usa la clave SSH `~/.ssh/taptop_hostinger` (la pública está en hPanel → Acceso SSH; borrarla allí
revoca el acceso). No hace falta PHP en el computador: el script revisa sintaxis, catálogo y pruebas
con el PHP 8.3 del servidor y, si algo falla, no publica.

- `public_html/` queda igual al proyecto en cada subida: si hPanel agrega reglas al `.htaccess` o
  se sube un archivo de verificación (Google, Meta), hay que pasarlo al proyecto o se borra en la
  siguiente subida. `public_html/.well-known/` se respeta.
- Volver a la versión anterior (restaura los archivos que cambiaron):
  `ssh -i ~/.ssh/taptop_hostinger -p 65002 u555341304@212.1.211.197 'cd domains/taptopmobile.com && tar -xzf deploy-backups/predeploy-AAAAMMDD-HHMMSS.tar.gz'`
- El CDN de Hostinger no guarda el HTML (`x-hcdn-cache-status: DYNAMIC`) y los assets llevan
  `?v=fecha`: no hace falta purgar la caché después de subir.
- En el dominio temporal, el CDN responde su propio `robots.txt` (bloquea a Googlebot); el de
  `router.php` se verá con el dominio final. Revisarlo al conectarlo.
- Al cambiar de dominio: actualizar `DOMAIN` en `tools/deploy.sh` y `site_url` en `config.php`.

Sin SSH (por ejemplo, desde otro computador): subir `app/` y `tools/` **al lado** de `public_html`,
el contenido de `public_html/` dentro del `public_html` del dominio (incluido `.htaccess`), dar
permiso de escritura a `app/logs/` y revisar `/`, un producto, una ruta inexistente (404) y que
`/app/config.php` no responda.

Mientras `site_url` sea `*.hostingersite.com`, todo sale con `noindex` (meta, cabecera y `.htaccess`).
Si `environment` es `'production'` y faltan razón social, NIT, correo u horario, el sitio responde
"Sitio en configuración" a propósito.

## Editar el catálogo en la Etapa 0

Los precios viven en `app/data/products.json`, un producto por bloque y los precios en cada variante:

- `price_cash` (consignación o transferencia) y `price_gateway` (tarjeta o pasarela): enteros en COP,
  **sin puntos ni signos** (`4650000`, no `"4.650.000"`).
- Un color con precio propio (como Borgoña) es una variante con `"color": "Borgoña"`.
- Agotado: `"available": false` en el producto, en un color o en una variante. Cómo se muestra lo
  decide `sold_out_display` en `config.php` (`mark`, `hide` o `show`).
- Fotos propias: la principal de cada color va en `colors[].image` y las de los slides en
  `specs.json` (`"images": {"Color": ruta}`). Nombre SEO: `{slug}-{color en español}[-{toma}].webp`,
  en minúsculas, con guiones y sin tildes ni ñ. Tomas: `trasera`, `frontal`, `camara`, `lateral`,
  `detalle`. Ejemplos: `iphone-18-pro-max-negro.webp` (principal), `iphone-18-pro-max-negro-trasera.webp`,
  `samsung-galaxy-s26-ultra-violeta-cobalto-camara.webp`. El validador avisa si un nombre no cumple.
- Nunca agregar costos, márgenes ni ganancias: el validador rechaza esos campos.

Antes de subir un cambio:

```
php tools/validate-products.php
```

Las fichas técnicas (borrador para revisión del cliente) están en `app/data/specs.json`. Con
`"status": "pendiente"` una ficha no se publica. Los slides del Inicio están en `app/data/hero-slides.json`.

## Herramientas

| Comando | Para qué |
|---|---|
| `php tools/validate-products.php` | Revisa `products.json` y `specs.json` antes de subirlos |
| `php tools/selftest.php` | Pruebas de la lógica: agotado, envío gratis, mensajes de WhatsApp, validador |
| `php tools/preflight.php` | Lista de la Etapa 1: qué falta para el lanzamiento público |
| `php tools/build-assets.php` | Minifica CSS y JS (se sirven solos si son más nuevos que el original) |
| `bash tools/deploy.sh` | Sube a Hostinger por SSH con pruebas y respaldo (ver "Subir a Hostinger") |

## Lo que viene (no construido)

**Paso 2, solo cuando el cliente confirme la Etapa 0:** MySQL (`schema.sql`, seeder desde
`products.json`, `MysqlProductRepository` con la misma interfaz), exportación diaria cifrada con subida
a Google Drive y panel de administración por etapas. Las credenciales irán en `app/config.local.php`.
El sitio ya calcula los precios en el servidor y no tiene carrito ni checkout, así que los pagos
automáticos (Fase 2) se pueden agregar sin rehacer nada.
