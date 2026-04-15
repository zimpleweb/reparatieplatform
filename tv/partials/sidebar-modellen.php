<?php if (!empty($vergelijk)): ?>
<div class="related-card">
  <h4>Andere <?= h($tv['merk']) ?> modellen</h4>
  <div class="related-list">
    <?php foreach ($vergelijk as $v): ?>
    <a href="<?= BASE_URL ?>/tv/<?= h($v['slug']) ?>" class="related-link">
      <span class="related-link-naam"><?= h($v['modelnummer']) ?></span>
      <span class="related-link-arrow">&rarr;</span>
    </a>
    <?php endforeach; ?>
  </div>
  <a href="<?= BASE_URL ?>/database.php?merk=<?= urlencode($tv['merk']) ?>" class="related-alle">
    Alle <?= h($tv['merk']) ?> modellen &rarr;
  </a>
</div>
<?php endif; ?>