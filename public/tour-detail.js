(() => {
 const root = document.querySelector('.trip-detail');
 if (!root) return;
 const header = document.querySelector('.site-header');
 const measure = () => root.style.setProperty('--trip-header-height', `${header?.getBoundingClientRect().height || 0}px`);
 measure();
 if (header) new ResizeObserver(measure).observe(header);
 const dialog = root.querySelector('.trip-lightbox');
 const photos = JSON.parse(root.querySelector('#trip-photos').textContent);
 let current = 0, activePhotos = photos;
 const show = index => {
  current = (index + activePhotos.length) % activePhotos.length;
  const photo = activePhotos[current], img = root.querySelector('#photo-full');
  img.src = photo.src; img.alt = photo.caption;
  root.querySelector('#photo-count').textContent = `${current + 1} / ${activePhotos.length}`;
  root.querySelector('#photo-caption').textContent = photo.caption;
 };
 root.querySelectorAll('[data-photo]').forEach(button => button.addEventListener('click', event => {
  event.preventDefault();
  const group = button.closest('[data-photo-group]');
  const indexes = group ? JSON.parse(group.dataset.photoGroup) : photos.map((_,i)=>i);
  activePhotos = indexes.map(i=>photos[i]);
  show(Math.max(0,indexes.indexOf(Number(button.dataset.photo))));
  dialog.querySelectorAll('[data-gallery-prev],[data-gallery-next]').forEach(control=>control.hidden=activePhotos.length<2);
  dialog.showModal(); document.body.classList.add('trip-gallery-open');
 }));
 dialog.addEventListener('close', () => document.body.classList.remove('trip-gallery-open'));
 dialog.querySelector('[data-gallery-close]').addEventListener('click', () => dialog.close());
 dialog.querySelector('[data-gallery-prev]').addEventListener('click', () => show(current - 1));
 dialog.querySelector('[data-gallery-next]').addEventListener('click', () => show(current + 1));
 dialog.querySelectorAll('[data-gallery-prev],[data-gallery-next]').forEach(button => button.hidden = photos.length < 2);
 const stage=dialog.querySelector('.trip-lightbox-stage');
 window.bindSliderDrag(stage, direction => show(current + direction));
 dialog.addEventListener('keydown', event => {
  if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); show(current + (event.key === 'ArrowRight' ? 1 : -1)); }
 });
 dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
 const days = [...root.querySelectorAll('.itinerary details')], toggle = root.querySelector('#itinerary-toggle');
 const syncDays = () => { const all = days.every(day => day.open); toggle.innerHTML = (all ? 'ปิดทุกวัน' : 'เปิดทุกวัน') + ' <i class="material-icons" aria-hidden="true">' + (all ? 'remove' : 'add') + '</i>'; toggle.setAttribute('aria-expanded', String(all)); };
 toggle.hidden = !days.length;
 toggle.addEventListener('click', () => { const open = !days.every(day => day.open); days.forEach(day => day.open = open); syncDays(); });
 days.forEach(day => day.addEventListener('toggle', syncDays)); syncDays();
 const revealCoverage = hash => { const target = hash.length > 1 ? document.getElementById(hash.slice(1)) : null; if (target?.matches('.trip-accordion')) target.querySelectorAll('details').forEach(item => item.open = true); };
 root.querySelectorAll('.trip-nav a').forEach(link => link.addEventListener('click', () => revealCoverage(link.hash)));
 window.addEventListener('hashchange', () => revealCoverage(location.hash));
 revealCoverage(location.hash);
 // Below the panel breakpoint the booking form is a sheet, so it opens over the page instead of being scrolled to.
 const panel = root.querySelector('#booking'), sheetQuery = matchMedia('(max-width:900px)');
 let sheetOpener = null;
 root.classList.add('has-booking-sheet');
 const setPageInert = inert => { for (let node = panel; node.parentElement && node !== document.body; node = node.parentElement) [...node.parentElement.children].forEach(sibling => { if (sibling !== node && !['SCRIPT','STYLE','LINK'].includes(sibling.tagName)) sibling.inert = inert; }); };
 const closeSheet = () => {
  if (!panel.classList.contains('is-open')) return;
  panel.classList.remove('is-open'); panel.removeAttribute('role'); panel.removeAttribute('aria-modal');
  setPageInert(false); document.body.classList.remove('trip-gallery-open');
  sheetOpener?.focus({preventScroll:true}); sheetOpener = null;
 };
 const openBooking = opener => {
  if (!sheetQuery.matches) { panel.scrollIntoView({block:'nearest'}); return; }
  if (panel.classList.contains('is-open')) return;
  sheetOpener = opener || document.activeElement;
  panel.classList.add('is-open'); panel.setAttribute('role','dialog'); panel.setAttribute('aria-modal','true');
  setPageInert(true); document.body.classList.add('trip-gallery-open');
  // A hidden element cannot take focus, so apply the open styles first.
  panel.getBoundingClientRect();
  panel.querySelector('[data-booking-close]').focus({preventScroll:true});
 };
 root.querySelectorAll('[data-booking-open]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); openBooking(link); }));
 panel.querySelector('[data-booking-close]').addEventListener('click', closeSheet);
 document.addEventListener('rv:booking-open', () => openBooking());
 document.addEventListener('keydown', event => { if (event.key === 'Escape' && panel.classList.contains('is-open')) closeSheet(); });
 // composedPath, not contains(): the calendar redraws during a click, leaving event.target detached from the panel.
 document.addEventListener('click', event => { if (panel.classList.contains('is-open') && !event.composedPath().includes(panel) && !event.target.closest('[data-booking-open],[data-ticket-help],.trip-departure-button')) closeSheet(); });
 sheetQuery.addEventListener('change', () => { if (!sheetQuery.matches) closeSheet(); });
 const openBookingHash = () => { if (location.hash === '#booking') openBooking(); };
 window.addEventListener('hashchange', openBookingHash);
 openBookingHash();
 const select = root.querySelector('#departure-select');
 if (select && !root.querySelector('#private-date')) {
  const rows = [...root.querySelectorAll('[data-departure]')];
  rows.forEach(row => {
   const option = [...select.options].find(option => option.value === row.dataset.departure);
   if (!option || option.disabled) return;
   const button = document.createElement('button'); button.type = 'button'; button.className = 'trip-departure-button'; button.innerHTML = 'เลือกรอบนี้ <i class="material-icons" aria-hidden="true">arrow_forward</i>';
   button.setAttribute('aria-label', `เลือกรอบ ${option.textContent}`);
   row.lastElementChild.append(button);
   button.addEventListener('click', () => { document.dispatchEvent(new Event('rv:booking-step-one')); select.value = row.dataset.departure; select.dispatchEvent(new Event('change', {bubbles:true})); document.dispatchEvent(new Event('rv:booking-open')); select.focus({preventScroll:true}); });
  });
  select.addEventListener('change', () => rows.forEach(row => row.classList.toggle('is-selected', row.dataset.departure === select.value)));
 }
 const links = [...root.querySelectorAll('.trip-nav a')];
 const sections = links.map(link => root.querySelector(link.getAttribute('href'))).filter(Boolean);
 const observer = new IntersectionObserver(entries => {
  const visible = entries.filter(entry => entry.isIntersecting).sort((a,b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
  if (!visible) return;
  links.forEach(link => { if (link.hash === `#${visible.target.id}`) link.setAttribute('aria-current','location'); else link.removeAttribute('aria-current'); });
 }, {rootMargin:'-25% 0px -50% 0px'});
 sections.forEach(section => observer.observe(section));
 const slider = root.querySelector('.related-slider');
 if (slider) {
  const controls = root.querySelector('.related-slider-controls');
  const previous = controls.querySelector('[data-related-prev]');
  const next = controls.querySelector('[data-related-next]');
  const cards = [...slider.children];
  const step = () => cards[0].getBoundingClientRect().width + parseFloat(getComputedStyle(slider).columnGap);
  const syncSlider = () => {
   previous.disabled = slider.scrollLeft <= 2;
   next.disabled = slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 2;
   const first = Math.round(slider.scrollLeft / step()) + 1;
   const visible = Math.max(1, Math.round(slider.clientWidth / step()));
   controls.querySelector('.related-slider-status').textContent = `${first}–${Math.min(cards.length, first + visible - 1)} / ${cards.length} ทัวร์`;
  };
  const move = direction => slider.scrollBy({left: direction * step(), behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
  previous.addEventListener('click', () => move(-1));
  next.addEventListener('click', () => move(1));
  slider.addEventListener('keydown', event => {
   if (event.target !== slider || !['ArrowLeft','ArrowRight'].includes(event.key)) return;
   event.preventDefault(); move(event.key === 'ArrowRight' ? 1 : -1);
  });
  slider.addEventListener('scroll', syncSlider, {passive:true});
  new ResizeObserver(syncSlider).observe(slider);
  controls.hidden = false;
  syncSlider();
 }
})();
