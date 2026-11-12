// Booking detail: runs the payment flow inside the Payment Summary card (partials/booking_payment).
// Forms post to booking/payment/{token}; with this script the server answers with the re-rendered
// card as JSON, so the page never navigates. Without it, the same forms post and redirect back.
(() => {
 const root = document.querySelector('[data-booking-payment]');
 if (!root || !window.fetch || !window.FormData) return;
 const MAX = 5 * 1024 * 1024;
 const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

 const flow = () => root.querySelector('.pay-flow');
 const enhance = () => flow()?.classList.add('is-enhanced');

 function go(step, focus) {
  const f = flow();
  if (!f) return;
  f.dataset.step = step;
  const target = step === 'slip' ? f.querySelector('.pay-slip input[name=reported_amount]') : f.querySelector('.pay-transfer [data-pay-goto]');
  if (focus) target?.focus({preventScroll: true});
  f.querySelector('.pay-steps')?.scrollIntoView({block: 'nearest', behavior: reduced ? 'auto' : 'smooth'});
 }

 function preview(input) {
  const drop = input.closest('.pay-drop');
  const file = input.files[0];
  const box = drop.querySelector('[data-pay-preview]');
  const label = drop.querySelector('[data-pay-file-label]');
  const bad = file && (!/^image\/(jpeg|png)$/.test(file.type) || file.size > MAX);
  drop.classList.toggle('is-invalid', !!bad);
  if (!file || bad) {
   label.textContent = bad ? 'ใช้ได้เฉพาะ JPG หรือ PNG ไม่เกิน 5 MB · เลือกรูปใหม่' : 'แนบสลิป · แตะเพื่อเลือกรูป';
   box.innerHTML = '<i class="material-icons" aria-hidden="true">upload_file</i>';
   if (bad) input.value = '';
   return;
  }
  label.textContent = file.name + ' · แตะเพื่อเปลี่ยนรูป';
  const img = new Image();
  img.alt = '';
  img.src = URL.createObjectURL(file);
  img.onload = () => URL.revokeObjectURL(img.src);
  box.replaceChildren(img);
 }

 root.addEventListener('click', e => {
  const goto = e.target.closest('[data-pay-goto]');
  if (goto) { e.preventDefault(); go(goto.dataset.payGoto, true); return; }
  const copy = e.target.closest('[data-copy]');
  if (copy) {
   const done = () => { copy.textContent = 'คัดลอกแล้ว'; copy.classList.add('is-copied'); setTimeout(() => { copy.textContent = 'คัดลอก'; copy.classList.remove('is-copied'); }, 1600); };
   const select = () => { const r = document.createRange(); r.selectNodeContents(copy.previousElementSibling); getSelection().removeAllRanges(); getSelection().addRange(r); };
   if (navigator.clipboard) navigator.clipboard.writeText(copy.dataset.copy).then(done, select); else select();
  }
 });

 root.addEventListener('change', e => { if (e.target.matches('.pay-drop input[type=file]')) preview(e.target); });
 root.addEventListener('dragover', e => { const d = e.target.closest('.pay-drop'); if (d) { e.preventDefault(); d.classList.add('is-over'); } });
 root.addEventListener('dragleave', e => e.target.closest('.pay-drop')?.classList.remove('is-over'));
 root.addEventListener('drop', e => {
  const d = e.target.closest('.pay-drop');
  if (!d || !e.dataTransfer.files.length) return;
  e.preventDefault();
  d.classList.remove('is-over');
  const input = d.querySelector('input[type=file]');
  input.files = e.dataTransfer.files;
  preview(input);
 });

 root.addEventListener('submit', async e => {
  const form = e.target.closest('form[data-pay-form]');
  if (!form) return;
  e.preventDefault();
  if (form.dataset.busy) return;
  if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;
  const file = form.querySelector('input[type=file]');
  if (file && !file.files.length) { file.closest('.pay-drop').classList.add('is-invalid'); file.closest('.pay-drop').querySelector('[data-pay-file-label]').textContent = 'กรุณาแนบรูปสลิปก่อนส่ง'; return; }
  const button = form.querySelector('[type=submit]');
  const label = button?.innerHTML;
  form.dataset.busy = '1';
  if (button) { button.disabled = true; button.textContent = 'กำลังส่ง…'; }
  try {
   // getAttribute: the form's <input name="action"> shadows form.action.
   const res = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
   if (!(res.headers.get('content-type') || '').includes('application/json')) throw new Error('not json');
   const data = await res.json();
   root.innerHTML = data.html;
   enhance();
   const f = flow();
   const focus = f?.querySelector('[role=alert]') || f?.querySelector('.pay-done-head, .pay-choice input:checked, .pay-transfer [data-pay-goto]');
   if (focus) { if (!focus.matches('input,a,button')) focus.tabIndex = -1; focus.focus({preventScroll: true}); }
   f?.scrollIntoView({block: 'nearest', behavior: reduced ? 'auto' : 'smooth'});
  } catch (err) {
   // Session expired or the server sent a page instead of JSON: hand the form to the browser as a normal post.
   delete form.dataset.busy;
   if (button) { button.disabled = false; button.innerHTML = label; }
   HTMLFormElement.prototype.submit.call(form);
  }
 });

 enhance();
 if (location.hash === '#payment' || location.hash === '#pay-slip') { const f = flow(); if (f) f.open = true; }
})();
