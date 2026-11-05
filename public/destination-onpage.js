(() => {
 const form=document.querySelector('#destination-form[data-rich-editor]');if(!form)return;
 const title=document.querySelector('[data-seo-title]'),description=document.querySelector('[data-seo-description]'),url=document.querySelector('[data-seo-url]');
 const update=()=>{
  title.textContent=form.elements.meta_title.value.trim() || 'ทัวร์'+form.elements.name.value.trim()+' | RoamVenture';
  description.textContent=form.elements.meta_description.value.trim() || form.elements.description.value.trim() || 'คำอธิบายหน้านี้จะแสดงที่นี่';
  const parent=form.elements.parent_id?.selectedOptions[0]?.dataset.slug || '{country}';
  url.textContent=url.dataset.prefix+(url.dataset.kind==='route' ? encodeURIComponent(parent)+'/' : '')+encodeURIComponent(form.elements.slug.value.trim());
 };
 form.addEventListener('input',update);form.addEventListener('change',update);update();
 document.querySelector('#import-destination-json')?.addEventListener('click',update);
 const imageField=form.elements.image_suggestions,list=form.querySelector('[data-image-suggestions-list]'),empty=form.querySelector('[data-image-suggestions-empty]'),clear=form.querySelector('[data-clear-image-suggestions]');
 const normalize=items=>{
  if(items==null)return [];
  if(!Array.isArray(items)||items.length>12)throw Error('รายการภาพอ้างอิงต้องเป็นรายการไม่เกิน 12 ภาพ');
  return items.map(item=>{
   if(!item||typeof item!=='object'||Array.isArray(item))throw Error('รูปแบบรายการภาพอ้างอิงไม่ถูกต้อง');
   const clean={};for(const [key,max] of Object.entries({section:160,keywords:200,alt:200})){
    const value=item[key]??'';if(typeof value!=='string'||[...value].length>max)throw Error('ข้อมูลภาพอ้างอิง '+key+' ไม่ถูกต้องหรือยาวเกินกำหนด');clean[key]=value.trim();
   }
   if(!clean.section||!/^[A-Za-z0-9][A-Za-z0-9 ,.'()&-]*$/.test(clean.keywords))throw Error('กรุณาระบุหัวข้อและคำค้นภาพภาษาอังกฤษ');
   clean.shutterstock_url='https://www.shutterstock.com/search/'+clean.keywords.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');return clean;
  });
 };
 const render=()=>{
  list.replaceChildren();let items;
  try{items=normalize(JSON.parse(imageField.value||'[]'));}catch{empty.textContent='อ่านรายการภาพอ้างอิงไม่ได้ กรุณานำเข้าใหม่';empty.hidden=false;clear.hidden=false;return;}
  empty.hidden=items.length>0;clear.hidden=items.length===0;
  for(const item of items){
   const card=document.createElement('article'),heading=document.createElement('strong'),keywords=document.createElement('p'),link=document.createElement('a'),alt=document.createElement('p');
   card.className='destination-image-reference';heading.textContent=item.section;keywords.textContent='คำค้น: '+item.keywords;link.textContent='ค้นหาภาพบน Shutterstock ↗';link.href=item.shutterstock_url;link.target='_blank';link.rel='noopener noreferrer';alt.textContent='Alt ที่แนะนำ: '+(item.alt||'ระบุตามภาพจริงหลังดาวน์โหลด');card.append(heading,keywords,link,alt);list.append(card);
  }
 };
 form.imageSuggestions={normalize,replace(items){imageField.value=JSON.stringify(normalize(items));render();}};
 clear.addEventListener('click',()=>{form.imageSuggestions.replace([]);form.dispatchEvent(new Event('change',{bubbles:true}));});render();
})();
