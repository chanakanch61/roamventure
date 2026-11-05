document.querySelectorAll('[data-hero-slider]').forEach(root => {
 const slides = [...root.querySelectorAll('[data-slide]')];
 if (slides.length < 2) return;
 const controls = root.querySelector('.slider-controls');
 const dots = [...root.querySelectorAll('[data-go]')], reduced = matchMedia('(prefers-reduced-motion: reduce)');
 let index = 0, timer, focused = false, hovered = false;
 controls.hidden = false;
 const schedule = () => { clearTimeout(timer); if (!reduced.matches && !focused && !hovered && !document.hidden && !document.documentElement.classList.contains('rv-motion-paused')) timer = setTimeout(() => show(index + 1), 7000); };
 const show = (next, manual = false) => {
  index = (next + slides.length) % slides.length;
  slides.forEach((slide, i) => { slide.classList.toggle('is-active', i === index); slide.inert = i !== index; slide.setAttribute('aria-hidden', String(i !== index)); });
  dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === index)));
  if (manual) root.querySelector('.slider-status').textContent = `สไลด์ ${index + 1} จาก ${slides.length}`;
  schedule();
 };
 root.querySelector('[data-prev]').addEventListener('click', () => show(index - 1, true));
 root.querySelector('[data-next]').addEventListener('click', () => show(index + 1, true));
 dots.forEach((dot, i) => dot.addEventListener('click', () => show(i, true)));
 root.addEventListener('focusin', () => { focused = true; schedule(); });
 root.addEventListener('focusout', () => { setTimeout(() => { focused = root.contains(document.activeElement); schedule(); }, 0); });
 root.addEventListener('mouseenter', () => { hovered = true; schedule(); });
 root.addEventListener('mouseleave', () => { hovered = false; schedule(); });
 document.addEventListener('rv:motion', schedule);
 root.addEventListener('keydown', event => { if (event.target.closest('.search-container')) return; if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); show(index + (event.key === 'ArrowRight' ? 1 : -1), true); } });
 window.bindSliderDrag(root.querySelector('.home-slides'), direction => show(index + direction, true));
 document.addEventListener('visibilitychange', schedule);
 reduced.addEventListener('change', schedule);
 schedule();
});
