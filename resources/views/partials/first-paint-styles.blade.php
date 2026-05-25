<style>
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.fp-skeleton {
  position: fixed;
  inset: 0;
  z-index: 9999;
  overflow: hidden;
  pointer-events: none;
  background: #ecf0f3;
}

.fp-skeleton__layout {
  display: flex;
  min-height: 100vh;
}

.fp-skeleton__sidebar {
  display: none;
  flex: none;
  background: rgba(255, 255, 255, 0.95);
  border-right: 1px solid rgba(229, 229, 229, 0.6);
  box-shadow: 0 4px 8px rgba(209, 217, 230, 0.25), inset -1px 0 0 rgba(255, 255, 255, 0.5);
}

.fp-skeleton__main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.fp-skeleton__navbar {
  min-height: 56px;
  padding: 0.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.95);
  -webkit-backdrop-filter: blur(12px);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(229, 229, 229, 0.6);
}

.fp-skeleton__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1.5rem;
}

.fp-skeleton__header,
.fp-skeleton__body {
  display: grid;
  gap: 0.75rem;
}

.fp-skeleton__grid,
.fp-skeleton__panels {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.fp-skeleton__block,
.fp-skeleton__card,
.fp-skeleton__panel {
  position: relative;
  overflow: hidden;
}

.fp-skeleton__block::after,
.fp-skeleton__card::after,
.fp-skeleton__panel::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shimmer 1.5s ease-in-out infinite;
}

.fp-skeleton__block {
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.8);
}

.fp-skeleton__block--eyebrow {
  width: 8rem;
  height: 0.75rem;
}

.fp-skeleton__block--hero {
  width: min(28rem, 90%);
  height: 2.5rem;
}

.fp-skeleton__block--body {
  width: 60%;
  height: 0.95rem;
}

.fp-skeleton__block--body-wide {
  width: 100%;
}

.fp-skeleton__card,
.fp-skeleton__panel {
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.55);
}

.fp-skeleton__card {
  min-height: 9rem;
}

.fp-skeleton__panel {
  min-height: 12rem;
}

.fp-skeleton__panel--chart        { min-height: 18rem; }
.fp-skeleton__panel--hero-media   { min-height: 16rem; }
.fp-skeleton__panel--hero-status  { min-height: 10rem; }
.fp-skeleton__panel--form         { min-height: 18rem; }
.fp-skeleton__panel--form-tall    { min-height: 22rem; }
.fp-skeleton__panel--table        { min-height: 28rem; }

.fp-skeleton__hero-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.fp-skeleton__strip {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.fp-skeleton__chip {
  position: relative;
  overflow: hidden;
  height: 2.25rem;
  width: 6rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.fp-skeleton__chip::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shimmer 1.5s ease-in-out infinite;
}

.dark .fp-skeleton__chip {
  background: rgba(31, 41, 55, 0.68);
  border-color: rgba(75, 85, 99, 0.3);
}

.dark .fp-skeleton__chip::after {
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.2), transparent);
}

.dark .fp-skeleton {
  background: #1a1a1a;
}

.dark .fp-skeleton__sidebar,
.dark .fp-skeleton__navbar {
  background: rgba(30, 30, 30, 0.95);
  border-color: rgba(55, 65, 81, 0.45);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.28), inset 0 0 0 1px rgba(255, 255, 255, 0.04);
}

.dark .fp-skeleton__card,
.dark .fp-skeleton__panel {
  background: rgba(31, 41, 55, 0.68);
  border-color: rgba(75, 85, 99, 0.3);
}

.dark .fp-skeleton__block {
  background: rgba(148, 163, 184, 0.22);
}

.dark .fp-skeleton__block::after,
.dark .fp-skeleton__card::after,
.dark .fp-skeleton__panel::after {
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.2), transparent);
}

@media (min-width: 768px) {
  .fp-skeleton__sidebar {
    display: block;
    width: 60px;
  }

  .fp-skeleton__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 768px) {
  .fp-skeleton__hero-row {
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
  }
}

@media (min-width: 1024px) {
  .fp-skeleton__sidebar {
    width: 250px;
  }

  .fp-skeleton__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .fp-skeleton__grid--three {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .fp-skeleton__panels {
    grid-template-columns: minmax(0, 1.6fr) minmax(18rem, 1fr);
  }
}

@media (prefers-reduced-motion: reduce) {
  .fp-skeleton__block::after,
  .fp-skeleton__card::after,
  .fp-skeleton__panel::after,
  .fp-skeleton__chip::after {
    animation: none;
    transform: none;
  }
}
</style>