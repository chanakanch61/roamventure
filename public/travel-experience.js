/* Shared public navigation enhancements. Native links and forms remain usable. */
(() => {
  if (!document.body.classList.contains('travel-experience')) return;
  const nav = document.querySelector('#site-nav');
  if (nav) {
    const contact = nav.querySelector('a[href$="contactus"]');
    contact?.classList.add('travel-nav-cta');
    const toggle = document.querySelector('.menu-toggle');
    const close = () => { nav.classList.remove('open'); toggle?.setAttribute('aria-expanded', 'false'); };
    nav.addEventListener('click', event => { if (event.target.closest('a')) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && nav.classList.contains('open')) { close(); toggle?.focus(); } });
    document.addEventListener('click', event => { if (!event.target.closest('.site-header') && nav.classList.contains('open')) close(); });
  }
  const top = document.createElement('button');
  top.className = 'travel-back-top';
  top.type = 'button';
  top.setAttribute('aria-label', 'กลับด้านบน');
  top.innerHTML = '<i class="material-icons" aria-hidden="true">arrow_upward</i><svg class="travel-back-ring" viewBox="0 0 48 48" aria-hidden="true"><circle class="track" cx="24" cy="24" r="23"/><circle class="bar" cx="24" cy="24" r="23" pathLength="1"/></svg>';
  top.hidden = true;
  document.body.append(top);
  const ring = top.querySelector('.bar');
  let queued = false;
  const update = () => {
    queued = false;
    top.hidden = scrollY < 650;
    const distance = document.documentElement.scrollHeight - innerHeight;
    ring.style.strokeDashoffset = distance > 0 ? 1 - Math.min(1, scrollY / distance) : 1;
  };
  addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(update); } }, { passive: true });
  addEventListener('resize', update);
  top.addEventListener('click', () => {
    if (window.rvLenis) window.rvLenis.scrollTo(0);
    else scrollTo({top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
    document.querySelector('.skip')?.focus({preventScroll: true});
  });
  update();
})();
