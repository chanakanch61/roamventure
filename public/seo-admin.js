document.querySelectorAll('[data-seo-form]').forEach(form => {
 const minWidth = Number(form.dataset.width), minHeight = Number(form.dataset.height);
 const shortened = value => value.trim().replace(/\s+/g, ' ');
 form.querySelectorAll('[data-seo-text]').forEach(field => {
  const count = form.querySelector(`[data-seo-count="${field.name}"]`), advice = Number(field.dataset.advice);
  const update = () => {
   const value = shortened(field.value), shown = value || field.dataset.fallback;
   form.querySelectorAll(`[data-seo-preview="${field.name}"]`).forEach(preview => { preview.textContent = shown; });
   count.textContent = value ? `${value.length} ตัวอักษร · แนะนำไม่เกิน ${advice}` : `ใช้ค่าเริ่มต้น · แนะนำไม่เกิน ${advice} ตัวอักษร`;
   count.classList.toggle('is-over', value.length > advice);
  };
  field.addEventListener('input', update);
  update();
 });

 const input = form.elements.image, remove = form.elements.remove_image, image = form.querySelector('[data-seo-image]'), note = form.querySelector('[data-seo-image-note]');
 const saved = image.src, guide = note.textContent;
 let previewUrl = '';
 const show = () => { image.src = previewUrl || (remove?.checked ? image.dataset.fallback : saved); };
 const reject = message => {
  input.setCustomValidity(message);
  input.reportValidity();
  previewUrl = '';
  note.textContent = guide;
  show();
 };
 input.addEventListener('change', () => {
  if (previewUrl) URL.revokeObjectURL(previewUrl);
  previewUrl = '';
  input.setCustomValidity('');
  const file = input.files[0];
  if (!file) { note.textContent = guide; show(); return; }
  if (file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { reject('เลือกรูป JPG, PNG หรือ WebP ไม่เกิน 5 MB'); return; }
  const url = URL.createObjectURL(file), probe = new Image();
  probe.onload = () => {
   if (input.files[0] !== file) return;
   if (probe.naturalWidth < minWidth || probe.naturalHeight < minHeight) { URL.revokeObjectURL(url); reject(`รูปเล็กเกินไป ต้องมีขนาดอย่างน้อย ${minWidth} × ${minHeight} px (รูปนี้ ${probe.naturalWidth} × ${probe.naturalHeight} px)`); return; }
   previewUrl = url;
   note.textContent = `ภาพที่เลือก ${probe.naturalWidth} × ${probe.naturalHeight} px · ระบบจะครอปกึ่งกลางเป็น ${minWidth} × ${minHeight} px ตามตัวอย่าง`;
   show();
  };
  probe.onerror = () => { URL.revokeObjectURL(url); reject('อ่านไฟล์รูปไม่ได้ กรุณาเลือกรูปอื่น'); };
  probe.src = url;
 });
 remove?.addEventListener('change', show);
});
