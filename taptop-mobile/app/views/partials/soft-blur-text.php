<?php
/**
 * Texto que entra letra por letra con desenfoque suave. site.js agrega .is-in al verlo.
 * Efecto basado en Soft Blur In de Smooth UI (Eduardo Calvo, MIT), en CSS sin motion.
 * Cada palabra va en un bloque para que el texto solo se parta entre palabras.
 * @var string $text
 */
$index = 0;
?>
<span class="visually-hidden"><?= e($text) ?></span>
<span class="soft-blur" aria-hidden="true" data-soft-blur>
  <?php foreach (explode(' ', $text) as $word): ?>
  <span class="soft-blur__word"><?php foreach (mb_str_split($word) as $char): ?><span class="soft-blur__char" style="--i: <?= $index++ ?>"><?= e($char) ?></span><?php endforeach; ?></span>
  <?php $index++; endforeach; ?>
</span>
