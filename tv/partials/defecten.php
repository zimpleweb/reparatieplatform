<div class="model-card">
  <div class="model-card-title">Bekende defecten &amp; klachten</div>
  <?php if (empty($klachten)): ?>
    <div class="klacht-leeg">
      <span class="klacht-leeg-icon">&#128203;</span>
      <p>Voor dit model zijn nog geen bekende defecten geregistreerd.</p>
      <a href="<?= BASE_URL ?>/#advies" class="info-link">Beschrijf jouw probleem &rarr;</a>
    </div>
  <?php else: ?>
    <p class="model-card-intro">
      Hieronder staan bekende problemen van de <?= h($tv['merk'] . ' ' . $tv['modelnummer']) ?>.
      Herken jij jouw klacht?
    </p>
    <div class="klacht-list">
      <?php
      $icMap = ['hoog' => 'ki-red', 'middel' => 'ki-yellow', 'laag' => 'ki-blue'];
      $icCls = ['hoog' => 'ki-hoog', 'middel' => 'ki-middel', 'laag' => 'ki-laag'];
      foreach ($klachten as $k):
        $ic  = $icMap[$k['frequentie']] ?? 'ki-blue';
        $cls = $icCls[$k['frequentie']] ?? 'ki-laag';
      ?>
      <div class="klacht-item <?= $cls ?>">
        <div class="klacht-icon <?= $ic ?>"><?= h($k['type_icon'] ?? '&#9888;') ?></div>
        <div>
          <div class="klacht-title"><?= h($k['titel']) ?></div>
          <?php if (!empty($k['omschrijving'])): ?>
            <div class="klacht-desc"><?= h($k['omschrijving']) ?></div>
          <?php endif; ?>
        </div>
        <span class="freq-badge freq-<?= h($k['frequentie']) ?>">
          <?= match($k['frequentie']) {
              'hoog'   => 'Veel gemeld',
              'middel' => 'Regelmatig',
              default  => 'Incidenteel'
          } ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>