// Shared pointer gestures; native touch scrolling stays enabled on scroll tracks.
window.bindSliderDrag = (element, onSwipe = null) => {
 let gesture = null, suppressClickUntil = 0;
 element.classList.add('drag-slider');
 const finish = event => {
  if (!gesture || event.pointerId !== gesture.id) return;
  const current = gesture;
  gesture = null;
  element.classList.remove('is-dragging');
  if (element.hasPointerCapture(current.id)) element.releasePointerCapture(current.id);
  if (!current.dragged) return;
  suppressClickUntil = Date.now() + 400;
  if (event.type !== 'pointerup') return;
  if (onSwipe) {
   if (Math.abs(current.dx) >= 50) onSwipe(current.dx < 0 ? 1 : -1);
  } else {
   const cards = [...element.children];
   const origin = cards[0]?.getBoundingClientRect().left;
   const positions = cards.map(card => card.getBoundingClientRect().left - origin);
   const nearest = positions.reduce((best, position) => Math.abs(position - element.scrollLeft) < Math.abs(best - element.scrollLeft) ? position : best, 0);
   element.scrollTo({left:nearest, behavior:matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
  }
 };
 element.addEventListener('pointerdown', event => {
  if (!event.isPrimary || event.button !== 0 || (!onSwipe && event.pointerType === 'touch')) return;
  if (event.target.closest('button,input,select,textarea,summary,[contenteditable="true"]')) return;
  if (!onSwipe && element.scrollWidth <= element.clientWidth + 1) return;
  suppressClickUntil = 0;
  gesture = {id:event.pointerId,x:event.clientX,y:event.clientY,left:element.scrollLeft,dx:0,dragged:false};
 });
 element.addEventListener('pointermove', event => {
  if (!gesture || event.pointerId !== gesture.id) return;
  const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
  if (!gesture.dragged) {
   if (Math.abs(dy) > 8 && Math.abs(dy) > Math.abs(dx)) { gesture = null; return; }
   if (Math.abs(dx) < 8) return;
   gesture.dragged = true;
   element.setPointerCapture(event.pointerId);
   element.classList.add('is-dragging');
  }
  event.preventDefault();
  gesture.dx = dx;
  if (!onSwipe) element.scrollLeft = gesture.left - dx;
 });
 element.addEventListener('pointerup', finish);
 element.addEventListener('pointercancel', finish);
 element.addEventListener('lostpointercapture', event => { if (event.target === element) finish(event); });
 element.addEventListener('pointerleave', () => { if (gesture && !gesture.dragged) gesture = null; });
 element.addEventListener('dragstart', event => { if (gesture) event.preventDefault(); });
 element.addEventListener('click', event => {
  if (Date.now() < suppressClickUntil) { event.preventDefault(); event.stopImmediatePropagation(); }
 }, true);
};
document.querySelectorAll('.related-slider,.trip-nav > .container,.home-journal-track').forEach(element => window.bindSliderDrag(element));
