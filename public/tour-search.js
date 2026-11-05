(() => {
 'use strict';
 const source=document.querySelector('#tour-search-catalog');
 if(!source)return;
 let catalog;
 try { catalog=JSON.parse(source.textContent); } catch { return; }
 if(!Array.isArray(catalog))return;
 const normalize=value=>String(value || '').toLowerCase().replace(/[\s/_-]+/gu,' ').trim();
 const entries=catalog.map((item,index)=>({...item,group:item.group==='destination' && item.kind==='country' ? 'country' : item.group,index,haystack:normalize(item.search),name:normalize(item.title),key:normalize(item.key)}));
 const groups=[['country','ประเทศ','flag'],['destination','เส้นทาง / โซน','location_on'],['tour','ทัวร์','travel_explore'],['private','แพ็คเกจส่วนตัว','groups']];
 const create=(tag,cls,text)=>{const node=document.createElement(tag);if(cls)node.className=cls;if(text!==undefined)node.textContent=text;return node;};
 const icon=name=>{const node=create('i','material-icons',name);node.setAttribute('aria-hidden','true');return node;};
 let current=null;
 document.querySelectorAll('form[data-tour-search]').forEach((form,index)=>{
  const input=form.querySelector('[data-tour-search-input]');
  if(!input || input.dataset.autocompleteReady)return;
  input.dataset.autocompleteReady='true';
  const anchor=input.closest('.search-keyword');
  const panel=create('div','tour-search-popup');panel.hidden=true;
  const topLayer=typeof panel.showPopover==='function';if(topLayer)panel.setAttribute('popover','manual');
  const heading=create('div','tour-search-heading'),copy=create('div');
  const eyebrow=create('span','tour-search-eyebrow','FIND YOUR NEXT JOURNEY');
  const title=create('strong','tour-search-title');copy.append(eyebrow,title);
  const closeButton=create('button','tour-search-close');closeButton.type='button';closeButton.setAttribute('aria-label','ปิดคำแนะนำการค้นหา');closeButton.append(icon('close'));heading.append(copy,closeButton);
  const filters=create('div','tour-search-filters');filters.setAttribute('role','group');filters.setAttribute('aria-label','เลือกประเภทคำแนะนำ');
  for(const [value,label] of [['all','ทั้งหมด'],['country','ประเทศ'],['destination','เส้นทาง'],['tour','ทัวร์'],['private','ส่วนตัว']]){
   const button=create('button','tour-search-filter',label);button.type='button';button.dataset.group=value;button.setAttribute('aria-pressed',String(value==='all'));filters.append(button);
  }
  const list=create('div','tour-search-results');list.id='tour-search-results-'+index;list.setAttribute('role','listbox');list.setAttribute('aria-label','คำแนะนำจุดหมายและโปรแกรม');
  const empty=create('div','tour-search-empty');empty.hidden=true;
  empty.append(icon('search_off'),create('strong','','ยังไม่พบรายการที่ตรงกัน'),create('p','','ลองชื่อประเทศ เส้นทาง หรือรหัสโปรแกรมอื่น'));
  const footer=create('div','tour-search-footer'),hint=create('span','tour-search-hint','↑ ↓ เลือกรายการ · Enter เพื่อเปิด · Esc เพื่อปิด');
  const submit=create('button','tour-search-submit');submit.type='button';footer.append(hint,submit);
  const status=create('span','tour-search-sr');status.setAttribute('role','status');status.setAttribute('aria-live','polite');status.setAttribute('aria-atomic','true');
  panel.append(heading,filters,list,empty,footer,status);document.body.append(panel);
  input.setAttribute('role','combobox');input.setAttribute('aria-autocomplete','list');input.setAttribute('aria-expanded','false');input.setAttribute('aria-haspopup','listbox');input.setAttribute('aria-controls',list.id);input.setAttribute('autocomplete','off');input.setAttribute('spellcheck','false');
  let options=[],active=-1,timer=null,selectedGroup='all',lastQuery=normalize(input.value);
  function resetGroup(){
   selectedGroup='all';
   filters.querySelectorAll('button').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.group==='all')));
  }
  function close(){
   clearTimeout(timer);timer=null;
   if(topLayer && panel.matches(':popover-open'))panel.hidePopover();
   panel.hidden=true;input.setAttribute('aria-expanded','false');input.removeAttribute('aria-activedescendant');active=-1;
   if(current===close)current=null;
  }
  function position(){
   if(panel.hidden)return;
   const rect=anchor.getBoundingClientRect(),formRect=form.getBoundingClientRect(),viewport=window.visualViewport;
   const viewportLeft=viewport?.offsetLeft || 0,viewportTop=viewport?.offsetTop || 0;
   const viewportWidth=viewport?.width || innerWidth,viewportBottom=viewportTop+(viewport?.height || innerHeight);
   if(rect.bottom<viewportTop || rect.top>viewportBottom){close();return;}
   const width=Math.min(1120,formRect.width,viewportWidth-24);
   panel.style.width=width+'px';
   panel.style.left=Math.max(viewportLeft+12,Math.min(formRect.left,viewportLeft+viewportWidth-width-12))+'px';
   const top=Math.max(viewportTop+8,rect.bottom+8);
   panel.style.top=top+'px';panel.style.maxHeight=Math.max(0,viewportBottom-top-12)+'px';
  }
  function highlight(node,text,terms){
   if(!terms.length){node.textContent=text;return;}
   const pattern=new RegExp('('+terms.map(term=>term.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')).join('|')+')','giu');
   let start=0;
   for(const match of text.matchAll(pattern)){
    node.append(document.createTextNode(text.slice(start,match.index)),create('mark','',match[0]));start=match.index+match[0].length;
   }
   node.append(document.createTextNode(text.slice(start)));
  }
  function select(value){
   active=value;
   options.forEach((option,i)=>option.setAttribute('aria-selected',String(i===active)));
   if(active<0){input.removeAttribute('aria-activedescendant');return;}
   input.setAttribute('aria-activedescendant',options[active].id);options[active].scrollIntoView({block:'nearest'});
  }
  function render(){
   const query=normalize(input.value),terms=[...new Set(query.split(' ').filter(Boolean))].slice(0,8);
   if(query!==lastQuery){resetGroup();lastQuery=query;}
   const score=item=>item.key===query ? 150 : item.name===query ? 140 : item.name.startsWith(query) ? 90 : item.key.startsWith(query) ? 80 : item.name.includes(query) ? 50 : 0;
   const matches=entries.filter(item=>(query || !form.dataset.searchScope || item.in_scope!==false) && terms.every(term=>item.haystack.includes(term))).sort((a,b)=>(query ? score(b)-score(a) : 0) || a.index-b.index);
   title.textContent=query ? (form.dataset.searchScope ? 'ผลค้นหาทุกจุดหมาย “' : 'ผลการค้นหา “')+input.value.trim()+'”' : 'ยอดนิยมและทริปแนะนำ';
   list.replaceChildren();list.classList.toggle('is-filtered',selectedGroup!=='all');options=[];select(-1);
   for(const [group,label,groupIcon] of groups){
    if(selectedGroup!=='all' && selectedGroup!==group)continue;
    let items=matches.filter(item=>item.group===group);
    if(!query && group==='destination'){
     const routes=items.filter(item=>item.kind==='route');if(routes.length)items=routes;
    }
    if(query && !items.length)continue;
    const section=create('div','tour-search-group');section.setAttribute('role','group');
    const groupHeading=create('div','tour-search-group-heading');groupHeading.id=list.id+'-'+group;groupHeading.append(icon(groupIcon),create('span','',label));section.setAttribute('aria-labelledby',groupHeading.id);section.append(groupHeading);
    for(const item of items.slice(0,query ? 5 : 4)){
     const option=create('a','tour-suggestion');option.id=list.id+'-'+item.id;option.href=item.url;option.tabIndex=-1;option.setAttribute('role','option');option.setAttribute('aria-selected','false');option.dataset.group=group;option.dataset.suggestionId=item.id;
     if(item.image){const img=create('img','tour-suggestion-image');img.src=item.image;img.alt='';img.loading='lazy';img.width=46;img.height=46;option.append(img);}
     else {const image=create('span','tour-suggestion-place');image.append(icon(item.kind==='zone' ? 'public' : item.kind==='country' ? 'flag' : 'location_on'));option.append(image);}
     const text=create('span','tour-suggestion-copy'),name=create('strong');highlight(name,item.title,terms);text.append(name,create('small','',item.subtitle));option.append(text,icon('north_east'));
     option.addEventListener('click',close);section.append(option);options.push(option);
    }
    if(!items.length)section.append(create('p','tour-search-group-empty','กำลังเตรียมโปรแกรมใหม่'));
    list.append(section);
   }
   empty.hidden=options.length>0;list.hidden=!options.length;
   const privateSearch=form.dataset.searchMode==='private';
   const scope=form.dataset.searchScope;
   submit.replaceChildren(create('span','',scope ? 'ค้นหาโปรแกรมใน'+scope : query ? 'ค้นหาทั้งหมดใน'+(privateSearch ? 'แพ็คเกจส่วนตัว' : 'ทัวร์') : 'ดู'+(privateSearch ? 'แพ็คเกจส่วนตัว' : 'ทัวร์')+'ทั้งหมด'),icon('arrow_forward'));
   const count=matches.filter(item=>selectedGroup==='all' || item.group===selectedGroup).length;
   status.textContent=query ? 'พบ '+count+' รายการ แสดงคำแนะนำ '+options.length+' รายการ' : 'แสดงคำแนะนำยอดนิยม '+options.length+' รายการ';
   position();
  }
  function open(){
   if(current && current!==close)current();current=close;
   render();panel.hidden=false;
   if(topLayer && !panel.matches(':popover-open'))panel.showPopover();
   input.setAttribute('aria-expanded','true');position();
   const height=window.visualViewport?.height || innerHeight;
   if(height-anchor.getBoundingClientRect().bottom<Math.min(360,height*.45)){anchor.scrollIntoView({block:'center',behavior:'instant'});position();}
  }
  input.addEventListener('focus',open);input.addEventListener('click',()=>{if(panel.hidden)open();});
  input.addEventListener('input',event=>{
   clearTimeout(timer);select(-1);if(event.isComposing)return;
   timer=setTimeout(()=>{timer=null;if(document.activeElement===input)open();},80);
  });
  input.addEventListener('compositionend',()=>{clearTimeout(timer);timer=setTimeout(()=>{timer=null;if(document.activeElement===input)open();},0);});
  input.addEventListener('keydown',event=>{
   if(event.isComposing)return;
   if(event.key==='Escape'){if(!panel.hidden || timer!==null){event.preventDefault();event.stopPropagation();close();}return;}
   if(event.key==='Tab'){close();return;}
   if(['ArrowDown','ArrowUp'].includes(event.key)){
    event.preventDefault();const pending=timer!==null;clearTimeout(timer);timer=null;if(panel.hidden || pending)open();
    if(options.length)select((active+(event.key==='ArrowDown' ? 1 : active<0 ? 0 : -1)+options.length)%options.length);return;
   }
   if(event.key==='Enter' && !panel.hidden && active>=0){event.preventDefault();options[active].click();}
  });
  input.addEventListener('blur',()=>setTimeout(()=>{if(!panel.contains(document.activeElement))close();},0));
  panel.addEventListener('focusout',()=>setTimeout(()=>{if(document.activeElement!==input && !panel.contains(document.activeElement))close();},0));
  panel.addEventListener('pointerdown',event=>{if(event.target.closest('[role=option],.tour-search-filter'))event.preventDefault();});
  filters.addEventListener('click',event=>{
   const button=event.target.closest('.tour-search-filter');if(!button)return;
   selectedGroup=button.dataset.group;
   filters.querySelectorAll('button').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));render();
  });
  closeButton.addEventListener('click',()=>{input.focus({preventScroll:true});close();});
  panel.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();input.focus({preventScroll:true});close();}});
  submit.addEventListener('click',()=>{close();form.requestSubmit();});form.addEventListener('submit',close);
  form.querySelector('[data-search-types]')?.addEventListener('click',()=>{if(!panel.hidden)render();});
  document.addEventListener('pointerdown',event=>{if(!panel.hidden && !panel.contains(event.target) && !anchor.contains(event.target))close();});
  window.addEventListener('scroll',position,{passive:true});window.addEventListener('resize',position);window.addEventListener('pagehide',close);
  window.addEventListener('pageshow',close);
  window.visualViewport?.addEventListener('resize',position);window.visualViewport?.addEventListener('scroll',position);
 });
})();
