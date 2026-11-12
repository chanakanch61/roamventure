// Hotel photos upload one at a time as they are picked, so the count is not capped by max_file_uploads.
(() => {
 const editor=document.querySelector('#tour-editor'), dayList=document.querySelector('#day-list');
 if(!editor||!dayList)return;
 const uploadUrl=editor.dataset.hotelUploadUrl, assetBase=editor.dataset.assetBase;
 const folder=editor.querySelector('[name=hotel_folder]').value, token=editor.querySelector('input[name=_token]').value;
 const state=new WeakMap();
 let pending=0, library=[];
 try {library=JSON.parse(document.querySelector('#hotel-library-data')?.textContent||'[]');} catch {}
 if(!Array.isArray(library))library=[];
 const save=box=>{box.querySelector('[data-hotels-field]').value=JSON.stringify(state.get(box));};
 const say=(box,text)=>{box.querySelector('[data-hotel-status]').textContent=text;};
 const iconButton=(attr,value,icon,label)=>{
  const button=document.createElement('button');button.type='button';button.className='icon-btn';button.setAttribute(attr,value);button.setAttribute('aria-label',label);button.textContent=icon;return button;
 };
 function render(box) {
  const hotels=state.get(box), list=box.querySelector('[data-hotel-list]');
  list.replaceChildren(...hotels.map((hotel,index)=>{
   const card=document.createElement('div');card.className='day-hotel-card';card.dataset.hotel=index;
   const head=document.createElement('div');head.className='day-hotel-card-head';
   const title=document.createElement('b');title.textContent='โรงแรมที่ '+(index+1)+(index ? ' · สำรอง' : ' · หลัก');
   const tools=document.createElement('div');
   const up=iconButton('data-hotel-move','-1','↑','เลื่อนโรงแรมขึ้น');up.disabled=index===0;
   const down=iconButton('data-hotel-move','1','↓','เลื่อนโรงแรมลง');down.disabled=index===hotels.length-1;
   const keep=document.createElement('button');keep.type='button';keep.className='text-button';keep.dataset.hotelLibrarySave='';keep.textContent='บันทึกเข้าคลัง';
   const remove=document.createElement('button');remove.type='button';remove.className='text-button danger';remove.dataset.hotelRemove='';remove.textContent='นำโรงแรมออก';
   tools.append(up,down,keep,remove);head.append(title,tools);
   const name=document.createElement('label');name.textContent='ชื่อโรงแรม';
   const nameInput=document.createElement('input');nameInput.maxLength=200;nameInput.value=hotel.name;nameInput.dataset.hotelName='';nameInput.placeholder='เช่น Vala Toms Cliff Hotel';name.append(nameInput);
   const photos=document.createElement('div');photos.className='day-hotel-photos';
   hotel.images.forEach((path,photo)=>{
    const cell=document.createElement('div');cell.className='day-hotel-photo';cell.dataset.photo=photo;
    const img=document.createElement('img');img.src=assetBase+path;img.alt='รูปที่ '+(photo+1)+' ของ '+(hotel.name||'โรงแรม');img.loading='lazy';
    const back=iconButton('data-photo-move','-1','←','เลื่อนรูปไปก่อนหน้า');back.disabled=photo===0;
    const next=iconButton('data-photo-move','1','→','เลื่อนรูปไปถัดไป');next.disabled=photo===hotel.images.length-1;
    const drop=iconButton('data-photo-remove','1','×','นำรูปนี้ออก');
    const bar=document.createElement('div');bar.append(back,next,drop);
    cell.append(img,bar);return photos.appendChild(cell);
   });
   const pick=document.createElement('label');pick.className='outline-link day-hotel-pick';pick.textContent='＋ เพิ่มรูป ('+hotel.images.length+')';
   const file=document.createElement('input');file.type='file';file.multiple=true;file.accept='image/jpeg,image/png,image/webp';file.dataset.hotelFiles='';pick.append(file);
   card.append(head,name,photos,pick);
   return card;
  }));
  save(box);
 }
 function setup(box) {
  if(state.has(box))return;
  let hotels=[];
  try {hotels=JSON.parse(box.querySelector('[data-hotels-field]').value||'[]');} catch {}
  state.set(box,Array.isArray(hotels) ? hotels : []);
  render(box);
 }
 const setupAll=()=>dayList.querySelectorAll('[data-day-hotels]').forEach(setup);
 new MutationObserver(setupAll).observe(dayList,{childList:true});
 setupAll();
 const swapItems=(items,index,step)=>{const target=index+step;if(target<0||target>=items.length)return;[items[index],items[target]]=[items[target],items[index]];};

 // One picker serves every day. It sits outside the form so its buttons never submit the program.
 let picker=null, pickerBox=null, picked=[];
 function buildPicker() {
  picker=document.createElement('dialog');picker.className='hotel-picker';picker.setAttribute('aria-labelledby','hotel-picker-title');
  const head=document.createElement('div');head.className='hotel-picker-head';
  const title=document.createElement('h3');title.id='hotel-picker-title';title.textContent='เลือกจากคลังโรงแรม';
  const hint=document.createElement('small');hint.textContent='ติ๊กตามลำดับที่ต้องการ โรงแรมแรกที่ติ๊กเป็นโรงแรมหลัก ถัดไปเป็นตัวสำรอง';
  head.append(title,hint);
  const search=document.createElement('input');search.type='search';search.placeholder='ค้นหาชื่อโรงแรม';search.dataset.pickerSearch='';search.setAttribute('aria-label','ค้นหาชื่อโรงแรม');
  const list=document.createElement('div');list.className='hotel-picker-list';list.dataset.pickerList='';
  const foot=document.createElement('div');foot.className='hotel-picker-foot';
  const manage=document.createElement('a');manage.className='text-link';manage.href=editor.dataset.hotelLibraryUrl;manage.target='_blank';manage.rel='noopener';manage.textContent='จัดการคลังโรงแรม ↗';
  const cancel=document.createElement('button');cancel.type='button';cancel.className='outline-link';cancel.dataset.pickerCancel='';cancel.textContent='ยกเลิก';
  const apply=document.createElement('button');apply.type='button';apply.className='btn';apply.dataset.pickerApply='';
  foot.append(manage,cancel,apply);
  picker.append(head,search,list,foot);
  document.body.append(picker);
  search.addEventListener('input',renderPicker);
  picker.addEventListener('change',event=>{
   if(!event.target.matches('[data-picker-hotel]'))return;
   const id=Number(event.target.value);
   picked=event.target.checked ? [...picked,id] : picked.filter(item=>item!==id);
   renderPicker();
  });
  picker.addEventListener('click',event=>{
   if(event.target===picker||event.target.closest('[data-picker-cancel]'))picker.close();
   if(event.target.closest('[data-picker-apply]'))applyPicked();
  });
 }
 function renderPicker() {
  const list=picker.querySelector('[data-picker-list]'), query=picker.querySelector('[data-picker-search]').value.trim().toLowerCase();
  const shown=library.filter(hotel=>!query||hotel.name.toLowerCase().includes(query)||picked.includes(hotel.id));
  if(!library.length){
   const empty=document.createElement('p');empty.className='fineprint';empty.textContent='ยังไม่มีโรงแรมในคลัง กด “บันทึกเข้าคลัง” ที่การ์ดโรงแรม หรือเพิ่มในหน้าจัดการคลังโรงแรม';
   list.replaceChildren(empty);
  } else {
   list.replaceChildren(...shown.map(hotel=>{
    const row=document.createElement('label');row.className='hotel-picker-row';
    const box=document.createElement('input');box.type='checkbox';box.value=hotel.id;box.dataset.pickerHotel='';box.checked=picked.includes(hotel.id);
    const thumb=document.createElement(hotel.images.length ? 'img' : 'span');thumb.className='hotel-picker-thumb';
    if(hotel.images.length){thumb.src=assetBase+hotel.images[0];thumb.alt='';thumb.loading='lazy';}
    const text=document.createElement('span');const name=document.createElement('b');name.textContent=hotel.name;const count=document.createElement('small');count.textContent=hotel.images.length+' รูป';text.append(name,count);
    const order=document.createElement('span');order.className='hotel-picker-order';
    const position=picked.indexOf(hotel.id);order.textContent=position<0 ? '' : position===0 ? '1 · หลัก' : (position+1)+' · สำรอง';
    row.append(box,thumb,text,order);return row;
   }));
   if(!shown.length){const none=document.createElement('p');none.className='fineprint';none.textContent='ไม่พบโรงแรมที่ตรงกับคำค้น';list.append(none);}
  }
  const apply=picker.querySelector('[data-picker-apply]');
  apply.disabled=!picked.length;apply.textContent=picked.length ? 'เพิ่ม '+picked.length+' โรงแรม' : 'เพิ่มโรงแรม';
 }
 function openPicker(box) {
  if(!picker)buildPicker();
  pickerBox=box;picked=[];
  picker.querySelector('[data-picker-search]').value='';
  renderPicker();
  picker.showModal();
  picker.querySelector('[data-picker-search]').focus();
 }
 function applyPicked() {
  const hotels=state.get(pickerBox), names=new Set(hotels.map(hotel=>hotel.name.trim().toLowerCase())), skipped=[];
  let added=0;
  picked.map(id=>library.find(hotel=>hotel.id===id)).filter(Boolean).forEach(hotel=>{
   if(names.has(hotel.name.trim().toLowerCase())){skipped.push(hotel.name);return;}
   hotels.push({name:hotel.name,images:[...hotel.images]});added++;
  });
  picker.close();render(pickerBox);
  say(pickerBox,(added ? 'เพิ่มจากคลังแล้ว '+added+' โรงแรม แก้เฉพาะโปรแกรมนี้ได้' : 'ไม่ได้เพิ่มโรงแรม')+(skipped.length ? ' · มีอยู่ในวันนี้แล้ว: '+skipped.join(', ') : ''));
 }
 async function saveToLibrary(box,hotel) {
  if(pending){say(box,'กรุณารอให้อัปโหลดรูปเสร็จก่อนบันทึกเข้าคลัง');return;}
  const name=hotel.name.trim();
  if(!name){say(box,'ใส่ชื่อโรงแรมก่อนบันทึกเข้าคลัง');return;}
  const data=new FormData();data.append('_token',token);data.append('name',name);data.append('images',JSON.stringify(hotel.images));
  say(box,'กำลังบันทึก “'+name+'” เข้าคลัง …');
  try {
   const response=await fetch(editor.dataset.hotelLibrarySaveUrl,{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
   const result=await response.json();
   if(!response.ok||!result.id)throw Error(result.error||'บันทึกเข้าคลังไม่สำเร็จ');
   library.push({id:result.id,name:result.name,images:result.images});
   library.sort((a,b)=>a.name.localeCompare(b.name,'th'));
   say(box,'บันทึก “'+result.name+'” เข้าคลังแล้ว เลือกใช้กับโปรแกรมอื่นได้ทันที');
  } catch(error) {say(box,error.message);}
 }

 editor.addEventListener('click',event=>{
  const button=event.target.closest('button');const box=button?.closest('[data-day-hotels]');
  if(!box)return;
  const hotels=state.get(box), card=button.closest('[data-hotel]'), index=card ? Number(card.dataset.hotel) : -1;
  if(button.hasAttribute('data-hotel-library-pick')){openPicker(box);return;}
  if(button.hasAttribute('data-hotel-library-save')){saveToLibrary(box,hotels[index]);return;}
  if(button.hasAttribute('data-hotel-add')){
   hotels.push({name:'',images:[]});render(box);box.querySelector('[data-hotel-list]').lastElementChild.querySelector('[data-hotel-name]').focus();return;
  }
  if(button.hasAttribute('data-hotel-copy')){
   const previous=box.closest('.day-item').previousElementSibling?.querySelector('[data-day-hotels]');
   const source=previous ? state.get(previous) : [];
   if(!source||!source.length){say(box,'วันก่อนหน้ายังไม่มีโรงแรมให้คัดลอก');return;}
   if(hotels.length&&!confirm('แทนที่โรงแรมของวันนี้ด้วยโรงแรมจากวันก่อนหน้า?'))return;
   state.set(box,JSON.parse(JSON.stringify(source)));render(box);say(box,'คัดลอกแล้ว '+source.length+' โรงแรม');return;
  }
  if(button.hasAttribute('data-hotel-remove')){
   if(!confirm('นำโรงแรมนี้ออก? การเปลี่ยนแปลงจะมีผลเมื่อบันทึกโปรแกรม'))return;
   hotels.splice(index,1);render(box);return;
  }
  if(button.dataset.hotelMove){swapItems(hotels,index,Number(button.dataset.hotelMove));render(box);return;}
  const cell=button.closest('[data-photo]');
  if(!cell)return;
  const images=hotels[index].images, photo=Number(cell.dataset.photo);
  if(button.dataset.photoMove)swapItems(images,photo,Number(button.dataset.photoMove));
  if(button.dataset.photoRemove)images.splice(photo,1);
  render(box);
 });
 editor.addEventListener('input',event=>{
  if(!event.target.matches('[data-hotel-name]'))return;
  const box=event.target.closest('[data-day-hotels]');
  state.get(box)[Number(event.target.closest('[data-hotel]').dataset.hotel)].name=event.target.value;
  save(box);
 });
 editor.addEventListener('change',async event=>{
  if(!event.target.matches('[data-hotel-files]'))return;
  const input=event.target, box=input.closest('[data-day-hotels]'), hotel=state.get(box)[Number(input.closest('[data-hotel]').dataset.hotel)];
  const files=[...input.files], failed=[];
  input.value='';
  pending+=files.length;
  for(const [index,file] of files.entries()){
   say(box,'กำลังอัปโหลดรูป '+(index+1)+' / '+files.length+' …');
   const data=new FormData();data.append('_token',token);data.append('folder',folder);data.append('photo',file);
   try {
    const response=await fetch(uploadUrl,{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
    const result=await response.json();
    if(!response.ok||!result.path)throw Error(result.error||'อัปโหลดไม่สำเร็จ');
    hotel.images.push(result.path);
   } catch(error) {failed.push(file.name+': '+error.message);}
   pending--;
   render(box);
  }
  say(box,failed.length ? 'อัปโหลดไม่สำเร็จ '+failed.length+' รูป — '+failed.join(' · ') : 'อัปโหลดแล้ว '+files.length+' รูป รูปจะแสดงบนเว็บเมื่อบันทึกโปรแกรม');
 });
 editor.addEventListener('submit',event=>{
  if(!pending)return;
  event.preventDefault();event.stopImmediatePropagation();
  alert('กำลังอัปโหลดรูปโรงแรม กรุณารอให้เสร็จก่อนบันทึก');
 },true);
})();
