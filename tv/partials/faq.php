<?php if (!empty($klachten)): ?>
<div class="model-card">
  <div class="model-card-title">&#10067; Veelgestelde vragen over de <?= h($tv['modelnummer']) ?></div>
  <div class="faq-lijst-fancy">

    <?php foreach ($klachten as $k): ?>
    <div class="faq-fancy-item">
      <button class="faq-fancy-q">
        <span class="faq-fancy-icon"><?= h($k['type_icon'] ?? '&#9888;') ?></span>
        <span><?= h($k['titel']) ?></span>
      </button>
      <div class="faq-fancy-a">
        <p><?= h($k['omschrijving']) ?></p>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($merkRepareerbaar): ?>
    <div class="faq-fancy-item">
      <button class="faq-fancy-q">
        <span class="faq-fancy-icon">&#128295;</span>
        <span>Kan de <?= h($tv['modelnummer']) ?> gerepareerd worden?</span>
      </button>
      <div class="faq-fancy-a">
        <p>De <?= h($tv['merk'] . ' ' . $tv['modelnummer']) ?> komt mogelijk in aanmerking voor reparatie,
        afhankelijk van de aankoopdatum en aanschafprijs. Gebruik de keuzehulp hierboven om te zien
        wat voor jouw situatie geldt.</p>
      </div>
    </div>
    <?php endif; ?>

    <div class="faq-fancy-item">
      <button class="faq-fancy-q">
        <span class="faq-fancy-icon">&#128203;</span>
        <span>Kan ik een taxatierapport aanvragen voor mijn <?= h($tv['modelnummer']) ?>?</span>
      </button>
      <div class="faq-fancy-a">
        <p>Ja. Reparatieplatform stelt officiële taxatierapporten op voor alle merken en modellen,
        geaccepteerd door alle Nederlandse verzekeraars.</p>
      </div>
    </div>

    <div class="faq-fancy-item">
      <button class="faq-fancy-q">
        <span class="faq-fancy-icon">&#128737;</span>
        <span>Heb ik nog garantie of coulance op mijn <?= h($tv['modelnummer']) ?>?</span>
      </button>
      <div class="faq-fancy-a">
        <p>Dat hangt af van je aankoopdatum en aanschafprijs. Wettelijke garantie geldt 2 jaar.
        Daarna kunnen verkopers coulance toepassen: tot 3 jaar onder €300, 4 jaar bij €300–500,
        5 jaar bij €500–1000, en 6 jaar boven €1000. Gebruik de keuzehulp voor jouw situatie.</p>
      </div>
    </div>

  </div>
</div>
<?php endif; ?>