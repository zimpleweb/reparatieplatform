<?php
/**
 * Admin Header Component — admin/includes/admin-header.php
 * Strakke witte topbar. Stijl volledig via assets/css/admin.css.
 */

if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$adminUsername   = $_SESSION['admin_username'] ?? 'Admin';
$adminActivePage = $adminActivePage ?? '';

// Ongelezen meldingen badge
$_adminOngelezen = 0;
try {
    $_adminOngelezen = (int) db()->query(
        'SELECT COUNT(*) FROM aanvragen_log WHERE gelezen=0 AND gearchiveerd=0'
    )->fetchColumn();
} catch (\Exception $e) {}

$_adminNav = [
    ['id' => 'dashboard',           'label' => 'Dashboard',      'href' => BASE_URL . '/admin/dashboard.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>'],
    ['id' => 'aanvragen',           'label' => 'Inzendingen',    'href' => BASE_URL . '/admin/aanvragen.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>'],
    ['id' => 'meldingen',           'label' => 'Meldingen',      'href' => BASE_URL . '/admin/meldingen.php',     'badge' => $_adminOngelezen,
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>'],
    ['id' => 'modellen',            'label' => 'TV-modellen',    'href' => BASE_URL . '/admin/modellen.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17,2 12,7 7,2"/></svg>'],
    ['id' => 'klachten',            'label' => 'Klachten',       'href' => BASE_URL . '/admin/klachten.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'],
    ['id' => 'advies-instellingen', 'label' => 'Adviesregels',   'href' => BASE_URL . '/admin/advies-instellingen.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07M8.46 8.46a5 5 0 0 0 0 7.07"/></svg>'],
    ['id' => 'coulance-regels',     'label' => 'Coulance Regels','href' => BASE_URL . '/admin/coulance-regels.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>'],
    ['id' => 'mailtemplates',       'label' => 'Mailtemplates',  'href' => BASE_URL . '/admin/mailtemplates.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'],
    ['id' => 'admins',              'label' => 'Beheerders',     'href' => BASE_URL . '/admin/admins.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    ['id' => 'instellingen',        'label' => 'Instellingen',   'href' => BASE_URL . '/admin/instellingen.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'],
];

// Initialen voor avatar
$_initials = strtoupper(substr($adminUsername, 0, 1));
if (strpos($adminUsername, ' ') !== false) {
    $parts = explode(' ', $adminUsername, 2);
    $_initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}
?>
<header class="adm-header" role="banner">
  <div class="adm-header-inner">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="adm-logo" aria-label="Reparatieplatform Admin">
      <img src="https://reparatieplatform.nl/wp-content/uploads/2025/06/REPARATIEPLATFORM-LOGO-WEBSITE-1200x336.png"
           alt="Reparatieplatform" height="32" style="max-width:200px;object-fit:contain;display:block;">
    </a>

    <!-- Navigatie -->
    <nav class="adm-nav" role="navigation" aria-label="Admin navigatie">
      <?php foreach ($_adminNav as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="adm-nav-link<?= $adminActivePage === $item['id'] ? ' is-active' : '' ?>"
           <?= $adminActivePage === $item['id'] ? 'aria-current="page"' : '' ?>>
          <span class="adm-nav-icon" aria-hidden="true"><?= $item['icon'] ?></span>
          <?= htmlspecialchars($item['label']) ?>
          <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
            <span class="adm-nav-badge" aria-label="<?= (int)$item['badge'] ?> ongelezen"><?= (int)$item['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Rechts: account + uitloggen -->
    <div class="adm-header-actions">
      <a href="<?= BASE_URL ?>/admin/account-instellingen.php"
         class="adm-account-link<?= $adminActivePage === 'account-instellingen' ? ' is-active' : '' ?>"
         title="Account instellingen">
        <span class="adm-account-avatar" aria-hidden="true"><?= $_initials ?></span>
        <span class="adm-account-name adm-action-label"><?= htmlspecialchars($adminUsername) ?></span>
      </a>
      <div class="adm-header-divider" aria-hidden="true"></div>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="adm-logout-link" title="Uitloggen">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="adm-action-label">Uitloggen</span>
      </a>
    </div>

    <!-- Hamburger (mobiel) -->
    <button class="adm-hamburger" id="admHamburger" aria-label="Menu openen" aria-expanded="false" aria-controls="admNav">
      <span></span><span></span><span></span>
    </button>

  </div>
  <style>

    /* ================================================================
   admin.css – Reparatieplatform Admin UI
   Eén consistente lichte stijl voor alle admin pagina's.
   Accent: #01696f (teal). Achtergrond: warm wit (#f5f4f1).
   Font: Inter + Epilogue via Google Fonts.
   ================================================================ */

/* ─────────────────────────────────────────────────────────────────
   CSS DESIGN TOKENS — één plek, alle pagina's
   ───────────────────────────────────────────────────────────────── */
:root {
  /* Kleurenpalet */
  --adm-bg:           #f5f4f1;       /* Paginaachtergrond (warm wit) */
  --adm-surface:      #ffffff;       /* Kaarten, inputs */
  --adm-surface-2:    #fafaf8;       /* Iets dieper oppervlak */
  --adm-border:       #e5e4e0;       /* Standaard rand */
  --adm-border-light: rgba(0,0,0,.06);

  /* Tekst */
  --adm-ink:          #0d0f14;       /* Primaire tekst */
  --adm-text:         #374151;       /* Secondaire tekst */
  --adm-muted:        #6b7280;       /* Gedempte tekst */
  --adm-faint:        #9ca3af;       /* Zeer gedempte tekst */

  /* Header (wit/licht) */
  --adm-h:            58px;
  --adm-header-bg:    #ffffff;
  --adm-header-border: rgba(0,0,0,.08);
  --adm-header-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 0 rgba(0,0,0,.04);

  /* Navigatie */
  --adm-nav-text:     #374151;
  --adm-nav-muted:    #6b7280;
  --adm-nav-hover:    #f3f4f6;
  --adm-nav-active-bg: #eef7f6;
  --adm-nav-active-txt: #01696f;

  /* Accent */
  --adm-accent:       #01696f;
  --adm-accent-hover: #015b61;
  --adm-accent-light: #eef7f6;
  --adm-accent-ring:  rgba(1,105,111,.10);

  /* Radius */
  --adm-radius:       8px;
  --adm-radius-sm:    6px;
  --adm-radius-lg:    12px;
  --adm-radius-xl:    14px;

  /* Badge */
  --adm-badge-bg:     #ef4444;
  --adm-badge-txt:    #ffffff;

  /* Shadows */
  --adm-shadow-sm:    0 1px 3px rgba(0,0,0,.05), 0 1px 0 rgba(0,0,0,.03);
  --adm-shadow-md:    0 4px 12px rgba(0,0,0,.08);

  /* Font */
  --adm-font:         'Inter', system-ui, sans-serif;
  --adm-font-display: 'Epilogue', 'Inter', sans-serif;
}

/* ─────────────────────────────────────────────────────────────────
   BASE RESET
   ───────────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

html {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  font-family: var(--adm-font);
  background: var(--adm-bg);
  color: var(--adm-ink);
  min-height: 100vh;
}

/* ─────────────────────────────────────────────────────────────────
   ADMIN HEADER — Witte horizontale topbalk
   ───────────────────────────────────────────────────────────────── */
.adm-header {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--adm-h);
  background: var(--adm-header-bg);
  border-bottom: 1px solid var(--adm-header-border);
  box-shadow: var(--adm-header-shadow);
  z-index: 1000;
}

.adm-header-inner {
  display: flex;
  align-items: center;
  height: 100%;
  padding: 0 1.5rem;
  gap: .5rem;
  max-width: 1600px;
  margin: 0 auto;
}

/* Spacer zodat content niet achter de header verdwijnt */
.adm-header-spacer {
  height: var(--adm-h);
}

/* ── Logo ── */
.adm-logo {
  display: flex;
  align-items: center;
  gap: .5rem;
  text-decoration: none;
  flex-shrink: 0;
  margin-right: .75rem;
}
.adm-logo-icon {
  border-radius: 7px;
  flex-shrink: 0;
}
.adm-logo-text {
  font-family: var(--adm-font-display);
  font-size: .9rem;
  font-weight: 600;
  color: #1f2937;
  letter-spacing: -.01em;
  white-space: nowrap;
}
.adm-logo-text strong {
  font-weight: 800;
  color: var(--adm-accent);
}

/* ── Navigatie ── */
.adm-nav {
  display: flex;
  align-items: center;
  gap: .15rem;
  flex: 1;
  overflow-x: auto;
  scrollbar-width: none;
}
.adm-nav::-webkit-scrollbar { display: none; }

.adm-nav-link {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .42rem .7rem;
  border-radius: var(--adm-radius);
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-nav-muted);
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s, color .15s;
  flex-shrink: 0;
}
.adm-nav-link:hover {
  background: var(--adm-nav-hover);
  color: var(--adm-ink);
}
.adm-nav-link.is-active {
  background: var(--adm-nav-active-bg);
  color: var(--adm-nav-active-txt);
  font-weight: 600;
}
.adm-nav-icon {
  display: flex;
  align-items: center;
  opacity: .7;
  flex-shrink: 0;
  line-height: 1;
}
.adm-nav-link.is-active .adm-nav-icon { opacity: 1; }

/* Nav badge (ongelezen meldingen) */
.adm-nav-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 17px;
  height: 17px;
  padding: 0 4px;
  border-radius: 99px;
  background: var(--adm-badge-bg);
  color: var(--adm-badge-txt);
  font-size: .62rem;
  font-weight: 700;
  line-height: 1;
  margin-left: .15rem;
}

/* ── Rechts: acties + account ── */
.adm-header-actions {
  display: flex;
  align-items: center;
  gap: .2rem;
  flex-shrink: 0;
  margin-left: auto;
}

.adm-action-link,
.adm-logout-link {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .4rem .65rem;
  border-radius: var(--adm-radius);
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-muted);
  text-decoration: none;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.adm-action-link:hover {
  background: var(--adm-nav-hover);
  color: var(--adm-ink);
}
.adm-logout-link:hover {
  background: #fef2f2;
  color: #dc2626;
}

.adm-header-divider {
  width: 1px;
  height: 20px;
  background: var(--adm-header-border);
  margin: 0 .2rem;
  flex-shrink: 0;
}

.adm-account-link {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .3rem .65rem .3rem .4rem;
  border-radius: var(--adm-radius);
  text-decoration: none;
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-text);
  transition: background .15s;
}
.adm-account-link:hover { background: var(--adm-nav-hover); }
.adm-account-link.is-active {
  background: var(--adm-nav-active-bg);
  color: var(--adm-nav-active-txt);
}

.adm-account-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--adm-accent);
  color: #fff;
  font-size: .7rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.adm-account-name {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Hamburger (mobiel) ── */
.adm-hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 36px;
  height: 36px;
  padding: 0 7px;
  background: none;
  border: 1px solid var(--adm-header-border);
  border-radius: 7px;
  cursor: pointer;
  margin-left: auto;
  order: 10;
  flex-shrink: 0;
}
.adm-hamburger span {
  display: block;
  height: 2px;
  background: var(--adm-text);
  border-radius: 2px;
  transition: transform .2s, opacity .2s;
}
.adm-hamburger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.adm-hamburger.is-open span:nth-child(2) { opacity: 0; }
.adm-hamburger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ─────────────────────────────────────────────────────────────────
   PAGINA WRAP
   ───────────────────────────────────────────────────────────────── */
.adm-page {
  padding: 2rem 2.25rem;
  max-width: 1400px;
  margin: 0 auto;
  box-sizing: border-box;
  width: 100%;
}

/* Paginatitel standaard */
.adm-page > h1,
.adm-page-title {
  font-family: var(--adm-font-display);
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--adm-ink);
  margin: 0 0 1.5rem;
  letter-spacing: -.025em;
  line-height: 1.2;
}
.adm-page-subtitle {
  font-size: .875rem;
  color: var(--adm-muted);
  margin: -.75rem 0 1.5rem;
  line-height: 1.55;
}

/* ─────────────────────────────────────────────────────────────────
   KAARTEN
   ───────────────────────────────────────────────────────────────── */
.admin-card {
  background: var(--adm-surface);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-xl);
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: var(--adm-shadow-sm);
}
.admin-card h2 {
  font-size: .95rem;
  font-weight: 700;
  margin-bottom: 1.1rem;
  color: var(--adm-ink);
}

/* Instellingen-kaart stijl (licht, voor settings-pagina's) */
.settings-card {
  background: var(--adm-surface);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-xl);
  padding: 1.75rem 2rem;
  margin-bottom: 1.25rem;
  box-shadow: var(--adm-shadow-sm);
}
.settings-card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--adm-border);
}
.settings-card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--adm-accent-light);
  border: 1px solid rgba(1,105,111,.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--adm-accent);
  font-size: 1.1rem;
}
.settings-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--adm-ink);
  margin: 0 0 .2rem;
  letter-spacing: -.02em;
}
.settings-card-desc {
  font-size: .8rem;
  color: var(--adm-muted);
  margin: 0;
  line-height: 1.6;
}

/* ─────────────────────────────────────────────────────────────────
   PAGE HEADER RIJEN (titel + actie naast elkaar)
   ───────────────────────────────────────────────────────────────── */
.page-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.page-header-row h1 {
  margin: 0;
}

/* ─────────────────────────────────────────────────────────────────
   TABEL
   ───────────────────────────────────────────────────────────────── */
.admin-table {
  width: 100%;
  border-collapse: collapse;
}
.admin-table th {
  padding: .55rem 1rem;
  text-align: left;
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--adm-faint);
  border-bottom: 1.5px solid var(--adm-border);
  white-space: nowrap;
  background: var(--adm-surface);
}
.admin-table td {
  padding: .65rem 1rem;
  font-size: .85rem;
  border-bottom: 1px solid #f3f2ef;
  vertical-align: middle;
  color: var(--adm-ink);
}
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: var(--adm-surface-2); }

/* ─────────────────────────────────────────────────────────────────
   FORMULIERVELDEN
   ───────────────────────────────────────────────────────────────── */
.field,
.s-field { margin-bottom: 1rem; }

.field label,
.s-field label,
.form-admin label {
  display: block;
  font-size: .775rem;
  font-weight: 600;
  color: var(--adm-muted);
  margin-bottom: .3rem;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.field input:not([type="checkbox"]),
.field select,
.field textarea,
.s-field input:not([type="checkbox"]),
.s-field select,
.s-field textarea,
.form-admin input:not([type="checkbox"]),
.form-admin select,
.form-admin textarea {
  width: 100%;
  padding: .6rem .85rem;
  border: 1.5px solid var(--adm-border);
  border-radius: 9px;
  font-size: .875rem;
  font-family: var(--adm-font);
  color: var(--adm-ink);
  background: var(--adm-surface-2);
  transition: border-color .2s, box-shadow .2s;
}
.field input:not([type="checkbox"]):focus,
.field select:focus,
.field textarea:focus,
.s-field input:not([type="checkbox"]):focus,
.s-field select:focus,
.s-field textarea:focus,
.form-admin input:not([type="checkbox"]):focus,
.form-admin select:focus,
.form-admin textarea:focus {
  outline: none;
  border-color: var(--adm-accent);
  background: var(--adm-surface);
  box-shadow: 0 0 0 3px var(--adm-accent-ring);
}

.field textarea,
.s-field textarea,
.form-admin textarea { min-height: 80px; resize: vertical; }

.field input[type="checkbox"],
.form-admin input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  padding: 0;
  border: 1.5px solid var(--adm-border);
  border-radius: 3px;
  background: white;
  accent-color: var(--adm-accent);
  cursor: pointer;
}

.field-hint,
.s-field .hint {
  font-size: .75rem;
  color: var(--adm-muted);
  margin-top: .35rem;
  line-height: 1.5;
}
.field-hint a,
.s-field .hint a {
  color: var(--adm-accent);
  text-decoration: none;
}
.field-hint a:hover,
.s-field .hint a:hover { text-decoration: underline; }

.form-check label {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  cursor: pointer;
  font-size: .875rem;
  font-weight: 500;
  color: var(--adm-ink);
  margin-bottom: 0;
  text-transform: none;
  letter-spacing: 0;
}

/* ─────────────────────────────────────────────────────────────────
   TOGGLE SWITCH
   ───────────────────────────────────────────────────────────────── */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: .75rem 1rem;
  background: var(--adm-surface-2);
  border: 1px solid var(--adm-border-light);
  border-radius: var(--adm-radius);
  margin-bottom: 1rem;
}
.toggle-row-label {
  font-size: .875rem;
  font-weight: 600;
  color: var(--adm-ink);
}
.toggle-row-desc {
  font-size: .75rem;
  color: var(--adm-muted);
  margin-top: .15rem;
}
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--adm-border);
  border-radius: 999px;
  cursor: pointer;
  transition: background .2s;
}
.toggle-slider::before {
  content: '';
  position: absolute;
  left: 3px; top: 3px;
  width: 18px; height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: transform .2s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .toggle-slider { background: var(--adm-accent); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

/* ─────────────────────────────────────────────────────────────────
   GRID HULPKLASSEN
   ───────────────────────────────────────────────────────────────── */
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
.s-row-2    { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

/* ─────────────────────────────────────────────────────────────────
   STATISTIEKEN
   ───────────────────────────────────────────────────────────────── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
  width: 100%;
}
.stat-card {
  background: var(--adm-surface);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-lg);
  padding: 1.1rem 1.25rem;
  box-shadow: var(--adm-shadow-sm);
}
.stat-val {
  font-size: 2rem;
  font-weight: 800;
  color: var(--adm-ink);
  line-height: 1;
  margin-bottom: .4rem;
  font-variant-numeric: tabular-nums;
  font-family: var(--adm-font-display);
}
.stat-label {
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-muted);
  text-transform: uppercase;
  letter-spacing: .04em;
}
.stat-icon { display: none; }

/* ─────────────────────────────────────────────────────────────────
   RECENTE AANVRAGEN GRID (dashboard)
   ───────────────────────────────────────────────────────────────── */
.recent-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-top: 1.25rem;
  width: 100%;
}
.recent-card {
  background: var(--adm-surface);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-lg);
  padding: 1rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: .35rem;
  transition: box-shadow .15s, transform .15s;
  text-decoration: none;
  color: inherit;
}
.recent-card:hover {
  box-shadow: var(--adm-shadow-md);
  transform: translateY(-2px);
}
.recent-card-merk {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--adm-accent);
}
.recent-card-model {
  font-size: .875rem;
  font-weight: 700;
  color: var(--adm-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.recent-card-email {
  font-size: .775rem;
  color: var(--adm-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.recent-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: .3rem;
  gap: .4rem;
  flex-wrap: wrap;
}
.recent-card-datum { font-size: .72rem; color: var(--adm-faint); }
.recent-card-route {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  padding: .15rem .45rem;
  border-radius: 4px;
}

/* ─────────────────────────────────────────────────────────────────
   BUTTONS
   ───────────────────────────────────────────────────────────────── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .52rem 1.1rem;
  border-radius: 9px;
  font-size: .845rem;
  font-weight: 600;
  font-family: var(--adm-font);
  cursor: pointer;
  border: 1.5px solid transparent;
  transition: background .18s, border-color .18s, color .18s;
  text-decoration: none;
  white-space: nowrap;
}
.btn-primary        { background: var(--adm-accent); color: #fff; }
.btn-primary:hover  { background: var(--adm-accent-hover); }
.btn-secondary      { background: var(--adm-surface); color: var(--adm-ink); border-color: var(--adm-border); }
.btn-secondary:hover{ background: var(--adm-bg); border-color: #ccc; }
.btn-danger         { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
.btn-danger:hover   { background: #fee2e2; }
.btn-sm             { padding: .35rem .75rem; font-size: .78rem; border-radius: 7px; }

/* btn-save alias voor instellingen-pagina's */
.btn-save {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: var(--adm-accent);
  color: #fff;
  border: none;
  border-radius: var(--adm-radius);
  padding: .65rem 1.4rem;
  font-size: .875rem;
  font-weight: 700;
  cursor: pointer;
  font-family: var(--adm-font);
  transition: background .15s, transform .1s;
  letter-spacing: -.01em;
}
.btn-save:hover  { background: var(--adm-accent-hover); }
.btn-save:active { transform: scale(.98); }

/* Kleine primaire button */
.btn-primary-sm {
  background: var(--adm-accent);
  color: #fff;
  padding: .38rem .85rem;
  font-size: .8rem;
  border-radius: var(--adm-radius);
  font-weight: 600;
  border: none;
  cursor: pointer;
  font-family: var(--adm-font);
  transition: background .15s;
}
.btn-primary-sm:hover { background: var(--adm-accent-hover); }

/* ─────────────────────────────────────────────────────────────────
   ALERTS
   ───────────────────────────────────────────────────────────────── */
.alert {
  padding: .8rem 1.1rem;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 500;
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  margin-bottom: 1rem;
}
.alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-info    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.alert-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }

/* ─────────────────────────────────────────────────────────────────
   BADGE / CHIP
   ───────────────────────────────────────────────────────────────── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  font-size: .7rem;
  font-weight: 700;
  padding: .18rem .55rem;
  border-radius: 99px;
}
.badge-green  { background: #dcfce7; color: #166534; }
.badge-blue   { background: #dbeafe; color: #1e40af; }
.badge-yellow { background: #fef9c3; color: #854d0e; }
.badge-orange { background: #fef3c7; color: #92400e; }
.badge-purple { background: #ede9fe; color: #5b21b6; }
.badge-gray   { background: #f1f5f9; color: #64748b; }
.badge-red    { background: #fef2f2; color: #991b1b; }

/* ─────────────────────────────────────────────────────────────────
   INFO BOX (settings-pagina's)
   ───────────────────────────────────────────────────────────────── */
.info-box {
  background: var(--adm-accent-light);
  border: 1px solid rgba(1,105,111,.2);
  border-radius: var(--adm-radius);
  padding: 1rem 1.25rem;
  font-size: .8rem;
  color: var(--adm-text);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}
.info-box strong { color: var(--adm-accent); }
.info-box code {
  background: rgba(1,105,111,.08);
  border-radius: 4px;
  padding: .1rem .4rem;
  font-family: monospace;
  font-size: .8rem;
  color: var(--adm-accent);
}

/* ─────────────────────────────────────────────────────────────────
   SPEC JA/NEE
   ───────────────────────────────────────────────────────────────── */
.spec-ja  { color: var(--adm-accent); font-weight: 700; }
.spec-nee { color: var(--adm-faint); font-weight: 700; }

/* ─────────────────────────────────────────────────────────────────
   FILTERBALK
   ───────────────────────────────────────────────────────────────── */
.filter-bar {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: .6rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.filter-bar .field { margin: 0; }
.filter-bar select,
.filter-bar input[type=text] {
  padding: .5rem .85rem;
  border: 1.5px solid var(--adm-border);
  border-radius: var(--adm-radius);
  font-size: .85rem;
  font-family: var(--adm-font);
  background: var(--adm-surface);
  color: var(--adm-ink);
  height: 38px;
}
.filter-bar input[type=text] { width: 240px; }
.filter-bar button {
  padding: 0 1.1rem;
  height: 38px;
  background: var(--adm-ink);
  color: #fff;
  border: none;
  border-radius: var(--adm-radius);
  font-size: .85rem;
  font-weight: 600;
  font-family: var(--adm-font);
  cursor: pointer;
  white-space: nowrap;
  transition: background .15s;
}
.filter-bar button:hover { background: #1f2937; }
.filter-bar .btn-secondary {
  height: 38px;
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

/* ─────────────────────────────────────────────────────────────────
   DETAIL KAART (aanvragen)
   ───────────────────────────────────────────────────────────────── */
.detail-card { border: 2px solid var(--adm-accent); }
.detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .5rem;
  margin-bottom: 1.25rem;
}
.detail-header h2 { margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--adm-ink); }
.detail-casenr {
  font-size: .8rem;
  font-weight: 700;
  color: #1d4ed8;
  letter-spacing: .03em;
}
.detail-casenr a { color: #1d4ed8; text-decoration: none; }
.detail-casenr a:hover { text-decoration: underline; }
.detail-header-right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.detail-section {
  margin-bottom: 1.25rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--adm-border);
}
.detail-section:last-child { border-bottom: none; }
.detail-section h4 {
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--adm-faint);
  margin-bottom: .7rem;
}
.specs-grid {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: .3rem .75rem;
  font-size: .875rem;
}
.specs-grid .lbl { color: var(--adm-muted); font-size: .82rem; }
.specs-grid .val { color: var(--adm-ink); font-weight: 500; }

/* ─────────────────────────────────────────────────────────────────
   FOTO WEERGAVE
   ───────────────────────────────────────────────────────────────── */
.fotos-wrap { display: flex; gap: 1rem; flex-wrap: wrap; }
.foto-item { text-align: center; }

/* ─────────────────────────────────────────────────────────────────
   MAILTEMPLATES
   ───────────────────────────────────────────────────────────────── */
.tpl-layout { display: grid; grid-template-columns: 260px 1fr; gap: 1.5rem; align-items: start; }
.tpl-list   { position: sticky; top: calc(var(--adm-h) + 1.5rem); }
.tpl-item {
  display: block;
  padding: .75rem 1rem;
  border-radius: 10px;
  font-size: .875rem;
  font-weight: 500;
  color: var(--adm-muted);
  text-decoration: none;
  transition: all .15s;
  margin-bottom: .3rem;
  border: 1.5px solid transparent;
}
.tpl-item:hover   { background: var(--adm-bg); color: var(--adm-ink); }
.tpl-item.active  { background: var(--adm-accent-light); color: var(--adm-accent); border-color: rgba(1,105,111,.3); font-weight: 700; }
.tpl-badge {
  display: inline-block;
  font-size: .65rem;
  font-weight: 700;
  padding: .1rem .45rem;
  border-radius: 99px;
  margin-left: .35rem;
  vertical-align: middle;
}
.tpl-badge-inzender { background: #dbeafe; color: #1e40af; }
.tpl-badge-admin    { background: #fef9c3; color: #854d0e; }
.tpl-badge-off      { background: #f1f5f9; color: #64748b; }
.tpl-edit-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: .75rem;
}
.tpl-edit-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--adm-ink); }
.vars-box {
  background: var(--adm-surface-2);
  border: 1px solid var(--adm-border);
  border-radius: 10px;
  padding: .85rem 1rem;
  font-size: .8rem;
  color: var(--adm-text);
  line-height: 1.8;
  margin-bottom: 1.25rem;
}
.vars-box strong { color: var(--adm-ink); display: block; margin-bottom: .3rem; }
.vars-box code {
  background: var(--adm-border);
  padding: .05rem .3rem;
  border-radius: 4px;
  font-size: .78rem;
}
.body-editor {
  width: 100%;
  min-height: 320px;
  resize: vertical;
  font-family: 'Courier New', monospace;
  font-size: .82rem;
  line-height: 1.55;
  padding: .85rem 1rem;
  border: 1.5px solid var(--adm-border);
  border-radius: var(--adm-radius);
  color: var(--adm-ink);
  background: var(--adm-surface);
  transition: border-color .2s;
}
.body-editor:focus { outline: none; border-color: var(--adm-accent); }
.preview-wrap {
  background: var(--adm-surface-2);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-lg);
  overflow: hidden;
  margin-top: 1.5rem;
}
.preview-hdr {
  background: var(--adm-border);
  padding: .6rem 1rem;
  font-size: .75rem;
  font-weight: 700;
  color: var(--adm-muted);
  letter-spacing: .06em;
  text-transform: uppercase;
}
.preview-body { padding: 1rem; }
iframe.preview-frame {
  width: 100%;
  height: 420px;
  border: none;
  border-radius: var(--adm-radius);
  background: white;
}
.testmail-row {
  display: flex;
  gap: .75rem;
  align-items: flex-end;
  flex-wrap: wrap;
  margin-top: 1rem;
}
.testmail-row .field { margin: 0; flex: 1; min-width: 200px; }
.tab-bar {
  display: flex;
  gap: .5rem;
  margin-bottom: 1.25rem;
  border-bottom: 2px solid var(--adm-border);
  padding-bottom: .75rem;
}
.tab-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-family: var(--adm-font);
  font-size: .875rem;
  font-weight: 600;
  color: var(--adm-muted);
  padding: .35rem .75rem;
  border-radius: 6px;
  transition: background .15s, color .15s;
}
.tab-btn:hover  { background: var(--adm-nav-hover); color: var(--adm-ink); }
.tab-btn.active { color: var(--adm-accent); background: var(--adm-accent-light); }

/* ─────────────────────────────────────────────────────────────────
   MODELLEN — toggle pills, badges
   ───────────────────────────────────────────────────────────────── */
.toggle-pill {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  padding: .2rem .6rem;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 700;
  text-decoration: none;
  transition: all .15s;
  border: none;
  cursor: pointer;
  white-space: nowrap;
}
.toggle-on        { background: #dcfce7; color: #14532d; }
.toggle-on:hover  { background: #bbf7d0; }
.toggle-off       { background: #f1f5f9; color: var(--adm-faint); }
.toggle-off:hover { background: #e2e8f0; color: #475569; }

.uitzondering-badge {
  display: inline-flex;
  align-items: center;
  gap: .2rem;
  font-size: .65rem;
  font-weight: 700;
  padding: .15rem .4rem;
  border-radius: 4px;
  margin-left: .3rem;
  vertical-align: middle;
  white-space: nowrap;
}
.uitz-positief { background: #fef9c3; color: #713f12; border: 1px solid #fde68a; }
.uitz-negatief { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }

.stats-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.stat-chip {
  font-size: .75rem;
  font-weight: 600;
  padding: .25rem .65rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  gap: .3rem;
  cursor: default;
}
.sc-total  { background: #f1f5f9; color: #475569; }
.sc-rep    { background: #dbeafe; color: #1e3a8a; }
.sc-tax    { background: #ede9fe; color: #3b0764; }
.sc-uitz   { background: #fef9c3; color: #713f12; cursor: pointer; text-decoration: none; }
.sc-uitz:hover { background: #fde68a; }

/* ─────────────────────────────────────────────────────────────────
   ADVIES / CHIPS
   ───────────────────────────────────────────────────────────────── */
.chip-garantie  { background: #dcfce7; color: #166534; }
.chip-coulance  { background: #fce7f3; color: #9d174d; }
.chip-reparatie { background: #dbeafe; color: #1e40af; }
.chip-taxatie   { background: #fef9c3; color: #92400e; }
.chip-recycling { background: #f3f4f6; color: #374151; }

/* Advies tab navigatie */
.ai-tab-bar { display: flex; gap: .4rem; border-bottom: 2px solid var(--adm-border); margin-bottom: 1.5rem; padding-bottom: .75rem; }
.ai-tab {
  background: none;
  border: none;
  font-family: var(--adm-font);
  font-size: .875rem;
  font-weight: 600;
  color: var(--adm-muted);
  padding: .35rem .85rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background .15s, color .15s;
}
.ai-tab:hover   { color: var(--adm-ink); background: var(--adm-nav-hover); }
.ai-tab.active  { color: var(--adm-accent); background: var(--adm-accent-light); }

.ai-panel { display: none; }
.ai-panel.active { display: block; }

.ai-rule-card {
  background: var(--adm-surface);
  border: 1px solid var(--adm-border);
  border-radius: var(--adm-radius-lg);
  padding: 1.1rem 1.25rem;
  margin-bottom: .75rem;
}
.ai-rule-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: .75rem;
  flex-wrap: wrap;
}

/* ─────────────────────────────────────────────────────────────────
   WACHTWOORD TOGGLE
   ───────────────────────────────────────────────────────────────── */
.pw-toggle-wrap { position: relative; }
.pw-toggle-wrap input { padding-right: 2.5rem; }
.pw-eye {
  position: absolute;
  right: .65rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--adm-muted);
  font-size: 1rem;
  padding: 0;
  line-height: 1;
}
.pw-strength { font-size: .75rem; margin-top: .3rem; height: 1.1em; }
.pw-strength.weak   { color: #dc2626; }
.pw-strength.medium { color: #d97706; }
.pw-strength.strong { color: #16a34a; }

/* ─────────────────────────────────────────────────────────────────
   MODALS
   ───────────────────────────────────────────────────────────────── */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  z-index: 2000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal-overlay.is-open { display: flex; }
.modal-box {
  background: var(--adm-surface);
  border-radius: var(--adm-radius-xl);
  padding: 2rem;
  max-width: 480px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
  max-height: 90vh;
  overflow-y: auto;
}
.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--adm-muted);
  font-size: 1.1rem;
  line-height: 1;
  padding: .25rem;
  border-radius: 4px;
  transition: color .15s;
}
.modal-close:hover { color: var(--adm-ink); }
.modal-title { font-size: 1.1rem; font-weight: 700; color: var(--adm-ink); margin-bottom: 1.25rem; }

/* ─────────────────────────────────────────────────────────────────
   2FA — stap kaarten
   ───────────────────────────────────────────────────────────────── */
.step-card {
  background: var(--adm-surface);
  border: 1.5px solid var(--adm-border);
  border-radius: var(--adm-radius-xl);
  padding: 1.25rem;
  margin-bottom: 1rem;
}
.step-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px; height: 24px;
  border-radius: 50%;
  background: var(--adm-ink);
  color: #fff;
  font-size: .75rem;
  font-weight: 700;
  margin-bottom: .5rem;
  flex-shrink: 0;
}
.step-body h3 { font-size: .9rem; font-weight: 700; margin-bottom: .3rem; color: var(--adm-ink); }
.step-body p  { font-size: .855rem; color: var(--adm-muted); line-height: 1.65; margin: 0 0 .6rem; }
.qr-wrap {
  background: var(--adm-surface-2);
  border: 1.5px solid var(--adm-border);
  border-radius: var(--adm-radius-lg);
  padding: 1rem;
  text-align: center;
  margin: .75rem 0;
}
.secret-code {
  font-family: 'Courier New', monospace;
  font-size: .875rem;
  letter-spacing: .1em;
  background: var(--adm-surface-2);
  border: 1.5px solid var(--adm-border);
  border-radius: var(--adm-radius);
  padding: .65rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  word-break: break-all;
  color: var(--adm-ink);
}
.copy-btn {
  background: var(--adm-ink);
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: .3rem .65rem;
  font-size: .75rem;
  font-weight: 600;
  cursor: pointer;
  font-family: var(--adm-font);
  transition: background .2s;
  flex-shrink: 0;
  margin-left: auto;
}
.copy-btn:hover { background: var(--adm-accent); }

/* 2FA status badge */
.fa-status-badge {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  font-size: .8rem;
  font-weight: 700;
  padding: .3rem .75rem;
  border-radius: 99px;
  margin-bottom: 1.25rem;
}
.fa-status-badge.on  { background: #dcfce7; color: #166534; }
.fa-status-badge.off { background: #fef2f2; color: #991b1b; }

/* ─────────────────────────────────────────────────────────────────
   SETTINGS NAVIGATIE (account-instellingen)
   ───────────────────────────────────────────────────────────────── */
.settings-layout { display: grid; grid-template-columns: 220px 1fr; gap: 2rem; align-items: start; }
.settings-nav { position: sticky; top: calc(var(--adm-h) + 1.5rem); }
.settings-nav-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .6rem .85rem;
  border-radius: var(--adm-radius);
  font-size: .875rem;
  font-weight: 500;
  color: var(--adm-muted);
  text-decoration: none;
  transition: all .15s;
  margin-bottom: .2rem;
  cursor: pointer;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  font-family: var(--adm-font);
}
.settings-nav-item:hover  { background: var(--adm-accent-light); color: var(--adm-accent); }
.settings-nav-item.active { background: var(--adm-accent-light); color: var(--adm-accent); font-weight: 700; }

/* ─────────────────────────────────────────────────────────────────
   THRESHOLD RANGE
   ───────────────────────────────────────────────────────────────── */
.threshold-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: .5rem;
}
.threshold-row input[type="range"] {
  flex: 1;
  accent-color: var(--adm-accent);
  cursor: pointer;
}
.threshold-val {
  font-size: .875rem;
  font-weight: 700;
  color: var(--adm-accent);
  min-width: 36px;
  text-align: right;
}

/* ─────────────────────────────────────────────────────────────────
   SYNC BOX (modellen)
   ───────────────────────────────────────────────────────────────── */
.sync-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: var(--adm-radius);
  padding: .75rem 1rem;
  font-size: .82rem;
  color: #14532d;
  margin-bottom: 1rem;
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  flex-wrap: wrap;
}
.sync-box a { color: #15803d; font-weight: 600; }

/* ─────────────────────────────────────────────────────────────────
   CHECKBOX RIJEN (modellen)
   ───────────────────────────────────────────────────────────────── */
.cb-row { display: flex; gap: 2rem; margin-top: .75rem; margin-bottom: .5rem; flex-wrap: wrap; }
.cb-item { display: flex; align-items: flex-start; gap: .5rem; }
.cb-item input[type="checkbox"] {
  appearance: checkbox !important;
  -webkit-appearance: checkbox !important;
  width: 16px !important; height: 16px !important;
  min-width: 16px !important; padding: 0 !important;
  margin: 3px 0 0 0 !important;
  border: 1px solid #ccc !important;
  border-radius: 3px !important;
  background: white !important;
  box-shadow: none !important;
  cursor: pointer;
  accent-color: var(--adm-accent);
  flex-shrink: 0;
}
.cb-item label {
  display: flex !important;
  flex-direction: column;
  font-size: .875rem !important;
  font-weight: 600 !important;
  color: var(--adm-ink) !important;
  cursor: pointer;
  margin: 0 !important;
}
.cb-hint { font-size: .72rem; font-weight: 400; color: var(--adm-faint); margin-top: .15rem; }

/* ─────────────────────────────────────────────────────────────────
   RESPONSIVE — MOBIEL
   ───────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .adm-hamburger { display: flex; }
  .adm-header-actions .adm-action-label { display: none; }
  .adm-account-name { display: none; }
  .adm-nav {
    display: none;
    position: fixed;
    top: var(--adm-h);
    left: 0; right: 0;
    background: var(--adm-header-bg);
    border-bottom: 1px solid var(--adm-header-border);
    box-shadow: var(--adm-shadow-md);
    flex-direction: column;
    align-items: stretch;
    gap: .1rem;
    padding: .5rem .75rem .75rem;
    overflow-y: auto;
  }
  .adm-nav.is-open { display: flex; }
  .adm-nav-link { font-size: .875rem; padding: .55rem .75rem; }
  .adm-header-actions { display: none; }
  .adm-logo { margin-right: 0; }
  .form-row-2, .form-row-3, .s-row-2 { grid-template-columns: 1fr; }
  .tpl-layout { grid-template-columns: 1fr; }
  .settings-layout { grid-template-columns: 1fr; }
  .adm-page { padding: 1.25rem 1rem; }
  .recent-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
  .recent-grid { grid-template-columns: 1fr; }
  .filter-bar input[type=text] { width: 100%; }
  .stats-row { gap: .3rem; }
}
/* In admin.css toevoegen — tabel helpers voor modellen */
.col-merk-dim  { color: var(--adm-faint); font-size: .78rem; }
.col-serie-dim { font-size: .78rem; color: var(--adm-faint); }
.acties-cel    { display: flex; gap: .4rem; flex-wrap: wrap; }

/* Uitzondering tekst kleuren */
.uitz-positief-tekst { color: #713f12; font-weight: 700; }
.uitz-negatief-tekst { color: #7f1d1d; font-weight: 700; }

/* Formulier actie rij */
.form-actions { display: flex; gap: .75rem; margin-top: 1rem; align-items: center; }

/* ─────────────────────────────────────────────────────────────────
   MAILTEMPLATES — extra hulpklassen
   ───────────────────────────────────────────────────────────────── */
.tpl-list-title {
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--adm-muted);
  margin-bottom: .75rem;
}
.tpl-list-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--adm-border);
}
.tpl-list-note {
  font-size: .75rem;
  color: var(--adm-muted);
  line-height: 1.55;
}
.tpl-edit-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--adm-ink);
  margin: 0 0 .2rem;
}
.tpl-edit-meta {
  font-size: .82rem;
  color: var(--adm-muted);
  margin: 0;
}
.tpl-slug-code {
  background: var(--adm-surface-2);
  padding: .1rem .3rem;
  border-radius: 4px;
  font-size: .8rem;
  font-family: monospace;
}
.tpl-save-row {
  display: flex;
  gap: .75rem;
  align-items: center;
}
.tpl-bijgewerkt {
  font-size: .8rem;
  color: var(--adm-muted);
}
.tpl-leeg-msg {
  color: var(--adm-muted);
  font-size: .9rem;
}
.tab-pane { display: none; }
.tab-pane.active { display: block; }
/* ─────────────────────────────────────────────────────────────────
   ADMINS — hulpklassen
   ───────────────────────────────────────────────────────────────── */
.badge-you {
  background: #d6f0eb;
  color: #287864;
  font-size: .7rem;
  font-weight: 700;
  padding: .15rem .5rem;
  border-radius: 99px;
  margin-left: .5rem;
  vertical-align: middle;
}

.adm-id-cel    { color: var(--adm-faint); font-size: .8rem; }
.adm-email-cel { font-size: .85rem; }
.adm-datum-cel { font-size: .8rem; color: var(--adm-muted); white-space: nowrap; }
.adm-leeg-em   { color: var(--adm-muted); font-style: italic; }

.adm-acties-row {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
  align-items: center;
}

.adm-btn-warning {
  background: #fef9c3;
  color: #854d0e;
  border: 1.5px solid #fef08a;
  text-decoration: none;
}
.adm-btn-warning:hover { background: #fef08a; }

.adm-create-form { max-width: 460px; }

.adm-opt-label {
  font-weight: 400;
  color: var(--adm-muted);
  font-size: .8rem;
  text-transform: none;
  letter-spacing: 0;
}

.adm-modal-actions {
  display: flex;
  gap: .75rem;
  margin-top: 1rem;
}

    </style>
</header>
<div class="adm-header-spacer" aria-hidden="true"></div>

<script>
(function () {
  var btn = document.getElementById('admHamburger');
  var nav = document.querySelector('.adm-nav');
  if (!btn || !nav) return;
  nav.id = 'admNav';
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('is-open');
    btn.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Menu sluiten' : 'Menu openen');
  });
  document.addEventListener('click', function (e) {
    if (!btn.contains(e.target) && !nav.contains(e.target)) {
      nav.classList.remove('is-open');
      btn.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Menu openen');
    }
  });
})();
</script>