document.querySelectorAll('[data-blog-confirm]').forEach(form => form.addEventListener('submit', event => { if (!confirm(form.dataset.blogConfirm)) event.preventDefault(); }));
(() => {
 const form=document.querySelector('#blog-editor-form, #destination-form[data-rich-editor]');if (!form) return;
 const editor=document.querySelector('#blog-visual'),source=document.querySelector('#blog-content'),sourceLabel=document.querySelector('#blog-source-label'),state=document.querySelector('#blog-save-state');
 const toolbar=document.querySelector('.blog-toolbar'),modes=[...document.querySelectorAll('[data-blog-mode]')],upload=document.querySelector('#blog-upload-image'),fileInput=document.querySelector('#blog-inline-image');
 let dirty=false,range=null,mode='visual',busy=false,start=0,end=0;
 editor.innerHTML=source.value;sourceLabel.hidden=true;editor.hidden=false;
 const changed=()=>{dirty=true;state.textContent='มีการแก้ไขที่ยังไม่บันทึก';};
 const imageDialog=document.querySelector('#blog-image-dialog'),altInput=document.querySelector('#blog-image-alt');
 let resolveAlt=null;
 const askAlt=(src,alt='',target=null)=>new Promise(resolve=>{
  form.dispatchEvent(new CustomEvent('rich-image-details',{detail:{target,mode}}));
  document.querySelector('#blog-image-detail-preview').src=src;altInput.value=alt;resolveAlt=resolve;
  imageDialog.showModal();altInput.focus();
 });
 const finishAlt=value=>{const done=resolveAlt;resolveAlt=null;imageDialog.close();if(done)done(value);};
 document.querySelector('#blog-image-details').addEventListener('submit',event=>{event.preventDefault();finishAlt(altInput.value.trim());});
 document.querySelector('#blog-image-cancel').addEventListener('click',()=>finishAlt(null));
 imageDialog.addEventListener('cancel',event=>{event.preventDefault();finishAlt(null);});
 editor.addEventListener('click',async event=>{
  if(busy || mode!=='visual' || event.target.tagName!=='IMG')return;
  event.preventDefault();const img=event.target;const alt=await askAlt(img.src,img.getAttribute('alt') || '',img);
  if(alt!==null && editor.contains(img) && alt!==img.getAttribute('alt')){img.alt=alt;changed();}
 });
 const pressed=(b,on)=>{b.classList.toggle('active',on);b.setAttribute('aria-pressed',String(on));};
 const update=()=>{
  const s=getSelection(),inside=mode==='visual' && s.rangeCount && editor.contains(s.anchorNode) && editor.contains(s.focusNode);
  let node=inside ? s.anchorNode : null;if(node && node.nodeType!==1)node=node.parentElement;
  const link=node?.closest('a'),block=inside ? document.queryCommandValue('formatBlock').toLowerCase().replace(/[<>]/g,'') : '';
  toolbar.querySelectorAll('[data-command]').forEach(b=>{
   const c=b.dataset.command;b.disabled=busy || mode==='code';
   if(c==='undo'){b.disabled=busy || mode==='code' || !document.queryCommandEnabled('undo');return;}
   if(c==='unlink'){b.disabled=busy || !inside || !link;return;}
   pressed(b,!!inside && (c==='formatBlock' ? block===b.dataset.value : document.queryCommandState(c)));
  });
  const b=document.querySelector('#blog-add-link');b.disabled=busy || mode==='code';pressed(b,!!inside && !!link);
  modes.forEach(b=>{pressed(b,b.dataset.blogMode===mode);b.disabled=busy;});upload.disabled=busy;
  form.querySelectorAll('[data-onpage-insert],#onpage-preview-button').forEach(b=>b.disabled=busy);
 };
 const remember=()=>{const s=getSelection();if(mode==='visual' && s.rangeCount && editor.contains(s.anchorNode) && editor.contains(s.focusNode))range=s.getRangeAt(0).cloneRange();update();};
 const restore=()=>{editor.focus();const s=getSelection();if(!range || !editor.contains(range.commonAncestorContainer)){range=document.createRange();range.selectNodeContents(editor);range.collapse(false);}s.removeAllRanges();s.addRange(range);};
 document.addEventListener('selectionchange',remember);
 form.addEventListener('input',()=>{changed();update();});form.addEventListener('change',changed);
 const setBusy=value=>{busy=value;form.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=value);source.readOnly=value;editor.contentEditable=String(!value);update();};
 const request=async(url,data)=>{
  const csrf=form.querySelector('input[name="'+form.dataset.csrfName+'"]');data.append(csrf.name,csrf.value);
  const response=await fetch(url,{method:'POST',body:data,credentials:'same-origin',headers:{Accept:'application/json'}});
  if(response.redirected)throw Error('กรุณาเข้าสู่ระบบใหม่ก่อนอัปโหลดหรือแปลงโค้ด');
  let result;try{result=await response.json();}catch{throw Error('ดำเนินการไม่สำเร็จ กรุณาลองใหม่หรือเข้าสู่ระบบอีกครั้ง');}
  if(!response.ok || result.error)throw Error(result.error || 'ดำเนินการไม่สำเร็จ');return result;
 };
 // Imports use the same server sanitizer as the HTML/Visual switch.
 form.richContent={
  get value(){return mode==='visual' ? editor.innerHTML : source.value;},
  async preview(){
   if(busy)throw Error('กรุณารอให้ตัวแก้ไขดำเนินการเสร็จก่อน');
   const html=mode==='visual' ? editor.innerHTML : source.value,previousState=state.textContent;
   setBusy(true);state.textContent='กำลังเตรียมตัวอย่าง…';
   try{const data=new FormData();data.append('content',html);return (await request(form.dataset.cleanUrl,data)).html;}
   finally{setBusy(false);state.textContent=previousState;}
  },
  async insert(html){
   if(busy)throw Error('กรุณารอให้ตัวแก้ไขดำเนินการเสร็จก่อน');
   const insertRange=range?.cloneRange(),start=source.selectionStart,end=source.selectionEnd;
   setBusy(true);
   try{
    const data=new FormData();data.append('content',html);const result=await request(form.dataset.cleanUrl,data);
    if(mode==='code')source.setRangeText(result.html,start,end,'end');
    else{
     editor.contentEditable='true';range=insertRange;restore();
     const selection=getSelection(),position=selection.getRangeAt(0).cloneRange();position.collapse(false);
     let block=position.startContainer;
     if(block!==editor){while(block.parentNode && block.parentNode!==editor)block=block.parentNode;position.setStartAfter(block);position.collapse(true);}
     const template=document.createElement('template');template.innerHTML=result.html;const last=template.content.lastChild;
     position.insertNode(template.content);if(last){position.setStartAfter(last);position.collapse(true);selection.removeAllRanges();selection.addRange(position);range=position.cloneRange();}
     remember();
    }
    changed();
   }catch(error){state.textContent=error.message;throw error;}
   finally{setBusy(false);}
  },
  async replace(html){
   if(busy)throw Error('กรุณารอให้ตัวแก้ไขดำเนินการเสร็จก่อนนำเข้า');
   setBusy(true);state.textContent='กำลังเตรียมเนื้อหาที่นำเข้า…';
   try{
    const data=new FormData();data.append('content',html);const result=await request(form.dataset.cleanUrl,data);
    source.value=result.html;editor.innerHTML=result.html;range=null;changed();return result.html;
   }catch(error){state.textContent=error.message;throw error;}
   finally{setBusy(false);}
  }
 };
 modes.forEach(b=>b.addEventListener('click',async()=>{
  const next=b.dataset.blogMode;if(busy || next===mode)return;
  if(next==='code'){source.value=editor.innerHTML;mode='code';editor.hidden=true;sourceLabel.hidden=false;source.focus();update();return;}
  setBusy(true);state.textContent='กำลังแปลง HTML…';
  try{const data=new FormData();data.append('content',source.value);const result=await request(form.dataset.cleanUrl,data);source.value=result.html;editor.innerHTML=result.html;range=null;mode='visual';sourceLabel.hidden=true;editor.hidden=false;state.textContent=dirty ? 'มีการแก้ไขที่ยังไม่บันทึก' : 'พร้อมเขียน';}
  catch(error){state.textContent=error.message;}
  finally{setBusy(false);if(mode==='visual')editor.focus();}
 }));
 toolbar.querySelectorAll('[data-command]').forEach(b=>{b.addEventListener('mousedown',e=>e.preventDefault());b.addEventListener('click',()=>{restore();document.execCommand(b.dataset.command,false,b.dataset.value || null);changed();remember();});});
 document.querySelector('#blog-add-link').addEventListener('mousedown',e=>e.preventDefault());
 document.querySelector('#blog-add-link').addEventListener('click',()=>{const url=prompt('URL ของลิงก์ (https://…)');if(!url)return;try{const parsed=new URL(url);if(!['http:','https:'].includes(parsed.protocol))throw Error();}catch{alert('กรุณากรอก URL ที่ขึ้นต้นด้วย http:// หรือ https://');return;}restore();document.execCommand('createLink',false,url);changed();remember();});
 upload.addEventListener('mousedown',e=>e.preventDefault());
 upload.addEventListener('click',()=>{remember();start=source.selectionStart;end=source.selectionEnd;fileInput.click();});
 fileInput.addEventListener('change',async()=>{
  const file=fileInput.files[0];if(!file)return;
  if(!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size>5*1024*1024){state.textContent='เลือกรูป JPG, PNG หรือ WebP ไม่เกิน 5 MB';fileInput.value='';return;}
  const insertRange=range?.cloneRange(),preview=URL.createObjectURL(file);
  const alt=await askAlt(preview,'',insertRange?.commonAncestorContainer);URL.revokeObjectURL(preview);
  if(alt===null){fileInput.value='';return;}
  setBusy(true);state.textContent='กำลังอัปโหลดรูปภาพ…';
  try{
   const data=new FormData();data.append('image',file);const result=await request(form.dataset.uploadUrl,data);
   const img=document.createElement('img');img.src=result.url;img.alt=alt;img.loading='lazy';
   if(mode==='code')source.setRangeText(img.outerHTML,start,end,'end');
   else{editor.contentEditable='true';range=insertRange;restore();document.execCommand('insertHTML',false,img.outerHTML);remember();}
   changed();state.textContent='แทรกรูปแล้ว · กดบันทึกเพื่อเก็บการเปลี่ยนแปลง';
  }catch(error){state.textContent=error.message;}
  finally{fileInput.value='';setBusy(false);}
 });
 editor.addEventListener('paste',e=>{e.preventDefault();document.execCommand('insertText',false,e.clipboardData.getData('text/plain'));changed();});
 editor.addEventListener('drop',e=>e.preventDefault());
 form.addEventListener('submit',e=>{if(busy){e.preventDefault();return;}if(mode==='visual')source.value=editor.innerHTML;dirty=false;state.textContent='กำลังบันทึก…';});
 window.addEventListener('beforeunload',e=>{if(dirty || busy){e.preventDefault();e.returnValue='';}});
 form.elements.slug.addEventListener('input',()=>{const preview=document.querySelector('#blog-slug-preview');if(preview)preview.textContent=form.elements.slug.value;});
 let previewUrl;
 form.elements.cover_file.addEventListener('change',()=>{const file=form.elements.cover_file.files[0];if(!file)return;if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024){alert('เลือกรูป JPG, PNG หรือ WebP ไม่เกิน 5 MB');form.elements.cover_file.value='';return;}if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=URL.createObjectURL(file);const preview=document.querySelector('#blog-cover-preview');preview.src=previewUrl;preview.hidden=false;});
 update();
})();
