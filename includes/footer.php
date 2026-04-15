<footer>
  <div class="footer-inner">

    <div class="footer-brand">
      <div class="footer-logo">Reparatie<span>Platform</span></div>
      <p class="footer-desc">
        Gratis en eerlijk advies voor consumenten met een defecte televisie.
        Een initiatief van TV Reparatie Service Nederland.
      </p>
    </div>

    <div class="footer-col">
      <h4>Advies</h4>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/garantie.php">Garantie</a>
        <a href="<?= BASE_URL ?>/coulance.php">Coulanceregeling</a>
        <a href="<?= BASE_URL ?>/reparatie.php">Reparatie aan huis</a>
        <a href="<?= BASE_URL ?>/taxatie.php">Taxatierapport</a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Merken</h4>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/database.php?merk=Samsung">Samsung</a>
        <a href="<?= BASE_URL ?>/database.php?merk=Philips">Philips</a>
        <a href="<?= BASE_URL ?>/database.php?merk=Sony">Sony</a>
        <a href="<?= BASE_URL ?>/database.php?merk=LG">LG</a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Overig</h4>
      <div class="footer-col-links">
        <a href="<?= BASE_URL ?>/database.php">TV Database</a>
        <a href="<?= BASE_URL ?>/disclaimer.php">Disclaimer</a>
        <a href="<?= BASE_URL ?>/privacy.php">Privacybeleid</a>
        <a href="<?= BASE_URL ?>/contact.php">Contact</a>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    <span>&copy; 2026 Reparatieplatform.nl &mdash; onderdeel van TV Reparatie Service Nederland</span>
    <span>Alle adviezen zijn indicatief en vrijblijvend</span>
  </div>
</footer>

<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script>
(function () {
  const BREAKPOINT = 768;

  function closeLinks(h4El) {
    const links = h4El.nextElementSibling;
    h4El.classList.remove('open');
    h4El.setAttribute('aria-expanded', 'false');
    links.style.maxHeight  = '0';
    links.style.opacity    = '0';
    links.style.visibility = 'hidden';
  }

  function openLinks(h4El) {
    const links = h4El.nextElementSibling;
    h4El.classList.add('open');
    h4El.setAttribute('aria-expanded', 'true');
    links.style.maxHeight  = links.scrollHeight + 'px';
    links.style.opacity    = '1';
    links.style.visibility = 'visible';
  }

  function destroyAccordion() {
    document.querySelectorAll('.footer-col').forEach(col => {
      const h4    = col.querySelector('h4');
      const links = col.querySelector('.footer-col-links');
      if (!h4 || !links) return;

      h4.classList.remove('is-toggle', 'open');
      h4.removeAttribute('aria-expanded');
      h4.style.cursor = '';

      links.style.maxHeight  = '';
      links.style.overflow   = '';
      links.style.visibility = '';
      links.style.opacity    = '';
    });
  }

  function buildAccordion() {
    document.querySelectorAll('.footer-col').forEach(col => {
      const h4    = col.querySelector('h4');
      const links = col.querySelector('.footer-col-links');
      if (!h4 || !links) return;

      // Kloon om oude listeners te verwijderen
      const newH4 = h4.cloneNode(true);
      col.replaceChild(newH4, h4);

      // Zet klasse en sluit dicht — ná replaceChild zodat newH4 in de DOM zit
      newH4.classList.add('is-toggle');
      newH4.setAttribute('aria-expanded', 'false');

      links.style.overflow   = 'hidden';
      links.style.maxHeight  = '0';
      links.style.opacity    = '0';
      links.style.visibility = 'hidden';

      newH4.addEventListener('click', function () {
        const isOpen = this.getAttribute('aria-expanded') === 'true';

        // Sluit alle andere kolommen
        document.querySelectorAll('.footer-col h4.is-toggle').forEach(other => {
          if (other !== this) closeLinks(other);
        });

        // Toggle huidige — links opnieuw ophalen via nextElementSibling
        if (isOpen) {
          closeLinks(this);
        } else {
          openLinks(this);
        }
      });
    });
  }

  function init() {
    if (window.innerWidth <= BREAKPOINT) {
      buildAccordion();
    } else {
      destroyAccordion();
    }
  }

  init();

  let resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(init, 150);
  });
})();
</script>
</body>
</html>