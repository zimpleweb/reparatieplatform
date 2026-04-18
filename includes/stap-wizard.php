<?php
/**
 * Stappenplan Component – includes/stap-wizard.php
 *
 * Gebruik (volledig):  <?php include __DIR__ . '/includes/stap-wizard.php'; ?>
 * Gebruik (compact):   <?php $wizardCompact = true; include __DIR__ . '/includes/stap-wizard.php'; ?>
 * Gebruik (donker):    <?php $wizardVariant = 'dark'; include __DIR__ . '/includes/stap-wizard.php'; ?>
 */

$wizardVariant = $wizardVariant ?? 'light';
$wizardCompact = $wizardCompact ?? false;

$steps = [
    [
        'nr'    => '01',
        'emoji' => '📝',
        'title' => 'Formulier invullen',
        'desc'  => 'Merk, modelnummer en een korte omschrijving van het probleem. Klaar in minder dan 2 minuten.',
    ],
    [
        'nr'    => '02',
        'emoji' => '🔍',
        'title' => 'Wij analyseren',
        'desc'  => 'Een specialist bekijkt jouw situatie en toetst aan garantie- en coulanceregels van de fabrikant.',
    ],
    [
        'nr'    => '03',
        'emoji' => '📧',
        'title' => 'Persoonlijk advies',
        'desc'  => 'Je ontvangt binnen 24 uur een helder advies met concrete vervolgstappen — gratis en vrijblijvend.',
    ],
];
?>

<?php if ($wizardCompact): ?>
<!-- Compact variant – hero-card -->
<div class="wizard-compact">
    <?php foreach ($steps as $s): ?>
    <div class="wizard-compact-step">
        <span class="wizard-compact-dot"><?= $s['nr'] ?></span>
        <span class="wizard-compact-label"><?= h($s['title']) ?></span>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<!-- Volledige variant -->
<div class="wizard-steps wizard-<?= h($wizardVariant) ?>">
    <?php foreach ($steps as $i => $s): ?>
    <div class="wizard-step">
        <div class="wizard-step-head">
            <div class="wizard-step-badge">
                <span class="wizard-step-emoji"><?= $s['emoji'] ?></span>
                <span class="wizard-step-nr"><?= $s['nr'] ?></span>
            </div>
            <?php if ($i < count($steps) - 1): ?>
            <div class="wizard-connector" aria-hidden="true"></div>
            <?php endif; ?>
        </div>
        <div class="wizard-step-body">
            <h3 class="wizard-step-title"><?= h($s['title']) ?></h3>
            <p class="wizard-step-desc"><?= h($s['desc']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
/* ── Wizard Steps – Volledig ───────────────────────────────────── */
.wizard-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    position: relative;
}
@media (max-width: 640px) {
    .wizard-steps { grid-template-columns: 1fr; gap: 1.75rem; }
}

.wizard-step-head {
    display: flex;
    align-items: center;
    margin-bottom: 1.25rem;
}
.wizard-step-badge {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
    font-size: 1.5rem;
    transition: transform .25s ease, box-shadow .25s ease;
}
.wizard-step:hover .wizard-step-badge {
    transform: scale(1.08);
}
.wizard-step-nr {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: .62rem;
    font-weight: 800;
    letter-spacing: -.01em;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    border: 2px solid #fff;
}

/* Horizontale connector tussen stappen */
.wizard-connector {
    flex: 1;
    height: 2px;
    margin: 0 .75rem;
    border-radius: 2px;
}
@media (max-width: 640px) {
    .wizard-connector { display: none; }
}

.wizard-step-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: .4rem;
    line-height: 1.3;
}
.wizard-step-desc {
    font-size: .875rem;
    line-height: 1.7;
    max-width: 26ch;
    margin: 0;
}

/* ── Licht thema ───────────────────────────────────────────────── */
.wizard-light .wizard-step-badge {
    background: var(--accent-light, #d6f0eb);
    box-shadow: 0 0 0 6px var(--accent-light, #d6f0eb), 0 4px 14px rgba(40,120,100,.12);
}
.wizard-light .wizard-step-emoji { color: var(--accent, #287864); }
.wizard-light .wizard-step-nr {
    background: var(--accent, #287864);
    color: #fff;
}
.wizard-light .wizard-connector {
    background: linear-gradient(90deg, var(--accent, #287864) 0%, var(--border, #e5e7eb) 100%);
    opacity: .35;
}
.wizard-light .wizard-step-title { color: var(--ink, #1a1a1a); }
.wizard-light .wizard-step-desc  { color: var(--muted, #6b7280); }

/* ── Donker thema ──────────────────────────────────────────────── */
.wizard-dark .wizard-step-badge {
    background: rgba(255,255,255,.12);
    box-shadow: 0 0 0 6px rgba(255,255,255,.06);
}
.wizard-dark .wizard-step-emoji { color: #fff; }
.wizard-dark .wizard-step-nr {
    background: #fff;
    color: var(--accent, #287864);
    border-color: transparent;
}
.wizard-dark .wizard-connector { background: rgba(255,255,255,.18); }
.wizard-dark .wizard-step-title { color: #fff; }
.wizard-dark .wizard-step-desc  { color: rgba(255,255,255,.7); }

/* ── Compact variant (hero-card) ───────────────────────────────── */
.wizard-compact {
    display: flex;
    flex-direction: column;
    gap: .65rem;
}
.wizard-compact-step {
    display: flex;
    align-items: center;
    gap: .75rem;
}
.wizard-compact-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.3);
}
.wizard-compact-label {
    font-size: .875rem;
    color: rgba(255,255,255,.9);
    line-height: 1.4;
}
</style>