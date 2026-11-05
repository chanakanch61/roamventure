(() => {
 // Index: "/" jumps to search like most reading apps; the toolbar gains a solid backdrop once it sticks under the header.
 const query=document.querySelector('#journal-query');
 if(query){
  addEventListener('keydown',e=>{if(e.key!=='/' || e.ctrlKey || e.metaKey || e.altKey)return;const t=e.target;if(t.closest('input,textarea,select,[contenteditable]'))return;e.preventDefault();query.focus({preventScroll:true});query.scrollIntoView({block:'center',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});query.select();});
  query.addEventListener('keydown',e=>{if(e.key==='Escape')query.blur();});
  const wrap=document.querySelector('.journal-toolbar-wrap');
  if(wrap && 'IntersectionObserver' in window){const sentinel=document.createElement('div');sentinel.className='journal-sticky-sentinel';sentinel.setAttribute('aria-hidden','true');wrap.before(sentinel);const offset=parseFloat(getComputedStyle(wrap).top)||98;new IntersectionObserver(([entry])=>wrap.classList.toggle('is-stuck',!entry.isIntersecting && entry.boundingClientRect.top<offset),{rootMargin:`-${offset}px 0px 0px 0px`}).observe(sentinel);}
 }
 const prose=document.querySelector('#journal-prose');if(!prose)return;
 const headings=[...prose.querySelectorAll('h2,h3')].filter(h=>h.textContent.trim());
 const toc=document.querySelector('.journal-toc'),list=toc.querySelector('div');
 headings.forEach((heading,index)=>{heading.id='story-section-'+(index+1);const a=document.createElement('a');a.href='#'+heading.id;a.textContent=heading.textContent;a.dataset.level=heading.tagName.slice(1);list.append(a);});
 toc.hidden=headings.length<2;
 const links=[...list.querySelectorAll('a')],progress=document.querySelector('.journal-progress span');let queued=false;
 const refresh=()=>{queued=false;const rect=prose.getBoundingClientRect(),distance=rect.height-innerHeight+150;const percent=distance>0 ? Math.max(0,Math.min(1,(150-rect.top)/distance)) : (rect.top<innerHeight ? 1 : 0);progress.style.transform=`scaleX(${percent})`;let current=0;headings.forEach((h,i)=>{if(h.getBoundingClientRect().top<=160)current=i;});links.forEach((a,i)=>{a.classList.toggle('active',i===current);if(i===current)a.setAttribute('aria-current','location');else a.removeAttribute('aria-current');});};
 const schedule=()=>{if(!queued){queued=true;requestAnimationFrame(refresh);}};
 addEventListener('scroll',schedule,{passive:true});addEventListener('resize',schedule);addEventListener('load',schedule);prose.querySelectorAll('img').forEach(img=>img.addEventListener('load',schedule));refresh();
 const canonical=()=>document.querySelector('link[rel=canonical]')?.href || location.href.split('#')[0];
 const copy=document.querySelector('[data-journal-copy]'),status=document.querySelector('.journal-copy-status');
 if(copy){copy.hidden=false;copy.addEventListener('click',async()=>{const url=canonical();try{if(navigator.clipboard && window.isSecureContext)await navigator.clipboard.writeText(url);else{const input=document.createElement('textarea');input.value=url;input.style.cssText='position:fixed;left:-9999px;top:0';document.body.append(input);input.select();const ok=document.execCommand('copy');input.remove();copy.focus();if(!ok)throw Error();}status.textContent='คัดลอกลิงก์แล้ว';}catch{status.textContent='คัดลอก URL จากแถบที่อยู่ของเบราว์เซอร์ได้เลย';}});}
 const share=document.querySelector('[data-journal-share]');
 if(share && navigator.share){share.hidden=false;share.addEventListener('click',()=>navigator.share({title:document.querySelector('.journal-story-title h1')?.textContent.trim() || document.title,url:canonical()}).catch(()=>{}));}
})();
