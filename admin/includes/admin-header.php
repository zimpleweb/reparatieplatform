<?php
/**
 * Admin Header Component – admin/includes/admin-header.php
 *
 * Gebruik in elke admin-pagina:
 *   $adminActivePage = 'dashboard';  // nav-item markeren als actief
 *   require_once __DIR__ . '/includes/admin-header.php';  (vanuit admin/)
 *   OF
 *   require_once __DIR__ . '/../admin/includes/admin-header.php';
 *
 * Geldige waarden voor $adminActivePage:
 *   dashboard | aanvragen | meldingen | modellen | klachten
 *   advies-instellingen | mailtemplates | admins | account-instellingen
 */

if (!defined('BASE_URL')) {
    // fallback – mag nooit voorkomen want db.php laadt de constante
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
    ['id' => 'dashboard',            'label' => 'Dashboard',          'icon' => '&#128202;', 'href' => BASE_URL . '/admin/dashboard.php'],
    ['id' => 'aanvragen',            'label' => 'Inzendingen',        'icon' => '&#128236;', 'href' => BASE_URL . '/admin/aanvragen.php'],
    ['id' => 'meldingen',            'label' => 'Meldingen',          'icon' => '&#128276;', 'href' => BASE_URL . '/admin/meldingen.php', 'badge' => $_adminOngelezen],
    ['id' => 'modellen',             'label' => 'TV-modellen',        'icon' => '&#128250;', 'href' => BASE_URL . '/admin/modellen.php'],
    ['id' => 'klachten',             'label' => 'Klachten',           'icon' => '&#9888;',   'href' => BASE_URL . '/admin/klachten.php'],
    ['id' => 'advies-instellingen',  'label' => 'Adviesregels',       'icon' => '&#9881;',   'href' => BASE_URL . '/admin/advies-instellingen.php'],
    ['id' => 'mailtemplates',        'label' => 'Mailtemplates',      'icon' => '&#128140;', 'href' => BASE_URL . '/admin/mailtemplates.php'],
    ['id' => 'admins',               'label' => 'Admin accounts',     'icon' => '&#128100;', 'href' => BASE_URL . '/admin/admins.php'],
];
?>
<header class="adm-header">
  <div class="adm-header-inner">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="adm-logo" aria-label="Admin dashboard">
      <svg class="adm-logo-icon" width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
        <rect width="28" height="28" rx="8" fill="#01696f"/>
        <path d="M8 20 L14 8 L20 20" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <path d="M10 16 L18 16" stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span class="adm-logo-text">Reparatie<strong>Platform</strong></span>
    </a>

    <!-- Hamburger (mobiel) -->
    <button class="adm-hamburger" id="admHamburger" aria-label="Menu openen" aria-expanded="false" aria-controls="admNavMenu">
      <span></span><span></span><span></span>
    </button>

    <!-- Nav -->
    <nav class="adm-nav" id="admNavMenu" role="navigation" aria-label="Admin navigatie">
      <?php foreach ($_adminNav as $item):
        $isActive = ($adminActivePage === $item['id']);
        $badge = $item['badge'] ?? 0;
      ?>
      <a href="<?= $item['href'] ?>"
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
        <span class="adm-action-label">Website</span>
      </a>
      <div class="adm-header-divider" aria-hidden="true"></div>
      <a href="<?= BASE_URL ?>/admin/account-instellingen.php"
         class="adm-account-link<?= $adminActivePage === 'account-instellingen' ? ' is-active' : '' ?>"
         title="Account instellingen">
        <span class="adm-account-avatar" aria-hidden="true">
          <?= mb_strtoupper(mb_substr($adminUsername, 0, 1)) ?>
        </span>
        <span class="adm-account-name"><?= htmlspecialchars($adminUsername) ?></span>
      </a>
      <div class="adm-header-divider" aria-hidden="true"></div>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="adm-logout-link" title="Uitloggen">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="adm-action-label">Uitloggen</span>
      </a>
    </div>

  </div><!-- /.adm-header-inner -->
</header>
<div class="adm-header-spacer" aria-hidden="true"></div>

<script>
(function(){
  var btn  = document.getElementById('admHamburger');
  var nav  = document.getElementById('admNavMenu');
  if (!btn || !nav) return;
  btn.addEventListener('click', function() {
    var open = nav.classList.toggle('is-open');
    btn.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // Sluit menu bij klik buiten
  document.addEventListener('click', function(e) {
    if (!btn.contains(e.target) && !nav.contains(e.target)) {
      nav.classList.remove('is-open');
      btn.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>