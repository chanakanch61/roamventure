<link rel="stylesheet" href="<?= base_url().'public/tour-import.css?v='.filemtime(base_path()."/".'public/tour-import.css') ?>">
<section class="panel editor-section" id="tour-import">
 <span class="eyebrow">PDF → AI → JSON</span><h2>นำเข้าโปรแกรมจาก PDF</h2>
 <ol><li>เลือกประเภทโปรแกรม คัดลอก Prompt แล้วนำไปให้ AI พร้อม PDF โปรแกรมทัวร์</li><li>นำ JSON ที่ได้มาวางหรือเลือกไฟล์ .json แล้วกดนำเข้า</li><li>ตรวจทานข้อมูลในฟอร์ม แนบรูปภาพและ PDF ต้นฉบับ แล้วบันทึกครั้งเดียว</li></ol>
 <label>ประเภทโปรแกรมที่จะนำเข้า<select id="tour-import-type"><option value="fixed">ทัวร์ปกติ · รอบเดินทางแน่นอน</option><option value="private">แพ็คเกจทัวร์ส่วนตัว · เลือกวันเอง</option></select></label>
 <p id="tour-import-type-help" class="notice"></p>
 <p>ข้อมูลที่ PDF ไม่ระบุให้เว้นว่าง ห้าม AI เดา · JSON ไม่ได้แนบรูปภาพหรือ PDF มาด้วย</p>
 <div class="actions"><button type="button" class="outline-link" id="copy-tour-prompt">คัดลอก Prompt</button><span id="prompt-status" role="status"></span></div>
 <details><summary>ดู Prompt / คัดลอกด้วยตนเอง</summary><textarea id="tour-prompt" rows="12" readonly aria-label="Prompt สำหรับ AI"></textarea></details>
 <div class="stack-form"><label>วาง JSON<textarea id="tour-json" rows="7" spellcheck="false" placeholder='{"schema_version":1,"title":"..."}'></textarea></label><label>หรือเลือกไฟล์ JSON (ไม่เกิน 2 MB)<input id="tour-json-file" type="file" accept=".json,application/json"></label></div>
 <p class="fineprint">การนำเข้าแทนที่ข้อมูลข้อความและรายการย่อยในฟอร์ม หากนำเข้าอีกครั้ง รูปรายวันที่เลือกไว้จะต้องแนบใหม่</p>
 <button type="button" class="btn" id="import-tour-json">นำเข้า JSON ลงฟอร์ม</button>
 <div id="tour-import-result" role="status" aria-live="polite" hidden></div>
</section>
<script src="<?= base_url().'public/tour-import.js?v='.filemtime(base_path()."/".'public/tour-import.js') ?>" defer></script>
