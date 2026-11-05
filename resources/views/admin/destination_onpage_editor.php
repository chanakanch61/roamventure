<div class="panel blog-writing">
 <div class="section-head"><div><span class="eyebrow">ON-PAGE CONTENT</span><h2>เนื้อหาบนหน้าจุดหมาย</h2></div><span id="blog-save-state" role="status">พร้อมเขียน</span></div>
 <p class="fineprint">เขียนเนื้อหา จัดหัวข้อ และแทรกรูปภาพได้ในฟอร์มนี้ เนื้อหาจะแสดงต่อจากรายการทัวร์เมื่อบันทึก</p>
 <div class="onpage-block-tools"><details><summary>+ เพิ่มบล็อกสำเร็จรูป</summary><div class="onpage-block-options">
 <?php foreach (['intro'=>'เปิดเรื่อง','highlights'=>'การ์ดไฮไลต์','split'=>'ภาพซ้าย / เนื้อหาขวา','split-reverse'=>'ภาพขวา / เนื้อหาซ้าย','mosaic'=>'ภาพ Mosaic','callout'=>'กล่องแนะนำ','faq'=>'FAQ เปิด–ปิด','cta'=>'ชวนดูโปรแกรม'] as $block=>$label): ?><button type="button" data-onpage-insert="<?= $block ?>"><?= e($label) ?></button><?php endforeach ?>
 </div></details><button type="button" id="onpage-preview-button">ดูตัวอย่างหน้านี้</button><a href="<?= e(base_url().'public/onpage-magazine-preview.html') ?>" target="_blank" rel="noopener">ดูตัวอย่างเลย์เอาต์ ↗</a></div>
 <p class="fineprint">วางเคอร์เซอร์แล้วเลือกบล็อก · คลิกช่องภาพในบล็อกแล้วกด “เพิ่มรูปภาพ” เพื่ออัปโหลด</p>
 <div class="blog-modes" aria-label="รูปแบบตัวแก้ไข"><button type="button" data-blog-mode="visual" class="active" aria-pressed="true">Visual</button><button type="button" data-blog-mode="code" aria-pressed="false">&lt;/&gt; HTML</button></div>
 <div class="blog-toolbar" role="toolbar" aria-label="จัดรูปแบบเนื้อหา">
  <button type="button" data-command="bold" aria-label="ตัวหนา"><b>B</b></button><button type="button" data-command="italic" aria-label="ตัวเอียง"><i>I</i></button>
  <button type="button" data-command="formatBlock" data-value="h2">หัวข้อ H2</button><button type="button" data-command="formatBlock" data-value="h3">H3</button><button type="button" data-command="formatBlock" data-value="p">ย่อหน้า</button>
  <button type="button" data-command="insertUnorderedList">• รายการ</button><button type="button" data-command="insertOrderedList">1. รายการ</button><button type="button" data-command="formatBlock" data-value="blockquote">❝ คำกล่าว</button>
  <button type="button" id="blog-add-link">ลิงก์</button><button type="button" data-command="unlink">ลบลิงก์</button><button type="button" data-command="undo" aria-label="ย้อนกลับ">↶</button>
 </div>
 <div class="blog-media-tools"><button type="button" id="blog-upload-image">+ เพิ่มรูปภาพ</button><span>JPG / PNG / WebP · ไม่เกิน 5 MB</span><input type="file" id="blog-inline-image" accept="image/jpeg,image/png,image/webp" hidden></div>
 <p id="onpage-image-guide" class="onpage-image-guide" role="status">คลิกช่องภาพเพื่อดูขนาดที่แนะนำ · ภาพอาจถูกครอปให้พอดีกับหน้าจอ ควรวางจุดเด่นไว้กลางภาพ</p>
 <div class="onpage-canvas">
  <div class="onpage-block-outline" aria-hidden="true" hidden></div>
  <div id="onpage-block-controls" class="onpage-block-controls" role="group" aria-label="จัดลำดับบล็อกที่เลือก" hidden><span data-onpage-selected-label aria-live="polite"></span><button type="button" data-onpage-move="up" aria-label="เลื่อนบล็อกขึ้น" aria-keyshortcuts="Alt+ArrowUp" title="เลื่อนบล็อกขึ้น (Alt + ↑)">↑ ขึ้น</button><button type="button" data-onpage-move="down" aria-label="เลื่อนบล็อกลง" aria-keyshortcuts="Alt+ArrowDown" title="เลื่อนบล็อกลง (Alt + ↓)">↓ ลง</button></div>
  <div id="blog-visual" class="blog-prose onpage-magazine onpage-editor" contenteditable="true" role="textbox" aria-label="เนื้อหา On-page" aria-multiline="true"></div>
 </div>
 <small>คลิกภายในบล็อกเพื่อใช้ปุ่ม ↑ ขึ้น / ↓ ลง · ใช้คีย์ลัด Alt + ↑ / ↓ ได้ในโหมด Visual</small>
 <label id="blog-source-label">เนื้อหา HTML<textarea id="blog-content" name="onpage_content" maxlength="200000" rows="18"><?= e($node['onpage_content'] ?? '') ?></textarea></label>
 <small>คลิกรูปเพื่อแก้ไข Alt text · ใช้ H2 / H3 เป็นหัวข้อย่อย โดยชื่อจุดหมายเป็น H1 ของหน้า</small>
 <section class="destination-image-references" aria-labelledby="destination-images-title">
  <div class="section-head"><h3 id="destination-images-title">ภาพประกอบจาก Shutterstock</h3><button type="button" class="text-button" data-clear-image-suggestions hidden>ล้างรายการ</button></div>
  <p class="fineprint">ลิงก์ค้นหาภาพตามหัวข้อสำหรับผู้เขียน · เปิดไปเลือกดาวน์โหลดภาพจริง แล้วอัปโหลดรูปปกหรือแทรกในเนื้อหา · ตรวจ Alt text ให้ตรงภาพที่เลือก</p>
  <input type="hidden" name="image_suggestions" value="<?= e($node['image_suggestions'] ?? '[]') ?>">
  <div data-image-suggestions-list></div><p class="fineprint" data-image-suggestions-empty>เมื่อใช้ Prompt ใหม่และนำเข้า JSON รายการลิงก์ค้นหาภาพจะปรากฏที่นี่ และเก็บไว้เมื่อบันทึก</p>
 </section>
</div>
<dialog id="onpage-layout-preview" class="onpage-preview-dialog" aria-labelledby="onpage-preview-title"><div class="onpage-preview-bar"><h2 id="onpage-preview-title">ตัวอย่าง On-page</h2><button type="button" data-close-onpage-preview aria-label="ปิดตัวอย่าง">ปิด ×</button></div><div class="onpage-magazine" data-onpage-preview-content></div></dialog>
