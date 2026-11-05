(() => {
 const editor=document.querySelector('#tour-editor');if(!editor)return;
 const urls=new Map();
 const sync=box=>{
  const index=box.closest('.day-item').querySelector('[name]').name.match(/^itinerary\[([^\]]+)\]/)[1];
  box.querySelectorAll('.day-picture').forEach((row,j)=>row.querySelectorAll('[data-picture-field]').forEach(input=>input.name=`itinerary[${index}][pictures][${j}][${input.dataset.pictureField}]`));
  box.querySelector('[data-picture-add]').disabled=box.querySelectorAll('.day-picture').length>=6;
 };
 const release=input=>{if(urls.has(input)){URL.revokeObjectURL(urls.get(input));urls.delete(input);}};
 const reset=row=>{
  const input=row.querySelector('[data-picture-upload]'),img=row.querySelector('img');release(input);input.value='';
  if(img.dataset.originalSrc)img.src=img.dataset.originalSrc;else img.removeAttribute('src');
  img.hidden=!img.dataset.originalSrc;row.querySelector('[data-picture-field=has_upload]').value='0';row.querySelector('[data-picture-clear]').hidden=true;
 };
 editor.addEventListener('click',event=>{
  const button=event.target.closest('button'),box=button?.closest('.day-photo-editor');if(!box)return;
  const list=box.querySelector('.day-picture-list'),row=button.closest('.day-picture');
  if(button.matches('[data-picture-add]')&&list.children.length<6){list.append(box.querySelector('template').content.cloneNode(true));sync(box);list.lastElementChild.querySelector('input[type=file]').focus();}
  if(button.matches('[data-picture-remove]')){release(row.querySelector('[data-picture-upload]'));row.remove();sync(box);box.querySelector('[data-day-photo-status]').textContent='นำภาพออกแล้ว · กดบันทึกโปรแกรมเพื่อยืนยัน';}
  if(button.matches('[data-picture-clear]'))reset(row);
  if(button.dataset.pictureMove==='up'&&row.previousElementSibling)list.insertBefore(row,row.previousElementSibling);
  if(button.dataset.pictureMove==='down'&&row.nextElementSibling)list.insertBefore(row.nextElementSibling,row);
  sync(box);
 });
 editor.addEventListener('change',event=>{
  const input=event.target;if(!input.matches('[data-picture-upload]'))return;
  const row=input.closest('.day-picture'),box=input.closest('.day-photo-editor'),status=box.querySelector('[data-day-photo-status]'),file=input.files[0];
  if(!file){reset(row);return;}
  if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024){reset(row);status.textContent='ใช้ JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB';return;}
  release(input);const url=URL.createObjectURL(file);urls.set(input,url);const img=row.querySelector('img');img.src=url;img.hidden=false;
  row.querySelector('[data-picture-field=has_upload]').value='1';row.querySelector('[data-picture-clear]').hidden=false;status.textContent='เลือกภาพแล้ว · กดบันทึกโปรแกรมเพื่ออัปโหลด';
 });
 editor.addEventListener('submit',()=>editor.querySelectorAll('.day-photo-editor').forEach(sync));
 editor.querySelectorAll('.day-photo-editor').forEach(sync);
 new MutationObserver(()=>{for(const [input,url] of urls)if(!input.isConnected){URL.revokeObjectURL(url);urls.delete(input);}}).observe(editor,{childList:true,subtree:true});
})();
