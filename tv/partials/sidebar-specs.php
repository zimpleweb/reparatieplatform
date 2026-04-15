<div class="info-card">
  <h4>Specificaties</h4>
  <table class="specs-table">
    <tr><td class="specs-label">Merk</td><td><?= h($tv['merk']) ?></td></tr>
    <tr><td class="specs-label">Model</td><td><?= h($tv['modelnummer']) ?></td></tr>
    <tr><td class="specs-label">Serie</td><td><?= h($tv['serie']) ?></td></tr>
    <tr>
      <td class="specs-label">Repareerbaar</td>
      <td>
        <?php if (!empty($tv['repareerbaar'])): ?>
          <span class="spec-ja">&#10003; Mogelijk</span>
        <?php else: ?>
          <span class="spec-nee">&#10007; Niet van toepassing</span>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <td class="specs-label">Taxatie</td>
      <td>
        <?php if (!empty($tv['taxatie'])): ?>
          <span class="spec-ja">&#10003; Mogelijk</span>
        <?php else: ?>
          <span class="spec-nee">&#10007; Niet van toepassing</span>
        <?php endif; ?>
      </td>
    </tr>
  </table>
</div>