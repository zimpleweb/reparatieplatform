<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
$tv   = getTv($slug);

if (!$tv) {
    http_response_code(404);
    $pageTitle = 'Televisie niet gevonden | Reparatieplatform.nl';
    include __DIR__ . '/../includes/header.php';
    echo '<div style="text-align:center;padding:6rem 2rem;">
            <h1 style="font-family:Epilogue,sans-serif;font-size:2rem;margin-bottom:1rem;">Televisie niet gevonden</h1>
            <p style="color:#6b7280;margin-bottom:2rem;">Dit model staat nog niet in onze database.</p>
            <a href="/database.php" style="background:#287864;color:white;padding:.85rem 2rem;border-radius:999px;font-weight:700;">Bekijk alle modellen</a>
          </div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$related = getRelated($tv['id'], $tv['merk'], $tv['serie']);

$pageTitle       = $tv['merk'].' '.$tv['modelnummer'].' kapot of defect? Reparatie & advies | Reparatieplatform.nl';
$pageDescription = $tv['merk'].' '.$tv['modelnummer'].' defect? Veelvoorkomende klachten en gratis advies over reparatie, garantie of taxatie voor uw verzekeraar.';
$canonicalUrl    = '/tv/'.$tv['slug'];

$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $tv['merk'].' '.$tv['modelnummer'],
    'brand'       => ['@type'=>'Brand','name'=>$tv['merk']],
    'description' => $tv['beschrijving'],
];
if (!empty($tv['klachten'])) {
    $faq = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
    foreach ($tv['klachten'] as $k) {
        $faq['mainEntity'][] = [
            '@type'          => 'Question',
            'name'           => $k['titel'],
            'acceptedAnswer' => ['@type'=>'Answer','text'=>$k['omschrijving']],
        ];
    }
    $schemaJson = json_encode($schema).'</script><script type="application/ld+json">'.json_encode($faq);
} else {
    $schemaJson = json_encode($schema);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb-bar">
  <div class="breadcrumb">
    <a href="/">Home</a><span class="sep">/</span>
    <a href="/database.php">Database</a><span class="sep">/</span>
    <a href="/database.php?merk=<?= urlencode($tv['merk']) ?>"><?= h($tv['merk']) ?></a><span class="sep">/</span>
    <a href="/database.php?merk=<?= urlencode($tv['merk']) ?>&serie=<?= urlencode($tv['serie']) ?>"><?= h($tv['serie']) ?></a><span class="sep">/</span>
    <span><?= h($tv['modelnummer']) ?></span>
  </div>
</div>

<div class="model-page">

  <div class="content">

    <div class="model-header">
      <div class="model-icon-wrap">&#128250;</div>
      <div>
        <div class="model-meta">
          <span class="meta-tag"><?= h($tv['merk']) ?></span>
          <span class="meta-tag"><?= h($tv['serie']) ?></span>
          <?php if ($tv['repareerbaar']): ?>
          <span class="meta-tag green">&#10003; Repareerbaar</span>
          <?php endif; ?>
        </div>
        <h1><?= h($tv['merk'].' '.$tv['modelnummer']) ?></h1>
        <p class="model-sub"><?= h($tv['beschrijving']) ?></p>
      </div>
    </div>

    <div class="card">
      <h2>Specificaties</h2>
      <table class="specs-table">
        <tr><td>Merk</td><td><?= h($tv['merk']) ?></td></tr>
        <tr><td>Serie</td><td><?= h($tv['serie']) ?></td></tr>
        <tr><td>Modelnummer</td><td><?= h($tv['modelnummer']) ?></td></tr>
        <tr><td>Repareerbaar</td><td><?= $tv['repareerbaar'] ? 'Ja' : 'Nader te bepalen' ?></td></tr>
      </table>
    </div>

    <?php if (!empty($tv['klachten'])): ?>
    <div class="card">
      <h2>Veelvoorkomende klachten</h2>
      <div class="klacht-list">
        <?php
        $iconColors = ['hoog'=>'ki-red','middel'=>'ki-yellow','laag'=>'ki-blue'];
        foreach ($tv['klachten'] as $k):
          $ic = $iconColors[$k['frequentie']] ?? 'ki-blue';
        ?>
        <div class="klacht-item">
          <div class="klacht-icon <?= $ic ?>"><?= h($k['type_icon']) ?></div>
          <div>
            <div class="klacht-title"><?= h($k['titel']) ?></div>
            <div class="klacht-desc"><?= h($k['omschrijving']) ?></div>
          </div>
          <span class="freq-badge freq-<?= h($k['frequentie']) ?>">
            <?= match($k['frequentie']) { 'hoog'=>'Veel gemeld','middel'=>'Regelmatig',default=>'Minder vaak' } ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <h2>Reparatie van de <?= h($tv['merk'].' '.$tv['modelnummer']) ?></h2>
      <p>
        De <?= h($tv['merk'].' '.$tv['modelnummer']) ?> is een televisie van <?= h($tv['merk']) ?>.
        Wij zijn gespecialiseerd in het repareren van <?= h($tv['merk']) ?>-televisies aan huis.
        LED strips, T-CON boards en backlight-problemen zijn onze specialiteit.
      </p>
      <p>
        Heb je een beschadigde <?= h($tv['modelnummer']) ?> en wil je weten of reparatie loont?
        Of heb je een taxatierapport nodig voor je verzekeraar?
        Vraag gratis en vrijblijvend advies aan via het formulier hiernaast.
      </p>
    </div>

    <?php if (!empty($tv['klachten'])): ?>
    <div class="card">
      <h2>Veelgestelde vragen</h2>
      <?php foreach ($tv['klachten'] as $k): ?>
      <div class="faq-item">
        <div class="faq-q"><?= h($k['titel']) ?></div>
        <div class="faq-a"><?= h($k['omschrijving']) ?></div>
      </div>
      <?php endforeach; ?>
      <div class="faq-item">
        <div class="faq-q">Kan de <?= h($tv['modelnummer']) ?> aan huis worden gerepareerd?</div>
        <div class="faq-a">
          Ja, wij repareren de <?= h($tv['merk'].' '.$tv['modelnummer']) ?> bij u thuis.
          Vraag een gratis advies aan en wij laten u weten wat de mogelijkheden zijn.
        </div>
      </div>
      <div class="faq-item">
        <div class="faq-q">Wat kost een taxatierapport voor mijn <?= h($tv['modelnummer']) ?>?</div>
        <div class="faq-a">
          Vraag vrijblijvend een advies aan via het formulier.
          Wij stellen een officieel taxatierapport op dat geaccepteerd wordt door uw verzekeraar.
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <aside class="sidebar">

    <div class="cta-card">
      <h3>Gratis advies aanvragen</h3>
      <p>Vul je klacht in en ontvang persoonlijk advies over jouw <?= h($tv['modelnummer']) ?>.</p>
      <form action="/api/send-advies.php" method="POST">
        <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
        <input type="hidden" name="merk"        value="<?= h($tv['merk']) ?>" />
        <input type="hidden" name="modelnummer" value="<?= h($tv['modelnummer']) ?>" />
        <div class="cta-field">
          <label>Type klacht</label>
          <select name="klacht_type">
            <option>Donkere vlekken / backlight-uitval</option>
            <option>Strepen of lijnen in beeld</option>
            <option>Geen beeld, wel geluid</option>
            <option>LED strip kapot</option>
            <option>TV gaat niet aan</option>
            <option>Kapot / gebarsten scherm</option>
            <option>Anders</option>
          </select>
        </div>
        <div class="cta-field">
          <label>Omschrijving</label>
          <textarea name="omschrijving" placeholder="Beschrijf het probleem kort..."></textarea>
        </div>
        <div class="cta-field">
          <label>E-mailadres *</label>
          <input type="email" name="email" placeholder="naam@email.nl" required />
        </div>
        <p class="cta-disclaimer">Advies is indicatief en vrijblijvend. Aan dit advies kunnen geen rechten worden ontleend.</p>
        <button type="submit" class="cta-submit">Verstuur en ontvang advies &rarr;</button>
      </form>
    </div>

    <div class="info-card">
      <h4>Reparatiemogelijkheden</h4>
      <div class="info-row"><span class="info-icon">&#10003;</span><p><strong>LED strip vervanging</strong><br>Meest voorkomende reparatie. Aan huis mogelijk.</p></div>
      <div class="info-row"><span class="info-icon">&#10003;</span><p><strong>T-CON board</strong><br>Oplossing voor strepen en beeldfouten.</p></div>
      <div class="info-row"><span class="info-icon">&#10003;</span><p><strong>Power supply</strong><br>Als de tv niet meer opstart.</p></div>
      <div class="info-row"><span class="info-icon">&#128203;</span><p><strong>Taxatierapport</strong><br>Voor verzekeraars, ook bij buitenschuld schade.</p></div>
    </div>

    <?php if (!empty($related)): ?>
    <div class="related-card">
      <h4>Vergelijkbare modellen</h4>
      <div class="related-list">
        <?php foreach ($related as $r): ?>
        <a href="/tv/<?= h($r['slug']) ?>" class="related-link">
          <?= h($r['merk'].' '.$r['modelnummer']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </aside>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>