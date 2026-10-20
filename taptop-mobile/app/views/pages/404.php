<?php
/**
 * Página no encontrada (responde con HTTP 404 real).
 */
?>
<section class="container not-found">
  <p class="not-found__code" aria-hidden="true">404</p>
  <h1 class="page-title">No encontramos esta página</h1>
  <p class="page-lede">Puede que el enlace esté mal escrito o que el producto ya no esté publicado. Revisa el catálogo o escríbenos y te ayudamos a encontrarlo.</p>
  <div class="not-found__actions">
    <a class="btn btn--primary" href="/productos">Ver productos</a>
    <a class="btn btn--outline" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => '404', 'page' => '404']) ?>><?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span></a>
  </div>
</section>
