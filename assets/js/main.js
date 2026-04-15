// ── Mobile nav toggle ────────────────────────────────
(function () {
  const toggle = document.getElementById('navToggle');
  const menu   = document.getElementById('navMenu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => menu.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!toggle.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('open');
      }
    });
  }

  // ── FAQ accordion ────────────────────────────────
  document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
      const answer = q.nextElementSibling;
      const isOpen = q.classList.contains('open');
      document.querySelectorAll('.faq-q').forEach(x => {
        x.classList.remove('open');
        if (x.nextElementSibling) x.nextElementSibling.style.display = 'none';
      });
      if (!isOpen) {
        q.classList.add('open');
        answer.style.display = 'block';
      }
    });
  });
})();

// ── Live zoekfunctie ─────────────────────────────────
function initZoek() {
  const input       = document.getElementById('zoekInput');
  const acBox       = document.getElementById('autocomplete-box');
  const liveResults = document.getElementById('live-results');
  const clearBtn    = document.getElementById('zoekClear');

  if (!input || !acBox) return;

  if (typeof BASE_URL === 'undefined') {
    console.error('BASE_URL is niet gedefinieerd!');
    return;
  }

  let timer     = null;
  let lastQuery = '';
  let activeIdx = -1;

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function highlight(text, q) {
    const escaped = esc(text);
    if (!q || q.length < 2) return escaped;
    const safe = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escaped.replace(new RegExp('(' + safe + ')', 'gi'), '<mark class="ac-mark">$1</mark>');
  }

  function badgeStyle(type) {
    const map = {
      'LED':      'background:#eff6ff;color:#1d4ed8',
      'OLED':     'background:#f5f3ff;color:#6d28d9',
      'QLED':     'background:#fff7ed;color:#c2410c',
      'Neo QLED': 'background:#fef9c3;color:#854d0e',
      'NanoCell': 'background:#f0fdf4;color:#15803d',
      'QNED':     'background:#ecfdf5;color:#047857',
      'QD-OLED':  'background:#fdf4ff;color:#7e22ce',
    };
    return map[type] || 'background:#f1f5f9;color:#475569';
  }

  function renderDropdown(results, q) {
    activeIdx = -1;
    if (!results.length) {
      acBox.innerHTML = `
        <div class="ac-not-found">
          <p>Geen resultaten voor <strong>${esc(q)}</strong></p>
          <a href="${BASE_URL}/#advies">Vraag gratis advies aan &rarr;</a>
        </div>`;
    } else {
      acBox.innerHTML = results.slice(0, 7).map((r, i) => `
        <div class="ac-item" data-slug="${esc(r.slug)}" data-i="${i}">
          <div class="ac-left">
            <div class="ac-model">${highlight(r.modelnummer, q)}</div>
            <div class="ac-sub">${esc(r.merk)} &mdash; ${esc(r.serie)}</div>
          </div>
          <span class="ac-badge" style="${badgeStyle(r.schermtype)}">${esc(r.schermtype)}</span>
        </div>`
      ).join('');

      acBox.querySelectorAll('.ac-item').forEach(el => {
        el.addEventListener('mouseenter', () => {
          activeIdx = parseInt(el.dataset.i);
          updateActive();
        });
        el.addEventListener('mousedown', e => {
          e.preventDefault();
          window.location.href = BASE_URL + '/tv/' + el.dataset.slug;
        });
      });
    }
    acBox.style.display = 'block';
  }

  function renderFullResults(results, q) {
    if (!liveResults) return;

    // Verberg standaard secties tijdens zoeken
    document.querySelectorAll('.db-section').forEach(el => el.style.display = 'none');

    if (!results.length) {
      liveResults.innerHTML = `
        <div class="not-found-block">
          <div class="not-found-icon">&#128250;</div>
          <h2>Geen resultaten voor &ldquo;${esc(q)}&rdquo;</h2>
          <p>Staat jouw televisie er niet bij? Vraag toch gratis advies aan.</p>
          <div class="not-found-actions">
            <a href="${BASE_URL}/#advies" class="btn-primary">Gratis advies aanvragen &rarr;</a>
            <a href="${BASE_URL}/database.php" class="btn-ghost">Terug naar database</a>
          </div>
          <div class="not-found-hint">
            &#128161; Controleer het modelnummer achter op de televisie of via
            <strong>Instellingen &rarr; Ondersteuning &rarr; Apparaatinformatie</strong>.
          </div>
        </div>`;
      liveResults.style.display = 'block';
      return;
    }

    const rows = results.map(r => `
      <tr onclick="location.href='${BASE_URL}/tv/${esc(r.slug)}'" style="cursor:pointer;">
        <td>
          <div class="td-model">${highlight(r.modelnummer, q)}</div>
          <div class="td-sub">${esc(r.merk)}</div>
        </td>
        <td>${esc(r.serie)}</td>
        <td><span class="td-link">Bekijk &rarr;</span></td>
      </tr>`
    ).join('');

    liveResults.innerHTML = `
      <p class="results-count">
        <strong>${results.length}</strong>
        televisie${results.length !== 1 ? 's' : ''} gevonden voor &ldquo;${esc(q)}&rdquo;
      </p>
      <div class="tv-table-wrap">
        <table>
          <thead><tr><th>Model</th><th>Serie</th><th></th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
    liveResults.style.display = 'block';
  }

  function hideAll() {
    acBox.style.display = 'none';
    if (liveResults) {
      liveResults.innerHTML = '';
      liveResults.style.display = 'none';
    }
    // Toon standaard secties weer
    document.querySelectorAll('.db-section').forEach(el => el.style.display = '');
  }

  function updateActive() {
    acBox.querySelectorAll('.ac-item').forEach((el, i) => {
      el.classList.toggle('active', i === activeIdx);
      if (i === activeIdx) el.scrollIntoView({ block: 'nearest' });
    });
  }

  function doSearch(q) {
    if (q === lastQuery) return;
    lastQuery = q;
    fetch(BASE_URL + '/api/autocomplete.php?q=' + encodeURIComponent(q))
      .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(data => { renderDropdown(data, q); renderFullResults(data, q); })
      .catch(err => { console.error('Zoekfout:', err); hideAll(); });
  }

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (clearBtn) clearBtn.style.display = q ? 'flex' : 'none';
    if (q.length < 2) { hideAll(); lastQuery = ''; return; }
    timer = setTimeout(() => doSearch(q), 200);
  });

  input.addEventListener('keydown', e => {
    const items = acBox.querySelectorAll('.ac-item');
    if (e.key === 'ArrowDown')    { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); updateActive(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); updateActive(); }
    else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) { e.preventDefault(); window.location.href = BASE_URL + '/tv/' + items[activeIdx].dataset.slug; }
    else if (e.key === 'Escape')  { acBox.style.display = 'none'; }
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      input.value = ''; clearBtn.style.display = 'none';
      hideAll(); lastQuery = ''; input.focus();
    });
  }

  document.addEventListener('click', e => {
    const wrap = document.querySelector('.hero-search-wrap');
    if (wrap && !wrap.contains(e.target)) acBox.style.display = 'none';
  });

  if (input.value.trim().length >= 2) doSearch(input.value.trim());
}

// Werkt altijd — of DOM al klaar is of nog niet
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initZoek);
} else {
  initZoek();
}