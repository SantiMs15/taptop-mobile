<?php
/**
 * Contáctanos (7): WhatsApp como canal principal, horario, Instagram, envíos y confianza.
 * Sin dirección ni mapa: no hay tienda física.
 */
$business = (string) config('business_name');
$zones = (array) config('shipping_zones', []);
?>
<section class="container contact">
  <div class="contact__intro">
    <h1 class="page-title">Contáctanos</h1>
    <p class="page-lede">Escríbenos por WhatsApp para confirmar disponibilidad, resolver dudas o seguir tu pedido. Te atiende una persona del equipo, no un robot.</p>
    <a class="btn btn--metal" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'contacto', 'page' => 'contacto']) ?>>
      <?= icon('whatsapp', 'icon icon--wa') ?><span>Escribir por WhatsApp</span>
    </a>
  </div>

  <dl class="contact__data">
    <div class="contact__item">
      <dt><?= icon('whatsapp', 'icon') ?>WhatsApp</dt>
      <dd><a href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'contacto', 'page' => 'contacto']) ?>><?= e(config('whatsapp_display')) ?></a></dd>
    </div>
    <div class="contact__item">
      <dt><?= icon('clock', 'icon') ?>Horario</dt>
      <dd><?= e(config('support_days_label')) ?>, <?= e_todo(config_text('support_hours_label')) ?> (<?= e(config('support_timezone_label')) ?>)</dd>
    </div>
    <div class="contact__item">
      <dt><?= icon('instagram', 'icon') ?>Instagram</dt>
      <dd><a href="<?= e(config('instagram_url')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram de <?= e($business) ?>"><?= instagram_is_provisional() ? 'Síguenos en Instagram' : e('@' . basename((string) parse_url((string) config('instagram_url'), PHP_URL_PATH))) ?></a></dd>
    </div>
    <div class="contact__item">
      <dt><?= icon('pin', 'icon') ?>Tienda</dt>
      <dd>100 % en línea, sin local físico. Enviamos a toda Colombia.</dd>
    </div>
  </dl>
</section>

<section class="container section contact-blocks">
  <article class="info-block" aria-labelledby="shipping-title">
    <h2 class="info-block__title" id="shipping-title">Envíos</h2>
    <ul class="info-list">
      <li>Enviamos a toda Colombia.</li>
      <li>Envío gratis en compras desde <?= e(format_price((int) config('free_shipping_min'))) ?>, según el precio que pagas con el medio elegido.</li>
      <li>Por debajo de ese valor, el envío cuesta <?= e(format_price((int) config('shipping_cost'))) ?>.</li>
      <li>Entrega en <?= e(delivery_label()) ?> después de confirmado el pago.</li>
      <li>El pedido sale cuando el pago está confirmado y te compartimos el seguimiento por WhatsApp.</li>
    </ul>
    <?php if ($zones !== []): ?>
    <table class="zones">
      <caption class="visually-hidden">Tiempos y costos de envío por zona</caption>
      <thead><tr><th scope="col">Zona</th><th scope="col">Tiempo</th><th scope="col">Costo</th></tr></thead>
      <tbody>
        <?php foreach ($zones as $zone): ?>
        <tr><td><?= e($zone['zone'] ?? '') ?></td><td><?= e($zone['time'] ?? '') ?></td><td><?= e($zone['cost'] ?? '') ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="info-block__note">Tiempo de entrega: <?= e(delivery_label()) ?> en toda Colombia.</p>
    <?php endif; ?>
  </article>

  <article class="info-block" aria-labelledby="trust-title">
    <h2 class="info-block__title" id="trust-title">Compra con confianza</h2>
    <ul class="trust">
      <li><?= icon('seal', 'icon') ?><span>Productos Apple y Samsung 100 % originales.</span></li>
      <li><?= icon('shield', 'icon') ?><span>Garantía directa con <?= e($business) ?>: <?= e(warranty_summary()) ?>. Si presenta un fallo, te lo cambiamos o te devolvemos tu dinero.</span></li>
      <li><?= icon('chat', 'icon') ?><span>Atención directa por WhatsApp, todos los días.</span></li>
    </ul>
    <a class="text-link" href="/garantia">Garantía, cambios, reembolsos y envíos</a>
  </article>
</section>
