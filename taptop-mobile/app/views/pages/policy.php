<?php
/**
 * Vista única para /garantia, /terminos y /privacidad (versión inicial, sección 4).
 * @var array $policy contenido de app/content/policies.php
 * @var string $policy_key
 */
$updated = format_date_es((string) config('legal_updated_at'));
?>
<article class="container policy">
  <header class="policy__head">
    <h1 class="page-title"><?= e($policy['title']) ?></h1>
    <p class="policy__meta"><?= e(config('legal_version_label')) ?>. Última actualización: <?= e($updated) ?>.</p>
    <p class="page-lede"><?= e($policy['intro']) ?></p>
  </header>

  <nav class="policy__toc" aria-label="En esta página">
    <ul>
      <?php foreach ($policy['sections'] as $section): ?>
      <li><a href="#<?= e($section['id']) ?>"><?= e($section['heading']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <?php foreach ($policy['sections'] as $section): ?>
  <section class="policy__section" id="<?= e($section['id']) ?>" aria-labelledby="<?= e($section['id']) ?>-title">
    <h2 id="<?= e($section['id']) ?>-title"><?= e($section['heading']) ?></h2>
    <?php foreach ($section['blocks'] as $block): ?>
      <?php if (isset($block['p'])): ?>
      <p><?= e_todo($block['p']) ?></p>
      <?php elseif (isset($block['ul'])): ?>
      <ul><?php foreach ($block['ul'] as $item): ?><li><?= e_todo($item) ?></li><?php endforeach; ?></ul>
      <?php elseif (isset($block['ol'])): ?>
      <ol><?php foreach ($block['ol'] as $item): ?><li><?= e_todo($item) ?></li><?php endforeach; ?></ol>
      <?php elseif (isset($block['dl'])): ?>
      <dl class="policy__data"><?php foreach ($block['dl'] as $term => $detail): ?><dt><?= e($term) ?></dt><dd><?= e_todo($detail) ?></dd><?php endforeach; ?></dl>
      <?php endif; ?>
    <?php endforeach; ?>
  </section>
  <?php endforeach; ?>

  <aside class="policy__contact">
    <p>¿Tienes preguntas sobre esta página?</p>
    <a class="btn btn--wa" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer"<?= track_attrs('whatsapp_click', ['placement' => 'politicas', 'page' => $policy_key]) ?>><?= icon('whatsapp', 'icon') ?><span>Escribir por WhatsApp</span></a>
  </aside>
</article>
