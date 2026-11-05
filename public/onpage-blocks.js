(() => {
 'use strict';
 const form=document.querySelector('#destination-form[data-rich-editor]');if(!form)return;
 const editor=form.querySelector('#blog-visual'),state=form.querySelector('#blog-save-state'),dialog=document.querySelector('#onpage-layout-preview');
 const guide=form.querySelector('#onpage-image-guide'),modalGuide=document.querySelector('#blog-image-size-guide');
 const imageGuide=target=>{
  const element=target?.nodeType===1?target:target?.parentElement,figure=element?.closest('figure');
  const type=figure&&editor.contains(figure)?figure.closest('section[data-onpage]')?.dataset.onpage:null;
  if(type==='intro')return 'ภาพเปิดเรื่อง · แนะนำ 1920 × 1080 px (16:9)';
  if(type==='split'||type==='split-reverse')return 'ภาพคู่เนื้อหา · แนะนำ 1320 × 1200 px (11:10)';
  if(type==='mosaic'){
   const first=figure.parentElement.querySelector('figure')===figure;
   return first?'Mosaic ช่องใหญ่ · แนะนำ 1400 × 1000 px (7:5)':'Mosaic ช่องเล็ก · แนะนำ 1200 × 600 px (2:1)';
  }
  return 'ภาพในเนื้อหา · แนะนำ 1600 × 1067 px (ประมาณ 3:2)';
 };
 const showGuide=target=>{guide.textContent=imageGuide(target)+' · ภาพอาจถูกครอปตามหน้าจอ ควรวางจุดเด่นไว้กลางภาพ';};
 document.addEventListener('selectionchange',()=>{const selection=getSelection();if(!editor.hidden&&selection.anchorNode&&editor.contains(selection.anchorNode))showGuide(selection.anchorNode);});
 form.addEventListener('rich-image-details',event=>{
  const text=imageGuide(event.detail.mode==='code'?null:event.detail.target);
  modalGuide.textContent=text+' · JPG / PNG / WebP ไม่เกิน 5 MB และไม่เกิน 6000 × 6000 px · เป็นขนาดแนะนำ ภาพอาจถูกครอปตามหน้าจอ';modalGuide.hidden=false;
 });
 const escape=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const template=type=>{
  const name=escape(form.elements.name.value.trim()||'จุดหมายที่คุณสนใจ');
  const templates={
   intro:`<section data-onpage="intro"><p data-onpage="kicker">YOUR NEXT JOURNEY</p><h2>ออกเดินทางไป${name}</h2><p>เริ่มวางแผนการเดินทางในแบบของคุณ เลือกเส้นทางและช่วงเวลาที่สนใจ แล้วดูรายละเอียดของแต่ละโปรแกรมก่อนตัดสินใจ</p><figure><br></figure></section>`,
   highlights:`<section data-onpage="highlights"><p data-onpage="kicker">FIND YOUR TRAVEL STYLE</p><h2>เลือกทริปที่เหมาะกับคุณ</h2><div data-onpage="cards"><article><h3>เส้นทางที่อยากไป</h3><p>เริ่มจากสถานที่ที่สนใจ แล้วเปรียบเทียบจุดหมายในแต่ละโปรแกรม</p></article><article><h3>จังหวะการเดินทาง</h3><p>ดูแผนรายวันและกิจกรรม เพื่อเลือกทริปที่ตรงกับสไตล์ของคุณ</p></article><article><h3>ช่วงเวลาที่สะดวก</h3><p>ตรวจวันเดินทางและรายละเอียดของรอบที่สนใจก่อนจอง</p></article></div></section>`,
   split:`<section data-onpage="split"><figure><br></figure><div><p data-onpage="kicker">EXPLORE THE DESTINATION</p><h2>สัมผัสเสน่ห์ของ${name}</h2><p>บอกเล่าจุดเด่นของสถานที่และกิจกรรมที่สัมพันธ์กับเส้นทาง เพื่อช่วยให้ผู้อ่านมองเห็นภาพการเดินทางและเลือกโปรแกรมที่สนใจ</p></div></section>`,
   'split-reverse':`<section data-onpage="split-reverse"><figure><br></figure><div><p data-onpage="kicker">A DIFFERENT PERSPECTIVE</p><h2>อีกมุมของ${name}</h2><p>เพิ่มเรื่องราวหรือจุดเด่นอีกด้านของจุดหมาย พร้อมข้อมูลที่ช่วยให้ผู้อ่านเลือกทริปได้เหมาะกับความสนใจ</p></div></section>`,
   mosaic:`<section data-onpage="mosaic"><p data-onpage="kicker">MOMENTS TO REMEMBER</p><h2>บรรยากาศที่อยากออกไปสัมผัส</h2><div data-onpage="gallery"><figure><br></figure><figure><br></figure><figure><br></figure></div></section>`,
   callout:`<section data-onpage="callout"><p data-onpage="kicker">MAKE IT YOUR JOURNEY</p><h2>ทริปแบบไหนที่เหมาะกับคุณ?</h2><p>ลองเริ่มจากสิ่งที่อยากทำ สถานที่ที่อยากเห็น และช่วงเวลาที่สะดวก แล้วเลือกโปรแกรมที่ตอบโจทย์การเดินทางของคุณ</p></section>`,
   faq:`<section data-onpage="faq"><p data-onpage="kicker">BEFORE YOU GO</p><h2>คำถามก่อนออกเดินทาง</h2><details><summary>ควรเลือกโปรแกรมอย่างไร?</summary><p>พิจารณาเส้นทาง กิจกรรม และวันเดินทางของแต่ละโปรแกรมให้ตรงกับความสนใจ แล้วสอบถามรายละเอียดที่ต้องการเพิ่มเติมก่อนจอง</p></details><details><summary>ดูรายละเอียดและวันเดินทางได้ที่ไหน?</summary><p>เลือกโปรแกรมที่สนใจจากรายการทัวร์ เพื่อดูรายละเอียดและรอบเดินทางที่แสดงในโปรแกรมนั้น</p></details></section>`,
   cta:`<section data-onpage="cta"><p data-onpage="kicker">LET THE JOURNEY BEGIN</p><h2>เริ่มเลือกทริป${name}ของคุณ</h2><p>ดูเส้นทางและรายละเอียดโปรแกรม แล้วเลือกการเดินทางที่เหมาะกับคุณ</p><a data-onpage="button" href="#destination-programs">ดูโปรแกรมทัวร์ ↗</a></section>`
  };
  return templates[type];
 };
 const canvas=form.querySelector('.onpage-canvas'),controls=form.querySelector('#onpage-block-controls'),outline=canvas.querySelector('.onpage-block-outline');
 const selectedLabel=controls.querySelector('[data-onpage-selected-label]'),up=controls.querySelector('[data-onpage-move="up"]'),down=controls.querySelector('[data-onpage-move="down"]');
 let selectedBlock=null,controlsScheduled=false;
 const blocks=()=>[...editor.children].filter(node=>node.matches('section[data-onpage]'));
 const blockAt=node=>{
  let element=node?.nodeType===1?node:node?.parentElement;
  while(element&&element!==editor&&element.parentElement!==editor)element=element.parentElement;
  return element?.parentElement===editor&&element.matches('section[data-onpage]')?element:null;
 };
 const refreshControls=()=>{
  controlsScheduled=false;
  if(!selectedBlock||selectedBlock.parentElement!==editor||editor.hidden||editor.contentEditable!=='true'){
   controls.hidden=true;outline.hidden=true;if(selectedBlock?.parentElement!==editor)selectedBlock=null;return;
  }
  const items=blocks(),index=items.indexOf(selectedBlock);up.disabled=index===0;down.disabled=index===items.length-1;
  const title=selectedBlock.querySelector('h2,h3')?.textContent.trim()||'บล็อก '+selectedBlock.dataset.onpage,label=(index+1)+' / '+items.length+' · '+title;
  if(selectedLabel.textContent!==label)selectedLabel.textContent=label;selectedLabel.title=title;
  controls.hidden=false;outline.hidden=false;
  const box=selectedBlock.getBoundingClientRect(),origin=canvas.getBoundingClientRect();
  controls.style.top=Math.max(8,box.top-origin.top-controls.offsetHeight-8)+'px';
  Object.assign(outline.style,{top:(box.top-origin.top-4)+'px',left:(box.left-origin.left-4)+'px',width:(box.width+8)+'px',height:(box.height+8)+'px'});
 };
 const scheduleControls=()=>{if(!controlsScheduled){controlsScheduled=true;requestAnimationFrame(refreshControls);}};
 const selectBlock=block=>{selectedBlock=block;scheduleControls();};
 const spacer=node=>node.nodeType===3?!node.textContent.trim():node.nodeType===1&&node.tagName==='P'&&[...node.childNodes].every(child=>child.nodeType===3?!child.textContent.trim():child.nodeType===1&&child.tagName==='BR');
 const unit=block=>{const nodes=[block];let next=block.nextSibling;while(next&&spacer(next)){nodes.push(next);next=next.nextSibling;}return nodes;};
 const moveBlock=direction=>{
  if(!selectedBlock||selectedBlock.parentElement!==editor||editor.hidden||editor.contentEditable!=='true')return;
  const items=blocks(),index=items.indexOf(selectedBlock),neighbor=items[index+(direction==='up'?-1:1)];if(!neighbor)return;
  const selection=getSelection(),oldRange=selection.rangeCount?selection.getRangeAt(0):null;
  const caret=oldRange&&selectedBlock.contains(oldRange.startContainer)&&selectedBlock.contains(oldRange.endContainer)?{start:oldRange.startContainer,startOffset:oldRange.startOffset,end:oldRange.endContainer,endOffset:oldRange.endOffset}:null;
  const keepButtonFocus=controls.contains(document.activeElement),reference=direction==='up'?neighbor:unit(neighbor).at(-1).nextSibling,fragment=document.createDocumentFragment();
  unit(selectedBlock).forEach(node=>fragment.append(node));editor.insertBefore(fragment,reference);
  if(!keepButtonFocus)editor.focus({preventScroll:true});
  const range=document.createRange();if(caret){range.setStart(caret.start,caret.startOffset);range.setEnd(caret.end,caret.endOffset);}else{range.selectNodeContents(selectedBlock);range.collapse(true);}
  selection.removeAllRanges();selection.addRange(range);editor.dispatchEvent(new Event('input',{bubbles:true}));
  selectedBlock.scrollIntoView({block:'nearest',behavior:'auto'});scheduleControls();
 };
 controls.querySelectorAll('[data-onpage-move]').forEach(button=>{
  button.addEventListener('mousedown',event=>event.preventDefault());button.addEventListener('click',()=>moveBlock(button.dataset.onpageMove));
 });
 editor.addEventListener('click',event=>selectBlock(blockAt(event.target)));
 document.addEventListener('selectionchange',()=>{const selection=getSelection();if(!editor.hidden&&selection.anchorNode&&editor.contains(selection.anchorNode))selectBlock(blockAt(selection.anchorNode));});
 editor.addEventListener('keydown',event=>{
  if(!event.altKey||event.ctrlKey||event.metaKey||event.shiftKey||!['ArrowUp','ArrowDown'].includes(event.key))return;
  const block=blockAt(getSelection().anchorNode);if(!block)return;event.preventDefault();selectedBlock=block;moveBlock(event.key==='ArrowUp'?'up':'down');
 });
 const openAnswers=()=>editor.querySelectorAll('[data-onpage="faq"] details:not([open])').forEach(d=>d.open=true);
 new MutationObserver(()=>{openAnswers();scheduleControls();}).observe(editor,{childList:true,characterData:true,subtree:true,attributes:true,attributeFilter:['hidden','contenteditable']});
 new ResizeObserver(scheduleControls).observe(editor);window.addEventListener('resize',scheduleControls);openAnswers();
 form.querySelectorAll('[data-onpage-insert]').forEach(button=>{
  button.addEventListener('mousedown',e=>e.preventDefault());
  button.addEventListener('click',async()=>{
   try{await form.richContent.insert(template(button.dataset.onpageInsert)+'<p><br></p>');button.closest('details').open=false;openAnswers();}
   catch(error){state.textContent=error.message;}
  });
 });
 editor.addEventListener('click',event=>{
  const figure=event.target.closest('figure');if(!figure||figure.querySelector('img')||!editor.contains(figure))return;
  editor.focus();const range=document.createRange();range.selectNodeContents(figure);range.collapse(true);const selection=getSelection();selection.removeAllRanges();selection.addRange(range);
 });
 document.querySelector('#onpage-preview-button').addEventListener('mousedown',e=>e.preventDefault());
 document.querySelector('#onpage-preview-button').addEventListener('click',async()=>{
  try{const html=await form.richContent.preview();dialog.querySelector('[data-onpage-preview-content]').innerHTML=html||'<p>เพิ่มเนื้อหาเพื่อดูตัวอย่างหน้านี้</p>';dialog.showModal();}
  catch(error){state.textContent=error.message;}
 });
 dialog.querySelector('[data-close-onpage-preview]').addEventListener('click',()=>dialog.close());
})();
