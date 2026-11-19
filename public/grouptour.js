(() => {
 'use strict';
 const mobile = matchMedia('(max-width: 800px)');
 const reducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

 // Past groups: filter cards by group type without reloading.
 const filter = document.querySelector('[data-gt-filter]');
 const cards = [...document.querySelectorAll('[data-gt-trips] .gt-trip')];
 const empty = document.querySelector('[data-gt-empty]');
 if (filter) filter.addEventListener('click', event => {
  const button = event.target.closest('button[data-type]');
  if (!button) return;
  filter.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b === button)));
  let shown = 0;
  cards.forEach(card => { const match = !button.dataset.type || card.dataset.type === button.dataset.type; card.hidden = !match; if (match) shown++; });
  if (empty) empty.hidden = shown > 0;
 });

 // Section nav sticks under the fixed header, whose height and top offset change with screen size and the announcement bar.
 const sectionNav = document.querySelector('.gt-page .rv-section-nav');
 const header = document.querySelector('.site-header');
 const requestSection = document.getElementById('request');
 if (sectionNav) {
  const root = document.documentElement;
  let queued = false;
  const update = () => {
   queued = false;
   const top = header ? Math.max(0, Math.round(header.getBoundingClientRect().bottom)) : 0;
   root.style.setProperty('--gt-sticky-top', top + 'px');
   root.style.setProperty('--gt-nav-height', (sectionNav.classList.contains('is-away') ? 0 : sectionNav.offsetHeight) + 'px');
   sectionNav.classList.toggle('is-stuck', sectionNav.getBoundingClientRect().top <= top + 1 && window.scrollY > 0);
  };
  const schedule = () => { if (!queued) { queued = true; requestAnimationFrame(update); } };
  update();
  addEventListener('scroll', schedule, {passive:true});
  addEventListener('resize', schedule);
  addEventListener('pageshow', schedule);
  // On narrow screens the links scroll sideways: keep the current section's link in view.
  const strip = sectionNav.firstElementChild;
  new MutationObserver(() => {
   const link = sectionNav.querySelector('a[aria-current]');
   if (!link || strip.scrollWidth <= strip.clientWidth) return;
   strip.scrollTo({left: strip.scrollLeft + link.getBoundingClientRect().left - strip.getBoundingClientRect().left - (strip.clientWidth - link.offsetWidth) / 2, behavior: reducedMotion() ? 'auto' : 'smooth'});
  }).observe(sectionNav, {subtree:true, attributes:true, attributeFilter:['aria-current']});
  // On phones the nav steps aside while the form is on screen, so the fields get the whole height.
  if (requestSection && 'IntersectionObserver' in window) {
   let inForm = false;
   const apply = () => {
    const away = inForm && mobile.matches;
    sectionNav.classList.toggle('is-away', away);
    // Floating chat / back-to-top buttons would cover the steppers and the Next button.
    document.body.classList.toggle('gt-form-focus', away);
    schedule();
   };
   new IntersectionObserver(entries => { inForm = entries[0].isIntersecting; apply(); }, {rootMargin:'-35% 0px -35% 0px'}).observe(requestSection);
   mobile.addEventListener('change', apply);
  }
 }

 const form = document.getElementById('gt-form');
 if (!form) return;
 const error = document.getElementById('gt-form-error');
 const from = form.elements.date_from;
 const to = form.elements.date_to;
 const syncDates = () => { if (from.value) to.min = from.value; };
 from.addEventListener('change', syncDates);
 syncDates();

 // −/+ buttons around head counts and days: easier than typing on a phone keyboard.
 form.querySelectorAll('input[data-stepper]').forEach(input => {
  const wrap = document.createElement('span');
  wrap.className = 'gt-stepper';
  input.replaceWith(wrap);
  const button = (sign, text) => {
   const b = document.createElement('button');
   b.type = 'button';
   b.textContent = text;
   b.setAttribute('aria-label', (sign < 0 ? 'ลด' : 'เพิ่ม') + input.dataset.stepper);
   b.addEventListener('click', () => {
    const min = Number(input.min || 0);
    const max = Number(input.max || 9999);
    const current = input.value === '' ? (sign > 0 ? min - 1 : min) : Number(input.value);
    input.value = String(Math.min(max, Math.max(min, current + sign)));
    input.dispatchEvent(new Event('input', {bubbles:true}));
   });
   return b;
  };
  wrap.append(button(-1, '−'), input, button(1, '+'));
 });

 const fieldLabel = el => {
  const label = (el.labels && el.labels[0]) || el.closest('label');
  return ((label && label.firstChild && label.firstChild.textContent) || el.name).trim();
 };
 const messageFor = el => el.name === 'consent' ? 'กรุณายืนยันการใช้ข้อมูลก่อนส่งคำขอ'
  : el.validity.valueMissing ? 'กรุณากรอก “' + fieldLabel(el) + '”'
  : el.name === 'phone' ? 'เบอร์โทรศัพท์ต้องมี 8–25 ตัว ใช้ได้เฉพาะตัวเลขและ + ( ) -'
  : el.name === 'email' ? 'รูปแบบอีเมลไม่ถูกต้อง'
  : 'กรุณาตรวจสอบ “' + fieldLabel(el) + '”';
 const showError = (message, field) => {
  error.textContent = message;
  error.hidden = false;
  if (field) { field.setAttribute('aria-invalid', 'true'); field.focus({preventScroll:false}); }
 };
 form.addEventListener('input', event => { event.target.removeAttribute('aria-invalid'); if (!error.hidden) error.hidden = true; });

 // On phones the form runs as four short steps; on wider screens every section shows at once.
 const steps = [...form.querySelectorAll('.gt-fieldset')];
 const progress = form.querySelector('.gt-progress');
 const stepNow = form.querySelector('[data-step-now]');
 const bars = [...form.querySelectorAll('.gt-progress-bar li')];
 const nav = form.querySelector('.gt-step-nav');
 const back = form.querySelector('[data-step-back]');
 const next = form.querySelector('[data-step-next]');
 const end = form.querySelector('.gt-form-end');
 const panel = form.closest('.gt-form-panel');
 let current = 0;
 const wizard = () => mobile.matches;
 const render = () => {
  const on = wizard();
  form.classList.toggle('is-wizard', on);
  progress.hidden = nav.hidden = !on;
  steps.forEach((step, i) => { step.hidden = on && i !== current; });
  end.hidden = on && current !== steps.length - 1;
  back.hidden = current === 0;
  next.hidden = current === steps.length - 1;
  stepNow.textContent = String(current + 1);
  bars.forEach((bar, i) => bar.classList.toggle('is-done', i <= current));
 };
 const stepOf = el => steps.findIndex(step => step.contains(el));
 const checkStep = i => {
  const invalid = [...steps[i].elements].find(el => el.willValidate && !el.checkValidity());
  if (invalid) { showError(messageFor(invalid), invalid); return false; }
  if (steps[i].contains(to)) {
   if (to.value && !from.value) { showError('กรุณาระบุวันเริ่มเดินทางคู่กับวันกลับ', from); return false; }
   if (to.value && from.value && to.value < from.value) { showError('วันกลับต้องไม่ก่อนวันเริ่มเดินทาง', to); return false; }
  }
  return true;
 };
 const go = i => {
  current = Math.max(0, Math.min(steps.length - 1, i));
  error.hidden = true;
  render();
  panel.scrollIntoView({block:'start', behavior: reducedMotion() ? 'auto' : 'smooth'});
  // Focus the step itself, not a field, so the phone keyboard does not jump up on its own.
  steps[current].focus({preventScroll:true});
 };
 next.addEventListener('click', () => { if (checkStep(current)) go(current + 1); });
 back.addEventListener('click', () => go(current - 1));
 // A server-side error (reCAPTCHA, rate limit) comes back with every field kept: reopen at the last step.
 if (!error.hidden && form.elements.contact_name.value) current = steps.length - 1;
 render();
 mobile.addEventListener('change', render);

 form.addEventListener('submit', function (event) {
  // Enter on an earlier step moves forward instead of sending a half-filled form.
  if (wizard() && current < steps.length - 1) { event.preventDefault(); next.click(); return; }
  error.hidden = true;
  // The form is novalidate so messages come out in Thai and in one place.
  for (let i = 0; i < steps.length; i++) {
   if (!checkStep(i)) {
    event.preventDefault();
    if (wizard() && i !== current) { const message = error.textContent; current = i; render(); showError(message, [...steps[i].elements].find(el => el.getAttribute('aria-invalid') === 'true')); }
    return;
   }
  }
  const consent = this.elements.consent;
  if (!consent.checked) { event.preventDefault(); showError(messageFor(consent), consent); return; }

  const button = this.querySelector('button[type="submit"]');
  const siteKey = this.dataset.recaptcha;
  if (siteKey) {
   // reCAPTCHA v3 tokens last two minutes, so one is fetched at send time and the form is then sent natively.
   // Without a token the form still goes: the server turns it away with a message and keeps the typed details.
   event.preventDefault();
   if (this.dataset.sending) return;
   this.dataset.sending = '1';
   const send = token => { this.elements.recaptcha_token.value = typeof token === 'string' ? token : ''; this.submit(); };
   if (window.grecaptcha) grecaptcha.ready(() => grecaptcha.execute(siteKey, {action: 'grouptour'}).then(send, () => send('')));
   else send('');
  }
  button.disabled = true;
  button.textContent = button.dataset.busy || 'กำลังส่ง…';
 });
 window.addEventListener('pageshow', () => {
  delete form.dataset.sending;
  const button = form.querySelector('button[type="submit"]');
  if (button.disabled) { button.disabled = false; button.innerHTML = 'ส่งคำขอจัดกรุ๊ป <i class="material-icons" aria-hidden="true">send</i>'; }
 });
})();
