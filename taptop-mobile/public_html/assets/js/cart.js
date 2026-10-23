/**
 * Carrito: "Agregar al carrito" sin recargar y contador de la cabecera.
 *
 * Intercepta el envío de [data-add-to-cart] y lo manda con fetch (Accept: application/json).
 * Si la respuesta no es 2xx con JSON {ok:true}, deja que el formulario se envíe normal
 * (lleva a /carrito). Sin JS el formulario funciona igual.
 */
(function () {
  'use strict';

  var doc = document;
  var toast = null;
  var timer = null;

  function counters() {
    return Array.prototype.slice.call(doc.querySelectorAll('[data-cart-count]'));
  }

  function setCount(count) {
    var n = Number(count) || 0;
    counters().forEach(function (node) {
      node.textContent = String(n);
      node.hidden = n === 0;
    });
    Array.prototype.forEach.call(doc.querySelectorAll('[data-cart-link]'), function (link) {
      link.setAttribute('aria-label', 'Carrito, ' + n + (n === 1 ? ' producto' : ' productos'));
    });
  }

  function showToast() {
    if (!toast) {
      toast = doc.createElement('div');
      toast.className = 'cart-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      toast.hidden = true;
      doc.body.appendChild(toast);
    }
    toast.textContent = '';
    var text = doc.createElement('span');
    text.textContent = 'Agregado ✓';
    var link = doc.createElement('a');
    link.href = '/carrito';
    link.textContent = 'Ver carrito';
    toast.appendChild(text);
    toast.appendChild(link);
    toast.hidden = false;
    window.clearTimeout(timer);
    timer = window.setTimeout(function () {
      toast.hidden = true;
    }, 4000);
  }

  doc.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.matches || !form.matches('[data-add-to-cart]') || event.defaultPrevented) {
      return;
    }
    if (typeof window.fetch !== 'function' || typeof window.FormData !== 'function') {
      return;
    }
    event.preventDefault();
    var button = form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = true;
    }

    function fallback() {
      // Vuelve al envío normal del formulario (redirige a /carrito).
      if (button) {
        button.disabled = false;
      }
      form.submit();
    }

    window.fetch(form.action, {
      method: 'POST',
      body: new URLSearchParams(new FormData(form)),
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (response) {
      var type = response.headers.get('Content-Type') || '';
      if (type.indexOf('json') === -1) {
        throw new Error('no-json');
      }
      return response.json().then(function (body) {
        return { status: response.status, body: body };
      });
    }).then(function (result) {
      if (button) {
        button.disabled = false;
      }
      if (result.body && result.body.ok === true) {
        setCount(result.body.count);
        showToast();
        return;
      }
      // JSON de error (agotado, carrito lleno): se muestra el aviso de la página del carrito.
      form.submit();
    }).catch(fallback);
  });
})();
