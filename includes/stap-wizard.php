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
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>',
        'title' => 'Formulier invullen',
        'desc'  => 'Merk, modelnummer en een korte omschrijving van het probleem. Klaar in minder dan 2 minuten.',
        'meta'  => '~2 minuten',
    ],
    [
        'nr'    => '02',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'title' => 'Wij analyseren',
        'desc'  => 'Een specialist bekijkt jouw situatie en toetst aan garantie- en coulanceregels van de fabrikant.',
        'meta'  => 'Binnen 24 uur',
    ],
    [
        'nr'    => '03',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'title' => 'Persoonlijk advies',
        'desc'  => 'Je ontvangt een helder advies met concrete vervolgstappen — gratis en volledig vrijblijvend.',
        'meta'  => 'Gratis & vrijblijvend',
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

        <!-- Stap-header: nummer + connector -->
        <div class="wizard-step-top">
            <div class="wizard-step-num">
                <span class="wizard-num-label"><?= h($s['nr']) ?></span>
                <div class="wizard-step-ico"><?= $s['icon'] ?></div>
            </div>
            <?php if ($i < count($steps) - 1): ?>
            <div class="wizard-connector" aria-hidden="true">
                <svg width="100%" height="2" preserveAspectRatio="none"><line x1="0" y1="1" x2="100%" y2="1" stroke-dasharray="6 4"/></svg>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stap-body -->
        <div class="wizard-step-body">
            <div class="wizard-step-meta"><?= h($s['meta']) ?></div>
            <h3 class="wizard-step-title"><?= h($s['title']) ?></h3>
            <p class="wizard-step-desc"><?= h($s['desc']) ?></p>
        </div>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
/* ── Wizard Steps – volledig herontwerp ────────────────────────── */
.wizard-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    position: relative;
}
@media (max-width: 640px) {
    .wizard-steps { grid-template-columns: 1fr; gap: 2rem; }
}

/* Stap-top: nummer cirkel + connector */
.wizard-step-top {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}
.wizard-step-num {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform .25s ease;
}
.wizard-step:hover .wizard-step-num {
    transform: translateY(-3px);
}
.wizard-step-ico {
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.wizard-step-ico svg {
    width: 100%;
    height: 100%;
}
.wizard-num-label {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: .6rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    letter-spacing: -.01em;
}

/* SVG stippellijn connector */
.wizard-connector {
    flex: 1;
    height: 2px;
    margin: 0 1rem;
    margin-top: -1px;
    opacity: .45;
}
@media (max-width: 640px) {
    .wizard-connector { display: none; }
}

/* Meta-label boven titel */
.wizard-step-meta {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: .4rem;
}

.wizard-step-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: .5rem;
    line-height: 1.3;
}
.wizard-step-desc {
    font-size: .875rem;
    line-height: 1.75;
    max-width: 28ch;
    margin: 0;
}

/* ── Licht thema ───────────────────────────────────────────────── */
.wizard-light .wizard-step-num {
    background: var(--accent-light, #eef7f6);
    box-shadow: 0 0 0 8px color-mix(in oklab, var(--accent, #01696f) 8%, transparent);
}
.wizard-light .wizard-step-ico { color: var(--accent, #01696f); }
.wizard-light .wizard-num-label {
    background: var(--accent, #01696f);
    color: #fff;
    border: 2px solid #fff;
}
.wizard-light .wizard-connector line {
    stroke: var(--accent, #01696f);
}
.wizard-light .wizard-step-meta { color: var(--accent, #01696f); }
.wizard-light .wizard-step-title { color: var(--ink, #111827); }
.wizard-light .wizard-step-desc  { color: var(--muted, #6b7280); }

/* ── Donker thema ──────────────────────────────────────────────── */
.wizard-dark .wizard-step-num {
    background: rgba(255,255,255,.1);
    box-shadow: 0 0 0 8px rgba(255,255,255,.04);
}
.wizard-dark .wizard-step-ico { color: rgba(255,255,255,.9); }
.wizard-dark .wizard-num-label {
    background: #fff;
    color: var(--accent, #01696f);
    border: 2px solid transparent;
}
.wizard-dark .wizard-connector line { stroke: rgba(255,255,255,.3); }
.wizard-dark .wizard-step-meta { color: rgba(255,255,255,.5); }
.wizard-dark .wizard-step-title { color: #fff; }
.wizard-dark .wizard-step-desc  { color: rgba(255,255,255,.65); }

/* ── Compact variant ───────────────────────────────────────────── */
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