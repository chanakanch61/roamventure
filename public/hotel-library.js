// Photo list on the hotel library form. Photos upload one at a time as they are picked, like a program's day hotels.
(() => {
 const form=document.querySelector('#hotel-library-form');
 if(!form)return;
 const field=form.querySelector('[data-library-images]'), list=form.querySelector('[data-library-photos]'), status=form.querySelector('[data-library-status]');
 const pick=form.querySelector('[data-library-pick]'), token=form.querySelector('input[name=_token]').value;
 let images=[], pending=0;
 try {images=JSON.parse(field.value||'[]');} catch {}
 if(!Array.isArray(images))images=[];
 const iconButton=(attr,value,icon,label)=>{
  const button=document.createElement('button');button.type='button';button.className='icon-btn';button.setAttribute(attr,value);button.setAttribute('aria-label',label);button.textContent=icon;return button;
 };
 function render() {
  list.replaceChildren(...images.map((path,index)=>{
   const cell=document.createElement('div');cell.className='day-hotel-photo';cell.dataset.photo=index;
   const img=document.createElement('img');img.src=form.dataset.assetBase+path;img.alt='รูปที่ '+(index+1);img.loading='lazy';
   const back=iconButton('data-photo-move','-1','←','เลื่อนรูปไปก่อนหน้า');back.disabled=index===0;
   const next=iconButton('data-photo-move','1','→','เลื่อนรูปไปถัดไป');next.disabled=index===images.length-1;
   const drop=iconButton('data-photo-remove','1','×','นำรูปนี้ออก');
   const bar=document.createElement('div');bar.append(back,next,drop);
   cell.append(img,bar);return cell;
  }));
  pick.firstChild.textContent='＋ เพิ่มรูป ('+images.length+')';
  field.value=JSON.stringify(images);
 }
 render();
 list.addEventListener('click',event=>{
  const button=event.target.closest('button'), cell=button?.closest('[data-photo]');
  if(!cell)return;
  const index=Number(cell.dataset.photo), target=index+Number(button.dataset.photoMove||0);
  if(button.dataset.photoMove&&target>=0&&target<images.length)[images[index],images[target]]=[images[target],images[index]];
  if(button.dataset.photoRemove)images.splice(index,1);
  render();
 });
 form.addEventListener('change',async event=>{
  if(!event.target.matches('[data-library-files]'))return;
  const files=[...event.target.files], failed=[];
  event.target.value='';
  pending+=files.length;
  for(const [index,file] of files.entries()){
   status.textContent='กำลังอัปโหลดรูป '+(index+1)+' / '+files.length+' …';
   const data=new FormData();data.append('_token',token);data.append('photo',file);
   try {
    const response=await fetch(form.dataset.uploadUrl,{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
    const result=await response.json();
    if(!response.ok||!result.path)throw Error(result.error||'อัปโหลดไม่สำเร็จ');
    images.push(result.path);
   } catch(error) {failed.push(file.name+': '+error.message);}
   pending--;
   render();
  }
  status.textContent=failed.length ? 'อัปโหลดไม่สำเร็จ '+failed.length+' รูป — '+failed.join(' · ') : 'อัปโหลดแล้ว '+files.length+' รูป กดบันทึกโรงแรมเพื่อเก็บเข้าคลัง';
 });
 form.addEventListener('submit',event=>{
  if(!pending)return;
  event.preventDefault();
  alert('กำลังอัปโหลดรูปโรงแรม กรุณารอให้เสร็จก่อนบันทึก');
 });
})();
