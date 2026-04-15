(function () {
  const REPAREERBARE_MERKEN = ['Philips', 'Samsung', 'LG', 'Sony'];
  const merkRepareerbaar    = REPAREERBARE_MERKEN.includes(WIZ_MERK);
  let wizKeuze = null;

  /* ── Navigatie ─────────────────────────────────────────────── */
  window.wizardNext = function (stap) {
    clearError(stap);
    if (stap === 1) {
      const m = document.getElementById('wizMaand').value;
      const j = document.getElementById('wizJaar').value;
      if (!m || !j) { showError(1, 'Selecteer een maand en jaar.'); return; }
      goTo(2);
    } else if (stap === 2) {
      const p = document.getElementById('wizPrijs').value;
      if (p === '' || isNaN(p) || Number(p) < 0) {
        showError(2, 'Vul een geldige aanschafprijs in (€\u00a00 als onbekend).');
        return;
      }
      goTo(3);
    }
  };

  window.wizardBack   = (stap) => goTo(stap - 1);
  window.wizardOpnieuw = function () {
    document.getElementById('wizMaand').value = '';
    document.getElementById('wizJaar').value  = '';
    document.getElementById('wizPrijs').value = '';
    wizKeuze = null;
    document.querySelectorAll('.wizard-keuze').forEach(b => b.classList.remove('selected'));
    goTo(1);
  };

  window.wizardKeuze = function (btn) {
    document.querySelectorAll('.wizard-keuze').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    wizKeuze = btn.dataset.value;
    clearError(3);
    setTimeout(berekenUitkomst, 320);
  };

  function goTo(stap) {
    document.querySelectorAll('.wizard-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.wizard-step').forEach(s => {
      const n = parseInt(s.dataset.step);
      s.classList.toggle('active', n === stap);
      s.classList.toggle('done',   n < stap);
    });
    const pane = stap === 4
      ? document.getElementById('wizResult')
      : document.getElementById('wizStep' + stap);
    if (pane) pane.classList.add('active');
    document.getElementById('wizardCard')
            .scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function showError(stap, msg) {
    const el = document.getElementById('wizErr' + stap);
    if (el) { el.textContent = msg; el.style.display = 'block'; }
  }
  function clearError(stap) {
    const el = document.getElementById('wizErr' + stap);
    if (el) { el.textContent = ''; el.style.display = 'none'; }
  }

  /* ── Berekening ─────────────────────────────────────────────── */
  function berekenUitkomst() {
    const maand = parseInt(document.getElementById('wizMaand').value);
    const jaar  = parseInt(document.getElementById('wizJaar').value);
    const prijs = parseFloat(document.getElementById('wizPrijs').value) || 0;

    const nu          = new Date();
    const aankoopDate = new Date(jaar, maand - 1, 1);
    const maandenOud  = (nu.getFullYear() - aankoopDate.getFullYear()) * 12
                      + (nu.getMonth()    - aankoopDate.getMonth());
    const jarenOud    = maandenOud / 12;

    const coulanceJaren = prijs <= 300  ? 3
                        : prijs <= 500  ? 4
                        : prijs <= 1000 ? 5
                        : 6;

    const binnenGarantie = jarenOud < 2;
    const binnenCoulance = jarenOud >= 2 && jarenOud < coulanceJaren;

    let uitkomst;
    if      (wizKeuze === 'schade')  uitkomst = 'taxatie';
    else if (binnenGarantie)         uitkomst = 'garantie';
    else if (binnenCoulance)         uitkomst = 'coulance';
    else if (merkRepareerbaar)       uitkomst = 'reparatie';
    else                             uitkomst = 'advies';

    toonResultaat(uitkomst, { jarenOud, maandenOud, coulanceJaren, prijs, jaar });
  }

  /* ── Resultaat ──────────────────────────────────────────────── */
  function toonResultaat(uitkomst, d) {
    const leeftijdTekst = d.maandenOud < 24
      ? `${d.maandenOud} maanden`
      : `${(Math.floor(d.jarenOud * 10) / 10).toLocaleString('nl-NL')} jaar`;
    const prijsTekst = d.prijs > 0
      ? `€${d.prijs.toLocaleString('nl-NL')}`
      : 'onbekende aanschafprijs';

    const map = {
      garantie: {
        cls:  'result-garantie',
        icon: '&#128737;',
        titel:'Jouw televisie valt waarschijnlijk nog onder garantie',
        tekst:`Je hebt de televisie <strong>${leeftijdTekst}</strong> geleden gekocht — binnen de wettelijke garantieperiode van 2 jaar. Bij een defect heb je recht op gratis herstel of vervanging via de verkoper.`,
        tip:  '&#128161; Neem contact op met de winkel waar je de televisie hebt gekocht. Zij zijn wettelijk verplicht het defect kosteloos op te lossen.',
        cta:  'Gratis garantie-advies aanvragen &rarr;',
      },
      coulance: {
        cls:  'result-coulance',
        icon: '&#9200;',
        titel:'Je hebt mogelijk recht op coulance',
        tekst:`Je hebt de televisie <strong>${leeftijdTekst}</strong> geleden gekocht voor ${prijsTekst}. De garantie is verlopen, maar voor deze prijsklasse geldt een coulanceperiode van <strong>${d.coulanceJaren} jaar</strong> (t/m ${d.jaar + d.coulanceJaren}).`,
        tip:  '&#128161; Doet de verkoper niet aan coulance? Dan kom je alsnog in aanmerking voor reparatie via Reparatieplatform.',
        cta:  'Vraag coulance-advies aan &rarr;',
      },
      reparatie: {
        cls:  'result-reparatie',
        icon: '&#128295;',
        titel:'Jouw televisie komt in aanmerking voor reparatie',
        tekst:`Je hebt de televisie <strong>${leeftijdTekst}</strong> geleden gekocht — buiten garantie en coulance. Reparatieplatform repareert ${WIZ_MERK}-televisies bij u thuis. Onze specialist beoordeelt vrijblijvend of reparatie de beste optie is.`,
        tip:  null,
        cta:  'Reparatie aanvragen &rarr;',
      },
      taxatie: {
        cls:  'result-taxatie',
        icon: '&#128203;',
        titel:'Taxatierapport voor je verzekering',
        tekst:`Bij schade door een val of stoot heb je mogelijk recht op vergoeding via je verzekering. Reparatieplatform stelt officiële taxatierapporten op die door alle Nederlandse verzekeraars worden geaccepteerd.`,
        tip:  '&#128161; Controleer je polis op dekking voor onbedoelde schade aan elektronica (allrisk of inboedel).',
        cta:  'Taxatierapport aanvragen &rarr;',
      },
      advies: {
        cls:  'result-advies',
        icon: '&#128172;',
        titel:'Vraag gratis advies aan',
        tekst:`${WIZ_MERK}-televisies worden door Reparatieplatform niet gerepareerd, maar wij helpen je verder met advies over garantie, coulance of een <strong>officieel taxatierapport</strong> voor je verzekeraar.`,
        tip:  null,
        cta:  'Gratis advies aanvragen &rarr;',
      },
    };

    const t = map[uitkomst];
    document.getElementById('wizResult').innerHTML = `
      <div class="wizard-result ${t.cls}">
        <div class="wizard-result-top">
          <span class="wizard-result-icoon">${t.icon}</span>
          <h3 class="wizard-result-titel">${t.titel}</h3>
        </div>
        <p class="wizard-result-tekst">${t.tekst}</p>
        ${t.tip ? `<div class="wizard-result-tip">${t.tip}</div>` : ''}
        <div class="wizard-result-actions">
          <a href="${WIZ_BASE}/#advies" class="btn-primary">${t.cta}</a>
          <button class="wizard-opnieuw" onclick="wizardOpnieuw()">&#8635; Opnieuw</button>
        </div>
      </div>`;
    goTo(4);
  }

  /* ── FAQ accordion ──────────────────────────────────────────── */
  document.querySelectorAll('.faq-fancy-q').forEach(btn => {
    btn.addEventListener('click', () => {
      const isOpen = btn.classList.contains('open');
      document.querySelectorAll('.faq-fancy-q.open').forEach(b => {
        b.classList.remove('open');
        b.nextElementSibling.style.display = 'none';
      });
      if (!isOpen) {
        btn.classList.add('open');
        btn.nextElementSibling.style.display = 'block';
      }
    });
  });

})();