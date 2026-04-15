<?php if (!empty($klachten)): ?>
<div class="info-card">
  <h4>Bekende defecten</h4>
  <?php foreach ($klachten as $k): ?>
  <div class="info-row">
    <span class="info-icon">
      <?= match($k['frequentie']) { 'hoog' => '&#128308;', 'middel' => '&#128992;', default => '&#128994;' } ?>
    </span>
    <p><strong><?= h($k['titel']) ?></strong></p>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>