<?php
/**
 * Admin Header Component — admin/includes/admin-header.php
 * Moderne horizontale topbar met navigatie, badge, account & acties.
 * Gebruik: stel $adminActivePage vóór de require_once in.
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
    ['id' => 'mailtemplates',       'label' => 'Mailtemplates',  'href' => BASE_URL . '/admin/mailtemplates.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'],
    ['id' => 'admins',              'label' => 'Beheerders',     'href' => BASE_URL . '/admin/admins.php',
     'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
];
?>
<style>
/* ═══════════════════════════════════════════════════
   Admin Header — Strakke horizontale topbar
   ═══════════════════════════════════════════════════ */
:root {
  --adm-h:         56px;
  --adm-bg:        #0f172a;
  --adm-border:    rgba(255,255,255,.08);
  --adm-text:      rgba(255,255,255,.65);
  --adm-text-act:  #ffffff;
  --adm-accent:    #4f98a3;
  --adm-accent-bg: rgba(79,152,163,.15);
  --adm-hover-bg:  rgba(255,255,255,.07);
  --adm-badge-bg:  #e74c3c;
  --adm-radius:    6px;
  --adm-font:      'Inter', system-ui, sans-serif;
}

.adm-header {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--adm-h);
  background: var(--adm-bg);
  border-bottom: 1px solid var(--adm-border);
  z-index: 1000;
  box-shadow: 0 1px 12px rgba(0,0,0,.25);
}
.adm-header-inner {
  display: flex;
  align-items: center;
  height: 100%;
  padding: 0 1.25rem;
  gap: 0;
  max-width: 100%;
}

/* ── Logo ── */
.adm-logo {
  display: flex;
  align-items: center;
  gap: .55rem;
  text-decoration: none;
  flex-shrink: 0;
  margin-right: 1.5rem;
}
.adm-logo-icon { flex-shrink: 0; border-radius: 7px; }
.adm-logo-text {
  font-family: var(--adm-font);
  font-size: .875rem;
  font-weight: 500;
  color: rgba(255,255,255,.75);
  white-space: nowrap;
  letter-spacing: -.01em;
}
.adm-logo-text strong { color: #fff; font-weight: 700; }

/* ── Nav ── */
.adm-nav {
  display: flex;
  align-items: center;
  gap: .1rem;
  flex: 1;
  overflow-x: auto;
  scrollbar-width: none;
}
.adm-nav::-webkit-scrollbar { display: none; }

.adm-nav-link {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .4rem .7rem;
  border-radius: var(--adm-radius);
  font-family: var(--adm-font);
  font-size: .8125rem;
  font-weight: 500;
  color: var(--adm-text);
  text-decoration: none;
  white-space: nowrap;
  transition: background 150ms, color 150ms;
  position: relative;
}
.adm-nav-link:hover {
  background: var(--adm-hover-bg);
  color: var(--adm-text-act);
}
.adm-nav-link.is-active {
  background: var(--adm-accent-bg);
  color: var(--adm-accent);
  font-weight: 600;
}
.adm-nav-link .adm-nav-icon {
  display: flex;
  align-items: center;
  opacity: .75;
  flex-shrink: 0;
}
.adm-nav-link.is-active .adm-nav-icon { opacity: 1; }

/* Badge */
.adm-nav-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 17px;
  height: 17px;
  padding: 0 4px;
  border-radius: 999px;
  background: var(--adm-badge-bg);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  line-height: 1;
  margin-left: .15rem;
}

/* ── Rechts: acties ── */
.adm-header-actions {
  display: flex;
  align-items: center;
  gap: .15rem;
  margin-left: auto;
  flex-shrink: 0;
  padding-left: 1rem;
}
.adm-header-divider {
  width: 1px;
  height: 20px;
  background: var(--adm-border);
  margin: 0 .35rem;
}
.adm-action-link {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .38rem .65rem;
  border-radius: var(--adm-radius);
  font-family: var(--adm-font);
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-text);
  text-decoration: none;
  transition: background 150ms, color 150ms;
  white-space: nowrap;
}
.adm-action-link:hover {
  background: var(--adm-hover-bg);
  color: var(--adm-text-act);
}
.adm-account-link {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .3rem .6rem;
  border-radius: var(--adm-radius);
  text-decoration: none;
  transition: background 150ms;
  white-space: nowrap;
}
.adm-account-link:hover { background: var(--adm-hover-bg); }
.adm-account-link.is-active { background: var(--adm-accent-bg); }
.adm-account-avatar {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--adm-accent);
  color: #fff;
  font-family: var(--adm-font);
  font-size: .72rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.adm-account-name {
  font-family: var(--adm-font);
  font-size: .8rem;
  font-weight: 500;
  color: var(--adm-text);
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.adm-account-link:hover .adm-account-name,
.adm-account-link.is-active .adm-account-name { color: var(--adm-text-act); }

.adm-logout-link {
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: .38rem .65rem;
  border-radius: var(--adm-radius);
  font-family: var(--adm-font);
  font-size: .8rem;
  font-weight: 500;
  color: rgba(255,255,255,.45);
  text-decoration: none;
  transition: background 150ms, color 150ms;
  white-space: nowrap;
}
.adm-logout-link:hover {
  background: rgba(231,76,60,.15);
  color: #fc8181;
}

/* ── Hamburger (mobiel) ── */
.adm-hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 4px;
  width: 36px;
  height: 36px;
  border-radius: var(--adm-radius);
  background: none;
  border: 1px solid var(--adm-border);
  cursor: pointer;
  margin-left: auto;
  flex-shrink: 0;
  padding: 0;
}
.adm-hamburger span {
  display: block;
  width: 16px;
  height: 2px;
  background: rgba(255,255,255,.7);
  border-radius: 2px;
  transition: transform 200ms, opacity 200ms;
}
.adm-hamburger.is-open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
.adm-hamburger.is-open span:nth-child(2) { opacity: 0; }
.adm-hamburger.is-open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

/* ── Spacer ── */
.adm-header-spacer { height: var(--adm-h); }

/* ── Content wrapper ── */
.adm-page {
  padding: 2rem 2.25rem;
  max-width: 1400px;
}

@media (max-width: 900px) {
  .adm-hamburger { display: flex; }
  .adm-nav {
    display: none;
    position: fixed;
    top: var(--adm-h);
    left: 0; right: 0;
    background: var(--adm-bg);
    border-bottom: 1px solid var(--adm-border);
    flex-direction: column;
    align-items: stretch;
    gap: .1rem;
    padding: .5rem .75rem .75rem;
    overflow-y: auto;
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
  }
  .adm-nav.is-open { display: flex; }
  .adm-nav-link { font-size: .875rem; padding: .55rem .75rem; }
  .adm-header-actions { display: none; }
  .adm-logo { margin-right: 0; }
}
</style>

<header class="adm-header">
  <div class="adm-header-inner">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="adm-logo" aria-label="Naar dashboard">
      <svg class="adm-logo-icon" width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
        <rect width="28" height="28" rx="7" fill="#4f98a3"/>
        <path d="M8 20 L14 8 L20 20" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <path d="M10.5 16 L17.5 16" stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span class="adm-logo-text">Reparatie<strong>Platform</strong></span>
    </a>

    <!-- Hamburger (mobiel) -->
    <button class="adm-hamburger" id="admHamburger" aria-label="Menu openen" aria-expanded="false" aria-controls="admNavMenu">
      <span></span><span></span><span></span>
    </button>

    <!-- Nav -->
    <nav class="adm-nav" id="admNavMenu" role="navigation" aria-label="Beheermenu">
      <?php foreach ($_adminNav as $item):
        $isActive = ($adminActivePage === $item['id']);
        $badge    = $item['badge'] ?? 0;
      ?>
      <a href="<?= htmlspecialchars($item['href']) ?>"
         class="adm-nav-link<?= $isActive ? ' is-active' : '' ?>"
         <?= $isActive ? 'aria-current="page"' : '' ?>>
        <span class="adm-nav-icon" aria-hidden="true"><?= $item['icon'] ?></span>
        <?= htmlspecialchars($item['label']) ?>
        <?php if ($badge > 0): ?>
          <span class="adm-nav-badge" aria-label="<?= $badge ?> ongelezen"><?= $badge ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <!-- Rechts: account + acties -->
    <div class="adm-header-actions">
      <a href="<?= BASE_URL ?>/" target="_blank" rel="noopener" class="adm-action-link" title="Website bekijken">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        <span>Website</span>
      </a>
      <div class="adm-header-divider" aria-hidden="true"></div>
      <a href="<?= BASE_URL ?>/admin/account-instellingen.php"
         class="adm-account-link<?= $adminActivePage === 'account-instellingen' ? ' is-active' : '' ?>"
         title="Account instellingen">
        <span class="adm-account-avatar" aria-hidden="true"><?= mb_strtoupper(mb_substr($adminUsername, 0, 1)) ?></span>
        <span class="adm-account-name"><?= htmlspecialchars($adminUsername) ?></span>
      </a>
      <div class="adm-header-divider" aria-hidden="true"></div>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="adm-logout-link" title="Uitloggen">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Uitloggen</span>
      </a>
    </div>

  </div>
</header>
<div class="adm-header-spacer" aria-hidden="true"></div>

<script>
(function(){
  var btn = document.getElementById('admHamburger');
  var nav = document.getElementById('admNavMenu');
  if (!btn || !nav) return;
  btn.addEventListener('click', function() {
    var open = nav.classList.toggle('is-open');
    btn.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Menu sluiten' : 'Menu openen');
  });
  document.addEventListener('click', function(e) {
    if (!btn.contains(e.target) && !nav.contains(e.target)) {
      nav.classList.remove('is-open');
      btn.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Menu openen');
    }
  });
})();
</script>