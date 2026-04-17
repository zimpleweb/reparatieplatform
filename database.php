<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$merk  = trim($_GET['merk']  ?? '');
$serie = trim($_GET['serie'] ?? '');
$q     = trim($_GET['q']     ?? '');

$merken  = getMerken();
$series  = getSeries($merk);

$heeftFilter = $merk || $serie || $q;
$tvs = ($heeftFilter && !$q) ? getAllTvs(['merk'=>$merk, 'serie'=>$serie]) : [];

$uitgelicht = db()->query(
    'SELECT * FROM tv_modellen WHERE actief=1 AND uitgelicht=1 ORDER BY merk,modelnummer LIMIT 12'
)->fetchAll();

$repareerbareMerken = ['Philips', 'Samsung', 'LG', 'Sony'];

$pageTitle       = 'TV Database – Zoek modelnummer | Reparatieplatform.nl';
$pageDescription = 'Zoek je televisie op merk, serie of modelnummer en ontdek veelvoorkomende klachten en reparatiemogelijkheden.';
$canonicalUrl    = '/database.php';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.7)">TV Database</span>
    </div>
    <h1>TV Database</h1>
    <p>Zoek op modelnummer of blader per merk. Je ziet meteen of het model repareerbaar is en welke klachten er bekend zijn.</p>

    <div class="hero-search-wrap">
      <div class="hero-search">
        <span class="hero-search-icon">&#128269;</span>
        <input
          type="text"
          id="zoekInput"
          placeholder="Zoek op modelnummer, bijv. UE55CU8000…"
          autocomplete="off"
          value="<?= h($q) ?>"
        />
        <button type="button" id="zoekClear" class="hero-search-clear" style="display:<?= $q?'flex':'none' ?>;">&#215;</button>
      </div>
      <div id="autocomplete-box" class="autocomplete-box"></div>
    </div>
  </div>
</div>

<div class="page-main">

  <div id="live-results"></div>

  <?php if ($heeftFilter && !$q): ?>

    <form method="GET" action="<?= BASE_URL ?>/database.php" id="filterForm" style="margin-bottom:1.5rem;">
      <div class="filter-bar-small">
        <div class="filter-group">
          <label>Merk</label>
          <select name="merk" onchange="this.form.submit()">
            <option value="">Alle merken</option>
            <?php foreach ($merken as $m): ?>
            <option value="<?= h($m) ?>" <?= $merk===$m?'selected':'' ?>><?= h($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Serie</label>
          <select name="serie" onchange="this.form.submit()" <?= !$merk?'disabled':'' ?>>
            <option value=""><?= $merk?'Alle series':'Selecteer eerst een merk' ?></option>
            <?php foreach ($series as $s): ?>
            <option value="<?= h($s) ?>" <?= $serie===$s?'selected':'' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <a href="<?= BASE_URL ?>/database.php" class="filter-reset">Wis filters</a>
      </div>
    </form>

    <?php if (!empty($tvs)): ?>
      <p class="results-count"><strong><?= count($tvs) ?></strong> televisie<?= count($tvs)!==1?'s':'' ?> gevonden</p>
      <div class="tv-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Model</th>
              <th>Serie</th>
              <th>Reparatie</th>
              <th>Taxatie</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tvs as $tv):
              $rep = in_array($tv['merk'], $repareerbareMerken);
            ?>
            <tr onclick="location.href='<?= BASE_URL ?>/tv/<?= h($tv['slug']) ?>'" style="cursor:pointer;">
              <td>
                <div class="td-model"><?= h($tv['modelnummer']) ?></div>
                <div class="td-sub"><?= h($tv['merk']) ?></div>
              </td>
              <td><?= h($tv['serie']) ?></td>
              <td>
                <?php if ($rep): ?>
                  <span class="spec-ja">&#10003;</span>
                <?php else: ?>
                  <span class="spec-nee">&#10007;</span>
                <?php endif; ?>
              </td>
              <td><span class="spec-ja">&#10003;</span></td>
              <td><a class="td-link" href="<?= BASE_URL ?>/tv/<?= h($tv['slug']) ?>">Bekijk &rarr;</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="not-found-block">
        <div class="not-found-icon">&#128250;</div>
        <h2>Geen modellen gevonden</h2>
        <p>Voor dit merk of deze serie staan nog geen modellen in de database. Je kunt ook zonder modelpagina advies aanvragen.</p>
        <div class="not-found-actions">
          <a href="<?= BASE_URL ?>/#advies" class="btn-primary">Gratis advies aanvragen &rarr;</a>
          <a href="<?= BASE_URL ?>/database.php" class="btn-ghost">Terug naar database</a>
        </div>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <!-- Merken -->
    <div class="db-section">
      <div class="db-section-header">
        <h2>Zoek op merk</h2>
        <p>Kies een merk om alle modellen en series te zien die in de database staan.</p>
      </div>
      <div class="merken-kaarten">
        <?php foreach ($merken as $m):
          $cnt = db()->prepare('SELECT COUNT(*) FROM tv_modellen WHERE merk=? AND actief=1');
          $cnt->execute([$m]);
          $aantal = $cnt->fetchColumn();
        ?>
        <a href="<?= BASE_URL ?>/database.php?merk=<?= urlencode($m) ?>" class="merk-kaart">
          <div class="merk-kaart-body">
            <div class="merk-kaart-naam"><?= h($m) ?></div>
            <div class="merk-kaart-count"><?= $aantal ?> modellen</div>
          </div>
          <span class="merk-kaart-arrow">&rarr;</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Populaire modellen -->
    <?php if (!empty($uitgelicht)): ?>
    <div class="db-section">
      <div class="db-section-header">
        <h2>Veel gerepareerde televisies</h2>
        <p>Dit zijn de modellen die het vaakst bij ons binnenkomen — met bekende klachten en reparatiemogelijkheden.</p>
      </div>
      <div class="populair-grid">
        <?php foreach ($uitgelicht as $tv): ?>
        <a href="<?= BASE_URL ?>/tv/<?= h($tv['slug']) ?>" class="populair-kaart">
          <div class="populair-kaart-top">
            <span class="type-badge type-<?= h(str_replace([' ','-'],'',$tv['schermtype'])) ?>"><?= h($tv['schermtype']) ?></span>
          </div>
          <div class="populair-kaart-model"><?= h($tv['modelnummer']) ?></div>
          <div class="populair-kaart-sub"><?= h($tv['merk']) ?> &mdash; <?= h($tv['serie']) ?></div>
          <span class="populair-kaart-link">Bekijk &rarr;</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>