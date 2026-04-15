<div class="hulp-card">
  <div class="hulp-card-icon">&#128172;</div>
  <div class="hulp-card-body">
    <h3>Hulp nodig met jouw <?= h($tv['modelnummer']) ?>?</h3>
    <p>
      Onze specialist bekijkt je situatie gratis en geeft persoonlijk advies over
      <?= $merkRepareerbaar ? 'reparatie, ' : '' ?>garantie, coulance of taxatie.
    </p>
  </div>
  <a href="<?= BASE_URL ?>/#advies" class="btn-primary" style="flex-shrink:0;">
    Gratis advies <span class="btn-primary-arrow">&rarr;</span>
  </a>
</div>