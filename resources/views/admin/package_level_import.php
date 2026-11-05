<link rel="stylesheet" href="<?= base_url().'public/package-level-import.css?v='.filemtime(base_path()."/".'public/package-level-import.css') ?>">
<section class="panel" id="package-level-import">
 <span class="eyebrow">PDF → AI → JSON</span><h2>นำเข้าระดับบริการจาก PDF</h2>
 <ol><li>คัดลอก Prompt แล้วนำไปให้ AI พร้อม PDF ที่ระบุระดับบริการ</li><li>นำ JSON ที่ได้มาวางหรือเลือกไฟล์ .json แล้วกดนำเข้า</li><li>ตรวจทานชื่อและลำดับ แล้วบันทึกทุกระดับในครั้งเดียว</li></ol>
 <p>ชื่อที่มีอยู่ในระบบแล้วจะถูกข้าม ไม่แก้ไขระดับเดิม · ลำดับเริ่มต่อจากระดับล่าสุดและแก้ไขได้ก่อนบันทึก</p>
 <div class="actions"><button type="button" class="outline-link" id="copy-package-level-prompt">คัดลอก Prompt</button><span id="package-level-prompt-status" role="status"></span></div>
 <details><summary>ดู Prompt / คัดลอกด้วยตนเอง</summary><textarea id="package-level-prompt" rows="12" readonly aria-label="Prompt สำหรับ AI"></textarea></details>
 <div class="stack-form"><label>วาง JSON<textarea id="package-level-json" rows="7" spellcheck="false" placeholder='{"schema_version":1,"kind":"package_levels","levels":[{"name":"..."}]}'></textarea></label><label>หรือเลือกไฟล์ JSON (ไม่เกิน 2 MB)<input id="package-level-json-file" type="file" accept=".json,application/json"></label></div>
 <button type="button" class="btn" id="import-package-level-json">นำเข้า JSON</button>
 <div id="package-level-import-result" role="status" aria-live="polite" hidden></div>
 <form method="post" action="<?= base_url().'admin/package-levels/import' ?>" class="stack-form" id="package-level-rows-form" hidden><?= csrf_field() ?><div class="stack-form" id="package-level-rows"></div><button type="submit" class="btn" id="save-package-level-rows">บันทึกระดับบริการ</button></form>
</section>
<script type="application/json" id="package-level-import-config"><?= json_encode(['existing'=>array_column($existing_levels,'name'),'next_sort'=>$existing_levels ? max(array_column($existing_levels,'sort_order'))+1 : 0,'rows'=>$import_rows],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= base_url().'public/package-level-import.js?v='.filemtime(base_path()."/".'public/package-level-import.js') ?>" defer></script>
