<?php
// Component: coulance-form.php
// Vereist: $inzending (array), lockedField() beschikbaar in scope
$coulanceShops = [
    'MediaMarkt', 'Coolblue', 'BCC', 'Expert', 'Electro World',
    'Bol.com', 'Amazon.nl', 'Wehkamp', 'HIFI Club', 'Samsung Store',
    'LG Store', 'Makro', 'Dixons', 'Mediaplanet', 'Marktplaats',
];

// Winkel support/contactpagina's — pas aan indien een URL wijzigt
$shopLinks = [
    'MediaMarkt'    => 'https://www.mediamarkt.nl/nl/service/klantenservice.html',
    'Coolblue'      => 'https://www.coolblue.nl/klantenservice',
    'BCC'           => 'https://www.bcc.nl/klantenservice',
    'Expert'        => 'https://www.expert.nl/klantenservice',
    'Electro World' => 'https://www.electroworld.nl/contact',
    'Bol.com'       => 'https://www.bol.com/nl/c/klantenservice/5858/',
    'Amazon.nl'     => 'https://www.amazon.nl/gp/help/customer/display.html',
    'Wehkamp'       => 'https://www.wehkamp.nl/klantenservice/',
    'HIFI Club'     => 'https://www.hifi.nl/klantenservice',
    'Samsung Store' => 'https://www.samsung.com/nl/support/',
    'LG Store'      => 'https://www.lg.com/nl/support/',
    'Makro'         => 'https://www.makro.nl/klantenservice/',
    'Dixons'        => 'https://www.dixons.nl/klantenservice',
    'Mediaplanet'   => '',
    'Marktplaats'   => 'https://www.marktplaats.nl/over-marktplaats/klantenservice/',
    'Anders'        => '',
];

// Fabrikant supportpagina's — pas aan indien een URL wijzigt
$merkLinks = [
    'Samsung'   => 'https://www.samsung.com/nl/support/',
    'LG'        => 'https://www.lg.com/nl/support/',
    'Philips'   => 'https://www.philips.nl/c-w/support-home/',
    'Sony'      => 'https://www.sony.nl/article/support/',
    'Panasonic' => 'https://www.panasonic.com/nl/consumer/contact-information.html',
    'Hisense'   => 'https://www.hisense.nl/support/',
    'TCL'       => 'https://www.tcl.com/nl/nl/support',
    'Toshiba'   => 'https://www.toshiba.nl/support/',
    'Sharp'     => 'https://www.sharp.nl/klantenservice',
];

$huidigWinkel  = $inzending['winkel_naam'] ?? '';
$huidigMerk    = $inzending['merk'] ?? '';
$modelRep      = $inzending['model_repareerbaar'] ?? '';
$isRepareerbaar = in_array($modelRep, ['1', 'ja', 1, true], true);
$fabrikantLink  = $merkLinks[$huidigMerk] ?? '';
?>
<div class="portal-action-card card-warning">
  <div class="portal-action-header">
    <div class="portal-action-icon icon-warning">🤝</div>
    <div>
      <h3>Coulancetraject</h3>
      <p>Vul de gegevens in en volg de stappen om uw coulanceverzoek te verwerken.</p>
    </div>
  </div>

  <form class="portal-form" method="POST" action="<?= BASE_URL ?>/api/aanvulling.php"
        id="coulance-form"
        data-shop-links="<?= htmlspecialchars(json_encode($shopLinks), ENT_QUOTES, 'UTF-8') ?>"
        data-merk-link="<?= htmlspecialchars($fabrikantLink, ENT_QUOTES, 'UTF-8') ?>"
        data-merk-naam="<?= htmlspecialchars($huidigMerk, ENT_QUOTES, 'UTF-8') ?>"
        data-repareerbaar="<?= $isRepareerbaar ? '1' : '0' ?>">
    <input type="hidden" name="csrf_token"  value="<?= csrf() ?>" />
    <input type="hidden" name="aanvraag_id" value="<?= (int)$inzending['id'] ?>" />
    <input type="hidden" name="casenummer"  value="<?= htmlspecialchars($inzending['casenummer'], ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="type"        value="coulance" />
    <input type="hidden" name="resultaat"   id="coulance-resultaat" value="" />

    <!-- Stap 1: Aankoopinformatie + winkel -->
    <div id="coulance-stap-1">
      <div class="portal-form-section">Aankoopinformatie</div>
      <div class="portal-field">
        <label>Exacte verkoopprijs *</label>
        <input type="text" name="verkoopprijs" required placeholder="Bijv. 499,00"
               value="<?= htmlspecialchars($inzending['verkoopprijs'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
      </div>

      <div class="portal-form-section">Winkel</div>
      <div class="portal-field">
        <label>Waar heeft u de TV gekocht? *</label>
        <select name="winkel_naam" id="coulance-winkel-select" required>
          <option value="">— Selecteer winkel —</option>
          <?php foreach ($coulanceShops as $shop): ?>
            <option value="<?= htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $huidigWinkel === $shop ? 'selected' : '' ?>>
              <?= htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
          <option value="Anders"
                  <?= ($huidigWinkel && !in_array($huidigWinkel, $coulanceShops)) ? 'selected' : '' ?>>
            Anders
          </option>
        </select>
      </div>

      <button type="button" class="portal-submit-btn btn-warning" onclick="coulanceNaarStap2()">
        Volgende &rarr;
      </button>
    </div><!-- /#coulance-stap-1 -->

    <!-- Stap 2: Resultaat bij de winkel -->
    <div id="coulance-stap-2" style="display:none;">
      <div class="portal-form-section">Resultaat bij de winkel</div>

      <!-- Winkellink (dynamisch ingevuld via JS) -->
      <div id="winkel-link-banner" style="display:none;margin-bottom:1.25rem;">
        <div class="portal-info-banner" style="background:#eff6ff;border-left:4px solid #2563eb;color:#1e40af;">
          <strong>Neem contact op met de winkel:</strong><br>
          <a id="winkel-link-knop" href="#" target="_blank" rel="noopener"
             style="display:inline-block;margin-top:.5rem;padding:.4rem 1rem;background:#2563eb;color:#fff;border-radius:6px;font-weight:700;font-size:.85rem;text-decoration:none;">
            Naar de klantenservice &rarr;
          </a>
        </div>
      </div>

      <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
        Heeft u contact opgenomen met de winkel en is er een coulanceoplossing bereikt?
      </p>
      <div class="portal-radio-group" style="margin-bottom:1.25rem;">
        <label class="portal-radio-label">
          <input type="radio" name="coulance_winkel_resultaat" value="1" id="winkel-ja" />
          Ja, coulance geslaagd
        </label>
        <label class="portal-radio-label">
          <input type="radio" name="coulance_winkel_resultaat" value="0" id="winkel-nee" />
          Nee, coulance niet gelukt
        </label>
      </div>
      <div id="winkel-ja-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#f0fdf4;border-left:4px solid #16a34a;color:#166534;">
          &#10003; De coulance is geslaagd. U kunt de aanvraag nu afsluiten.
        </div>
        <button type="button" class="portal-submit-btn" onclick="coulanceVerzenden('winkel_gelukt')"
                style="background:#16a34a;">
          Aanvraag afsluiten &#10003;
        </button>
      </div>
      <div id="winkel-nee-actie" style="display:none;">
        <p style="font-size:.88rem;color:#92400e;margin-bottom:.75rem;">
          U kunt het coulanceverzoek nog proberen bij de fabrikant.
        </p>
        <button type="button" class="portal-submit-btn btn-warning" onclick="coulanceStap(3)">
          Doorgaan naar fabrikant &rarr;
        </button>
      </div>
    </div><!-- /#coulance-stap-2 -->

    <!-- Stap 3: Resultaat bij de fabrikant -->
    <div id="coulance-stap-3" style="display:none;">
      <div class="portal-form-section">Resultaat bij de fabrikant</div>

      <!-- Fabrikantlink -->
      <?php if ($fabrikantLink || $huidigMerk): ?>
      <div style="margin-bottom:1.25rem;">
        <div class="portal-info-banner" style="background:#eff6ff;border-left:4px solid #2563eb;color:#1e40af;">
          <strong>Neem contact op met <?= htmlspecialchars($huidigMerk ?: 'de fabrikant', ENT_QUOTES, 'UTF-8') ?>:</strong><br>
          <?php if ($fabrikantLink): ?>
          <a href="<?= htmlspecialchars($fabrikantLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"
             style="display:inline-block;margin-top:.5rem;padding:.4rem 1rem;background:#2563eb;color:#fff;border-radius:6px;font-weight:700;font-size:.85rem;text-decoration:none;">
            Naar <?= htmlspecialchars($huidigMerk ?: 'fabrikant', ENT_QUOTES, 'UTF-8') ?> support &rarr;
          </a>
          <?php else: ?>
          <span style="display:block;margin-top:.4rem;font-size:.85rem;">
            Zoek de supportpagina van <?= htmlspecialchars($huidigMerk ?: 'de fabrikant', ENT_QUOTES, 'UTF-8') ?> op via hun website.
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <p style="font-size:.9rem;color:#374151;margin-bottom:1rem;line-height:1.65;">
        Is het bij de fabrikant gelukt om een coulanceoplossing te bereiken?
      </p>
      <div class="portal-radio-group" style="margin-bottom:1.25rem;">
        <label class="portal-radio-label">
          <input type="radio" name="coulance_fabrikant_resultaat" value="1" id="fabrikant-ja" />
          Ja, coulance geslaagd
        </label>
        <label class="portal-radio-label">
          <input type="radio" name="coulance_fabrikant_resultaat" value="0" id="fabrikant-nee" />
          Nee, coulance niet gelukt
        </label>
      </div>
      <div id="fabrikant-ja-actie" style="display:none;">
        <div class="portal-info-banner" style="background:#f0fdf4;border-left:4px solid #16a34a;color:#166534;">
          &#10003; De coulance is geslaagd. U kunt de aanvraag nu afsluiten.
        </div>
        <button type="button" class="portal-submit-btn" onclick="coulanceVerzenden('fabrikant_gelukt')"
                style="background:#16a34a;">
          Aanvraag afsluiten &#10003;
        </button>
      </div>
      <div id="fabrikant-nee-actie" style="display:none;">

        <!-- Model is NIET repareerbaar -->
        <div id="coulance-niet-repareerbaar" style="display:none;">
          <div class="portal-info-banner" style="background:#fef2f2;border-left:4px solid #dc2626;color:#991b1b;margin-bottom:.75rem;">
            &#9888; Reparatie van uw televisie is helaas niet mogelijk via ons platform.
            Een gespecialiseerde reparateur in uw regio kan wellicht wel helpen.
          </div>
          <button type="button" class="portal-submit-btn"
                  onclick="coulanceVerzenden('afsluiten')"
                  style="background:#475569;">
            Inzending afsluiten
          </button>
        </div>

        <!-- Model IS repareerbaar: vraag of reparatieadvies gewenst is -->
        <div id="coulance-reparatie-aanbod" style="display:none;">
          <div class="portal-info-banner" style="background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;margin-bottom:.75rem;">
            Helaas is coulance niet gelukt. Wilt u een reparatieaanvraag starten?
          </div>
          <div class="portal-radio-group" style="margin-bottom:1rem;">
            <label class="portal-radio-label">
              <input type="radio" name="reparatie_aanbod" value="ja" id="reparatie-ja" />
              Ja, reparatieaanvraag starten
            </label>
            <label class="portal-radio-label">
              <input type="radio" name="reparatie_aanbod" value="nee" id="reparatie-nee" />
              Nee, inzending afsluiten
            </label>
          </div>
          <div id="reparatie-ja-actie" style="display:none;">
            <button type="button" class="portal-submit-btn btn-warning"
                    onclick="coulanceVerzenden('reparatie_starten')">
              Reparatieaanvraag starten &rarr;
            </button>
          </div>
          <div id="reparatie-nee-actie" style="display:none;">
            <button type="button" class="portal-submit-btn"
                    onclick="coulanceVerzenden('afsluiten')"
                    style="background:#475569;">
              Inzending afsluiten
            </button>
          </div>
        </div>

      </div>
    </div><!-- /#coulance-stap-3 -->
  </form>
</div>

<script>
(function () {
  var form         = document.getElementById('coulance-form');
  var shopLinks    = JSON.parse(form.dataset.shopLinks || '{}');
  var repareerbaar = form.dataset.repareerbaar === '1';

  function coulanceStap(nr) {
    [1, 2, 3].forEach(function (n) {
      document.getElementById('coulance-stap-' + n).style.display = (n === nr) ? '' : 'none';
    });
  }

  function coulanceNaarStap2() {
    var winkel = document.getElementById('coulance-winkel-select').value;
    var url    = shopLinks[winkel] || '';
    var banner = document.getElementById('winkel-link-banner');
    var knop   = document.getElementById('winkel-link-knop');
    if (url) {
      knop.href        = url;
      knop.textContent = 'Naar de klantenservice van ' + winkel + ' →';
      banner.style.display = '';
    } else {
      banner.style.display = 'none';
    }
    coulanceStap(2);
  }

  document.getElementById('winkel-ja').addEventListener('change', function () {
    document.getElementById('winkel-ja-actie').style.display  = '';
    document.getElementById('winkel-nee-actie').style.display = 'none';
  });
  document.getElementById('winkel-nee').addEventListener('change', function () {
    document.getElementById('winkel-ja-actie').style.display  = 'none';
    document.getElementById('winkel-nee-actie').style.display = '';
  });
  document.getElementById('fabrikant-ja').addEventListener('change', function () {
    document.getElementById('fabrikant-ja-actie').style.display  = '';
    document.getElementById('fabrikant-nee-actie').style.display = 'none';
  });
  document.getElementById('fabrikant-nee').addEventListener('change', function () {
    document.getElementById('fabrikant-ja-actie').style.display  = 'none';
    document.getElementById('fabrikant-nee-actie').style.display = '';
    if (repareerbaar) {
      document.getElementById('coulance-niet-repareerbaar').style.display = 'none';
      document.getElementById('coulance-reparatie-aanbod').style.display  = '';
    } else {
      document.getElementById('coulance-niet-repareerbaar').style.display = '';
      document.getElementById('coulance-reparatie-aanbod').style.display  = 'none';
    }
  });
  document.getElementById('reparatie-ja').addEventListener('change', function () {
    document.getElementById('reparatie-ja-actie').style.display  = '';
    document.getElementById('reparatie-nee-actie').style.display = 'none';
  });
  document.getElementById('reparatie-nee').addEventListener('change', function () {
    document.getElementById('reparatie-ja-actie').style.display  = 'none';
    document.getElementById('reparatie-nee-actie').style.display = '';
  });

  window.coulanceStap      = coulanceStap;
  window.coulanceNaarStap2 = coulanceNaarStap2;
  window.coulanceVerzenden = function (resultaat) {
    document.getElementById('coulance-resultaat').value = resultaat;
    document.getElementById('coulance-form').submit();
  };
}());
</script>
