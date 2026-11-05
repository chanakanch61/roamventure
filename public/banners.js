document.querySelectorAll('[data-banner-upload]').forEach(input => {
 let previewUrl;
 const image = input.parentElement.querySelector('.banner-preview');
 const original = image.src;
 input.addEventListener('change', () => {
  if (previewUrl) URL.revokeObjectURL(previewUrl);
  const file = input.files[0];
  input.setCustomValidity(file && (file.size > 5 * 1024 * 1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)) ? 'เลือกรูป JPG, PNG หรือ WebP ไม่เกิน 5 MB' : '');
  if (!input.checkValidity()) { input.reportValidity(); image.src=original; return; }
  image.src=file ? (previewUrl=URL.createObjectURL(file)) : original;
  const size = input.parentElement.querySelector('[data-image-size]');
  image.onload = () => { if (size) size.textContent = file ? `ภาพที่เลือก ${image.naturalWidth} × ${image.naturalHeight} px · ${(file.size / 1024).toFixed(0)} KB` : 'สูงสุด 5 MB · ขนาดภาพเป็นขนาดแนะนำ'; };
 });
});
document.querySelectorAll('[data-hero-form]').forEach(form => form.addEventListener('submit', event => {
 if (event.submitter?.value === 'delete' && !confirm('ลบสไลด์นี้ออกจากหน้าแรก?')) event.preventDefault();
}));
