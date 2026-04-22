<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Hoe het werkt – Van defecte tv naar oplossing | Reparatieplatform.nl';
$pageDescription = 'Ontdek hoe Reparatieplatform.nl werkt. In drie stappen van defecte televisie naar persoonlijk advies over garantie, reparatie of taxatie.';
$canonicalUrl    = '/hoe-het-werkt.php';

include __DIR__ . '/includes/header.php';

require __DIR__ . '/includes/hoetwerkt-hero.php';
require __DIR__ . '/includes/hoetwerkt-stappen.php';
require __DIR__ . '/includes/hoetwerkt-opties.php';
require __DIR__ . '/includes/hoetwerkt-faq.php';
require __DIR__ . '/includes/hoetwerkt-cta.php';

include __DIR__ . '/includes/footer.php';
?>