<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <a href="<?= BASE_URL ?>/database.php">TV Database</a><span class="sep">/</span>
      <a href="<?= BASE_URL ?>/database.php?merk=<?= urlencode($tv['merk']) ?>"><?= h($tv['merk']) ?></a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)"><?= h($tv['modelnummer']) ?></span>
    </div>
    <div class="model-header-row">
      <div>
        <div class="model-eyebrow">
          <span class="model-eyebrow-merk">&#128250; <?= h($tv['merk']) ?></span>
          <?php if ($merkRepareerbaar): ?>
            <span class="model-eyebrow-rep">&#128295; Reparatie mogelijk</span>
          <?php else: ?>
            <span class="model-eyebrow-taxatie">&#128203; Taxatie mogelijk</span>
          <?php endif; ?>
        </div>
        <h1><?= h($tv['merk'] . ' ' . $tv['modelnummer']) ?> defect?</h1>
        <p class="model-header-sub"><?= h($tv['serie']) ?></p>
      </div>
      <div class="model-header-cta">
        <a href="<?= BASE_URL ?>/#advies" class="btn-primary">
          Gratis advies aanvragen
          <span class="btn-primary-arrow">&rarr;</span>
        </a>
      </div>
    </div>
  </div>
</div>