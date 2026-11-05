(() => {
 'use strict';
 const form=document.querySelector('#tour-editor'), panel=document.querySelector('#tour-import');
 if (!panel) return;
 const $=s=>document.querySelector(s), nodes=JSON.parse($('#destination-options').textContent);
 const fields=['title','flight_included','days','nights','summary','highlights','hotel_status','hotel_details','transport_status','transport_details','guide_status','guide_details','photographer_status','photographer_details','inclusions','exclusions','payment_terms','cancellation_terms','know_before_go','terms','deposit_per_person'];
 const dayFields=['title','description','hotel','breakfast','lunch','dinner'];
 const flightFields=['leg_type','day_number','airline','flight_number','from_airport','to_airport','depart_time','arrive_time','arrive_next_day','duration','included'];
 const depFields=['start_date','end_date','capacity','price_per_person','single_supplement','deposit_cutoff_days','balance_due_days','lead_days','blackout_dates'];
 const typeSelect=$('#tour-import-type'), programType=$('#program-type');
 const typeLabels={fixed:'ทัวร์ปกติ',private:'แพ็คเกจทัวร์ส่วนตัว'};
 const levels=[...$('#departure-template').content.querySelectorAll('[name$="[level_id]"] option')].filter(o=>o.value).map(o=>o.textContent);
 function refreshPrompt() {
 const type=typeSelect.value, isPrivate=type==='private';
 const departureFields=depFields.filter(k=>isPrivate ? !['capacity','price_per_person'].includes(k) : !['lead_days','blackout_dates'].includes(k));
 const schema={schema_version:1,program_type:type,...Object.fromEntries(fields.map(k=>[k,null])),review_notes:[],destinations:[{zone:null,country:null,route:null}],...(isPrivate?{}:{flights:[Object.fromEntries(flightFields.map(k=>[k,null]))]}),itinerary:[{...Object.fromEntries(dayFields.map(k=>[k,null])),hotel_options:[]}],departures:[{...Object.fromEntries(departureFields.map(k=>[k,null])),deposit_enabled:null,...(isPrivate?{rates:[{level:null,min:null,max:null,price:null,details:null}]}:{})}]};
 const pricing=isPrivate
  ? 'แพ็คเกจทัวร์ส่วนตัว: departures แต่ละแถวคือช่วงวันที่เปิดให้ลูกค้าเลือกเดินทาง start_date คือวันแรกที่เริ่มเดินทางได้ end_date คือวันสุดท้ายที่ต้องเดินทางกลับถึง ไม่ใช่วันไป/กลับของรอบทัวร์ตายตัว ห้ามแปลงรอบทัวร์ปกติเป็นช่วงเปิดขายเอง หากเอกสารไม่ระบุช่วงให้เว้น null\nราคาใช้ rates ทุกระดับบริการและทุกช่วงจำนวนคน level คือชื่อระดับ min/max คือจำนวนคนขั้นต่ำ/สูงสุด price คือราคาต่อคนหน่วยบาท details คือบริการของเรตนั้น ห้ามใช้ราคารวมกรุ๊ปแทนราคาต่อคน หาก PDF มีเพียงราคารวมให้เก็บข้อความใน terms และเว้น price\nlead_days คือวันจองล่วงหน้า blackout_dates คือวันงดเดินทางรูปแบบ YYYY-MM-DD หนึ่งวันต่อบรรทัด ห้ามกำหนดที่นั่งหรือราคาเดียวต่อรอบ Standard กับ Economy คือระดับเดียวกัน ให้ใช้ชื่อ Economy ในทุกช่อง หากระดับอื่นจับคู่ไม่ได้ให้ใช้ชื่อจาก PDF'
  : 'ทัวร์ปกติ: departures แต่ละแถวคือรอบเดินทาง start_date คือวันไป end_date คือวันกลับของรอบนั้น ใส่ทุกรอบตาม PDF\ncapacity คือจำนวนที่นั่งทั้งหมดของรอบ ไม่ใช่จำนวนที่นั่งคงเหลือ price_per_person คือราคาผู้ใหญ่ปกติต่อคนหน่วยบาท ไม่ใช้ราคาเด็ก/โปรโมชั่นแทน ไม่ใช้ rates หรือช่วงราคาตามขนาดกรุ๊ป';
 $('#tour-import-type-help').textContent=isPrivate
  ? 'แพ็คเกจทัวร์ส่วนตัว: ช่วงวันที่เลือกเดินทางได้ · วันงดเดินทาง · จองล่วงหน้า · ราคาตามระดับบริการและจำนวนคน'
  : 'ทัวร์ปกติ: วันไป–กลับแน่นอนในแต่ละรอบ · จำนวนที่นั่ง · ราคาต่อคนและค่าพักเดี่ยว';
 $('#copy-tour-prompt').textContent='คัดลอก Prompt · '+typeLabels[type];
 $('#prompt-status').textContent='';
 const prompt=`อ่าน PDF ที่แนบแล้วแปลงเป็น JSON สำหรับ${typeLabels[type]} ส่ง JSON object เดียว ไม่ใช้ Markdown และไม่เพิ่ม key นอกโครงสร้าง
ห้ามเดาหรือเติมข้อมูลเอง ข้อมูลที่ PDF ไม่ระบุหรืออ่านไม่ชัดให้ใช้ null (ห้ามใช้ 0 หรือ false แทนข้อมูลที่ขาด) รายการที่ไม่มีใช้ [] ไม่คัดลอกแถวตัวอย่างว่าง
ลูกค้าจะเห็นทุกข้อความใน JSON ยกเว้น review_notes จึงต้องเขียนถึงลูกค้าโดยตรง ห้ามอ้างถึงการอ่านต้นฉบับ เช่น "เอกสารระบุ" "หน้าปกระบุ" "ตารางสรุป" "PDF" "ไม่ระบุ" ข้อมูลส่วนไหนไม่มีให้เว้นไว้ ไม่ต้องเขียนว่าไม่มี
ข้อมูลขัดแย้งกัน ข้อมูลที่ขาด การจับคู่ที่ไม่แน่ใจ และข้อสังเกตถึงผู้ดูแล ให้ใส่ใน review_notes เป็นรายการข้อความเท่านั้น ห้ามใส่ใน terms หรือช่องอื่น
ชื่อวันใน itinerary ไม่ต้องขึ้นต้นด้วย DAY 1 หรือ วันที่ 1 เพราะหน้าเว็บแสดงลำดับวันให้แล้ว
ใช้ schema_version: 1 ชื่อ key ตามด้านล่าง ข้อความเป็น string จำนวน/ราคาเป็น number มื้ออาหารและ deposit_enabled เป็น true/false/null
ผู้ใช้เลือกประเภทไว้แล้ว กำหนด program_type เป็น "${type}" เสมอ ห้าม AI เปลี่ยนประเภท หากข้อมูลใน PDF ไม่ตรงกับประเภทที่เลือกให้เก็บข้อแตกต่างไว้ใน review_notes และเว้นช่องที่ไม่มีข้อมูล
days/nights คือจำนวนวัน/คืน highlights หนึ่งรายการต่อบรรทัด
inclusions คือสิ่งที่รวมในราคา exclusions คือสิ่งที่ไม่รวมในราคา หนึ่งรายการต่อบรรทัด
payment_terms คือการจอง มัดจำ และกำหนดชำระเงิน cancellation_terms คือเงื่อนไขการยกเลิกและการคืนเงิน หนึ่งช่วงเวลาต่อบรรทัด know_before_go คือเรื่องที่ลูกค้าควรรู้ก่อนเดินทาง เช่น ความสูงและการปรับตัว สภาพอากาศ น้ำหนักกระเป๋า วีซ่า หรือร้านค้าที่แวะ หนึ่งเรื่องต่อบรรทัด terms ใช้เฉพาะเงื่อนไขอื่นที่ไม่เข้าช่องเหล่านี้
flight_included เป็น true เมื่อ PDF ระบุว่าราคารวมตั๋วเครื่องบิน false เมื่อระบุว่าไม่รวม และ null เมื่อไม่ระบุชัดเจน ห้ามเดาจากชื่อสายการบิน หากรวมเพียงบางช่วงให้ใช้ false และเก็บรายละเอียดไว้ใน terms
${isPrivate?'แพ็คเกจส่วนตัวไม่มีตารางบิน ถ้า PDF ระบุเที่ยวบินให้เก็บไว้ใน terms':'flights คือเที่ยวบินชุดเดียวที่ PDF ระบุ เรียงตามลำดับการเดินทาง leg_type ใช้ outbound (ขาไป), domestic (ตั๋วภายในระหว่างเมือง) หรือ return (ขากลับ) day_number คือวันที่ของทริป airline คือชื่อสายการบิน flight_number เช่น KY8370 from_airport/to_airport ใช้รหัสสนามบิน depart_time/arrive_time ใช้ HH:MM arrive_next_day เป็น true เมื่อถึงวันถัดไป duration ใช้ H:MM ตามที่ PDF ระบุเท่านั้น ห้ามคำนวณจากเวลาออกและถึง included เป็น true เมื่อเที่ยวนั้นรวมในราคาทัวร์ ถ้าแต่ละรอบบินต่างกันให้ใส่เฉพาะชุดที่แน่ใจและเก็บส่วนที่เหลือไว้ใน terms'}
สถานะบริการ *_status ใช้ included (รวม), extra (จ่ายเพิ่ม), none (ไม่รวม) หรือ null (ไม่ระบุ)
itinerary เรียงตามวัน ใส่รายละเอียดครบ โรงแรมและอาหารแต่ละวัน ห้ามสรุปจนข้อมูลหาย
hotel_options คือชื่อโรงแรมของวันนั้นเรียงตามลำดับ ตัวแรกคือโรงแรมหลัก ตัวถัดไปคือตัวสำรองเมื่อโรงแรมก่อนหน้าไม่ได้ ใส่เฉพาะชื่อโรงแรมที่ PDF ระบุ ถ้าไม่มีให้ใช้ []
วันที่ใช้ YYYY-MM-DD ค.ศ. เท่านั้น แปลง พ.ศ. ได้เมื่อระบุชัดเจน ห้ามเดาปี วันกลับ จำนวนที่นั่ง หรือราคา single_supplement คือค่าพักเดี่ยวเพิ่มต่อคนตลอดทริป
${pricing}
ราคาเด็ก ราคาประเภทอื่น หรือเงื่อนไขราคาที่ไม่มีช่อง ให้เก็บข้อความทั้งหมดใน terms พร้อมระบุรอบ ห้ามทิ้งข้อมูล ห้ามแปลงสกุลเงินเอง ถ้าไม่ใช่บาทให้เว้นราคาตัวเลขแล้วเก็บราคาและสกุลเงินไว้ใน terms
deposit_cutoff_days/balance_due_days คือจำนวนวันก่อนเดินทาง
destinations ใช้ชื่อ zone/country/route จากรายการระบบเมื่อจับคู่ได้แน่นอน ถ้าไม่ตรงใช้ชื่อจาก PDF และห้ามสร้าง ID ห้ามเลือกเส้นทางเพียงเพราะอยู่ในประเทศเดียวกัน
JSON ไม่แนบรูปภาพและ PDF ต้นฉบับ ผู้ใช้ต้องแนบไฟล์ในหน้าเว็บเอง ไม่ใส่ URL/path/base64
โครงสร้าง (null หมายถึงไม่มีข้อมูล ไม่ใช่ค่าที่ต้องคัดลอก):
${JSON.stringify(schema,null,2)}
รายการจุดหมายในระบบ:
${JSON.stringify(nodes.map(n=>({name:n.name,kind:n.kind,parent:nodes.find(p=>String(p.id)===String(n.parent_id))?.name || null})),null,2)}
${isPrivate?'ระดับบริการในระบบ: '+JSON.stringify(levels):''}`;
 $('#tour-prompt').value=prompt;
 }
 typeSelect.value=programType.value;
 refreshPrompt();
 typeSelect.addEventListener('change',()=>{programType.value=typeSelect.value;programType.dispatchEvent(new Event('change',{bubbles:true}));});
 programType.addEventListener('change',()=>{typeSelect.value=programType.value;refreshPrompt();});
 $('#copy-tour-prompt').addEventListener('click',async()=>{try{await navigator.clipboard.writeText($('#tour-prompt').value);$('#prompt-status').textContent='คัดลอกแล้ว นำไปให้ AI พร้อม PDF';}catch(e){$('#tour-prompt').closest('details').open=true;$('#tour-prompt').select();$('#prompt-status').textContent='เลือกข้อความแล้ว กด Ctrl+C หรือคัดลอกด้วยตนเอง';}});
 const result=$('#tour-import-result'); let imported=false, notices=[];
 const blank=v=>v===null || v===undefined || v==='';
 const obj=v=>v!==null && typeof v==='object' && !Array.isArray(v);
 function validate(data) {
  if(!obj(data) || data.schema_version!==1) throw Error('ต้องเป็น JSON object และ schema_version ต้องเป็น 1');
  const scalar=(row,keys,path)=>{for(const key of keys) if(!blank(row[key]) && !['string','number','boolean'].includes(typeof row[key])) throw Error(path+key+' ต้องเป็นข้อความ ตัวเลข หรือ null');};
  const unknown=(row,keys,path)=>Object.keys(row).filter(k=>!keys.includes(k)).forEach(k=>notices.push('ไม่รองรับ '+path+k+' — กรุณาเพิ่มข้อมูลนี้ในฟอร์มเอง'));
  unknown(data,['schema_version','program_type',...fields,'review_notes','flights','destinations','itinerary','departures'],''); scalar(data,fields,'');
  if(data.review_notes!=null && (!Array.isArray(data.review_notes) || !data.review_notes.every(note=>typeof note==='string'))) throw Error('review_notes ต้องเป็นรายการข้อความ');
  (data.review_notes||[]).forEach(note=>notices.push('ข้อสังเกตจาก AI: '+note));
  if(!['fixed','private'].includes(data.program_type)) throw Error('ต้องระบุ program_type เป็น fixed หรือ private ตาม Prompt ที่เลือก');
  if(data.program_type!==typeSelect.value) throw Error('JSON เป็น'+typeLabels[data.program_type]+' แต่เลือกนำเข้า'+typeLabels[typeSelect.value]+' กรุณาเลือกประเภทให้ตรงกับ JSON ก่อนนำเข้า');
  for(const key of ['destinations','itinerary','departures']) {
   if(data[key]===undefined || data[key]===null) data[key]=[];
   if(!Array.isArray(data[key]) || data[key].length>60 || !data[key].every(obj)) throw Error(key+' ต้องเป็นรายการ object ไม่เกิน 60 แถว');
  }
  if(data.flights==null) data.flights=[];
  if(!Array.isArray(data.flights) || data.flights.length>12 || !data.flights.every(obj)) throw Error('flights ต้องเป็นรายการ object ไม่เกิน 12 แถว');
  data.flights.forEach(f=>{scalar(f,flightFields,'flights.');unknown(f,flightFields,'flights.');});
  data.destinations.forEach((d,i)=>{scalar(d,['zone','country','route'],'จุดหมาย '+(i+1)+' ');unknown(d,['zone','country','route'],'destinations.');});
  data.itinerary.forEach(d=>{scalar(d,dayFields,'itinerary.');unknown(d,[...dayFields,'hotel_options'],'itinerary.');if(d.hotel_options!=null && (!Array.isArray(d.hotel_options) || !d.hotel_options.every(name=>typeof name==='string'))) throw Error('hotel_options ต้องเป็นรายการชื่อโรงแรม');for(const key of ['breakfast','lunch','dinner']) if(!blank(d[key]) && typeof d[key]!=='boolean') throw Error('มื้ออาหารต้องเป็น true, false หรือ null');});
  data.departures.forEach(d=>{
   scalar(d,depFields,'departures.');unknown(d,[...depFields,'deposit_enabled','rates'],'departures.');
   if(!blank(d.deposit_enabled) && typeof d.deposit_enabled!=='boolean') throw Error('deposit_enabled ต้องเป็น true, false หรือ null');
   if(d.rates==null) d.rates=[];
   if(!Array.isArray(d.rates) || d.rates.length>30 || !d.rates.every(obj)) throw Error('rates ต้องเป็นรายการ object ไม่เกิน 30 แถว');
   if(data.program_type==='fixed' && d.rates.length) throw Error('ทัวร์ปกติใช้ capacity และ price_per_person ต่อรอบ ไม่ใช้ rates ของแพ็คเกจส่วนตัว');
   if(data.program_type==='fixed' && (!blank(d.lead_days) || !blank(d.blackout_dates))) throw Error('ทัวร์ปกติใช้วันไป–กลับของแต่ละรอบ ไม่ใช้ lead_days หรือ blackout_dates ของแพ็คเกจส่วนตัว');
   if(data.program_type==='private' && (!blank(d.capacity) || !blank(d.price_per_person))) throw Error('แพ็คเกจส่วนตัวใช้ rates ตามระดับบริการและจำนวนคน กรุณานำ capacity และ price_per_person ออกจาก JSON');
   d.rates.forEach(r=>{scalar(r,['level','min','max','price','details'],'rates.');unknown(r,['level','min','max','price','details'],'rates.');});
  });
  // Customers read these fields, so anything that still talks about the source document gets flagged.
  const sourceNote=/เอกสาร|หน้าปก|ตารางสรุป|PDF|ไม่ระบุ/;
  const flagNote=(value,label)=>{if(typeof value==='string' && sourceNote.test(value)) notices.push('ตรวจทาน: '+label+' อาจมีบันทึกของ AI ที่ลูกค้าจะเห็น');};
  fields.forEach(k=>flagNote(data[k],k));
  data.itinerary.forEach((d,i)=>['title','description','hotel'].forEach(k=>flagNote(d[k],'วันที่ '+(i+1)+' '+k)));
  data.departures.forEach((d,i)=>d.rates.forEach((r,j)=>flagNote(r.details,'รอบ '+(i+1)+' เรต '+(j+1))));
 }
 function set(input,value,label) {
  if(!input) return;
  input.value=blank(value)?'':typeof value==='boolean'?(value?'1':'0'):String(value);
  if(!blank(value) && input.value!==String(typeof value==='boolean'?(value?'1':'0'):value)) notices.push(label+': จับคู่หรือใช้ค่าไม่ได้ ('+String(value)+')');
  if(input.maxLength>0 && input.value.length>input.maxLength) notices.push(label+': ข้อความยาวเกินกำหนด ต้องแก้ไขก่อนบันทึก');
 }
 function field(name){return form.elements.namedItem(name);}
 function report() {
  if(!imported) return;
  result.hidden=false;result.className='notice';result.replaceChildren();
  const heading=document.createElement('b');heading.textContent='นำเข้าแล้ว '+$('#day-list').children.length+' วัน / '+$('#departure-list').children.length+' รอบ — ยังไม่ได้บันทึก';result.append(heading);
  const issues=[...notices];
  for(const name of fields) if(blank(field(name)?.value)) issues.push('ยังไม่ระบุ: '+(field(name)?.closest('label')?.childNodes[0]?.textContent.trim() || name));
  form.querySelectorAll('input,select,textarea').forEach(el=>{if(!el.disabled && !el.validity.valid) issues.push('ต้องแก้ไข: '+(el.closest('label')?.textContent.trim().slice(0,65) || el.name));});
  if($('#day-list').children.length!==Number(field('days').value)) issues.push('จำนวนแผนรายวันไม่ตรงกับจำนวนวัน');
  if(!$('#departure-list').children.length) issues.push('ยังไม่มีรอบเดินทางและราคา');
  if(!field('cover_file').files.length) issues.push('ยังไม่ได้แนบรูปปก');
  if(!field('source_pdf_file').files.length) issues.push('ยังไม่ได้แนบ PDF ต้นฉบับ');
  form.querySelectorAll('.day-item').forEach((row,i)=>{
   for(const key of ['title','description','hotel']) if(!row.querySelector('[name$="['+key+']"]').value) issues.push('วันที่ '+(i+1)+': ไม่ระบุ '+key);
   row.querySelectorAll('select').forEach(el=>{if(el.value==='') issues.push('มื้ออาหาร '+el.name+': ไม่ระบุ');});
  });
  const starts=new Set();
  form.querySelectorAll('.departure-item').forEach((row,i)=>{
   const start=row.querySelector('[name$="[start_date]"]').value,end=row.querySelector('[name$="[end_date]"]').value;
   if(start && starts.has(start)) issues.push('รอบ '+(i+1)+': วันเริ่มเดินทางซ้ำ');
   starts.add(start);
   if(start && end && end<start) issues.push('รอบ '+(i+1)+': วันกลับอยู่ก่อนวันเริ่ม');
   if(start && end && field('program_type').value==='fixed' && (Date.parse(end)-Date.parse(start))/86400000+1!==Number(field('days').value)) issues.push('รอบ '+(i+1)+': ช่วงวันเดินทางไม่ตรงกับจำนวนวัน');
  });
  const ul=document.createElement('ul');[...new Set(issues)].forEach(msg=>{const li=document.createElement('li');li.textContent=msg;ul.append(li);});result.append(ul);
 }
 $('#tour-json-file').addEventListener('change',async event=>{
  const file=event.target.files[0]; if(!file)return;
  try {if(file.size>2*1024*1024)throw Error('ไฟล์ JSON ต้องไม่เกิน 2 MB');$('#tour-json').value=await file.text();}catch(e){result.hidden=false;result.textContent=e.message;}
 });
 $('#import-tour-json').addEventListener('click',()=>{
  try {
   const raw=$('#tour-json').value.replace(/^\uFEFF/,'').trim().replace(/^```(?:json)?\s*/i,'').replace(/\s*```$/,'');
   if(raw.length>2*1024*1024)throw Error('JSON ต้องไม่เกิน 2 MB');
   const data=JSON.parse(raw);notices=[];validate(data);
   if(imported && !confirm('นำเข้าใหม่จะแทนที่ข้อมูลและรูปรายวันที่เลือกไว้ ดำเนินการต่อหรือไม่?'))return;
   fields.forEach(k=>set(field(k),data[k],k));
   if(blank(data.flight_included)){field('flight_included').value='0';notices.push('เอกสารไม่ระบุเรื่องตั๋วเครื่องบิน กรุณาเลือกรวมหรือไม่รวมตั๋วเอง');}
   field('program_type').required=true;set(field('program_type'),data.program_type,'ประเภทโปรแกรม');
   field('status').value='draft';
   const places=$('#tour-destination-rows');places.replaceChildren();
   for(const d of data.destinations.length?data.destinations:[{}]) {
    document.querySelector('[data-place-add]').click();const row=places.lastElementChild;
    let parent=null;
    for(const kind of ['zone','country','route']) {
     const matches=nodes.filter(n=>n.kind===kind && String(n.name).trim().toLocaleLowerCase()===String(d[kind]??'').trim().toLocaleLowerCase() && (kind==='zone'||String(n.parent_id)===String(parent)));
     const el=row.querySelector('[data-place-kind='+kind+']');el.value=matches.length===1?String(matches[0].id):'';el.dispatchEvent(new Event('change',{bubbles:true}));parent=el.value;
     if(!parent)notices.push('จุดหมาย '+kind+': '+(d[kind]||'ไม่ระบุ')+' — กรุณาเลือกในฟอร์ม');
    }
   }
   $('#day-list').replaceChildren();
   data.itinerary.forEach((d,i)=>{document.querySelector('[data-add=day]').click();const row=$('#day-list').lastElementChild;dayFields.forEach(k=>set(row.querySelector('[name$="['+k+']"]'),d[k],'วันที่ '+(i+1)+' '+k));row.querySelector('[data-hotels-field]').value=JSON.stringify((d.hotel_options||[]).map(name=>({name,images:[]})));});
   $('#flight-set-list').replaceChildren();
   if(data.program_type==='fixed' && data.flights.length){
    document.querySelector('[data-flight-set-add]').click();
    const flightSet=$('#flight-set-list').lastElementChild, rows=flightSet.querySelector('.flight-rows');
    flightSet.querySelector('[name$="[name]"]').value='ชุดหลัก';
    data.flights.forEach((f,i)=>{
     if(i)flightSet.querySelector('[data-flight-add]').click();
     const row=rows.lastElementChild, cell=key=>row.querySelector('[name$="['+key+']"]');
     ['day_number','flight_number','from_airport','to_airport','depart_time','arrive_time','duration'].forEach(k=>set(cell(k),f[k],'เที่ยวบิน '+(i+1)+' '+k));
     cell('leg_type').value=['outbound','domestic','return'].includes(f.leg_type)?f.leg_type:'outbound';
     cell('arrive_next_day').checked=f.arrive_next_day===true;
     if(typeof f.included==='boolean')cell('included').value=f.included?'1':'0';
     const airlines=[...cell('airline_id').options].filter(o=>o.value&&o.textContent.trim().toLocaleLowerCase()===String(f.airline??'').trim().toLocaleLowerCase());
     cell('airline_id').value=airlines.length===1?airlines[0].value:'';
     if(!cell('airline_id').value)notices.push('สายการบินจับคู่ไม่ได้: '+(f.airline||'ไม่ระบุ')+' — เพิ่มที่หน้าสายการบิน แล้วเลือกในฟอร์ม');
    });
   }else if(data.flights.length)notices.push('แพ็คเกจส่วนตัวไม่แสดงตารางบิน จึงไม่นำเข้า flights');
   $('#departure-list').replaceChildren();
   data.departures.forEach((d,i)=>{
    document.querySelector('[data-add=departure]').click();const row=$('#departure-list').lastElementChild;
    depFields.forEach(k=>set(row.querySelector('[name$="['+k+']"]'),d[k],'รอบ '+(i+1)+' '+k));
    row.querySelector('[name$="[deposit_enabled]"]').checked=d.deposit_enabled===true;
    if(blank(d.deposit_enabled))notices.push('รอบ '+(i+1)+': ไม่ระบุการรับมัดจำ ปิดไว้จนกว่าจะตรวจทาน');
    row.querySelector('[name$="[active]"]').checked=false;
    const rates=row.querySelector('.private-rates'), first=rates.firstElementChild;
    d.rates.forEach((r,j)=>{if(j)rates.append(first.cloneNode(true));const rr=rates.children[j];['min','max','price','details'].forEach(k=>set(rr.querySelector('[name$="['+k+']"]'),r[k],'ราคา '+k));const select=rr.querySelector('select');const options=[...select.options].filter(o=>o.value&&o.textContent.trim()===r.level);select.value=options.length===1?options[0].value:'';if(!select.value)notices.push('ระดับบริการจับคู่ไม่ได้: '+(r.level||'ไม่ระบุ'));});
    if(!d.rates.length)first.querySelectorAll('input,select,textarea').forEach(el=>el.value='');
   });
   field('program_type').dispatchEvent(new Event('change',{bubbles:true}));
   notices.push('นำเข้าเป็นฉบับร่าง และปิดรับคำขอทุกรอบ กรุณาตรวจข้อมูลก่อนเปิดรับและเผยแพร่');
   imported=true;report();result.scrollIntoView({block:'start',behavior:'smooth'});
  }catch(e){result.hidden=false;result.className='notice error';result.textContent='นำเข้าไม่สำเร็จ: '+e.message;}
 });
 form.addEventListener('input',report);form.addEventListener('change',report);
})();
