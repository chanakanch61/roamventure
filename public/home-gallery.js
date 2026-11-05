/* Progressive gallery: native scrolling and album links work without this file. */
(() => {
 'use strict';
 const root=document.querySelector('[data-home-gallery]');if(!root)return;
 const track=root.querySelector('.hpg-track'),cards=[...track.children],reduced=matchMedia('(prefers-reduced-motion: reduce)'),fine=matchMedia('(hover: hover) and (pointer: fine)');
 const nav=root.querySelector('.hpg-navigation'),currentLabel=root.querySelector('[data-hpg-current]'),progress=root.querySelector('.hpg-progress>span'),live=root.querySelector('.hpg-live');
 let current=0,scrollFrame=0,drag=null,suppressUntil=0;
 const max=()=>Math.max(0,track.scrollWidth-track.clientWidth);
 const offset=i=>Math.min(max(),Math.max(0,cards[i].offsetLeft-cards[0].offsetLeft));
 const sync=()=>{
  scrollFrame=0;let nearest=0;
  cards.forEach((card,i)=>{if(Math.abs(offset(i)-track.scrollLeft)<Math.abs(offset(nearest)-track.scrollLeft)-1)nearest=i;});
  current=nearest;currentLabel.textContent=String(current+1).padStart(2,'0');
  const visible=Math.min(1,track.clientWidth/track.scrollWidth),ratio=max()?track.scrollLeft/max():0;
  progress.style.transform=`scaleX(${visible+(1-visible)*ratio})`;
  nav.hidden=max()<2;
 };
 const go=(i,announce=true)=>{
  i=Math.max(0,Math.min(cards.length-1,i));
  track.scrollTo({left:offset(i),behavior:reduced.matches?'instant':'smooth'});
  if(announce)live.textContent=`ภาพที่ ${i+1} จาก ${cards.length}`;
 };
 root.querySelector('[data-hpg-prev]').addEventListener('click',()=>go(track.scrollLeft<3?cards.length-1:current-1));
 root.querySelector('[data-hpg-next]').addEventListener('click',()=>go(track.scrollLeft>=max()-3?0:current+1));
 track.addEventListener('scroll',()=>{if(!scrollFrame)scrollFrame=requestAnimationFrame(sync);},{passive:true});
 track.addEventListener('keydown',event=>{
  if(event.key==='ArrowRight'||event.key==='ArrowLeft'){event.preventDefault();go(event.key==='ArrowRight'?(track.scrollLeft>=max()-3?0:current+1):(track.scrollLeft<3?cards.length-1:current-1));}
  else if(event.key==='Home'||event.key==='End'){event.preventDefault();go(event.key==='Home'?0:cards.length-1);}
 });
 track.addEventListener('pointerdown',event=>{if(event.pointerType!=='mouse'||event.button!==0)return;drag={id:event.pointerId,x:event.clientX,y:event.clientY,left:track.scrollLeft,moved:false};});
 track.addEventListener('pointermove',event=>{
  if(!drag||drag.id!==event.pointerId)return;const dx=event.clientX-drag.x,dy=event.clientY-drag.y;
  if(!drag.moved&&Math.abs(dy)>8&&Math.abs(dy)>Math.abs(dx)){drag=null;return;}
  if(!drag.moved&&Math.abs(dx)>7){drag.moved=true;track.setPointerCapture(event.pointerId);track.classList.add('is-dragging');}
  if(drag.moved){event.preventDefault();track.scrollLeft=drag.left-dx;}
 });
 const finish=event=>{
  if(!drag||drag.id!==event.pointerId)return;const moved=drag.moved;drag=null;
  track.classList.remove('is-dragging');if(track.hasPointerCapture(event.pointerId))track.releasePointerCapture(event.pointerId);
  if(moved){suppressUntil=Date.now()+400;sync();if(event.type==='pointerup')go(current,false);}
 };
 track.addEventListener('pointerup',finish);track.addEventListener('pointercancel',finish);track.addEventListener('lostpointercapture',finish);
 track.addEventListener('pointerleave',()=>{if(drag&&!drag.moved)drag=null;});
 track.addEventListener('dragstart',event=>event.preventDefault());
 track.addEventListener('click',event=>{if(Date.now()<suppressUntil){event.preventDefault();event.stopImmediatePropagation();}},true);
 // Tabbing to a card brings its native buttons and link into view.
 track.addEventListener('focusin',event=>{const index=cards.findIndex(card=>card.contains(event.target));if(index<0)return;const rect=cards[index].getBoundingClientRect();if(rect.left<0||rect.right>innerWidth)go(index,false);});
 let lastWidth=track.clientWidth;
 if('ResizeObserver'in window)new ResizeObserver(()=>{if(track.clientWidth!==lastWidth){lastWidth=track.clientWidth;track.scrollTo({left:offset(current),behavior:'instant'});}sync();}).observe(track);
 sync();
 if('IntersectionObserver'in window){
  root.classList.add('hpg-ready');
  const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.remove('hpg-pending');observer.unobserve(entry.target);}}),{threshold:.08});
  root.querySelectorAll('[data-hpg-reveal]').forEach(element=>{if(!reduced.matches)element.classList.add('hpg-pending');observer.observe(element);});
  const revealAll=()=>root.querySelectorAll('.hpg-pending').forEach(el=>el.classList.remove('hpg-pending'));
  reduced.addEventListener('change',revealAll);addEventListener('beforeprint',revealAll);
  root.addEventListener('focusin',event=>event.target.closest('.hpg-pending')?.classList.remove('hpg-pending'));
 }
 const cursor=root.querySelector('.hpg-cursor'),carousel=root.querySelector('.hpg-carousel');let pointerFrame=0;
 carousel.addEventListener('pointermove',event=>{
  if(!fine.matches||reduced.matches)return;
  const interactive=event.target.closest('a,.hpg-zoom');cursor.classList.toggle('is-visible',!interactive);
  if(pointerFrame)return;const x=event.clientX,y=event.clientY,card=event.target.closest('.hpg-card');
  pointerFrame=requestAnimationFrame(()=>{pointerFrame=0;const box=carousel.getBoundingClientRect();cursor.style.transform=`translate3d(${x-box.left-37}px,${y-box.top-37}px,0)`;if(card){const rect=card.getBoundingClientRect();card.style.setProperty('--hpg-x',`${x-rect.left}px`);card.style.setProperty('--hpg-y',`${y-rect.top}px`);}});
 });
 carousel.addEventListener('pointerleave',()=>cursor.classList.remove('is-visible'));
 // A native dialog supplies the focus trap, including when opened from a scrolled card.
 const dialog=root.querySelector('.hpg-lightbox'),photos=[...root.querySelectorAll('[data-hpg-photo]')],image=dialog.querySelector('.hpg-lightbox-stage>img'),thumbRail=dialog.querySelector('.hpg-thumbs');
 let index=0,opener=null,oldOverflow='',touch=null;
 const show=n=>{
  index=(n+photos.length)%photos.length;const photo=photos[index];image.src=photo.dataset.src;image.alt=photo.dataset.caption;
  dialog.querySelector('[data-hpg-caption]').textContent=photo.dataset.caption;
  dialog.querySelector('[data-hpg-count]').textContent=`${String(index+1).padStart(2,'0')} / ${String(photos.length).padStart(2,'0')}`;
  dialog.querySelector('[data-hpg-album]').href=photo.dataset.album;
  [...thumbRail.children].forEach((thumb,i)=>thumb.setAttribute('aria-current',String(i===index)));
  const selected=thumbRail.children[index];thumbRail.scrollLeft=selected.offsetLeft-thumbRail.offsetLeft-thumbRail.clientWidth/2+selected.clientWidth/2;
 };
 photos.forEach((photo,i)=>{
  const thumb=document.createElement('button'),img=document.createElement('img');thumb.type='button';thumb.setAttribute('aria-label',`ดูภาพที่ ${i+1}`);img.src=photo.dataset.src;img.alt='';img.loading='lazy';thumb.append(img);thumb.addEventListener('click',()=>show(i));thumbRail.append(thumb);
  photo.addEventListener('click',()=>{opener=photo;show(i);oldOverflow=document.body.style.overflow;document.body.style.overflow='hidden';dialog.showModal();root.querySelector('[data-hpg-close]').focus();});
 });
 dialog.querySelector('[data-hpg-close]').addEventListener('click',()=>dialog.close());
 dialog.querySelector('[data-hpg-image-prev]').addEventListener('click',()=>show(index-1));dialog.querySelector('[data-hpg-image-next]').addEventListener('click',()=>show(index+1));
 dialog.addEventListener('keydown',event=>{if(event.key==='ArrowLeft'||event.key==='ArrowRight'){event.preventDefault();event.stopPropagation();show(index+(event.key==='ArrowRight'?1:-1));}});
 dialog.addEventListener('close',()=>{document.body.style.overflow=oldOverflow;opener?.focus({preventScroll:true});});
 image.addEventListener('touchstart',event=>{touch={x:event.touches[0].clientX,y:event.touches[0].clientY};},{passive:true});
 image.addEventListener('touchend',event=>{if(!touch)return;const dx=event.changedTouches[0].clientX-touch.x,dy=event.changedTouches[0].clientY-touch.y;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy))show(index+(dx<0?1:-1));touch=null;},{passive:true});
 image.addEventListener('touchcancel',()=>{touch=null;},{passive:true});
})();
