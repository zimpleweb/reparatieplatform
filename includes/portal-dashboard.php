<?php
// Component: portal-dashboard.php
// Variabelen vereist: $inzending (array), $sl (array), $status (string),
//                     $stapNr (int), $melding (string), $meldingOk (bool),
//                     $isTaxatie (bool), $isReparatie (bool)
?>
<div class="portal-wrap">

  <!-- Top bar -->
  <div class="portal-top-bar">
    <div>
      <div class="portal-case-title">
        Aanvraag <?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>
        <span class="portal-status-badge <?= $sl['css'] ?>"><?= htmlspecialchars($sl['tekst'], ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="portal-case-sub">
        <?= htmlspecialchars($inzending['merk'] . ' ' . $inzending['modelnummer'], ENT_QUOTES, 'UTF-8') ?>
        &nbsp;&bull;&nbsp; <?= htmlspecialchars($inzending['klacht_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </div>
    </div>
    <a href="?uitloggen=1" class="portal-logout">← Uitloggen</a>
  </div>

  <!-- Melding -->
  <?php if ($melding): ?>
    <div class="portal-alert <?= $meldingOk ? 'alert-success' : 'alert-error' ?>">
      <span><?= $meldingOk ? '✓' : '⚠' ?></span>
      <span><?= htmlspecialchars($melding, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- Status stappen -->
  <div class="portal-status-steps">
    <div class="portal-status-steps-title">Voortgang</div>
    <div class="status-steps-track">

      <div class="status-step <?= $stapNr >= 1 ? ($stapNr === 1 ? 'active' : 'done') : '' ?>">
        <div class="status-step-dot"><?= $stapNr > 1 ? '✓' : '1' ?></div>
        <div class="status-step-label">Ontvangen</div>
      </div>
      <div class="status-step-connector <?= $stapNr >= 2 ? 'done' : '' ?>"></div>

      <div class="status-step <?= $stapNr >= 2 ? ($stapNr === 2 ? 'active' : 'done') : '' ?>">
        <div class="status-step-dot"><?= $stapNr > 2 ? '✓' : '2' ?></div>
        <div class="status-step-label">In behandeling</div>
      </div>
      <div class="status-step-connector <?= $stapNr >= 3 ? 'done' : '' ?>"></div>

      <div class="status-step <?= $stapNr >= 3 ? ($stapNr === 3 ? 'active' : 'done') : '' ?>">
        <div class="status-step-dot"><?= $stapNr > 3 ? '✓' : '3' ?></div>
        <div class="status-step-label">Ingediend</div>
      </div>
      <div class="status-step-connector <?= $stapNr >= 4 ? 'done' : '' ?>"></div>

      <div class="status-step <?= $stapNr >= 4 ? 'active' : '' ?>">
        <div class="status-step-dot">4</div>
        <div class="status-step-label">Afgerond</div>
      </div>
    </div>
  </div>

  <div class="portal-grid">
    <div class="portal-main">

      <?php if ($status === 'doorgestuurd'): ?>
        <?php if ($isReparatie): require __DIR__ . '/portal-form-reparatie.php';
        elseif ($isTaxatie):    require __DIR__ . '/portal-form-taxatie.php';
        else:                   require __DIR__ . '/portal-form-aanvulling.php';
        endif; ?>

      <?php elseif ($status === 'coulance'): ?>
        <div class="portal-action-card card-warning">
          <div class="portal-action-header">
            <div class="portal-action-icon icon-warning">🤝</div>
            <div>
              <h3>Coulancetraject</h3>
              <p>Neem contact op met de verkoper of fabrikant voor een coulanceverzoek.</p>
            </div>
          </div>
          <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
            Leg uw situatie rustig uit en verwijs naar de wettelijke regels rondom consumentenkoop.
            Vermeld dat de televisie <?= (int)(date('Y') - (int)($inzending['aanschafjaar'] ?? date('Y'))) ?> jaar oud is
            en een technisch defect heeft dat niet door uzelf is veroorzaakt.
          </p>
          <p style="font-size:.85rem;color:var(--muted);margin-bottom:1.5rem;">
            Lukt het coulanceverzoek niet? Dan kunt u via onderstaande knop een reparatieaanvraag starten.
          </p>
          <form method="POST" action="<?= BASE_URL ?>/api/aanvulling.php">
            <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
            <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
            <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="actie"       value="coulance_naar_reparatie" />
            <button type="submit" class="portal-submit-btn btn-warning">
              Coulance lukt niet — reparatieaanvraag starten
            </button>
          </form>
        </div>

      <?php elseif ($status === 'recycling'): ?>
        <div class="portal-action-card card-purple">
          <div class="portal-action-header">
            <div class="portal-action-icon icon-purple">♻</div>
            <div>
              <h3>Recyclingverzoek indienen</h3>
              <p>Uw televisie komt in aanmerking voor verantwoorde recycling.</p>
            </div>
          </div>
          <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
            <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
            <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="actie"       value="recycling_aanvraag" />
            <div class="portal-field">
              <label>Naam *</label>
              <input type="text" name="naam" required value="<?= htmlspecialchars($inzending['naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="portal-field">
              <label>Telefoonnummer *</label>
              <input type="tel" name="telefoon" required value="<?= htmlspecialchars($inzending['telefoon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="portal-field">
              <label>Ophaaladres (straat + huisnummer, postcode, stad) *</label>
              <input type="text" name="adres" required value="<?= htmlspecialchars($inzending['adres'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <button type="submit" class="portal-submit-btn btn-purple">Recyclingverzoek indienen &rarr;</button>
          </form>
        </div>

      <?php elseif ($status === 'inzending'): ?>
        <div class="portal-info-card info-blue">
          <h3>🔍 Uw aanvraag is ontvangen</h3>
          <p>Ons team beoordeelt uw aanvraag zo spoedig mogelijk. Gemiddelde verwerkingstijd: 1 werkdag.</p>
        </div>

      <?php elseif (in_array($status, ['aanvraag', 'behandeld', 'archief'])): ?>
        <div class="portal-info-card info-green">
          <h3>&#10003; <?= htmlspecialchars($sl['tekst'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p>Er zijn geen verdere acties vereist. Bij vragen kunt u contact opnemen via onze contactpagina.</p>
        </div>
      <?php endif; ?>

    </div><!-- /.portal-main -->

    <!-- Zijbalk: aanvraagdetails + log -->
    <div class="portal-sidebar">
      <div class="portal-detail-card">
        <h4>Aanvraagdetails</h4>
        <dl class="portal-dl">
          <dt>Casenummer</dt>
          <dd><?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?></dd>
          <dt>Merk</dt>
          <dd><?= htmlspecialchars($inzending['merk'], ENT_QUOTES, 'UTF-8') ?></dd>
          <dt>Model</dt>
          <dd><?= htmlspecialchars($inzending['modelnummer'], ENT_QUOTES, 'UTF-8') ?></dd>
          <?php if (!empty($inzending['aanschafjaar'])): ?>
          <dt>Aanschafjaar</dt>
          <dd><?= (int)$inzending['aanschafjaar'] ?></dd>
          <?php endif; ?>
          <dt>Ingediend op</dt>
          <dd><?= date('d-m-Y', strtotime($inzending['aangemaakt'] ?? 'now')) ?></dd>
        </dl>
      </div>

      <?php if (!empty($inzending['log'])): ?>
      <div class="portal-log-card">
        <h4>Activiteiten</h4>
        <ul class="portal-log-list">
          <?php foreach ($inzending['log'] as $logRegel): ?>
            <li class="portal-log-item">
              <span class="portal-log-date"><?= date('d-m-Y H:i', strtotime($logRegel['aangemaakt'])) ?></span>
              <span class="portal-log-msg">
                <?= htmlspecialchars($logRegel['actie'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($logRegel['opmerking'])): ?>
                  <small><?= htmlspecialchars($logRegel['opmerking'], ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div><!-- /.portal-sidebar -->
  </div><!-- /.portal-grid -->
</div><!-- /.portal-wrap -->