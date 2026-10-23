<?php
/**
 * Header fijo: logo 3D, Inicio · Productos · Contáctanos y botón de WhatsApp.
 * En el Inicio flota transparente sobre el escenario y se vuelve vidrio al hacer scroll.
 * @var bool $overlay
 * @var string $page
 */
$cartCount = cart_count();
$cartLabel = 'Carrito, ' . $cartCount . ($cartCount === 1 ? ' producto' : ' productos');
$links = [
    ['label' => 'Inicio', 'href' => '/', 'current' => $page === 'home'],
    ['label' => 'Productos', 'href' => '/productos', 'current' => in_array($page, ['products', 'product'], true)],
    ['label' => 'Contáctanos', 'href' => '/contacto', 'current' => $page === 'contact'],
];
?>
<header class="site-header<?= $overlay ? ' site-header--overlay' : '' ?>" data-header>
  <div class="site-header__bar">
    <a class="brand" href="/" aria-label="<?= e(config('business_name')) ?>, ir al inicio">
      <span class="brand__spin" style="background-image: url('<?= e(asset('images/brand/logo-3d-spin.webp')) ?>')" aria-hidden="true"></span>
      <span class="brand__name" aria-hidden="true"><?= e(config('business_name')) ?></span>
    </a>
    <nav class="site-nav" id="site-nav" aria-label="Principal">
      <div class="switch switch--nav" data-switch>
        <span class="switch__capsule glass-capsule" aria-hidden="true"></span>
        <ul class="switch__list">
          <?php foreach ($links as $link): ?>
          <li><a class="switch__item" href="<?= e($link['href']) ?>"<?= $link['current'] ? ' aria-current="page"' : '' ?>><?= e($link['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
    <a class="cart-link" href="<?= e(url('/carrito')) ?>" aria-label="<?= e($cartLabel) ?>" data-cart-link>
      <?= icon('cart', 'icon') ?><span class="cart-link__count" data-cart-count<?= $cartCount === 0 ? ' hidden' : '' ?> aria-hidden="true"><?= $cartCount ?></span>
    </a>
    <a class="btn btn--outline btn--header" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp"<?= track_attrs('whatsapp_click', ['placement' => 'header', 'page' => $page]) ?>>
      <?= icon('whatsapp', 'icon icon--wa') ?><span>Escríbenos</span>
    </a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-menu-toggle>
      <?= icon('menu', 'icon menu-toggle__open') ?><?= icon('close', 'icon menu-toggle__close') ?>
      <span class="visually-hidden">Menú</span>
    </button>
  </div>
</header>
