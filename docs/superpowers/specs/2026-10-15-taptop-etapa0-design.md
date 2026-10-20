# Taptop Mobile, Etapa 0: diseño aplicado

Fecha: 15-oct-2026. Fuente funcional: `TAPTOP-MOBILE-brief.md` (spec cerrado) y `TAPTOP-MOBILE-specs-borrador.md`.
Este documento solo registra las decisiones visuales y de implementación que el brief deja abiertas.

## Decisiones aprobadas por Santi

1. **Hero = "escenario + carrusel de vidrio"** (inspiración: prompt LTX "The world model").
   - Slide 0: logo 3D (`<model-viewer>`) de pie en un estudio azul niebla, con bloques redondeados
     gigantes que repiten la geometría del isotipo (equivalente a las paredes del set de LTX).
   - Slides 1 a 4: fotos 16:9 a sangre (hoy placeholders SVG de equipos en el mismo estudio).
   - El controlador de vidrio de LTX (pista + cápsula + borde cónico) es la lista de pestañas del carrusel:
     autoplay de 3 s (slide 0: 6 s), la cápsula salta a la pestaña activa, línea cian de progreso,
     pausa al pasar el cursor por los controles/textos o al tocar, botón pausar/reanudar, flechas y swipe.
   - Títulos de tres palabras que entran y salen palabra por palabra con desenfoque (900 ms, desfase 90 ms).
   - Transición "seam-safe": la capa nueva se muestra solo cuando su imagen está decodificada; la anterior
     sigue visible hasta que la nueva termina de entrar. Token por transición, sin cola de transiciones.
2. **Paleta desde el GLB** (regla del brief 9: "sustituir si difieren").
   - Tinta `#0D2655` (material Navy_Matte): texto principal, botones, enlaces. Contraste 14,8:1.
   - Señal `#00B3F6` (material Cyan_Gloss): solo detalles (progreso, ondas, indicadores). Nunca texto.
   - Pizarra `#394770` (color del render, aprobado antes): tono secundario (hover, bordes).
   - Niebla `#EEF1F8` superficie, gris azulado `#5B6685` texto secundario (5,7:1), blanco `#FFFFFF`.
   - WhatsApp `#25D366` con texto `#0B3D1E` (7,4:1); el blanco sobre ese verde no cumple contraste.
   - Variables pedidas por el brief: `--color-primary`, `--color-accent`, `--color-surface`,
     `--color-text-muted`, `--color-whatsapp`.

## Sistema visual

- **Tipografía:** Inter variable autoalojada con eje óptico (`opsz`), así los titulares grandes usan el corte
  Display. Pesos 400/500/600. Titulares con tracking negativo (-0,045 em) como en LTX; cifras tabulares en precios.
- **Escala:** 13 / 14 / 16 / 18 / 21 / 24 / 36 / 48 / display fluido hasta 96 px.
- **Radios por jerarquía:** escenario 32, pedestal de producto 24, campos 14, píldoras 999.
- **Easing:** `--ease-out: cubic-bezier(0.22, 1, 0.36, 1)` y `--ease-inout: cubic-bezier(0.65, 0, 0.35, 1)`.
- **Un solo momento audaz:** el escenario del hero. El resto del sitio es blanco, quieto y alineado a la izquierda.
  Sin animaciones de entrada por sección, sin tarjetas con sombra genérica, sin etiquetas en mayúsculas.
- **Producto:** sin caja; la foto descansa en un "pedestal" niebla con luz de piso (continuidad con el estudio).
  Las fotos reales de fondo claro se integran con `mix-blend-mode: multiply`.
- **Vidrio reutilizado** con mesura: controlador del hero, navegación del header y filtro de categorías.
- **Logo del header (arriba a la izquierda) y del footer:** el logo 3D, como imagen fija del mismo modelo
  del hero (encuadre 20° / 80°, sin sombra de piso), en 1x/2x/3x (`logo-3d-mark-40/80/120.webp`, 1–5 KB),
  36 px de alto en móvil y 40 px en escritorio. Elegido por Santi; no se usa el visor 3D en el
  header para no cargar ~1,5 MB en cada página. El 2026-10-19 Santi confirmó que **el logo oficial es este
  3D** (`Logo/taptop-logo-3d-footer.png`, ángulo 20° / 80°): favicon (32 y 192 px), apple-touch-icon y
  `logo-mark-512.png` salen de ese render. No hay SVG plano; el isotipo plano provisional se retiró.

## Implementación (resumen)

- PHP 8.2+ puro, estructura 10.2: `app/` fuera de `public_html/`, front controller, `.htaccess`, partials.
- `ProductRepository` (interfaz) + `JsonProductRepository` sobre `app/data/products.json`
  (23 productos, 30 variantes). Fichas en `app/data/specs.json` (borrador), separadas del catálogo.
- Precios por variante; la matriz color × opción se calcula en el servidor con los enlaces de WhatsApp ya armados.
  El JS solo elige entre enlaces precalculados: nunca calcula precios.
- Placeholders: siluetas SVG teñidas con el hex del color elegido (`--dv`), así el selector cambia la imagen hoy.
- Intro: MP4 del cliente a 3x (3 s), una vez por sesión y solo si se entra por el Inicio.
- 3D: `model-viewer` 4.2.0 autoalojado (4.3.x deja logs de depuración en consola), carga diferida; requiere
  `'wasm-unsafe-eval'` en el CSP. Plan B con imagen fija (movimiento reducido, ahorro de datos, red lenta,
  equipo modesto) o video si solo falla WebGL.
- `noindex` mientras `site_url` sea `*.hostingersite.com` o el entorno no sea producción.
