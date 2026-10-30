<?php
/**
 * Listado (6.1): filtro por categoría (sin filtros de marca ni de precio) y grilla de tarjetas.
 * @var string $heading
 * @var string $lede texto bajo el título (catalog_lede)
 * @var list<array> $cards
 * @var list<array> $categories
 */
?>
<section class="page-head container">
  <h1 class="page-title"><?= e($heading) ?></h1>
  <p class="page-lede"><?= e($lede) ?></p>
</section>

<div class="container filters">
  <nav class="filters__categories" aria-label="Categorías">
    <div class="switch switch--filter" data-switch>
      <span class="switch__capsule glass-capsule" aria-hidden="true"></span>
      <ul class="switch__list">
        <?php foreach ($categories as $item): ?>
        <li>
          <a class="switch__item" href="<?= e($item['href']) ?>"<?= $item['current'] ? ' aria-current="page"' : '' ?>>
            <?= e($item['label']) ?><span class="switch__count"><?= (int) $item['count'] ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
</div>

<section class="container section--list" aria-label="Lista de productos">
  <?php if ($cards === []): ?>
  <div class="empty-state">
    <h2 class="empty-state__title">No hay productos aquí por ahora</h2>
    <p>Escríbenos y te contamos qué tenemos disponible o cuándo llega.</p>
    <div class="empty-state__actions">
      <a class="btn btn--outline" href="/productos">Ver todos los productos</a>
      <a class="btn btn--wa" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'listado', 'page' => 'productos']) ?>><?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span></a>
    </div>
  </div>
  <?php else: ?>
  <p class="list-count"><?= count($cards) ?> <?= count($cards) === 1 ? 'producto' : 'productos' ?></p>
  <div class="grid-cards">
    <?php foreach ($cards as $card): ?>
      <?php partial('product-card', ['card' => $card]); ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
