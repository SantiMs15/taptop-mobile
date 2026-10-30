/**
 * Selector de color y capacidad de tarjetas y detalle.
 *
 * Lee la matriz que el servidor dejó en <script type="application/json" data-variants>:
 * por cada combinación color × opción trae precios ya formateados, envío, disponibilidad,
 * texto del botón y el enlace de WhatsApp ya armado. Aquí no se calcula ningún precio:
 * solo se elige la entrada que corresponde (sección 8 y 10.7 del brief).
 */
(function () {
  'use strict';

  var doc = document;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function toArray(list) {
    return Array.prototype.slice.call(list);
  }

  function track(event, params) {
    if (typeof window.ttTrack === 'function') {
      window.ttTrack(event, params);
    }
  }

  function trackParams(element) {
    var params = {};
    if (!element) {
      return params;
    }
    toArray(element.attributes).forEach(function (attribute) {
      if (attribute.name.indexOf('data-track-') === 0) {
        params[attribute.name.slice(11).replace(/-/g, '_')] = attribute.value;
      }
    });
    return params;
  }

  function initCard(card) {
    var source = card.querySelector('script[data-variants]');
    if (!source) {
      return;
    }
    var data;
    try {
      data = JSON.parse(source.textContent);
    } catch (error) {
      return;
    }

    var hasColors = data.colors.length > 0;
    var state = { color: data.sel.c, option: data.sel.o, touched: false };
    var colorInputs = toArray(card.querySelectorAll('[data-color-index]'));
    var optionInputs = toArray(card.querySelectorAll('[data-option-index]'));
    var el = {
      colorName: card.querySelector('[data-color-name]'),
      from: card.querySelector('[data-price-from]'),
      cash: card.querySelector('[data-price-cash]'),
      gateway: card.querySelector('[data-price-gw]'),
      ship: card.querySelector('[data-price-ship]'),
      shipText: card.querySelector('[data-price-ship-text]'),
      stock: card.querySelector('[data-price-stock]'),
      live: card.querySelector('[data-price-live]'),
      cta: card.querySelector('[data-cta]'),
      ctaLabel: card.querySelector('[data-cta-label]'),
      buy: card.querySelector('[data-buy]'),
      add: card.querySelector('[data-add-to-cart]'),
      buyNote: card.querySelector('[data-buy-note]'),
      media: card.querySelector('[data-media]')
    };
    // Los slides viven fuera de la tarjeta, pero solo hay un detalle por página.
    var slideMedia = data.slides ? toArray(doc.querySelectorAll('[data-slide-media]')) : [];

    function key(color, option) {
      return (hasColors ? String(color) : '_') + '|' + option;
    }

    function entry(color, option) {
      return data.m[key(color, option)] || null;
    }

    /** Si la capacidad elegida no existe o está agotada en el color nuevo, pasa a una disponible. */
    function fixOption() {
      var current = entry(state.color, state.option);
      if (current && current.available) {
        return;
      }
      var fallback = null;
      for (var option = 0; option < data.options.length; option++) {
        var candidate = entry(state.color, option);
        if (candidate && candidate.available) {
          state.option = option;
          return;
        }
        if (candidate && fallback === null) {
          fallback = option;
        }
      }
      if (!current && fallback !== null) {
        state.option = fallback;
      }
    }

    /**
     * Muestra en un contenedor la foto propia (src) o, sin foto, la silueta teñida del color.
     * La foto se carga y decodifica antes de mostrarla (sin parpadeo).
     */
    function showPhoto(container, src, alt, color) {
      var placeholder = container.querySelector('.ph');
      var image = container.querySelector('img[data-media-img]');
      // Solo la última foto pedida se muestra (si el cliente cambia rápido de color o configuración).
      container.ttWant = src || null;
      if (placeholder) {
        placeholder.style.setProperty('--dv', color.hex);
        placeholder.setAttribute('aria-label', color.label || 'Imagen de referencia de ' + data.name + ' en ' + color.name);
      }
      if (!src) {
        if (image) {
          image.hidden = true;
        }
        if (placeholder) {
          placeholder.classList.remove('is-covered');
        }
        return;
      }
      var next = new Image();
      next.src = src;
      var ready = typeof next.decode === 'function' ? next.decode() : Promise.resolve();
      ready.then(function () {
        if (container.ttWant !== src) {
          return;
        }
        if (!image) {
          image = doc.createElement('img');
          image.className = 'media__img';
          image.setAttribute('data-media-img', '');
          image.decoding = 'async';
          container.appendChild(image);
        }
        // Cambio de foto (color, configuración o miniatura): entra con un fundido corto y un leve
        // desenfoque, en vez de saltar. La primera foto de la página no se anima.
        var swap = !image.hidden && image.getAttribute('src') !== src && !reduceMotion.matches && typeof image.animate === 'function';
        image.src = src;
        image.alt = alt;
        image.hidden = false;
        if (swap) {
          image.animate([
            { opacity: 0, filter: 'blur(8px)', transform: 'scale(0.985)' },
            { opacity: 1, filter: 'blur(0)', transform: 'none' }
          ], { duration: 320, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });
        }
        if (placeholder) {
          placeholder.classList.add('is-covered');
        }
      }, function () { /* si falla, queda la silueta */ });
    }

    function updateMedia() {
      if (!hasColors) {
        return;
      }
      var color = data.colors[state.color];
      var subject = data.name + ' en ' + color.name;
      if (el.media) {
        showPhoto(el.media, color.image, subject, color);
      }
      // Detalle: los slides "En detalle" muestran las fotos del mismo color.
      slideMedia.forEach(function (container, index) {
        var slide = data.slides[index];
        if (slide) {
          showPhoto(container, slide.byColor[state.color], subject + ': ' + slide.title, color);
        }
      });
    }

    function render(announce) {
      var current = entry(state.color, state.option);
      if (!current) {
        return;
      }
      if (el.cash) {
        el.cash.textContent = current.cash_f;
      }
      if (el.gateway) {
        el.gateway.textContent = current.gw_f;
      }
      if (el.from) {
        el.from.hidden = !(data.from && !state.touched);
      }
      if (el.ship && el.shipText) {
        el.shipText.textContent = current.ship;
        el.ship.classList.toggle('is-free', Boolean(current.ship_free));
      }
      if (el.stock) {
        el.stock.hidden = current.available;
      }
      if (el.cta) {
        el.cta.setAttribute('href', current.wa);
        el.cta.setAttribute('data-track-color', current.color || '');
        el.cta.setAttribute('data-track-storage', current.storage || '');
        el.cta.setAttribute('data-track-price-shown', String(current.cash));
      }
      if (el.ctaLabel) {
        el.ctaLabel.textContent = current.cta;
      }
      // SPIKE Wompi: "Comprar ahora" solo con la variante disponible; la ruta la arma el servidor.
      if (el.buy) {
        el.buy.hidden = !current.buy;
        if (current.buy) {
          el.buy.setAttribute('href', current.buy);
        }
        if (el.buyNote) {
          el.buyNote.hidden = !current.buy;
        }
      }
      // Agregar al carrito: solo con la variante disponible; el servidor valida sku y color.
      if (el.add) {
        var addSku = el.add.querySelector('[data-add-sku]');
        var addColor = el.add.querySelector('[data-add-color]');
        el.add.hidden = !current.available;
        if (addSku) {
          addSku.value = current.sku || '';
        }
        if (addColor) {
          addColor.value = current.color || '';
        }
        var addButton = el.add.querySelector('[data-track]');
        if (addButton) {
          addButton.setAttribute('data-track-color', current.color || '');
          addButton.setAttribute('data-track-storage', current.storage || '');
          addButton.setAttribute('data-track-price-shown', String(current.cash));
        }
      }
      if (el.colorName && hasColors) {
        el.colorName.textContent = data.colors[state.color].name;
      }
      colorInputs.forEach(function (input) {
        input.checked = Number(input.value) === state.color;
      });
      optionInputs.forEach(function (input) {
        var index = Number(input.value);
        var candidate = entry(state.color, index);
        var disabled = !candidate || !candidate.available;
        input.disabled = disabled;
        input.checked = index === state.option;
        var label = input.nextElementSibling;
        var stateText = label ? label.querySelector('[data-option-state]') : null;
        if (stateText) {
          stateText.textContent = !candidate ? ', no disponible en este color' : (candidate.available ? '' : ', agotado');
        }
      });
      card.classList.toggle('is-sold-out', !current.available);
      if (announce && el.live) {
        el.live.textContent = (current.available ? '' : 'Agotado. ') + current.cash_f + ' por consignación o transferencia'
          + (el.gateway ? '; ' + current.gw_f + ' con tarjeta o pasarela.' : '.');
      }
    }

    colorInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) {
          return;
        }
        state.color = Number(input.value);
        state.touched = true;
        fixOption();
        render(true);
        updateMedia();
        buildThumbs();
        var params = trackParams(el.cta);
        params.color = data.colors[state.color].name;
        track('select_color', params);
      });
    });

    optionInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked) {
          return;
        }
        state.option = Number(input.value);
        state.touched = true;
        render(true);
        var params = trackParams(el.cta);
        params.storage = data.options[state.option].label + (data.options[state.option].sub ? ' ' + data.options[state.option].sub : '');
        track('select_storage', params);
      });
    });

    // --- Galería de miniaturas (detalle): la elegida pasa a la foto grande ------------------
    var gallery = card.querySelector('[data-gallery]');
    var galleryWrap = card.querySelector('[data-gallery-wrap]');

    function thumbColor() {
      return hasColors ? data.colors[state.color] : { hex: '#C8C1B5', name: data.name };
    }

    function selectThumb(button) {
      toArray(gallery.querySelectorAll('[data-thumb]')).forEach(function (other) {
        other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
      });
    }

    /** Arma las miniaturas del color elegido (sin galería, se oculta). */
    function buildThumbs() {
      if (!gallery || !data.gallery) {
        return;
      }
      var list = data.gallery[hasColors ? state.color : 0] || [];
      gallery.textContent = '';
      list.forEach(function (item, i) {
        var button = doc.createElement('button');
        button.className = 'thumb';
        button.type = 'button';
        button.setAttribute('data-thumb', String(i));
        button.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
        button.setAttribute('aria-label', 'Ver foto: ' + item.alt);
        var image = doc.createElement('img');
        image.src = item.src;
        image.alt = '';
        image.width = 76;
        image.height = 76;
        image.decoding = 'async';
        button.appendChild(image);
        gallery.appendChild(button);
      });
      gallery.hidden = list.length === 0;
      if (galleryWrap) {
        galleryWrap.classList.toggle('has-gallery', list.length > 0);
      }
    }

    if (gallery && data.gallery) {
      gallery.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-thumb]') : null;
        if (!button || !el.media) {
          return;
        }
        var item = (data.gallery[hasColors ? state.color : 0] || [])[Number(button.getAttribute('data-thumb'))];
        if (!item) {
          return;
        }
        selectThumb(button);
        showPhoto(el.media, item.src, item.alt, thumbColor());
        track('select_photo', { index: button.getAttribute('data-thumb') });
      });
    }

    // --- Configurador (Apple Watch): ejes → configuración, o "No disponible" ----------------
    var cfgInputs = data.cfg ? toArray(card.querySelectorAll('[data-cfg-axis]')) : [];
    var gwRow = el.gateway ? el.gateway.parentNode : null;
    var cashLabel = el.cash ? el.cash.parentNode.querySelector('.price__label') : null;

    function cfgFind(sel) {
      for (var i = 0; i < data.cfg.opts.length; i++) {
        var opt = data.cfg.opts[i];
        var same = true;
        for (var a = 0; a < opt.length; a++) {
          same = same && opt[a] === sel[a];
        }
        if (same) {
          return i;
        }
      }
      return -1;
    }

    /** Hay alguna configuración con ese valor en el eje. */
    function cfgExists(axis, value) {
      return data.cfg.opts.some(function (opt) {
        return opt[axis] === value;
      });
    }

    /**
     * Configuración con ese valor en el eje que más se parece a lo elegido (más opciones iguales;
     * si empatan, la más barata). -1 si ninguna tiene ese valor.
     */
    function cfgClosest(axis, value) {
      var best = -1;
      var bestScore = -1;
      data.cfg.opts.forEach(function (opt, i) {
        if (opt[axis] !== value) {
          return;
        }
        var score = opt.filter(function (index, other) {
          return index === state.cfg[other];
        }).length;
        if (score > bestScore || (score === bestScore && entry(null, i).cash < entry(null, best).cash)) {
          best = i;
          bestScore = score;
        }
      });
      return best;
    }

    function cfgLabel(sel) {
      var label = data.cfg.label;
      data.cfg.axes.forEach(function (axis, a) {
        label = label.split('{' + axis.name + '}').join(axis.values[sel[a]]);
      });
      return label;
    }

    function cfgRefresh() {
      cfgInputs.forEach(function (input) {
        var axis = Number(input.getAttribute('data-cfg-axis'));
        var value = Number(input.value);
        var unavailable = !cfgExists(axis, value);
        input.checked = state.cfg[axis] === value;
        // Como un color agotado del iPhone: tachado y no se puede elegir (la foto no cambia).
        input.disabled = unavailable;
        var label = input.nextElementSibling;
        if (label) {
          if (label.classList.contains('swatch')) {
            label.classList.toggle('is-sold-out', unavailable);
          }
          var title = label.getAttribute('data-cfg-title');
          if (title) {
            label.setAttribute('title', title + (unavailable ? ' (no disponible)' : ''));
          }
          var stateText = label.querySelector('[data-cfg-state]');
          if (stateText) {
            stateText.textContent = unavailable ? ', no disponible' : '';
          }
        }
      });
      toArray(card.querySelectorAll('[data-cfg-value]')).forEach(function (legend) {
        var axis = Number(legend.getAttribute('data-cfg-value'));
        legend.textContent = data.cfg.axes[axis].values[state.cfg[axis]];
      });
    }

    /** Foto de la configuración; sin foto (o combinación sin variante), la silueta con el color de la caja. */
    function cfgMedia(current) {
      if (!el.media) {
        return;
      }
      var hex = null;
      data.cfg.axes.forEach(function (axis, a) {
        if (hex === null && axis.hex) {
          hex = axis.hex[axis.values[state.cfg[a]]];
        }
      });
      var subject = data.name + ' ' + cfgLabel(state.cfg);
      showPhoto(el.media, current ? current.img : null, subject, {
        hex: hex || '#C8C1B5',
        name: cfgLabel(state.cfg),
        label: 'Imagen de referencia de ' + subject
      });
    }

    /** Combinación sin variante: sin precio ni compra; WhatsApp pregunta por esa combinación. */
    function renderUnavailable() {
      var label = cfgLabel(state.cfg);
      if (el.cash) {
        el.cash.textContent = 'No disponible';
      }
      [el.from, gwRow, cashLabel, el.ship, el.stock, el.add, el.buy, el.buyNote].forEach(function (node) {
        if (node) {
          node.hidden = true;
        }
      });
      if (el.cta) {
        el.cta.setAttribute('href', data.cfg.ask.split('%7BCONFIG%7D').join(encodeURIComponent(label)));
        el.cta.setAttribute('data-track-storage', label);
        el.cta.setAttribute('data-track-price-shown', '');
      }
      if (el.ctaLabel) {
        el.ctaLabel.textContent = 'Preguntar por disponibilidad';
      }
      card.classList.add('is-sold-out');
      if (el.live) {
        el.live.textContent = 'No disponible: ' + label + '.';
      }
    }

    if (cfgInputs.length) {
      state.cfg = data.cfg.opts[state.option].slice();
      cfgInputs.forEach(function (input) {
        input.addEventListener('change', function () {
          if (!input.checked) {
            return;
          }
          var axis = Number(input.getAttribute('data-cfg-axis'));
          state.cfg[axis] = Number(input.value);
          state.touched = true;
          var found = cfgFind(state.cfg);
          if (found === -1) {
            // Sin configuración exacta: si el valor existe en otra, se ajustan las demás opciones.
            found = cfgClosest(axis, state.cfg[axis]);
            if (found !== -1) {
              state.cfg = data.cfg.opts[found].slice();
            }
          }
          if (found === -1) {
            renderUnavailable();
            cfgMedia(null);
          } else {
            state.option = found;
            [gwRow, cashLabel, el.ship].forEach(function (node) {
              if (node) {
                node.hidden = false;
              }
            });
            render(true);
            cfgMedia(entry(null, state.option));
          }
          cfgRefresh();
          var params = trackParams(el.cta);
          params.axis = data.cfg.axes[axis].name;
          params.value = data.cfg.axes[axis].values[state.cfg[axis]];
          track('select_option', params);
        });
      });
    }

    // El navegador puede restaurar radios marcados al volver atrás: se respeta esa elección.
    colorInputs.forEach(function (input) {
      if (input.checked) {
        state.color = Number(input.value);
      }
    });
    optionInputs.forEach(function (input) {
      if (input.checked) {
        state.option = Number(input.value);
      }
    });
    render(false);
    updateMedia();
    if (hasColors && state.color !== data.sel.c) {
      buildThumbs();
    }
    if (cfgInputs.length) {
      // Si el navegador restauró radios de otra combinación, se vuelve a la configuración inicial.
      state.cfg = data.cfg.opts[state.option].slice();
      cfgRefresh();
    }
  }

  toArray(doc.querySelectorAll('[data-product-card]')).forEach(initCard);
})();
