<?php
/**
 * Carrito de compras (cookie). Los precios salen del catálogo en el servidor.
 * @var array $cart resultado de cart_priced()
 * @var bool $changed
 */
$lines = $cart['lines'];
$lineTrack = static fn (array $l): array => ['product_slug' => $l['slug'], 'sku' => $l['sku'], 'quantity' => $l['qty']];
?>
<section class="container cart">
  <h1 class="page-title">Carrito</h1>

  <?php if ($changed): ?>
  <p class="cart__notice" role="status">Tu carrito cambió: revisa precios y disponibilidad antes de pagar.</p>
  <?php endif; ?>

  <?php if ($lines === []): ?>
  <p class="page-lede">Tu carrito está vacío.</p>
  <div class="not-found__actions">
    <a class="btn btn--primary" href="<?= e(url('/productos')) ?>">Ver productos</a>
  </div>
  <?php else: ?>
  <ul class="cart__list">
    <?php foreach ($lines as $line): ?>
    <li class="cart__line<?= $line['available'] ? '' : ' is-sold-out' ?>">
      <a class="cart__thumb" href="<?= e($line['url']) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($line['image'] !== null): ?>
        <img src="<?= e($line['image']) ?>" width="64" height="64" alt="" loading="lazy" decoding="async">
        <?php else: ?>
        <?= device_placeholder($line['kind'], $line['hex'], ['decorative' => true]) ?>
        <?php endif; ?>
      </a>
      <div class="cart__info">
        <a class="cart__name" href="<?= e($line['url']) ?>"><?= e($line['name']) ?></a>
        <?php if ($line['detail'] !== ''): ?><p class="cart__label"><?= e($line['detail']) ?></p><?php endif; ?>
        <?php if (!$line['available']): ?><span class="badge badge--sold">Agotado</span><?php endif; ?>
        <form class="cart__qty" method="post" action="<?= e(url('/carrito/actualizar')) ?>">
          <input type="hidden" name="index" value="<?= (int) $line['index'] ?>">
          <?php if ($line['available']): ?>
          <button class="cart__step" type="submit" name="qty" value="<?= $line['qty'] - 1 ?>" aria-label="Quitar una unidad de <?= e($line['name']) ?>">−</button>
          <span class="cart__n" aria-label="Cantidad"><?= (int) $line['qty'] ?></span>
          <button class="cart__step" type="submit" name="qty" value="<?= min(CART_MAX_QTY, $line['qty'] + 1) ?>" aria-label="Agregar una unidad de <?= e($line['name']) ?>"<?= $line['qty'] >= CART_MAX_QTY ? ' disabled' : '' ?>>+</button>
          <?php endif; ?>
          <button class="cart__remove" type="submit" name="qty" value="0" aria-label="Quitar <?= e($line['name']) ?> del carrito">Quitar</button>
        </form>
      </div>
      <?php if ($line['available']): ?>
      <p class="cart__price">
        <strong><?= e(format_price($line['line_gw'])) ?></strong>
        <small><?= e(format_price($line['line_cash'])) ?> por transferencia</small>
      </p>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($cart['has_unavailable']): ?>
  <p class="cart__notice" role="status">Los productos agotados no se incluyen en el total.</p>
  <?php endif; ?>

  <dl class="cart__totals">
    <div><dt>Total pagando en línea</dt><dd class="cart__total"><?= e(format_price($cart['total_gateway'])) ?></dd></div>
    <div><dt>Total por transferencia</dt><dd><?= e(format_price($cart['total_cash'])) ?></dd></div>
  </dl>
  <?php if ($cart['count'] > 0): ?>
  <p class="cart__shipping"><?= e($cart['shipping']['label']) ?><?= $cart['shipping']['free'] ? '' : ' (incluido en el total)' ?>. Entrega en <?= e(delivery_label()) ?>.</p>
  <?php endif; ?>

  <?php if ($cart['count'] > 0): ?>
  <div class="cart__actions">
    <?php if (wompi() !== null): ?>
    <a class="btn btn--metal" href="<?= e(url('/pago/envio', ['origen' => 'carrito'])) ?>"<?= track_attrs('begin_checkout', ['placement' => 'carrito', 'value' => $cart['total_gateway'], 'currency' => 'COP', 'quantity' => $cart['count']]) ?>>Pagar ahora · <?= e(format_price($cart['total_gateway'])) ?></a>
    <?php endif; ?>
    <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link(wa_message_cart($cart))) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'carrito']) ?>><?= icon('whatsapp', 'icon') ?><span>Pagar por transferencia (WhatsApp)</span></a>
  </div>
  <?php endif; ?>
  <p class="cart__back"><a href="<?= e(url('/productos')) ?>">‹ Seguir comprando</a></p>
  <?php endif; ?>
</section>
