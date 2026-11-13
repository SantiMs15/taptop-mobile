<?php
/**
 * Footer: marca, enlaces, atención (WhatsApp, horario, Instagram provisional),
 * promesas de envío y garantía, datos legales y aviso de datos/cookies.
 * @var string $page
 */
$business = (string) config('business_name');
$hours = config_text('support_hours_label');
?>
<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <a class="brand" href="/" aria-label="<?= e($business) ?>, ir al inicio">
        <?= logo_mark_3d('brand__mark brand__mark--3d') ?>
        <span class="brand__name" aria-hidden="true"><?= e($business) ?></span>
      </a>
      <p>Celulares y tecnología Apple y Samsung originales, con envío a toda Colombia.</p>
    </div>

    <nav class="site-footer__col" aria-label="Tienda">
      <h2 class="footer-title">Tienda</h2>
      <ul class="footer-links">
        <li><a href="/">Inicio</a></li>
        <li><a href="/productos">Productos</a></li>
        <li><a href="/contacto">Contáctanos</a></li>
      </ul>
    </nav>

    <nav class="site-footer__col" aria-label="Información">
      <h2 class="footer-title">Información</h2>
      <ul class="footer-links">
        <li><a href="/garantia">Garantía, cambios, reembolsos y envíos</a></li>
        <li><a href="/terminos">Términos y condiciones</a></li>
        <li><a href="/privacidad">Tratamiento de datos</a></li>
      </ul>
    </nav>

    <div class="site-footer__col">
      <h2 class="footer-title">Atención</h2>
      <ul class="footer-links footer-links--icons">
        <li>
          <a href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'footer', 'page' => $page]) ?>>
            <?= icon('whatsapp', 'icon') ?><span>WhatsApp <?= e(config('whatsapp_display')) ?></span>
          </a>
        </li>
        <li>
          <?= icon('clock', 'icon') ?><span><?= e(config('support_days_label')) ?>, <?= e_todo($hours) ?> (<?= e(config('support_timezone_label')) ?>)</span>
        </li>
        <li>
          <a href="<?= e(config('instagram_url')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram de <?= e($business) ?>">
            <?= icon('instagram', 'icon') ?><span>Instagram</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="container">
    <ul class="site-footer__promises">
      <li><?= icon('truck', 'icon') ?><span>Envío gratis desde <?= e(format_price((int) config('free_shipping_min'))) ?>; por debajo, <?= e(format_price((int) config('shipping_cost'))) ?>. Entrega en <?= e(delivery_label()) ?>.</span></li>
      <li><?= icon('shield', 'icon') ?><span>Garantía directa con <?= e($business) ?>: <?= e(warranty_summary()) ?>.</span></li>
    </ul>
  </div>

  <div class="container site-footer__legal">
    <p>© <?= e(date('Y')) ?> <?= e($business) ?></p>
    <p>
      Usamos cookies de analítica solo si las aceptas. <a href="/privacidad#cookies">Política de datos</a>
      <?php if (analytics_enabled()): ?>
      <button class="link-button" type="button" data-consent-open>Cookies</button>
      <?php endif; ?>
    </p>
  </div>
</footer>
