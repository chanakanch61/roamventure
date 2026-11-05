document.querySelectorAll('[data-home-journeys]').forEach(root => {
 const tabs = [...root.querySelectorAll('[role=tab]')];
 const panels = [...root.querySelectorAll('[role=tabpanel]')];
 const tracks = panels.map(panel => panel.querySelector('.journey-track'));
 const links = [...root.querySelectorAll('[data-journey-all]')];
 const controls = root.querySelector('[data-journey-controls]');
 const status = root.querySelector('[data-journey-status]');
 const blurb = root.querySelector('[data-journey-blurb]');
 const layout = root.querySelector('.journey-layout');
 const reduced = matchMedia('(prefers-reduced-motion: reduce)'), wide = matchMedia('(min-width: 961px)');
 let active = 0, timer, focused = false, hovered = false, inView = false;
 const step = track => track.firstElementChild.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap);
 const sync = () => {
  const track = tracks[active], size = step(track);
  controls.hidden = track.scrollWidth <= track.clientWidth + 2;
  const first = Math.round(track.scrollLeft / size) + 1;
  const visible = Math.max(1, Math.round(track.clientWidth / size));
  const total = track.querySelectorAll('.tour-card').length;
  status.textContent = `${visible > 1 ? `${first}–${Math.min(total, first + visible - 1)}` : first} / ${total}`;
 };
 const move = (direction, manual = true) => {
  const track = tracks[active], max = track.scrollWidth - track.clientWidth;
  let left = track.scrollLeft + direction * step(track);
  if (left > max + 4) left = 0;
  if (left < -4) left = max;
  status.setAttribute('aria-live', manual ? 'polite' : 'off');
  track.scrollTo({left, behavior:reduced.matches ? 'instant' : 'smooth'});
 };
 const schedule = () => {
  clearTimeout(timer);
  if (reduced.matches || !wide.matches || !inView || focused || hovered || document.hidden || document.documentElement.classList.contains('rv-motion-paused')) return;
  timer = setTimeout(() => { if (!controls.hidden) move(1, false); schedule(); }, 4500);
 };
 const show = next => {
  active = (next + tabs.length) % tabs.length;
  tabs.forEach((tab, i) => { tab.setAttribute('aria-selected', String(i === active)); tab.tabIndex = i === active ? 0 : -1; });
  panels.forEach((panel, i) => { panel.hidden = i !== active; });
  links.forEach((link, i) => { link.hidden = i !== active; });
  blurb.textContent = tabs[active].dataset.blurb;
  tracks[active].scrollLeft = 0;
  sync();
  schedule();
 };
 tabs.forEach((tab, i) => tab.addEventListener('click', () => show(i)));
 root.querySelector('[role=tablist]').addEventListener('keydown', event => {
  const target = {ArrowDown:active + 1, ArrowRight:active + 1, ArrowUp:active - 1, ArrowLeft:active - 1, Home:0, End:tabs.length - 1}[event.key];
  if (target === undefined) return;
  event.preventDefault();
  show(target);
  tabs[active].focus();
  tabs[active].scrollIntoView({block:'nearest', inline:'nearest'});
 });
 root.querySelector('[data-journey-prev]').addEventListener('click', () => move(-1));
 root.querySelector('[data-journey-next]').addEventListener('click', () => move(1));
 tracks.forEach(track => {
  // The curtain reveal suits scrolling down the page, not cards sliding in sideways.
  const edge = track.getBoundingClientRect().right;
  [...track.children].forEach(card => { if (card.getBoundingClientRect().left >= edge) card.querySelector('.rv-curtain-pending')?.classList.remove('rv-curtain-pending'); });
  window.bindSliderDrag(track);
  track.addEventListener('scroll', sync, {passive:true});
  track.addEventListener('keydown', event => {
   if (event.target !== track || !['ArrowLeft','ArrowRight'].includes(event.key)) return;
   event.preventDefault(); move(event.key === 'ArrowRight' ? 1 : -1);
  });
 });
 layout.addEventListener('focusin', () => { focused = true; schedule(); });
 layout.addEventListener('focusout', () => { setTimeout(() => { focused = layout.contains(document.activeElement); schedule(); }, 0); });
 layout.addEventListener('mouseenter', () => { hovered = true; schedule(); });
 layout.addEventListener('mouseleave', () => { hovered = false; schedule(); });
 new IntersectionObserver(entries => { inView = entries[0].isIntersecting; schedule(); }).observe(layout);
 new ResizeObserver(sync).observe(layout);
 document.addEventListener('rv:motion', schedule);
 document.addEventListener('visibilitychange', schedule);
 reduced.addEventListener('change', schedule);
 wide.addEventListener('change', schedule);
 sync();
});
