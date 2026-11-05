(()=>{'use strict';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 document.querySelectorAll('[data-gallery-slider]').forEach(deck=>{
  const track=deck.querySelector('.gal-deck-track'),slides=[...track.children],thumbs=[...deck.querySelectorAll('[data-slide-to]')],prev=deck.querySelector('[data-slide-prev]'),next=deck.querySelector('[data-slide-next]');
  if(!slides.length){prev.disabled=next.disabled=true;return;}
  let current=0,frame=0,drag=null,suppressClick=false;
  const offset=i=>slides[i].offsetLeft-slides[0].offsetLeft;
  const update=i=>{current=i;deck.querySelector('[data-slide-current]').textContent=String(i+1).padStart(2,'0');deck.querySelector('[data-slide-progress]').style.transform=`scaleX(${(i+1)/slides.length})`;prev.disabled=i===0;next.disabled=i===slides.length-1;thumbs.forEach((b,j)=>b.setAttribute('aria-current',String(i===j)));const rail=deck.querySelector('.gal-deck-thumbs'),active=thumbs[i];if(active)rail.scrollLeft=active.offsetLeft-rail.offsetLeft-rail.clientWidth/2+active.clientWidth/2;};
  const go=(i,instant=false)=>{i=Math.max(0,Math.min(slides.length-1,i));update(i);track.scrollTo({left:offset(i),behavior:instant||reduced.matches?'auto':'smooth'});};
  const sync=()=>{frame=0;let best=0;slides.forEach((s,i)=>{if(Math.abs(offset(i)-track.scrollLeft)<Math.abs(offset(best)-track.scrollLeft))best=i;});if(best!==current)update(best);};
  prev.addEventListener('click',()=>go(current-1));next.addEventListener('click',()=>go(current+1));thumbs.forEach((b,i)=>b.addEventListener('click',()=>go(i)));
  track.addEventListener('scroll',()=>{if(!frame)frame=requestAnimationFrame(sync);},{passive:true});
  deck.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();go(current+(e.key==='ArrowRight'?1:-1));}});
  track.addEventListener('pointerdown',e=>{if(e.pointerType!=='mouse'||e.button!==0)return;drag={x:e.clientX,left:track.scrollLeft,id:e.pointerId,moved:false};});
  track.addEventListener('pointermove',e=>{if(!drag)return;const dx=e.clientX-drag.x;if(Math.abs(dx)>6&&!drag.moved){drag.moved=true;track.setPointerCapture(e.pointerId);track.classList.add('is-dragging');}if(drag.moved){e.preventDefault();track.scrollLeft=drag.left-dx;}});
  const finish=e=>{if(!drag)return;const moved=drag.moved;drag=null;track.classList.remove('is-dragging');if(track.hasPointerCapture(e.pointerId))track.releasePointerCapture(e.pointerId);if(moved){suppressClick=true;sync();go(current);setTimeout(()=>{suppressClick=false;},200);}};
  track.addEventListener('pointerup',finish);track.addEventListener('pointercancel',finish);track.addEventListener('pointerleave',e=>{if(drag&&!drag.moved)finish(e);});track.addEventListener('click',e=>{if(suppressClick){e.preventDefault();e.stopPropagation();}},true);track.addEventListener('dragstart',e=>e.preventDefault());
  track.addEventListener('focusin',e=>{const i=slides.findIndex(s=>s.contains(e.target));if(i>=0&&i!==current)go(i,true);});
  let width=track.clientWidth;if('ResizeObserver' in window)new ResizeObserver(()=>{if(width!==track.clientWidth){width=track.clientWidth;go(current,true);}}).observe(track);update(0);
 });
 const photos=[...document.querySelectorAll('[data-gallery-photo]')],dialog=document.querySelector('.gal-lightbox');
 if(dialog&&photos.length){let index=0,opener,oldOverflow='',touchX=null;const img=dialog.querySelector('img');
  const thumbs=dialog.querySelector('[data-gallery-thumbs]');
  const show=n=>{index=(n+photos.length)%photos.length;img.src=photos[index].dataset.src;img.alt=photos[index].dataset.caption;dialog.querySelector('[data-gallery-caption]').textContent=img.alt;dialog.querySelector('[data-gallery-count]').textContent=`${index+1} / ${photos.length}`;if(thumbs){[...thumbs.children].forEach((b,i)=>b.setAttribute('aria-current',String(i===index)));const active=thumbs.children[index];thumbs.scrollLeft=active.offsetLeft-thumbs.offsetLeft-thumbs.clientWidth/2+active.clientWidth/2;}};
  if(thumbs)photos.forEach((p,i)=>{const b=document.createElement('button'),image=document.createElement('img');b.type='button';b.setAttribute('aria-label',`ดูภาพที่ ${i+1}`);image.src=p.dataset.src;image.alt='';image.loading='lazy';b.append(image);b.addEventListener('click',()=>show(i));thumbs.append(b);});
  photos.forEach((p,i)=>p.addEventListener('click',()=>{opener=p;show(i);oldOverflow=document.body.style.overflow;document.body.style.overflow='hidden';dialog.showModal();}));
  dialog.querySelector('[data-gallery-close]').addEventListener('click',()=>dialog.close());
  dialog.querySelector('[data-gallery-prev]').addEventListener('click',()=>show(index-1));dialog.querySelector('[data-gallery-next]').addEventListener('click',()=>show(index+1));
  dialog.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();show(index+(e.key==='ArrowRight'?1:-1));}});
  dialog.addEventListener('close',()=>{document.body.style.overflow=oldOverflow;opener?.focus();});
  img.addEventListener('touchstart',e=>{touchX=e.touches[0].clientX;},{passive:true});img.addEventListener('touchend',e=>{if(touchX!==null){const delta=e.changedTouches[0].clientX-touchX;if(Math.abs(delta)>50)show(index+(delta<0?1:-1));touchX=null;}},{passive:true});
 }
 const input=document.querySelector('#gallery-upload'),form=document.querySelector('#gallery-editor');
 if(input){let urls=[];const preview=document.querySelector('#gallery-upload-preview'),status=document.querySelector('#gallery-upload-status'),drop=document.querySelector('#gallery-drop');
  const update=()=>{urls.forEach(URL.revokeObjectURL);urls=[];preview.replaceChildren();const files=[...input.files],remaining=document.querySelectorAll('.gal-edit-photo:not(.is-removed)').length;let error='';
   if(files.length>15||files.length+remaining>60)error='เลือกได้ครั้งละ 15 ภาพ และรวมไม่เกิน 60 ภาพ';
   else if(files.some(f=>f.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(f.type)))error='กรุณาเลือก JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB';
   input.setCustomValidity(error);status.textContent=error|| (files.length?`เลือกแล้ว ${files.length} ภาพ · กดบันทึกเพื่ออัปโหลด`:'');
   if(!error)files.forEach(f=>{const img=document.createElement('img'),url=URL.createObjectURL(f);urls.push(url);img.src=url;img.alt=f.name;preview.append(img);});
  };
  input.addEventListener('change',update);drop.addEventListener('dragover',e=>{e.preventDefault();drop.classList.add('is-dragging');});drop.addEventListener('dragleave',()=>drop.classList.remove('is-dragging'));drop.addEventListener('drop',e=>{e.preventDefault();drop.classList.remove('is-dragging');if(e.dataTransfer.files.length){input.files=e.dataTransfer.files;update();}});
  document.querySelectorAll('[data-remove-photo]').forEach(c=>c.addEventListener('change',()=>{c.closest('.gal-edit-photo').classList.toggle('is-removed',c.checked);update();}));
  form.addEventListener('submit',()=>{form.querySelectorAll('button[type=submit]').forEach(b=>{b.disabled=true;b.textContent='กำลังบันทึก…';});});
 }
 document.querySelectorAll('[data-gallery-delete]').forEach(f=>f.addEventListener('submit',e=>{const title=f.dataset.galleryDelete;if(!confirm(`ยืนยันลบอัลบั้ม${title?` “${title}” `:''}และภาพทั้งหมดถาวร? การลบนี้ไม่สามารถย้อนกลับได้`))e.preventDefault();}));
})();
// Public gallery motion: collage drift, album frame previews, cursor bubble, scroll reveals and album masonry. Content stays usable without it.
(()=>{'use strict';
 const root=document.querySelector('[data-gx]');if(!root)return;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)'),fine=matchMedia('(hover: hover) and (pointer: fine)').matches;
 const collage=root.querySelector('[data-gx-collage]');
 if(collage&&fine){let frame=0;collage.parentElement.addEventListener('pointermove',e=>{if(reduced.matches||frame)return;const x=(e.clientX/innerWidth-.5)*2,y=(e.clientY/innerHeight-.5)*2;frame=requestAnimationFrame(()=>{collage.style.setProperty('--px',x.toFixed(3));collage.style.setProperty('--py',y.toFixed(3));frame=0;});},{passive:true});}
 // Each album tile flips through its preview photos, story-style, while hovered (or while in view on touch screens).
 const tiles=[...root.querySelectorAll('[data-gx-tile]')],FRAME=1400;
 const players=new Map();
 tiles.forEach(tile=>{
  const frames=[...tile.querySelectorAll('.gx-tile-frames img')],bars=[...tile.querySelectorAll('.gx-tile-bars i')];if(frames.length<2)return;
  let index=0,timer=0;
  const show=n=>{index=n%frames.length;frames.forEach((f,i)=>f.classList.toggle('is-active',i===index));bars.forEach((b,i)=>{b.classList.toggle('is-done',i<index);b.classList.remove('is-current');});void bars[index].offsetWidth;bars[index].classList.add('is-current');};
  const start=()=>{if(timer||reduced.matches)return;show(index);timer=setInterval(()=>show(index+1),FRAME);};
  const stop=()=>{clearInterval(timer);timer=0;show(0);bars.forEach(b=>b.classList.remove('is-current','is-done'));};
  tile.style.setProperty('--gx-frame',FRAME+'ms');players.set(tile,{start,stop});
  if(fine){tile.addEventListener('pointerenter',start);tile.addEventListener('pointerleave',stop);}
  tile.addEventListener('focus',start);tile.addEventListener('blur',stop);
 });
 if(!fine&&players.size&&'IntersectionObserver' in window){const io=new IntersectionObserver(es=>es.forEach(en=>players.get(en.target)[en.isIntersecting?'start':'stop']()),{threshold:.6});players.forEach((p,tile)=>io.observe(tile));}
 if(fine&&tiles.length){
  const cursor=document.createElement('div');cursor.className='gx-cursor';cursor.setAttribute('aria-hidden','true');cursor.textContent='ดูอัลบั้ม';document.body.append(cursor);root.classList.add('gx-has-cursor');
  let frame=0,x=0,y=0;
  root.addEventListener('pointermove',e=>{x=e.clientX;y=e.clientY;if(!frame)frame=requestAnimationFrame(()=>{cursor.style.transform=`translate(${x}px,${y}px)`;frame=0;});},{passive:true});
  tiles.forEach(t=>{t.addEventListener('pointerenter',()=>cursor.classList.add('is-visible'));t.addEventListener('pointerleave',()=>cursor.classList.remove('is-visible'));});
 }
 // Album frames: measure each frame onto 4px grid rows so order reads left to right; wide landscape shots lead every fifth slot.
 const grid=root.querySelector('[data-gx-masonry]');
 if(grid){
  const shots=[...grid.children];let wideOk=false,width=0;
  const fit=s=>{s.style.gridRowEnd=`span ${Math.ceil(s.firstElementChild.getBoundingClientRect().height/4)}`;};
  const widen=(s,i)=>{const img=s.querySelector('img');s.classList.toggle('is-wide',wideOk&&i%5===0&&img.naturalWidth>img.naturalHeight*1.2);};
  const refresh=()=>{wideOk=getComputedStyle(grid).gridTemplateColumns.split(' ').length>=3;shots.forEach(widen);shots.forEach(fit);};
  grid.classList.add('is-masonry');
  shots.forEach((s,i)=>s.querySelector('img').addEventListener('load',()=>{widen(s,i);fit(s);}));
  if('ResizeObserver' in window){const ro=new ResizeObserver(es=>es.forEach(en=>fit(en.target.parentElement)));shots.forEach(s=>ro.observe(s.firstElementChild));new ResizeObserver(()=>{if(grid.clientWidth!==width){width=grid.clientWidth;refresh();}}).observe(grid);}
  else addEventListener('resize',refresh);
  refresh();
 }
 const cover=root.querySelector('[data-gx-cover]');
 if(cover){let frame=0;const host=cover.parentElement,update=()=>{frame=0;if(reduced.matches)return;const top=host.getBoundingClientRect().top;if(-top<host.offsetHeight)cover.style.setProperty('--gx-shift',`${Math.round(-top*.35)}px`);};addEventListener('scroll',()=>{if(!frame)frame=requestAnimationFrame(update);},{passive:true});update();}
 // Curtain reveal for tiles and frames below the fold.
 if('IntersectionObserver' in window&&!reduced.matches){
  const items=[...root.querySelectorAll('.gx-tile,.gx-shot')];
  const io=new IntersectionObserver(es=>es.forEach(en=>{if(en.isIntersecting){en.target.classList.remove('is-pending');io.unobserve(en.target);}}),{threshold:.08,rootMargin:'0px 0px -40px 0px'});
  items.forEach((el,i)=>{el.classList.add('gx-reveal');el.style.setProperty('--reveal-delay',`${i%3*110}ms`);if(el.getBoundingClientRect().top>innerHeight)el.classList.add('is-pending');io.observe(el);});
  root.addEventListener('focusin',e=>e.target.closest('.gx-reveal')?.classList.remove('is-pending'));
  addEventListener('beforeprint',()=>items.forEach(el=>el.classList.remove('is-pending')));
 }
})();
