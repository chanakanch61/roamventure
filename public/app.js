// Omit empty filters while keeping native GET form submission and validation.
document.querySelectorAll('form').forEach(form => {
 if (form.method.toLowerCase() !== 'get') return;
 form.addEventListener('formdata', event => {
  const data = event.formData;
  for (const name of new Set(data.keys())) {
   const values = data.getAll(name);
   const kept = values.filter(value => typeof value !== 'string' ||
    (value.trim() !== '' && !(form.hasAttribute('data-tour-search') && name === 'program_type' && value === 'all')));
   if (kept.length === values.length) continue;
   data.delete(name);
   kept.forEach(value => data.append(name, value));
  }
 });
 form.addEventListener('submit', event => {
  if (event.defaultPrevented || event.submitter?.name) return;
  if (!new FormData(form).keys().next().done) return;
  event.preventDefault();
  const url = new URL(form.action, location.href);
  url.search = '';
  location.assign(url.href);
 });
});

// The ticket help button sits beside the flights, but the choice itself belongs to the booking form.
document.querySelectorAll('[data-ticket-help]').forEach(button => button.addEventListener('click', () => {
 const box = document.querySelector('#ticket-help');
 document.dispatchEvent(new Event('rv:booking-open'));
 if (!box) return;
 box.checked = true;
 box.dispatchEvent(new Event('change', {bubbles:true}));
 // The checkbox sits on the contact step, which may not be showing yet, so the button confirms the choice.
 button.textContent = 'เลือกแล้ว · ทีมงานจะช่วยจองตั๋วให้';
}));

// Switch the per-person price table between service levels on private package cards.
document.querySelectorAll('[data-rate-levels]').forEach(table => {
 table.addEventListener('click', event => {
  const button = event.target.closest('[data-rate-level]');
  if (!button) return;
  table.querySelectorAll('[data-rate-level]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
  table.querySelectorAll('[data-rate-panel]').forEach(panel => { panel.hidden = panel.dataset.ratePanel !== button.dataset.rateLevel; });
 });
});

// Change search destinations without resetting the keyword or travel dates.
document.querySelectorAll('[data-search-types]').forEach(group => {
 const form = group.closest('form');
 group.addEventListener('click', event => {
  const button = event.target.closest('[data-search-path]');
  if (!button) return;
  const isPrivate = button.dataset.searchKind === 'private';
  const mode = ['private', 'all', 'flight', 'land'].includes(button.dataset.searchKind) ? button.dataset.searchKind : 'fixed';
  form.dataset.searchMode = mode;
  if (form.elements.namedItem('program_type')) form.elements.namedItem('program_type').value = mode;
  form.querySelector('.search-month').hidden = isPrivate;
  form.querySelector('[name=month]').disabled = isPrivate;
  form.querySelector('[data-date-range]').hidden = !isPrivate;
  form.querySelectorAll('[name=date_from], [name=date_to]').forEach(input => { input.disabled = !isPrivate; });
  group.querySelectorAll('button').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
  form.action = button.dataset.searchPath;
  const scope = form.dataset.searchScope;
  form.setAttribute('aria-label', scope ? 'ค้นหาโปรแกรมใน'+scope : isPrivate ? 'ค้นหาแพ็คเกจส่วนตัว' : 'ค้นหาทัวร์');
  form.querySelector('.search-keyword .search-label').textContent = scope ? 'ค้นหาใน'+scope : isPrivate ? 'ค้นหาแพ็คเกจที่ใช่' : 'ค้นหาทัวร์ที่ใช่';
  form.querySelector('[name=q]').placeholder = isPrivate ? 'ชื่อแพ็คเกจ ประเทศ เส้นทาง หรือรหัสโปรแกรม' : 'ชื่อทัวร์ ประเทศ เส้นทาง หรือรหัสโปรแกรม';
  form.querySelector('.date-range-heading p').textContent = isPrivate ? 'ค้นหาแพ็คเกจที่เริ่มเดินทางได้ในช่วงวันที่เลือก' : 'ค้นหารอบทัวร์ที่เริ่มเดินทางในช่วงวันที่เลือก';
 });
});

// Keep the fixed navigation below announcements until they scroll out of view.
(() => {
 const header = document.querySelector('.site-header');
 if (!header) return;
 const topbar = document.querySelector('.topbar');
 const listingSearch = document.querySelector('.listing-search');
 const updateHeader = () => {
  const top = Math.max(0, topbar?.getBoundingClientRect().bottom || 0);
  header.style.setProperty('--header-top', `${top}px`);
  listingSearch?.style.setProperty('--listing-search-top', `${top + header.offsetHeight + 8}px`);
  header.classList.toggle('is-scrolled', window.scrollY > 8);
 };
 updateHeader();
 window.addEventListener('scroll', updateHeader, { passive: true });
 window.addEventListener('resize', updateHeader);
 window.addEventListener('pageshow', updateHeader);
 if (topbar && 'ResizeObserver' in window) new ResizeObserver(updateHeader).observe(topbar);
 if (listingSearch && 'ResizeObserver' in window) new ResizeObserver(updateHeader).observe(header);
})();

(() => {
 const toggle = document.querySelector('.menu-toggle');
 const nav = document.querySelector('#site-nav');
 if (!toggle || !nav) return;
 const mobile = window.matchMedia('(max-width: 800px)');
 const setOpen = open => {
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'ปิดเมนู' : 'เปิดเมนู');
  toggle.querySelector('.material-icons').textContent = open ? 'close' : 'menu';
  nav.classList.toggle('open', open);
  document.documentElement.classList.toggle('menu-open', open);
 };
 toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
 nav.addEventListener('click', event => {
  if (event.target.closest('a')) setOpen(false);
 });
 document.addEventListener('keydown', event => {
  if (toggle.getAttribute('aria-expanded') !== 'true') return;
  if (event.key === 'Escape') { setOpen(false); toggle.focus(); }
  if (event.key === 'Tab') {
   const last = nav.querySelector('a:last-child');
   if (event.shiftKey && document.activeElement === toggle) { event.preventDefault(); last?.focus(); }
   else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); toggle.focus(); }
  }
 });
 mobile.addEventListener('change', () => { if (!mobile.matches) setOpen(false); });
})();

// Shared entrance and scroll motion lives in motion.js.

const departures = document.querySelector('#departure-data');
if (departures) {
 const data = JSON.parse(departures.textContent), dateSelect = document.querySelector('#departure-select'), countInput = document.querySelector('#traveler-count');
 const minus = document.querySelector('#traveler-minus'), plus = document.querySelector('#traveler-plus');
 const rooms = ['twin','double','triple','single'].map(type => ({ type, capacity: { twin: 2, double: 2, triple: 3, single: 1 }[type], input: document.querySelector('#room-' + type + '-count'), minus: document.querySelector('#room-' + type + '-minus'), plus: document.querySelector('#room-' + type + '-plus') }));
 const privateDate=document.querySelector('#private-date'), privateLevel=document.querySelector('#private-level');
 const addDays=(date,n)=>{ const d=new Date(date+'T00:00:00Z'); d.setUTCDate(d.getUTCDate()+n); return Number.isNaN(d.valueOf()) ? '' : d.toISOString().slice(0,10); };
 const ratesOf=d=>JSON.parse(d.private_rates || '[]');
 // Public quick quote: price shows before a date is chosen, rooms fill themselves, contact fields come last.
 const quick=document.querySelector('#booking-form').hasAttribute('data-quick-quote');
 const allRates=privateDate && quick ? data.flatMap(ratesOf).filter(r=>Number(r.price)>0) : [];
 const roomDetails=document.querySelector('#room-details'), bookingNext=document.querySelector('#booking-next'), bookingContact=document.querySelector('#booking-contact');
 let roomsTouched=false;
 const previewRate=()=>{ const count=Number(countInput.value); return allRates.filter(r=>r.level===privateLevel.value && count>=r.min && count<=r.max).sort((a,b)=>a.price-b.price)[0]; };
 const rateRange=r=>(Number(r.min)===Number(r.max) ? r.min : r.min+'–'+r.max)+' คน';
 const cheapestLevel=()=>[...allRates].sort((a,b)=>a.price-b.price)[0]?.level;
 // An odd group takes one triple so the suggestion never adds a single-room charge it can avoid.
 function suggestRooms(dep,count) {
  const plan={twin:0,double:0,triple:0,single:0};
  if (Number(dep.room_available)) {
   if (count===1) plan.single=dep.single_supplement===null ? 0 : 1;
   else { plan.triple=count%2; plan.twin=(count-plan.triple*3)/2; }
  }
  rooms.forEach(r=>{ r.input.value=String(plan[r.type]); });
 }
 // Two steps that each fit the panel: choose the trip, then contact details with a summary to copy into chat.
 const bookingPanel=document.querySelector('#booking'), bookingChoose=document.querySelector('#booking-choose');
 let bookingStep=1, resumeContact=quick && bookingContact.hasAttribute('data-open');
 function showBookingStep(step,moveFocus=true) {
  bookingStep=step;
  bookingChoose.hidden=step!==1; bookingContact.hidden=step!==2;
  bookingChoose.querySelectorAll('details[open]').forEach(item=>{ item.open=false; });
  bookingPanel.dataset.step=step; bookingPanel.scrollTop=0;
  bookingPanel.querySelectorAll('[data-step-marker]').forEach(marker=>{ if (Number(marker.dataset.stepMarker)===step) marker.setAttribute('aria-current','step'); else marker.removeAttribute('aria-current'); });
  if (!moveFocus) return;
  // On phones the keyboard would cover the summary, so only wide screens jump straight into the first field.
  if (step===1) bookingNext.focus();
  else if (matchMedia('(min-width:901px)').matches) bookingContact.querySelector('input[name=customer_name]').focus();
  else bookingContact.focus({preventScroll:true});
 }
 if (quick) {
  bookingNext.addEventListener('click',()=>showBookingStep(2));
  document.querySelector('#booking-back').addEventListener('click',()=>showBookingStep(1));
  // Enter on the first step moves on rather than submitting a form whose contact fields are still hidden.
  bookingChoose.addEventListener('keydown',event=>{ if (event.key==='Enter' && event.target.matches('input')) { event.preventDefault(); if (!bookingNext.disabled) bookingNext.click(); } });
  document.addEventListener('rv:booking-step-one',()=>{ if (bookingStep!==1) showBookingStep(1,false); });
 }
 const selected = () => {
  const d=data.find(d => String(d.id) === dateSelect.value); if (!privateDate || !d) return d;
  const rate=ratesOf(d).find(r=>r.level===privateLevel.value && Number(countInput.value)>=r.min && Number(countInput.value)<=r.max);
  return rate ? {...d, price_per_person:rate.price, start_date:privateDate.value, end_date:addDays(privateDate.value,Number(d.days)-1), packageRate:rate} : undefined;
 };
 // The calendar minimum is the first bookable date, not today's base date.
 function periodFor(start) {
  return data.find(d=>{const end=addDays(start,Number(d.days)-1); return start && start>=d.start_date && end<=d.end_date && start>=addDays(privateDate.dataset.today,Number(d.lead_days)) && !JSON.parse(d.blackout_dates || '[]').some(x=>x>=start && x<=end);});
 }
 const calendar=document.querySelector('#private-calendar');
 const thaiDate=new Intl.DateTimeFormat('th-TH',{day:'numeric',month:'short',year:'numeric',timeZone:'UTC'});
 const thaiFullDate=new Intl.DateTimeFormat('th-TH',{day:'numeric',month:'long',year:'numeric',timeZone:'UTC'});
 const thaiMonth=new Intl.DateTimeFormat('th-TH',{month:'long',year:'numeric',timeZone:'UTC'});
 const utcDate=iso=>new Date(iso+'T00:00:00Z');
 let calendarMonth='';
 function dayPrice(iso) {
  const d=periodFor(iso), count=Number(countInput.value);
  const rate=d && ratesOf(d).find(r=>r.level===privateLevel.value && count>=r.min && count<=r.max);
  return rate && Number(rate.price)>0 ? Number(rate.price) : null;
 }
 function firstPricedDay() {
  for (let iso=privateDate.min; iso && iso<=privateDate.max; iso=addDays(iso,1)) if (dayPrice(iso)) return iso;
  return privateDate.min;
 }
 function renderCalendar(focusSelector) {
  if (!calendar) return;
  if (!calendarMonth) calendarMonth=(privateDate.value || firstPricedDay()).slice(0,7);
  const first=calendarMonth+'-01', date=utcDate(first), blanks=date.getUTCDay();
  const length=new Date(Date.UTC(date.getUTCFullYear(),date.getUTCMonth()+1,0)).getUTCDate();
  const shift=step=>new Date(Date.UTC(date.getUTCFullYear(),date.getUTCMonth()+step,1)).toISOString().slice(0,7);
  const days=Array.from({length},(_,i)=>{ const iso=calendarMonth+'-'+String(i+1).padStart(2,'0'); return {iso,day:i+1,price:dayPrice(iso)}; });
  const prices=[...new Set(days.map(d=>d.price).filter(Boolean))];
  const start=privateDate.value, tripEnd=start && periodFor(start) ? addDays(start,Number(periodFor(start).days)-1) : '';
  const shortPrice=n=>n>=100000 ? Math.round(n/1000)+'k' : n.toLocaleString('en-US');
  calendar.replaceChildren();
  const head=document.createElement('div'); head.className='trip-calendar-head';
  const title=document.createElement('b'); title.textContent='วันเริ่มเดินทาง · '+thaiMonth.format(date); title.setAttribute('aria-live','polite');
  const monthButton=(step,icon,label,disabled)=>{ const b=document.createElement('button'); b.type='button'; b.dataset.month=shift(step); b.dataset.step=step; b.disabled=disabled; b.setAttribute('aria-label',label); b.innerHTML='<i class="material-icons" aria-hidden="true">'+icon+'</i>'; return b; };
  head.append(monthButton(-1,'chevron_left','เดือนก่อนหน้า',calendarMonth<=privateDate.min.slice(0,7)),title,monthButton(1,'chevron_right','เดือนถัดไป',calendarMonth>=privateDate.max.slice(0,7)));
  const grid=document.createElement('div'); grid.className='trip-calendar-grid';
  ['อา','จ','อ','พ','พฤ','ศ','ส'].forEach(name=>{ const cell=document.createElement('span'); cell.className='trip-calendar-weekday'; cell.textContent=name; cell.setAttribute('aria-hidden','true'); grid.append(cell); });
  for (let i=0;i<blanks;i++) grid.append(document.createElement('span'));
  days.forEach(d=>{
   const b=document.createElement('button'); b.type='button'; b.disabled=!d.price; b.dataset.date=d.iso;
   b.classList.toggle('is-start',d.iso===start); b.classList.toggle('is-trip',Boolean(tripEnd) && d.iso>start && d.iso<=tripEnd);
   b.setAttribute('aria-pressed',String(d.iso===start));
   b.setAttribute('aria-label',thaiFullDate.format(utcDate(d.iso))+(d.price ? ' ราคา '+format(d.price)+' ต่อคน' : ' จองไม่ได้'));
   const number=document.createElement('span'); number.textContent=d.day; b.append(number);
   if (d.price && prices.length>1) { const price=document.createElement('small'); price.textContent=shortPrice(d.price); b.append(price); }
   grid.append(b);
  });
  const note=document.createElement('small'); note.className='trip-calendar-note';
  note.textContent=!prices.length ? 'เดือนนี้ไม่มีวันที่จองได้สำหรับระดับและจำนวนคนนี้' : prices.length===1 ? 'วันที่เลือกได้ราคา '+format(prices[0])+' ต่อคน' : 'ราคาต่อคนตามวันเริ่มเดินทาง';
  calendar.append(head,grid,note);
  if (focusSelector) calendar.querySelector(focusSelector)?.focus();
 }
 if (calendar) {
  privateDate.closest('label').hidden=true;
  calendar.hidden=false;
  calendar.addEventListener('click',event=>{
   const button=event.target.closest('button');
   if (!button || button.disabled) return;
   if (button.dataset.month) {
    calendarMonth=button.dataset.month;
    // Stay on the same arrow; at the end of the range it is disabled, so use the other one.
    renderCalendar('[data-step="'+button.dataset.step+'"]:not([disabled])');
    if (!calendar.contains(document.activeElement)) calendar.querySelector('[data-month]:not([disabled])')?.focus();
    return;
   }
   privateDate.value=button.dataset.date;
   privateDate.dispatchEvent(new Event('change',{bubbles:true}));
   // The date is chosen, so return to the option rows; rooms may have opened themselves if they need attention.
   const dateDetails=document.querySelector('#date-details');
   dateDetails.open=false;
   (roomDetails.open ? roomDetails : dateDetails).querySelector('summary').focus();
  });
 }
 function selectPrivateDate() {
  const start=privateDate.value;
  const d=periodFor(start);
  dateSelect.value=d ? String(d.id) : '';
  const previous=privateLevel.value; privateLevel.replaceChildren();
  const levelRates=d ? ratesOf(d) : allRates;
  [...new Set(levelRates.map(r=>r.level))].forEach(level=>privateLevel.add(new Option(level,level)));
  if([...privateLevel.options].some(o=>o.value===previous)) privateLevel.value=previous;
  else if(!d && cheapestLevel()) privateLevel.value=cheapestLevel();
  const countOpen=d || allRates.length;
  countInput.disabled=!countOpen; countInput.min=countOpen ? Math.min(...levelRates.map(r=>r.min)) : 1; countInput.max=d ? d.capacity : countOpen ? Math.max(...data.map(x=>Number(x.capacity))) : 500;
  if(countOpen && Number(countInput.value)<Number(countInput.min)) countInput.value=countInput.min;
  if(quick && countOpen && Number(countInput.value)>Number(countInput.max)) countInput.value=countInput.max;
  privateDate.setCustomValidity(d || !start ? '' : 'วันที่นี้ไม่เปิดรับจอง กรุณาตรวจช่วงวัน วันงดเดินทาง และระยะจองล่วงหน้า');
  document.querySelector('#private-date-help').textContent=!start ? privateDate.dataset.emptyHint : d && calendar ? 'เดินทาง '+thaiDate.format(utcDate(start))+' – '+thaiDate.format(utcDate(addDays(start,Number(d.days)-1)))+' · รอทีมงานยืนยันบริการ' : d ? 'วันกลับ '+addDays(start,Number(d.days)-1)+' · จองล่วงหน้าอย่างน้อย '+Number(d.lead_days)+' วัน · รอทีมงานยืนยันบริการ' : 'วันที่นี้ไม่เปิดรับจอง ตรวจช่วงวัน วันงดเดินทาง และระยะจองล่วงหน้า';
  updatePrice();
 }
 if(privateDate){ privateDate.addEventListener('input',selectPrivateDate); privateDate.addEventListener('change',selectPrivateDate); privateLevel.addEventListener('change',updatePrice); }

 const format = n => '฿' + Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });
 const cents = value => Math.round(Number(value) * 100);
 const roomLabels = { twin: 'พักคู่ · 2 เตียงเดี่ยว', double: 'พักคู่ · 1 เตียงใหญ่', triple: 'ห้องพัก 3 คน', single: 'พักเดี่ยว · 1 คน/ห้อง', none: 'ไม่มีที่พักในโปรแกรม' };
 const roomNames = { twin: 'ห้องคู่ 2 เตียงเดี่ยว', double: 'ห้องคู่ 1 เตียงใหญ่', triple: 'ห้องพัก 3 คน', single: 'ห้องเดี่ยว' };
 // Keeps the panel heading and the mobile bar on the customer's own price; null restores the page's text.
 function setLive(selector, text) {
  const el = document.querySelector(selector);
  if (!el) return;
  el.dataset.liveDefault ??= el.textContent;
  el.textContent = text ?? el.dataset.liveDefault;
 }
 const lineLinks = document.querySelectorAll('[data-line-id]');
 const summary = document.querySelector('#trip-summary'), summaryCopy = document.querySelector('#trip-summary-copy');
 const lineUrl = (id, text) => id.startsWith('@') ? 'https://line.me/R/oaMessage/' + encodeURIComponent(id) + '/?' + encodeURIComponent(text) : 'https://line.me/R/ti/p/' + encodeURIComponent(id);
 let summaryText = '', copyTimer;
 function setCopyState(state) {
  const [icon, text] = { idle: ['content_copy', 'คัดลอกสรุป'], done: ['check', 'คัดลอกแล้ว — วางในแชท'], failed: ['error_outline', 'คัดลอกไม่ได้ — เลือกข้อความเอง'] }[state];
  summaryCopy.querySelector('i').textContent = icon;
  summaryCopy.querySelector('span').textContent = text;
  summaryCopy.classList.toggle('is-copied', state === 'done');
 }
 // navigator.clipboard needs https; the textarea route keeps copying working on plain http and older browsers.
 async function copyText(text) {
  try { await navigator.clipboard.writeText(text); return true; } catch (_) {}
  const area = document.createElement('textarea');
  area.value = text; area.setAttribute('readonly', ''); area.style.position = 'fixed'; area.style.opacity = '0';
  summaryCopy.after(area); area.select();
  let copied = false;
  try { copied = document.execCommand('copy'); } catch (_) {}
  area.remove(); summaryCopy.focus();
  return copied;
 }
 summaryCopy?.addEventListener('click', async () => {
  const copied = await copyText(summaryText);
  setCopyState(copied ? 'done' : 'failed');
  if (!copied) getSelection().selectAllChildren(document.querySelector('#trip-summary-body'));
  clearTimeout(copyTimer);
  copyTimer = setTimeout(() => setCopyState('idle'), 5000);
 });
 // One wording for the card, the clipboard and the prefilled LINE message.
 function tripSummaryLines(dep, count, validCount, validRoom, hasRooms, total) {
  const { program, title, code, duration } = summary.dataset;
  const name = /\d\s*วัน/.test(title) ? title : title + ' ' + duration;
  const lines = ['สนใจ' + program + ' "' + name + '" (' + code + ')'];
  if (dep) lines.push('วันเดินทาง: ' + thaiDate.format(utcDate(dep.start_date)) + ' – ' + thaiDate.format(utcDate(dep.end_date)));
  if (validCount) lines.push('ผู้เดินทาง: ' + count + ' คน');
  if (dep && privateDate) lines.push('ระดับบริการ: ' + privateLevel.value);
  if (dep && validRoom && hasRooms) lines.push('ห้องพัก: ' + rooms.filter(r => Number(r.input.value) > 0).map(r => roomNames[r.type] + ' × ' + r.input.value).join(', '));
  if (dep && validCount && validRoom) lines.push('ยอดรวม: ' + total + ' (ทีมงานยืนยันอีกครั้ง)');
  if (document.querySelector('#ticket-help')?.checked) lines.push('ต้องการให้ทีมงานช่วยจองตั๋วเครื่องบิน');
  return lines;
 }
 function updatePrice() {
  const dep = selected(), count = Number(countInput.value), raw=data.find(d=>String(d.id)===dateSelect.value), limit = Number((privateDate ? raw?.capacity ?? (allRates.length ? countInput.max : 0) : dep?.seats_available) || 0);
  const validCount = Number.isInteger(count) && count >= 1 && count <= limit;
  const hasRooms = dep && Number(dep.room_available);
  const preview = !raw && allRates.length ? previewRate() : undefined;
  if (quick && dep && validCount && !roomsTouched) suggestRooms(dep,count);
  const validNumbers = rooms.every(r => r.input.value !== '' && Number.isInteger(Number(r.input.value)) && Number(r.input.value) >= 0 && Number(r.input.value) <= 500);
  const occupied = rooms.reduce((sum,r) => sum + Number(r.input.value) * r.capacity, 0);
  const singleCount = Number(rooms.find(r => r.type === 'single').input.value);
  const validRoom = dep && validNumbers && (hasRooms ? occupied === count && (!singleCount || dep.single_supplement !== null) : occupied === 0);
  const baseTotal = dep && validCount ? cents(dep.price_per_person) * count : 0;
  const supplementTotal = dep && validNumbers && singleCount && dep.single_supplement !== null ? cents(dep.single_supplement) * singleCount : 0;
  document.querySelector('#price-person').textContent = dep ? format(dep.price_per_person) : preview ? 'เริ่มต้น ' + format(preview.price) : '—';
  document.querySelector('#base-count').textContent = validCount ? count + ' คน' : '';
  document.querySelector('#base-total').textContent = dep && validCount ? format(baseTotal / 100) : preview && validCount ? 'เริ่มต้น ' + format(cents(preview.price) * count / 100) : '—';
  document.querySelector('#supplement-count').textContent = validNumbers && singleCount ? singleCount + ' ห้อง' : '';
  document.querySelector('#supplement-total').textContent = dep && validNumbers ? format(supplementTotal / 100) : '—';
  document.querySelector('#price-total').textContent = dep && validCount && validRoom ? format((baseTotal + supplementTotal) / 100) : '—';
  document.querySelector('.booking-total').classList.toggle('has-supplement', supplementTotal > 0);
  document.querySelector('#group-capacity').textContent = dep ? dep.capacity + ' คน' : '—';
  document.querySelector('#seats-available').textContent = dep ? dep.seats_available + ' ที่นั่ง' : '—';
  document.querySelector('#departure-quote').value = dep?.quote || '';
  minus.disabled = !(privateDate ? raw || allRates.length : dep) || limit < 1 || count <= Number(countInput.min) || !Number.isFinite(count);
  plus.disabled = !(privateDate ? raw || allRates.length : dep) || limit < 1 || count >= limit;
  document.querySelector('#traveler-help').textContent = dep ? 'จองได้ 1–' + limit + ' คน ตามที่นั่งว่างของรอบ' : 'เลือกวันเดินทางก่อน · จองได้ตั้งแต่ 1 คนตามที่นั่งว่าง';
  if(privateDate){
   document.querySelector('#traveler-help').textContent=raw ? ratesOf(raw).filter(r=>r.level===privateLevel.value).map(r=>rateRange(r)).join(' / ')+(dep ? ' · กรุ๊ปส่วนตัว' : ' · จำนวนนี้ไม่มีราคา กรุณาปรับจำนวนหรือสอบถามทีมงาน') : allRates.length ? [...new Set(allRates.filter(r=>r.level===privateLevel.value).map(r=>rateRange(r)))].join(' / ')+(preview ? ' · กรุ๊ปส่วนตัว' : ' · จำนวนนี้ไม่มีราคา กรุณาปรับจำนวนหรือสอบถามทีมงาน') : 'เลือกวันที่เปิดรับจองก่อน';
   document.querySelector('#private-details').textContent=dep?.packageRate?.details || preview?.details || '';
   const levelDetails=document.querySelector('#level-details'), hasDetails=Boolean(document.querySelector('#private-details').textContent);
   if (levelDetails) { levelDetails.hidden=!hasDetails; if (!hasDetails) levelDetails.open=false; }
  }
  rooms.forEach(r => {
   const available = hasRooms && limit > 0 && (r.type !== 'single' || dep.single_supplement !== null);
   r.input.disabled = !available;
   r.input.max = String(Math.floor(count / r.capacity) || 0);
   r.minus.disabled = !available || !(Number(r.input.value) > 0);
   r.plus.disabled = !available || !validCount || !validNumbers || occupied + r.capacity > count;
   document.querySelector('[data-room="' + r.type + '"]').hidden = Boolean(dep && !hasRooms);
  });
  document.querySelector('#single-room-price').textContent = !dep ? 'เลือกวันเดินทางเพื่อดูราคาเพิ่ม' : dep.single_supplement === null ? 'สอบถามค่าพักเดี่ยวกับทีมงาน' : '+' + format(dep.single_supplement) + ' / ห้อง / ตลอดทริป';
  let roomHelp = 'เลือกวันเดินทางก่อน แล้วระบุจำนวนห้องพัก';
  if (dep && !hasRooms) roomHelp = 'รอบนี้ไม่มีที่พัก จึงไม่มีค่าพักเดี่ยวเพิ่ม';
  else if (dep) {
   roomHelp = validNumbers && validCount ? 'จัดห้องแล้ว ' + occupied + ' / ' + count + ' คน' + (occupied === count ? ' · ครบแล้ว' : occupied < count ? ' · เหลืออีก ' + (count - occupied) + ' คน' : ' · กรุณาลดจำนวนห้อง') : 'กรุณาระบุจำนวนผู้เดินทางและจำนวนห้องเป็นจำนวนเต็ม';
   if (dep.single_supplement === null) roomHelp += ' · หากต้องการห้องเดี่ยว กรุณาติดต่อ LINE เพื่อให้ทีมงานจัดห้องและแจ้งราคา';
  }
  document.querySelector('#room-help').textContent = roomHelp;
  document.querySelector('#booking-form button[type=submit]').disabled = !validCount || !validRoom;
  if (quick) {
   const ready = Boolean(dep && validCount && validRoom);
   if (privateDate) document.querySelector('#date-summary').textContent = raw ? thaiDate.format(utcDate(privateDate.value)) + ' – ' + thaiDate.format(utcDate(addDays(privateDate.value, Number(raw.days) - 1))) : 'เลือกวันเริ่มเดินทาง';
   document.querySelector('#room-summary').textContent = !dep ? 'เลือกวันเดินทางก่อน' : !hasRooms ? 'ไม่มีที่พักในโปรแกรม' : ready ? rooms.filter(r => Number(r.input.value) > 0).map(r => roomNames[r.type] + ' × ' + r.input.value).join(', ') : 'ยังจัดห้องไม่ครบ';
   if (dep && hasRooms && !ready && !roomsTouched) roomDetails.open = true;
   bookingNext.disabled = !ready;
   if (privateDate) renderCalendar(calendar?.contains(document.activeElement) && document.activeElement.dataset.date ? '[data-date="'+document.activeElement.dataset.date+'"]' : '');
   const perPerson = dep ? dep.price_per_person : preview?.price;
   const grandTotal = format((baseTotal + supplementTotal) / 100);
   setLive('[data-live-label]', dep ? 'ราคาของคุณ' : 'เริ่มต้น');
   setLive('[data-live-price]', perPerson ? format(perPerson) : null);
   setLive('[data-live-note]', ready ? 'ยอดรวม ' + grandTotal + ' · ' + count + ' คน' : null);
   setLive('[data-live-mobile-label]', ready ? 'ยอดรวม ' + count + ' คน' : perPerson ? (dep ? 'ราคาของคุณ / คน' : 'เริ่มต้น / คน') : null);
   setLive('[data-live-mobile-price]', ready ? grandTotal : perPerson ? format(perPerson) : null);
   bookingNext.textContent = ready ? 'ถัดไป · กรอกข้อมูลติดต่อ' : !dep ? (privateDate ? 'เลือกวันเดินทางเพื่อดูยอดจริง' : 'เลือกรอบเดินทางเพื่อดูราคา') : 'จัดห้องพักให้ครบก่อน';
   // A returning customer keeps their contact details, but the contact step only makes sense with a complete trip.
   if (bookingStep===2 && !ready) showBookingStep(1,false);
   else if (resumeContact && ready) { resumeContact=false; showBookingStep(2,false); }
  }
  showFlights();
  if (summary) {
   const lines = tripSummaryLines(dep, count, validCount, validRoom, hasRooms, format((baseTotal + supplementTotal) / 100));
   const text = lines.join('\n') + '\n' + location.href.split('#')[0];
   if (summaryText && text !== summaryText) setCopyState('idle');
   summaryText = text;
   document.querySelector('#trip-summary-body').replaceChildren(...lines.map(item => { const row = document.createElement('p'); row.textContent = item; return row; }));
   lineLinks.forEach(link => { link.href = lineUrl(link.dataset.lineId, text); });
  }
 }
 // The flights shown follow the round chosen in the booking form.
 function showFlights() {
  const sets = document.querySelectorAll('[data-flight-set]');
  const dep = selected();
  if (!sets.length || !dep) return;
  const id = String(dep.flight_set_id || '');
  let found = false;
  sets.forEach(set => { const match = set.dataset.flightSet === id; set.hidden = !match; found = found || match; });
  document.querySelector('[data-flight-none]').hidden = found;
  const round = document.querySelector('[data-flight-round]');
  round.hidden = false;
  round.textContent = 'รอบเดินทาง ' + dateSelect.options[dateSelect.selectedIndex].text;
 }
 document.querySelector('#ticket-help')?.addEventListener('change', updatePrice);
 dateSelect.addEventListener('change', () => {
  const dep = selected(), limit = Number(dep?.seats_available || 0);
  countInput.disabled = !dep || limit < 1; countInput.max = String(Math.max(1, limit));
  countInput.value = String(Math.max(1, Math.min(limit || 1, Math.trunc(Number(countInput.value)) || 1)));
  rooms.forEach(r => { if (!dep || !Number(dep.room_available) || (r.type === 'single' && dep.single_supplement === null)) r.input.value = '0'; });
  updatePrice();
 });
 countInput.addEventListener('input', updatePrice);
 countInput.addEventListener('change', updatePrice);
 rooms.forEach(r => {
  const touchRooms = () => { roomsTouched = true; updatePrice(); };
  r.input.addEventListener('input', touchRooms); r.input.addEventListener('change', touchRooms);
  [r.minus,r.plus].forEach(button => button.addEventListener('click', () => {
   roomsTouched = true;
   r.input.value = String(Math.max(0, (Math.trunc(Number(r.input.value)) || 0) + (button === r.plus ? 1 : -1))); updatePrice();
  }));
 });
 [minus, plus].forEach(button => button.addEventListener('click', () => {
  const limit = privateDate ? Number(countInput.max) : Number(selected()?.seats_available || 0); if (!limit) return;
  const current = Math.trunc(Number(countInput.value)) || 1;
  countInput.value = String(Math.max(Number(countInput.min), Math.min(limit, current + (button === plus ? 1 : -1)))); updatePrice();
 }));
 const requestedDeparture = new URLSearchParams(location.search).get('departure');
 if (requestedDeparture && [...dateSelect.options].some(o => o.value === requestedDeparture && !o.disabled)) dateSelect.value = requestedDeparture;
 // Recompute controls on history restoration, including after a submitted form.
 window.addEventListener('pageshow', () => roomsTouched = rooms.some(r => Number(r.input.value) > 0));
 window.addEventListener('pageshow', () => privateDate ? selectPrivateDate() : dateSelect.dispatchEvent(new Event('change')));
 document.querySelector('#booking-form').addEventListener('submit', function () { const button = this.querySelector('button[type="submit"]'); button.disabled = true; button.textContent = button.dataset.busy || 'กำลังส่งคำขอ…'; });
}
const destinationPicker = document.querySelector('#tour-destination-picker');
if (destinationPicker) {
 const nodes = JSON.parse(document.querySelector('#destination-options').textContent), list = document.querySelector('#tour-destination-rows');
 function rebuild(select, kind, parent, placeholder) {
  const previous = select.value; select.replaceChildren(new Option(placeholder, ''));
  nodes.filter(o => o.kind === kind && String(o.parent_id) === parent).forEach(o => select.add(new Option(o.name,String(o.id))));
  select.value = [...select.options].some(o => o.value === previous) ? previous : '';
  select.disabled = !parent;
 }
 function update(row, countryOnly = false) {
  const zone=row.querySelector('[data-place-kind=zone]'), country=row.querySelector('[data-place-kind=country]'), route=row.querySelector('[data-place-kind=route]');
  if (!countryOnly) rebuild(country,'country',zone.value,'เลือกประเทศ');
  rebuild(route,'route',country.value,'เลือกเส้นทาง');
 }
 function renumberPlaces() {
  [...list.children].forEach((row,i) => {
   row.querySelector('.place-number').textContent=String(i+1);
   row.querySelectorAll('[data-place-kind]').forEach(select => { select.name='destinations['+i+']['+select.dataset.placeKind+'_id]'; select.id='tour-'+select.dataset.placeKind+(i ? '-'+i : ''); });
   row.querySelector('[data-place-up]').disabled=i===0;
   row.querySelector('[data-place-down]').disabled=i===list.children.length-1;
   row.querySelector('[data-place-remove]').disabled=list.children.length===1;
  });
  destinationPicker.querySelector('[data-place-add]').disabled=list.children.length>=60;
 }
 destinationPicker.addEventListener('change', event => { const kind=event.target.dataset.placeKind; if (kind==='zone' || kind==='country') update(event.target.closest('.tour-place-row'),kind==='country'); });
 destinationPicker.addEventListener('click', event => {
  const button=event.target.closest('button'); if (!button) return;
  const row=button.closest('.tour-place-row');
  if (button.hasAttribute('data-place-add') && list.children.length<60) { list.insertAdjacentHTML('beforeend',document.querySelector('#tour-destination-template').innerHTML); update(list.lastElementChild); list.lastElementChild.querySelector('select').focus(); }
  if (row && button.hasAttribute('data-place-remove') && list.children.length>1) row.remove();
  if (row && button.hasAttribute('data-place-up') && row.previousElementSibling) list.insertBefore(row,row.previousElementSibling);
  if (row && button.hasAttribute('data-place-down') && row.nextElementSibling) list.insertBefore(row.nextElementSibling,row);
  renumberPlaces();
 });
 [...list.children].forEach(row=>update(row)); renumberPlaces();
 document.querySelector('#tour-editor').addEventListener('submit',renumberPlaces);
 window.addEventListener('pageshow', () => { [...list.children].forEach(row=>update(row)); renumberPlaces(); });
}
const editor = document.querySelector('#tour-editor');
if (editor) {
 let nextDay = document.querySelectorAll('.day-item').length, nextDeparture = document.querySelectorAll('.departure-item').length;
 function renumber() { document.querySelectorAll('.day-number').forEach((el, i) => el.textContent = i + 1); }
 function reorderInputs(selector, prefix) {
  document.querySelectorAll(selector).forEach((row, i) => row.querySelectorAll('[name]').forEach(el => el.name = el.name.replace(new RegExp('^' + prefix + '\\[[^\\]]+\\]'), prefix + '[' + i + ']')));
 }
 editor.addEventListener('click', event => {
  const button = event.target.closest('button'); if (!button) return;
  if (button.dataset.add) {
   const kind = button.dataset.add, index = kind === 'day' ? nextDay++ : nextDeparture++;
   const list = document.querySelector('#' + kind + '-list');
   if (list.children.length >= 60) { alert('เพิ่มได้สูงสุด 60 รายการ'); return; }
   const template = document.querySelector('#' + kind + '-template').innerHTML.replaceAll('__INDEX__', index);
   list.insertAdjacentHTML('beforeend', template); renumber(); list.lastElementChild.querySelector('input:not([type=hidden])')?.focus();
  }
  if (button.hasAttribute('data-remove')) { if (confirm('นำรายการนี้ออก? การเปลี่ยนแปลงจะมีผลเมื่อบันทึกโปรแกรม')) button.closest('.repeater-item').remove(); renumber(); }
  if (button.dataset.move) {
   const row = button.closest('.repeater-item, .gallery-edit');
   if (button.dataset.move === 'up' && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
   else if (button.dataset.move === 'down' && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
   renumber();
  }
 });
 editor.addEventListener('submit', () => { reorderInputs('.day-item', 'itinerary'); reorderInputs('.gallery-edit', 'images'); });
}

document.querySelectorAll('.vehicle-edit').forEach(vehicleEdit => {
 const form = vehicleEdit.closest('form'), list = vehicleEdit.querySelector('#vehicle-list'), sample = vehicleEdit.querySelector('[data-vehicle-sample]');
 const token = () => form.querySelector('input[name=_token]').value;
 const settings = vehicleEdit.dataset;
 const select = vehicleEdit.querySelector('[data-vehicle-template-select]'), templateStatus = vehicleEdit.querySelector('[data-vehicle-template-status]');
 let next = Number(settings.vehicleNext), uploads = 0;
 const setPhoto = (row, path) => {
  const type = row.querySelector('[data-vehicle-type]'), preview = row.querySelector('[data-vehicle-preview]');
  row.querySelector('[data-vehicle-image]').value = path;
  preview.src = path ? settings.assetBase + path : type.selectedOptions[0].dataset.image;
  preview.classList.toggle('is-photo', Boolean(path));
  row.querySelector('[data-vehicle-pick-label]').textContent = path ? 'เปลี่ยนรูปรถ' : 'อัปโหลดรูปรถจริง';
  row.querySelector('[data-vehicle-photo-remove]').hidden = !path;
 };
 const add = values => {
  list.insertAdjacentHTML('beforeend', vehicleEdit.querySelector('#vehicle-template').innerHTML.replaceAll('__INDEX__', next++));
  const row = list.lastElementChild;
  sample.hidden = true;
  Object.entries(values || {}).forEach(([field, value]) => { row.querySelector('[name$="[' + field + ']"]').value = value; });
  setPhoto(row, values?.image || '');
  if (values?.image) row.querySelector('[data-vehicle-status]').textContent = '';
 };
 const readRows = () => [...list.children].map(row => Object.fromEntries(['min', 'max', 'type', 'seats', 'image'].map(field => [field, row.querySelector('[name$="[' + field + ']"]').value])));
 const applyTemplate = () => {
  const option = select.selectedOptions[0];
  if (!option.value) { templateStatus.textContent = 'เลือก template ก่อน'; return; }
  if (list.children.length && !confirm('แทนที่ตารางรถในโปรแกรมนี้ด้วย ' + option.textContent + '?')) return;
  list.replaceChildren();
  JSON.parse(option.dataset.rows).forEach(add);
  templateStatus.textContent = 'ใช้ ' + option.textContent + ' แล้ว แก้เฉพาะโปรแกรมนี้ได้ ตารางจะแสดงบนเว็บเมื่อบันทึกโปรแกรม';
 };
 const saveTemplate = async () => {
  if (uploads) { templateStatus.textContent = 'กรุณารอให้อัปโหลดรูปเสร็จก่อน'; return; }
  if (!list.children.length) { templateStatus.textContent = 'เพิ่มรถอย่างน้อย 1 แถวก่อนบันทึกเป็น template'; return; }
  const name = (prompt('ตั้งชื่อ template เช่น Land A') || '').trim();
  if (!name) return;
  const data = new FormData();
  data.append('_token', token());
  data.append('name', name);
  data.append('folder', settings.uploadFolder);
  data.append('rows', JSON.stringify(readRows()));
  templateStatus.textContent = 'กำลังบันทึก template …';
  try {
   const response = await fetch(settings.templateSaveUrl, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
   const result = await response.json();
   if (!response.ok || !result.id) throw Error(result.error || 'บันทึก template ไม่สำเร็จ');
   const option = new Option(result.name, result.id, true, true);
   option.dataset.rows = JSON.stringify(result.rows);
   select.add(option);
   templateStatus.textContent = 'บันทึก template “' + result.name + '” แล้ว ใช้กับโปรแกรมอื่นได้ทันที';
  } catch (error) { templateStatus.textContent = error.message; }
 };
 vehicleEdit.addEventListener('click', event => {
  const button = event.target.closest('button'); if (!button) return;
  if (button.hasAttribute('data-vehicle-add')) { if (list.children.length < 12) add(); else alert('เพิ่มรถได้สูงสุด 12 แถว'); }
  if (button.hasAttribute('data-vehicle-sample')) JSON.parse(button.dataset.rows).forEach(add);
  if (button.hasAttribute('data-vehicle-remove')) { button.closest('.vehicle-row').remove(); sample.hidden = list.children.length > 0; }
  if (button.hasAttribute('data-vehicle-photo-remove')) { const row = button.closest('.vehicle-row'); setPhoto(row, ''); row.querySelector('[data-vehicle-status]').textContent = 'จะใช้ภาพวาดแทนเมื่อบันทึก'; }
  if (button.hasAttribute('data-vehicle-template-apply')) applyTemplate();
  if (button.hasAttribute('data-vehicle-template-save')) saveTemplate();
 });
 vehicleEdit.addEventListener('change', async event => {
  const row = event.target.closest('.vehicle-row'); if (!row) return;
  if (event.target.matches('[data-vehicle-type]')) { setPhoto(row, row.querySelector('[data-vehicle-image]').value); return; }
  if (!event.target.matches('[data-vehicle-file]') || !event.target.files[0]) return;
  const status = row.querySelector('[data-vehicle-status]'), data = new FormData();
  data.append('_token', token());
  data.append('folder', settings.uploadFolder);
  data.append('photo', event.target.files[0]);
  event.target.value = '';
  status.textContent = 'กำลังอัปโหลดรูป …';
  uploads++;
  try {
   const response = await fetch(settings.uploadUrl, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
   const result = await response.json();
   if (!response.ok || !result.path) throw Error(result.error || 'อัปโหลดไม่สำเร็จ');
   setPhoto(row, result.path);
   status.textContent = 'อัปโหลดแล้ว รูปจะแสดงเมื่อกดบันทึก';
  } catch (error) { status.textContent = error.message; }
  uploads--;
 });
 form.addEventListener('submit', event => {
  if (!uploads) return;
  event.preventDefault(); event.stopImmediatePropagation();
  alert('กำลังอัปโหลดรูปรถ กรุณารอให้เสร็จก่อนบันทึก');
 }, true);
});

document.querySelectorAll('[data-branding-upload]').forEach(input=>{
 let objectUrl;
 input.addEventListener('change',()=>{
  const slot=input.closest('.branding-slot'), img=slot.querySelector('.branding-preview img'), empty=slot.querySelector('.branding-preview span');
  if(!Object.prototype.hasOwnProperty.call(img.dataset,'original')) img.dataset.original=img.getAttribute('src')||'';
  if(objectUrl) URL.revokeObjectURL(objectUrl);
  const file=input.files[0]; input.setCustomValidity(file && file.size>2*1024*1024 ? 'ขนาดไฟล์ต้องไม่เกิน 2 MB' : '');
  if(file && input.checkValidity()){objectUrl=URL.createObjectURL(file);img.src=objectUrl;img.hidden=false;empty.hidden=true;const remove=slot.querySelector('[type=checkbox]');if(remove) remove.checked=false;}
  else{img.hidden=!img.dataset.original;empty.hidden=!img.hidden;if(img.dataset.original)img.src=img.dataset.original;else img.removeAttribute('src');if(file)input.reportValidity();}
 });
});

if (editor) {
 const programType=editor.querySelector('#program-type');
 function refreshPrivate(){
  const privateMode=programType.value==='private';
  editor.querySelectorAll('[data-private-only], [data-fixed-only]').forEach(el=>{const active=el.hasAttribute('data-private-only')===privateMode;el.hidden=!active;el.querySelectorAll('input,select,textarea,button').forEach(input=>input.disabled=!active);});
  editor.querySelectorAll('[data-start-label]').forEach(el=>el.textContent=privateMode ? 'เดินทางได้ตั้งแต่' : 'วันเริ่มเดินทาง');
  editor.querySelectorAll('[data-end-label]').forEach(el=>el.textContent=privateMode ? 'วันกลับต้องไม่เกิน' : 'วันกลับ');
 }
 programType.addEventListener('change',refreshPrivate);
 const flightSetList=editor.querySelector('#flight-set-list'), flightIncluded=editor.querySelector('[name=flight_included]');
 let flightCounter=Date.now()%1000000000;
 function syncFlightSets(){
  const sets=[...flightSetList.children].map(set=>({key:set.querySelector('[name$="[key]"]').value,name:set.querySelector('[name$="[name]"]').value.trim()||'ชุดยังไม่มีชื่อ',usage:set.querySelector('[data-flight-set-usage]')}));
  const selects=[...editor.querySelectorAll('[data-flight-set-select]')];
  selects.forEach(select=>{
   const current=select.value;
   select.replaceChildren(new Option(sets.length?'ยังไม่เลือกชุด':'ยังไม่มีชุดเที่ยวบิน',''));
   sets.forEach(set=>select.add(new Option(set.name,set.key)));
   select.value=sets.some(set=>set.key===current)?current:(sets.length===1?sets[0].key:'');
  });
  sets.forEach(set=>{set.usage.textContent='ใช้กับ '+selects.filter(select=>select.value===set.key).length+' รอบ';});
 }
 function flightRowHtml(setIndex){return document.querySelector('#flight-row-template').innerHTML.replaceAll('__SET__',setIndex).replaceAll('__ROW__',++flightCounter);}
 function refreshFlightTitle(){editor.querySelector('[data-flight-title]').textContent=flightIncluded.value==='1'?'เที่ยวบินของทัวร์นี้':'เที่ยวบินแนะนำ';}
 flightSetList.addEventListener('input',syncFlightSets);
 editor.querySelector('#departure-list').addEventListener('change',syncFlightSets);
 flightIncluded.addEventListener('change',()=>{
  refreshFlightTitle();
  // Switching the ticket type changes what every leg means, so carry the answer down to the rows.
  flightSetList.querySelectorAll('[data-flight-included]').forEach(select=>{select.value=flightIncluded.value;});
 });
 editor.addEventListener('click',event=>{
  const button=event.target.closest('button'); if(!button)return;
  if(button.hasAttribute('data-flight-set-add')){
   const index=++flightCounter;
   flightSetList.insertAdjacentHTML('beforeend',document.querySelector('#flight-set-template').innerHTML.replaceAll('__SET__',index));
   const added=flightSetList.lastElementChild;
   added.querySelector('[name$="[key]"]').value='n'+index;
   added.querySelector('.flight-rows').innerHTML=flightRowHtml(index);
   added.querySelectorAll('[data-flight-included]').forEach(select=>{select.value=flightIncluded.value;});
   added.querySelector('[name$="[name]"]').focus();
  }
  if(button.hasAttribute('data-flight-add')){
   const set=button.closest('.flight-set-item'), rows=set.querySelector('.flight-rows');
   if(rows.children.length>=12){alert('ชุดเที่ยวบินหนึ่งชุดมีได้สูงสุด 12 เที่ยวบิน');return;}
   const setIndex=set.querySelector('[name$="[key]"]').name.match(/^flight_sets\[([^\]]+)\]/)[1];
   rows.insertAdjacentHTML('beforeend',flightRowHtml(setIndex));
   rows.lastElementChild.querySelector('[data-flight-included]').value=flightIncluded.value;
  }
  if(button.hasAttribute('data-flight-remove')){
   const row=button.closest('.flight-row');
   if(row.parentNode.children.length>1)row.remove();
   else row.querySelectorAll('input:not([type=checkbox])').forEach(input=>{input.value='';});
  }
  if(button.hasAttribute('data-flight-set-remove') && confirm('นำชุดเที่ยวบินนี้ออก? รอบที่ใช้ชุดนี้จะไม่มีเที่ยวบินจนกว่าจะเลือกชุดใหม่'))button.closest('.flight-set-item').remove();
  if(button.hasAttribute('data-flight-set-copy')){
   const source=button.closest('.flight-set-item'), copy=source.cloneNode(true), index=++flightCounter;
   const sourceFields=[...source.querySelectorAll('select')];
   copy.querySelectorAll('select').forEach((select,i)=>{select.value=sourceFields[i].value;});
   copy.querySelectorAll('[name]').forEach(el=>{el.name=el.name.replace(/^flight_sets\[[^\]]+\]/,'flight_sets['+index+']');});
   copy.querySelector('[name$="[key]"]').value='n'+index;
   const copyName=copy.querySelector('[name$="[name]"]');copyName.value=(copyName.value||'ชุดเที่ยวบิน')+' (สำเนา)';
   source.after(copy);
  }
  syncFlightSets();
 });
 editor.addEventListener('click',event=>{
  const button=event.target.closest('button'); if(!button)return;
  if(button.hasAttribute('data-rate-add')){
   const list=button.previousElementSibling;
   if(list.children.length>=30)return;
   const row=list.firstElementChild.cloneNode(true);
   row.querySelectorAll('input,textarea').forEach(el=>el.value=el.type==='number' && el.step!=='0.01' ? '2' : '');
   list.append(row);
  }
  if(button.hasAttribute('data-rate-remove')){const row=button.closest('.private-rate');if(row.parentNode.children.length>1)row.remove();}
  refreshPrivate();
 });
 editor.addEventListener('submit',()=>editor.querySelectorAll('.departure-item').forEach((dep,i)=>{
  dep.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/^departures\[[^\]]+\]/,'departures['+i+']'));
  dep.querySelectorAll('.private-rate').forEach((row,j)=>row.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/\[rates\]\[[^\]]+\]/,'[rates]['+j+']')));
 }));
 refreshPrivate();
 syncFlightSets();
 refreshFlightTitle();
}
// The link still opens the image in a new tab if the script fails.
document.querySelectorAll('[data-license-open]').forEach(link => {
 const dialog = document.querySelector('.license-dialog');
 link.addEventListener('click', event => { event.preventDefault(); dialog.showModal(); });
 dialog.querySelector('[data-license-close]').addEventListener('click', () => dialog.close());
 dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
});
