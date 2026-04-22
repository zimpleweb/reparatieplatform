<?php
// Component: hoetwerkt-hero.php
?>
<div class="page-header-hero-only">
  <div class="page-header-stappen-inner">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/">Home</a><span class="sep">/</span>
      <span style="color:rgba(255,255,255,.4)">Hoe het werkt</span>
    </div>
    <h1>Hoe werkt<br>Reparatieplatform.nl?</h1>
    <p class="hero-lead">Televisie kapot? Wij helpen je uitzoeken wat de beste stap is. Gratis en zonder verplichtingen.</p>
    <div class="hero-badge">&#9881; Eenvoudig proces</div>
  </div>
</div>

<style>
.page-header-hero-only {
  background: var(--ink, #0d1117);
  padding: 5rem 2.5rem 4rem;
  position: relative;
  overflow: hidden;
}
.page-header-hero-only::before {
  content: '';
  position: absolute;
  top: -100px; right: -100px;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(40,120,100,.2) 0%, transparent 70%);
  pointer-events: none;
}
.page-header-stappen-inner {
  max-width: 1280px;
  margin: 0 auto;
  position: relative;
}
.page-header-hero-only h1 {
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 800;
  color: white;
  letter-spacing: -.03em;
  margin-bottom: .75rem;
}
.page-header-hero-only .hero-lead {
  font-size: 1rem;
  color: rgba(255,255,255,.55);
  max-width: 520px;
  margin-bottom: 2.5rem;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(40,120,100,.15);
  border: 1px solid rgba(40,120,100,.3);
  border-radius: 999px;
  padding: .3rem 1rem;
  font-size: .75rem;
  font-weight: 700;
  color: #4ecb9e;
  margin-bottom: 1.1rem;
  letter-spacing: .04em;
}
@media (max-width: 768px) {
  .page-header-hero-only { padding: 4rem 1.25rem 3rem; }
}
</style>