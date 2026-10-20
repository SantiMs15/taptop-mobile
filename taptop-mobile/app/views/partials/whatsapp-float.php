<?php
/**
 * Botón flotante de WhatsApp (todas las páginas, esquina inferior derecha).
 * @var string $page
 */
?>
<a class="wa-float" href="<?= e(whatsapp_link(wa_message_general())) ?>" target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp"<?= track_attrs('whatsapp_click', ['placement' => 'flotante', 'page' => $page]) ?>>
  <?= icon('whatsapp', 'icon') ?>
</a>
