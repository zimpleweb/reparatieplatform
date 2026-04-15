<div class="model-card">
  <div class="model-card-title">Over de <?= h($tv['merk'] . ' ' . $tv['modelnummer']) ?></div>
  <p class="model-card-body">
    <?= !empty($tv['beschrijving'])
        ? h($tv['beschrijving'])
        : 'De ' . h($tv['merk'] . ' ' . $tv['modelnummer']) . ' is een televisie van ' . h($tv['merk']) . '. Is jouw toestel defect, kapot of heb je schade? Gebruik de keuzehulp hieronder om te zien wat de mogelijkheden zijn.'
    ?>
  </p>
</div>