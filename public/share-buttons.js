// Share rows: shows copy-link and native share only where the browser supports them.
document.querySelectorAll('[data-share]').forEach(row => {
 const url = row.dataset.shareUrl, title = row.dataset.shareTitle, status = row.querySelector('.share-status');
 const copy = row.querySelector('[data-share-copy]'), native = row.querySelector('[data-share-native]');
 let timer;
 const say = text => { status.textContent = text; clearTimeout(timer); timer = setTimeout(() => { status.textContent = ''; }, 2200); };
 // Clipboard API needs HTTPS; plain HTTP falls back to a hidden textarea.
 const fallback = () => {
  const area = Object.assign(document.createElement('textarea'), {value: url, readOnly: true});
  area.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
  document.body.append(area); area.select();
  const ok = document.execCommand('copy'); area.remove();
  return ok ? Promise.resolve() : Promise.reject();
 };
 if (copy) {
  copy.hidden = false;
  copy.addEventListener('click', () => (navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(url) : fallback()).then(() => say('คัดลอกลิงก์แล้ว'), () => say('คัดลอกไม่สำเร็จ')));
 }
 if (native && navigator.share) {
  native.hidden = false;
  native.addEventListener('click', () => navigator.share({title, url}).catch(() => {}));
 }
});
