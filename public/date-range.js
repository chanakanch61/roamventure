(() => {
 const parse = value => { const [y,m,d] = value.split('-').map(Number); return new Date(y,m-1,d,12); };
 const iso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
 const format = value => new Intl.DateTimeFormat('th-TH-u-ca-gregory',{day:'numeric',month:'short',year:'numeric'}).format(parse(value));
 const monthName = date => new Intl.DateTimeFormat('th-TH-u-ca-gregory',{month:'long',year:'numeric'}).format(date);
 document.querySelectorAll('[data-date-range]').forEach((root,index) => {
  const trigger=root.querySelector('.date-range-trigger'), dialog=root.querySelector('dialog');
  const from=root.querySelector('[name=date_from]'), to=root.querySelector('[name=date_to]');
  const months=dialog.querySelector('.date-range-months'), summary=dialog.querySelector('.date-range-summary');
  const apply=dialog.querySelector('.date-range-apply'), today=root.dataset.today;
  const firstMonth=new Date(parse(today).getFullYear(),parse(today).getMonth(),1,12);
  let start='',end='',hover='',view=firstMonth;
  dialog.id=`travel-calendar-${index}`; trigger.setAttribute('aria-controls',dialog.id);
  function display() {
   root.querySelector('.date-range-value').textContent=from.value && to.value ? `${format(from.value)} – ${format(to.value)}` : 'วันเริ่มต้น – วันสิ้นสุด';
   trigger.classList.toggle('has-dates',!!from.value);
  }
  function paint() {
   const finish=end || hover;
   months.querySelectorAll('[data-date]').forEach(button=>{
    const value=button.dataset.date;
    button.classList.toggle('range-start',value===start);
    button.classList.toggle('range-end',value===end);
    button.classList.toggle('in-range',!!start && !!finish && value>start && value<finish);
    button.setAttribute('aria-pressed',String(value===start || value===end));
   });
   summary.textContent=start ? (end ? `${format(start)} – ${format(end)}` : `${format(start)} — เลือกวันสิ้นสุด`) : 'เลือกวันเริ่มต้น';
   apply.disabled=!!start && !end;
  }
  function render() {
   months.replaceChildren();
   for(let offset=0;offset<2;offset++) {
    const date=new Date(view.getFullYear(),view.getMonth()+offset,1,12);
    const section=document.createElement('section'); section.className='date-range-month';
    const heading=document.createElement('div'); heading.className='date-range-month-heading';
    const title=document.createElement('strong'); title.textContent=monthName(date);
    const nav=document.createElement('button'); nav.type='button'; nav.dataset.step=offset ? '1' : '-1';
    nav.className='date-range-nav'; nav.textContent=offset ? '›' : '‹'; nav.setAttribute('aria-label',offset ? 'เดือนถัดไป' : 'เดือนก่อนหน้า');
    if(!offset) nav.disabled=view<=firstMonth;
    heading.append(...(offset ? [title,nav] : [nav,title])); section.append(heading);
    const grid=document.createElement('div'); grid.className='date-range-grid';
    ['อา','จ','อ','พ','พฤ','ศ','ส'].forEach(day=>{const cell=document.createElement('span');cell.className='date-range-weekday';cell.textContent=day;grid.append(cell);});
    for(let blank=0;blank<date.getDay();blank++) grid.append(document.createElement('span'));
    const count=new Date(date.getFullYear(),date.getMonth()+1,0).getDate();
    for(let day=1;day<=count;day++) {
     const value=iso(new Date(date.getFullYear(),date.getMonth(),day,12));
     const button=document.createElement('button'); button.type='button';button.className='date-range-day';button.dataset.date=value;button.textContent=day;
     button.disabled=value<today;button.setAttribute('aria-label',format(value));
     if(value===today) button.setAttribute('aria-current','date');
     grid.append(button);
    }
    section.append(grid); months.append(section);
   }
   paint();
  }
  function position() {
   if(!dialog.open) return;
   const rect=trigger.getBoundingClientRect(), width=dialog.offsetWidth;
   const left=Math.max(12,Math.min(rect.left,innerWidth-width-12));
   const top=Math.max(12,Math.min(rect.bottom+8,innerHeight-dialog.offsetHeight-12));
   dialog.style.left=`${left}px`;dialog.style.top=`${top}px`;
  }
  trigger.addEventListener('click',()=>{
   start=from.value;end=to.value;hover='';
   const selected=start && start>=today ? parse(start) : firstMonth;
   view=new Date(selected.getFullYear(),selected.getMonth(),1,12);
   render();dialog.showModal();trigger.setAttribute('aria-expanded','true');position();
   (months.querySelector('.range-start:not(:disabled)') || months.querySelector('[data-date]:not(:disabled)')).focus({preventScroll:true});
  });
  months.addEventListener('click',event=>{
   const nav=event.target.closest('[data-step]');
   if(nav){view=new Date(view.getFullYear(),view.getMonth()+Number(nav.dataset.step),1,12);render();position();months.querySelector(`[data-step="${nav.dataset.step}"]`).focus();return;}
   const day=event.target.closest('[data-date]');if(!day || day.disabled)return;
   const value=day.dataset.date;
   if(!start || end || value<start){start=value;end='';}else{end=value;}
   hover='';paint();
  });
  months.addEventListener('mouseover',event=>{const day=event.target.closest('[data-date]');if(start && !end && day && !day.disabled){hover=day.dataset.date;paint();}});
  months.addEventListener('mouseleave',()=>{hover='';paint();});
  months.addEventListener('keydown',event=>{
   const day=event.target.closest('[data-date]');if(!day)return;
   const delta={ArrowLeft:-1,ArrowRight:1,ArrowUp:-7,ArrowDown:7}[event.key];if(!delta)return;
   event.preventDefault();const target=parse(day.dataset.date);target.setDate(target.getDate()+delta);const value=iso(target);if(value<today)return;
   if(!months.querySelector(`[data-date="${value}"]`)){view=new Date(target.getFullYear(),target.getMonth(),1,12);render();position();}
   months.querySelector(`[data-date="${value}"]`).focus();
  });
  dialog.querySelector('.date-range-clear').addEventListener('click',()=>{start='';end='';hover='';paint();});
  apply.addEventListener('click',()=>{from.value=start;to.value=end;display();dialog.close();});
  dialog.querySelector('.date-range-close').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left || event.clientX>r.right || event.clientY<r.top || event.clientY>r.bottom)dialog.close();});
  dialog.addEventListener('close',()=>{
   trigger.setAttribute('aria-expanded','false');
   // A queued close event must not take focus from a field the user already entered.
   const focused=document.activeElement;
   if(focused===document.body || focused===trigger || dialog.contains(focused))trigger.focus({preventScroll:true});
  });
  window.addEventListener('resize',position);
  display();
 });
})();
