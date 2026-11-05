/* Progressive enhancement: content remains available without JavaScript. */
(() => {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const root = document.documentElement;
  const publicPage = !!document.querySelector('.site-header');
  function sync() {
    const off = reduced.matches;
    root.classList.toggle('rv-motion-paused', off);
    document.dispatchEvent(new CustomEvent('rv:motion', { detail: { paused: off } }));
  }
  sync();
  reduced.addEventListener('change', sync);
  if (!publicPage) return;

  const progress = document.createElement('div');
  progress.className = 'scroll-progress';
  progress.setAttribute('aria-hidden', 'true');
  document.body.append(progress);
  let queued = false;
  function update() {
    const distance = root.scrollHeight - innerHeight;
    progress.style.transform = `scaleX(${distance > 0 ? Math.min(1, Math.max(0, scrollY / distance)) : 0})`;
    queued = false;
  }
  addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(update); } }, { passive: true });
  addEventListener('resize', update);
  update();

  if (!('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.remove('is-pending');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.06, rootMargin: '0px 0px -20px 0px' });
  // Siblings stagger as a group, so each grid starts its own cascade.
  const siblings = new Map();
  document.querySelectorAll('.section-head,.tour-card,.destination-card,.why-image,.why-copy,.why-item,.benefit-strip>div,.steps-grid>div,.faq-grid>div,.cta-banner,.home-journal-card,.blog-card,.service-box').forEach(element => {
    const index = siblings.get(element.parentElement) || 0;
    siblings.set(element.parentElement, index + 1);
    element.classList.add('rv-reveal');
    element.style.setProperty('--reveal-delay', `${index % 4 * 90}ms`);
    if (element.matches('.why-image')) element.dataset.rvFrom = 'left';
    if (element.matches('.why-copy')) element.dataset.rvFrom = 'right';
    if (element.getBoundingClientRect().top > innerHeight && !reduced.matches) element.classList.add('is-pending');
    observer.observe(element);
  });
  document.addEventListener('focusin', event => event.target.closest('.rv-reveal')?.classList.remove('is-pending'));
  addEventListener('beforeprint', () => document.querySelectorAll('.is-pending').forEach(el => el.classList.remove('is-pending')));

  // A restrained highlight follows the pointer on dark cards; touch stays native.
  if (document.body.classList.contains('public-dark') && matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.querySelectorAll('.tour-card').forEach(card => {
      let frame = 0;
      card.addEventListener('pointermove', event => {
        if (reduced.matches || frame) return;
        const x = event.clientX, y = event.clientY;
        frame = requestAnimationFrame(() => {
          const rect = card.getBoundingClientRect();
          card.style.setProperty('--spot-x', `${x - rect.left}px`);
          card.style.setProperty('--spot-y', `${y - rect.top}px`);
          frame = 0;
        });
      }, { passive: true });
    });
  }
})();
