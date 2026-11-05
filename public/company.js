(() => {
 'use strict';
 const root = document.querySelector('.rv-company');
 if (!root) return;
 const reduced = matchMedia('(prefers-reduced-motion: reduce)');
 const reveal = element => element.classList.remove('rv-waiting');
 if ('IntersectionObserver' in window) {
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
   if (entry.isIntersecting) { reveal(entry.target); observer.unobserve(entry.target); }
  }), {threshold: 0.04});
  root.querySelectorAll('[data-rv-reveal]').forEach(element => {
   if (!reduced.matches && element.getBoundingClientRect().top > innerHeight) element.classList.add('rv-waiting');
   observer.observe(element);
  });
  const links = [...root.querySelectorAll('.rv-section-nav a')];
  const sections = new IntersectionObserver(entries => entries.forEach(entry => {
   if (!entry.isIntersecting) return;
   links.forEach(link => {
    if (link.hash === '#' + entry.target.id) link.setAttribute('aria-current', 'location');
    else link.removeAttribute('aria-current');
   });
  }), {rootMargin:'-15% 0px -55% 0px', threshold:0});
  links.forEach(link => { const section = document.querySelector(link.hash); if (section) sections.observe(section); });
 }
 root.addEventListener('focusin', event => { const element = event.target.closest('[data-rv-reveal]'); if (element) reveal(element); });
 const showAll = () => root.querySelectorAll('.rv-waiting').forEach(reveal);
 reduced.addEventListener('change', showAll);
 addEventListener('beforeprint', showAll);
 const hero = root.querySelector('.rv-company-hero');
 if (hero) {
  let queued = false;
  const update = () => {
   queued = false;
   if (reduced.matches) { hero.style.removeProperty('--rv-parallax'); return; }
   const rect = hero.getBoundingClientRect();
   if (rect.bottom > 0) hero.style.setProperty('--rv-parallax', Math.min(35, Math.max(0, -rect.top * .07)) + 'px');
  };
  addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(update); } }, {passive:true});
 }
 if (matchMedia('(hover: hover) and (pointer: fine)').matches) root.querySelectorAll('.rv-channel').forEach(card => {
  card.addEventListener('pointermove', event => {
   if (reduced.matches) return;
   const rect = card.getBoundingClientRect();
   card.style.setProperty('--spot-x', event.clientX - rect.left + 'px');
   card.style.setProperty('--spot-y', event.clientY - rect.top + 'px');
  }, {passive:true});
 });
 async function copy(text, status) {
  try {
   if (navigator.clipboard && isSecureContext) await navigator.clipboard.writeText(text);
   else {
    const previous = document.activeElement;
    const textarea = document.createElement('textarea');
    textarea.value = text; textarea.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none';
    document.body.append(textarea); textarea.focus(); textarea.select();
    let success = false;
    try { success = document.execCommand('copy'); } finally { textarea.remove(); previous?.focus({preventScroll:true}); }
    if (!success) throw new Error('Clipboard unavailable');
   }
   status.textContent = 'คัดลอกแล้ว พร้อมนำไปวางในข้อความของคุณ';
  } catch (_) { status.textContent = 'คัดลอกอัตโนมัติไม่ได้ กรุณาเลือกและคัดลอกข้อความที่แสดงด้วยตนเอง'; }
 }
 root.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', () => copy(button.dataset.copy, document.getElementById('copy-status'))));
 const form = document.getElementById('rv-trip-form');
 if (!form) return;
 const preview = document.getElementById('rv-message-preview');
 const status = document.getElementById('rv-form-status');
 const getMessage = () => {
  const data = new FormData(form);
  const value = name => String(data.get(name) || '').trim();
  return ['สวัสดีค่ะ/ครับ สนใจสอบถาม ' + data.get('topic'),
   value('destination') ? 'จุดหมาย / โปรแกรม / คำขอ: ' + value('destination') : '',
   data.get('travel_date') ? 'วันเดินทางที่สนใจ: ' + data.get('travel_date') : '',
   data.get('travelers') ? 'จำนวนผู้เดินทาง: ' + data.get('travelers') + ' คน' : '',
   value('message') ? 'รายละเอียดเพิ่มเติม: ' + value('message') : '',
   'ขอทราบรายละเอียดและเงื่อนไขเพิ่มเติม ขอบคุณค่ะ/ครับ'].filter(Boolean).join('\n');
 };
 const update = () => {
  preview.textContent = getMessage();
  document.getElementById('rv-char-count').textContent = form.elements.message.value.length.toLocaleString('th-TH');
  status.textContent = '';
 };
 form.addEventListener('input', update);
 form.addEventListener('change', update);
 document.getElementById('rv-copy-message').addEventListener('click', () => { if (form.reportValidity()) copy(getMessage(), status); });
 form.addEventListener('submit', event => {
  event.preventDefault();
  if (!form.reportValidity()) return;
  const channel = event.submitter?.value || (form.dataset.line ? 'line' : 'email');
  const message = encodeURIComponent(getMessage());
  if (channel === 'line' && form.dataset.line) {
   const id = form.dataset.line;
   // Official IDs support prefilled OA messages; personal LINE IDs only support opening the profile.
   const url = id.startsWith('@') ? 'https://line.me/R/oaMessage/' + encodeURIComponent(id) + '/?' + message : 'https://line.me/R/ti/p/' + encodeURIComponent(id);
   window.open(url, '_blank', 'noopener,noreferrer');
   form.dispatchEvent(new CustomEvent('rv:contact', {bubbles:true,detail:{channel:'line'}}));
   status.textContent = id.startsWith('@') ? 'เปิด LINE แล้ว กรุณาตรวจสอบผู้รับและกดส่งในแอป หากแอปไม่เปิด ให้คัดลอกข้อความและใช้ลิงก์ LINE ด้านบน' : 'เปิด LINE แล้ว กรุณาคัดลอกข้อความไปวางในแชตและกดส่งด้วยตนเอง';
  } else if (channel === 'email' && form.dataset.email) {
   window.location.href = 'mailto:' + form.dataset.email + '?subject=' + encodeURIComponent('สอบถามการเดินทางกับ RoamVenture') + '&body=' + message;
   form.dispatchEvent(new CustomEvent('rv:contact', {bubbles:true,detail:{channel:'email'}}));
   status.textContent = 'เรียกแอปอีเมลแล้ว กรุณาตรวจสอบและกดส่งอีกครั้ง หากไม่มีแอปอีเมล ให้คัดลอกข้อความและอีเมลด้านบน';
  }
 });
 update(); form.hidden = false;
})();
