(() => {
 'use strict';
 const panel=document.querySelector('#package-level-import');if(!panel)return;
 const $=s=>document.querySelector(s),object=v=>v!==null&&typeof v==='object'&&!Array.isArray(v),blank=v=>v===null||v===undefined||v==='';
 const config=JSON.parse($('#package-level-import-config').textContent);
 const normalize=v=>String(v).trim().normalize('NFC').toLocaleLowerCase(),existing=new Set(config.existing.map(normalize));
 const promptBox=$('#package-level-prompt'),promptStatus=$('#package-level-prompt-status'),result=$('#package-level-import-result'),rowsForm=$('#package-level-rows-form'),rows=$('#package-level-rows'),saveButton=$('#save-package-level-rows');
 const schema={schema_version:1,kind:'package_levels',levels:[{name:null}]};
 promptBox.value=`อ่าน PDF ที่แนบแล้วดึงรายการ "ระดับบริการแพ็คเกจ" ออกมาเป็น JSON ส่ง JSON object เดียว ไม่ใช้ Markdown และไม่เพิ่ม key นอกโครงสร้าง
ระดับบริการคือชื่อชั้นของแพ็คเกจที่ PDF ใช้แบ่งเรตราคาหรือมาตรฐานบริการ เช่น Comfort, Luxury, Exclusive
ห้ามนำสิ่งต่อไปนี้มาเป็นระดับบริการ: ชื่อโรงแรม ประเภทห้องพัก ชั้นโดยสารเครื่องบิน ประเภทรถ ขนาดกรุ๊ปหรือช่วงจำนวนคน ราคา วันเดินทาง และชื่อโปรแกรมทัวร์
ห้ามเดา แปล หรือแต่งชื่อระดับเอง ใช้ชื่อตามที่ PDF เขียน ไม่เกิน 100 ตัวอักษร หากอ่านไม่ชัดให้ไม่ใส่ระดับนั้น
ระดับในระบบปัจจุบัน: ${JSON.stringify(config.existing)}
หากชื่อใน PDF ตรงกับระดับในระบบ โดยต่างกันเพียงตัวพิมพ์เล็กใหญ่หรือคำว่า Package / Class ต่อท้าย ให้ใช้ตัวสะกดตามระบบ ห้ามจับคู่ชื่อที่ต่างกัน เช่น Deluxe กับ Luxury
ส่งทุกระดับที่พบใน PDF รวมระดับที่มีในระบบแล้ว ระบบจะข้ามชื่อที่ซ้ำให้เอง เรียงตามลำดับที่ปรากฏใน PDF และไม่ใส่ชื่อซ้ำ
กำหนด schema_version เป็น 1 และ kind เป็น "package_levels" หาก PDF ไม่ระบุระดับบริการให้ levels เป็น []
โครงสร้าง:
${JSON.stringify(schema,null,2)}`;
 $('#copy-package-level-prompt').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(promptBox.value);promptStatus.textContent='คัดลอกแล้ว นำไปให้ AI พร้อม PDF';}catch(e){promptBox.closest('details').open=true;promptBox.select();promptStatus.textContent='เลือกข้อความแล้ว กด Ctrl+C หรือคัดลอกด้วยตนเอง';}});
 function error(message){result.hidden=false;result.className='notice error';result.textContent='นำเข้าไม่สำเร็จ: '+message;}
 function refresh(){const count=rows.children.length;rowsForm.hidden=!count;saveButton.textContent='บันทึก '+count+' ระดับบริการ';}
 function addRow(name,sort){
  const row=document.createElement('div'),index=rows.dataset.next=Number(rows.dataset.next||0)+1;row.className='package-level-row';
  const nameLabel=document.createElement('label'),nameInput=document.createElement('input');nameLabel.append('ชื่อระดับบริการ',nameInput);Object.assign(nameInput,{name:'levels['+index+'][name]',required:true,maxLength:100,value:name});
  const sortLabel=document.createElement('label'),sortInput=document.createElement('input');sortLabel.append('ลำดับ',sortInput);Object.assign(sortInput,{type:'number',name:'levels['+index+'][sort_order]',required:true,min:0,max:9999,value:sort});
  const remove=document.createElement('button');Object.assign(remove,{type:'button',className:'text-button danger',textContent:'นำออก'});remove.addEventListener('click',()=>{row.remove();refresh();});
  row.append(nameLabel,sortLabel,remove);rows.append(row);
 }
 function report(added,skipped){
  result.hidden=false;result.className='notice';result.replaceChildren();
  const title=document.createElement('b');title.textContent=added?'นำเข้า '+added+' ระดับแล้ว — ยังไม่ได้บันทึก ตรวจทานชื่อและลำดับด้านล่าง':'ไม่มีระดับใหม่ให้เพิ่ม';result.append(title);
  const list=document.createElement('ul');skipped.forEach(text=>{const li=document.createElement('li');li.textContent=text;list.append(li);});if(skipped.length)result.append(list);
 }
 $('#package-level-json-file').addEventListener('change',async event=>{const file=event.target.files[0];if(!file)return;try{if(file.size>2*1024*1024)throw Error('ไฟล์ JSON ต้องไม่เกิน 2 MB');$('#package-level-json').value=await file.text();}catch(e){error(e.message);}});
 $('#import-package-level-json').addEventListener('click',()=>{
  try{
   const raw=$('#package-level-json').value.replace(/^﻿/,'').trim().replace(/^```(?:json)?\s*/i,'').replace(/\s*```$/,'');if(new Blob([raw]).size>2*1024*1024)throw Error('JSON ต้องไม่เกิน 2 MB');
   const data=JSON.parse(raw);if(!object(data)||data.schema_version!==1)throw Error('ต้องเป็น JSON object และ schema_version ต้องเป็น 1');
   if(data.kind!=='package_levels')throw Error('kind ต้องเป็น package_levels สำหรับหน้านี้ กรุณาใช้ Prompt ของหน้านี้');
   if(!Array.isArray(data.levels))throw Error('levels ต้องเป็น array');
   data.levels.forEach((level,i)=>{if(!object(level))throw Error('levels แถวที่ '+(i+1)+' ต้องเป็น object');if(!blank(level.name)&&typeof level.name!=='string')throw Error('levels แถวที่ '+(i+1)+': name ต้องเป็นข้อความหรือ null');if(typeof level.name==='string'&&[...level.name.trim()].length>100)throw Error('levels แถวที่ '+(i+1)+': name ยาวเกิน 100 ตัวอักษร');});
   if(rows.children.length&&!confirm('แทนที่รายการที่นำเข้าไว้ด้วย JSON นี้หรือไม่?'))return;
   const skipped=[],seen=new Set(),names=[];
   Object.keys(data).filter(k=>!['schema_version','kind','levels'].includes(k)).forEach(k=>skipped.push('ไม่รองรับช่อง '+k));
   data.levels.forEach((level,i)=>{
    Object.keys(level).filter(k=>k!=='name').forEach(k=>skipped.push('ไม่รองรับช่อง levels.'+k));
    const name=String(level.name??'').trim(),key=normalize(name);
    if(!name)skipped.push('ข้ามแถวที่ '+(i+1)+': ไม่มีชื่อระดับ');
    else if(existing.has(key))skipped.push('ข้าม '+name+': มีอยู่ในระบบแล้ว');
    else if(seen.has(key))skipped.push('ข้าม '+name+': ซ้ำใน JSON');
    else{seen.add(key);names.push(name);}
   });
   rows.replaceChildren();names.forEach((name,i)=>addRow(name,Math.min(config.next_sort+i,9999)));refresh();
   report(names.length,[...new Set(skipped)]);result.scrollIntoView({block:'nearest',behavior:'smooth'});
  }catch(e){error(e.message);}
 });
 config.rows.forEach(row=>addRow(row.name,row.sort_order));refresh();
})();
