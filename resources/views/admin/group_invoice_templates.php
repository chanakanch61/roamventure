<?php
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$list = $rows ?: [['description' => '', 'unit' => 'ท่าน', 'price' => 0, 'disbursement' => 0]];
$row = fn ($i, $r) => '<tr data-gq-row><td><input name="items['.$i.'][description]" maxlength="300" value="'.e($r['description']).'" aria-label="รายการ" placeholder="เช่น ค่าธรรมเนียมวีซ่าจีน"></td><td><input name="items['.$i.'][unit]" maxlength="30" value="'.e($r['unit']).'" aria-label="หน่วย" placeholder="ท่าน"></td><td><input name="items['.$i.'][price]" inputmode="decimal" value="'.e($num($r['price'])).'" aria-label="ราคาตั้งต้น" class="gq-num"></td><td class="gi-disb"><input type="checkbox" name="items['.$i.'][disbursement]" value="1" aria-label="เงินทดรองจ่าย"'.(! empty($r['disbursement']) ? ' checked' : '').'></td><td class="gi-move"><button type="button" class="text-button" data-gt-up aria-label="เลื่อนขึ้น">↑</button><button type="button" class="text-button" data-gt-down aria-label="เลื่อนลง">↓</button></td><td><button type="button" class="text-button danger" data-gq-remove aria-label="ลบรายการนี้">✕</button></td></tr>';
?>
<div class="section-head"><div><a class="text-link" href="<?= base_url().'admin/group-invoices' ?>">← ใบแจ้งหนี้ทั้งหมด</a><h1><?= e($title) ?></h1><p>รายการที่ใช้บ่อยในใบแจ้งหนี้ค่าใช้จ่ายอื่น เลือกจาก dropdown ในช่องรายการแล้วระบบเติมหน่วย ราคา และทดรองจ่ายให้ แก้ราคาในใบแจ้งหนี้ได้เสมอ</p></div></div>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" class="panel" id="gi-templates"><?= csrf_field() ?>
 <div class="table-scroll"><table class="gq-items gi-items">
  <colgroup><col><col class="gq-c-unit"><col class="gq-c-price"><col class="gi-c-disb"><col class="gi-c-move"><col class="gq-c-del"></colgroup>
  <thead><tr><th>รายการ</th><th>หน่วย</th><th class="gq-num">ราคาตั้งต้น</th><th title="เงินทดรองจ่าย: ไม่คิด VAT และไม่หัก ณ ที่จ่าย">ทดรองจ่าย</th><th><span class="sr-only">ลำดับ</span></th><th><span class="sr-only">ลบ</span></th></tr></thead>
  <tbody data-gq-rows><?php foreach ($list as $i => $r): ?><?= $row($i, $r) ?><?php endforeach ?></tbody>
 </table></div>
 <template id="gq-row-template"><?= $row('__i__', ['description' => '', 'unit' => 'ท่าน', 'price' => 0, 'disbursement' => 0]) ?></template>
 <div class="actions gq-add"><button type="button" class="outline-link" data-gq-add>＋ เพิ่มรายการ</button><button class="btn" type="submit">บันทึก template</button></div>
 <p class="fineprint">ราคาตั้งต้นใส่ 0 ได้ถ้าราคาไม่แน่นอน แก้หรือลบ template ไม่กระทบใบแจ้งหนี้ที่ออกไปแล้ว ในใบแจ้งหนี้เลือก “อื่น ๆ (พิมพ์เอง)” เพื่อใส่รายการที่ไม่มีในนี้</p>
</form>
