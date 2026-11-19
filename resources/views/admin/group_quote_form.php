<?php
use App\Repositories\GroupQuoteRepository as Quotes;
$q = $quote;
$val = fn ($key) => e((string) ($q[$key] ?? ''));
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$items = $q['items'] ?: [['description' => '', 'qty' => 1, 'unit' => '', 'price' => 0]];
?>
<div class="section-head"><div><a class="text-link" href="<?= base_url().'admin/group-quotes' ?>">← ใบเสนอราคาทั้งหมด</a><h1><?= e($title) ?></h1><?php if ($request_row): ?><p>จากคำขอ <a class="text-link" href="<?= base_url().'admin/group-requests/'.(int) $request_row['id'] ?>"><?= e($request_row['reference']) ?></a> · <?= e($request_row['destination']) ?></p><?php endif ?></div><?php if ($q['id']): ?><div class="actions"><a class="btn" href="<?= base_url().'admin/group-quotes/'.(int) $q['id'].'/print' ?>" target="_blank" rel="noopener">พิมพ์ / บันทึก PDF ↗</a></div><?php endif ?></div>
<?php if (! empty($invoices) && array_filter($invoices, fn ($i) => $i['status'] !== 'cancelled')): ?><p class="notice">ใบเสนอราคานี้ออกใบแจ้งหนี้แล้ว การแก้ไขที่นี่ไม่เปลี่ยนใบแจ้งหนี้ที่ออกไปแล้ว แต่ยอดคงเหลือสำหรับงวดถัดไปจะคิดจากยอดใหม่</p><?php endif ?>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach ?></div><?php endif ?>
<form method="post" class="admin-two-col gq-form" id="gq-form"><?= csrf_field() ?>
 <div>
  <section class="panel stack-form">
   <h2>01 / ลูกค้า</h2>
   <div class="form-grid"><label>ชื่อลูกค้า / ผู้ติดต่อ <span aria-hidden="true">*</span><input name="customer_name" required maxlength="180" value="<?= $val('customer_name') ?>"></label><label>บริษัท / หน่วยงาน<input name="customer_company" maxlength="200" value="<?= $val('customer_company') ?>"></label></div>
   <label>ที่อยู่ (สำหรับออกเอกสาร)<textarea name="customer_address" rows="2" maxlength="1000"><?= $val('customer_address') ?></textarea></label>
   <div class="form-grid"><label>เลขประจำตัวผู้เสียภาษี<input name="customer_tax_id" maxlength="30" value="<?= $val('customer_tax_id') ?>"></label><label>โทรศัพท์<input name="customer_phone" maxlength="40" value="<?= $val('customer_phone') ?>"></label></div>
   <label>อีเมล<input name="customer_email" type="email" maxlength="190" value="<?= $val('customer_email') ?>"></label>
  </section>
  <section class="panel stack-form">
   <h2>02 / ทริป</h2>
   <label>ชื่อรายการ / ทริป <span aria-hidden="true">*</span><input name="title" required maxlength="250" value="<?= $val('title') ?>" placeholder="เช่น กรุ๊ปเหมา ญี่ปุ่น โตเกียว–ฟูจิ 5 วัน 4 คืน"></label>
   <div class="form-grid"><label>วันเดินทาง<input name="travel_dates" maxlength="150" value="<?= $val('travel_dates') ?>" placeholder="เช่น 10–14 มี.ค. 2570"></label><label>จำนวนผู้เดินทาง (ท่าน)<input name="travelers" type="number" min="1" max="9999" value="<?= $val('travelers') ?>"></label></div>
  </section>
  <section class="panel gq-items-panel">
   <div class="section-head"><h2>03 / รายการค่าใช้จ่าย</h2><span>ราคาเป็นบาท (THB)</span></div>
   <div class="table-scroll"><table class="gq-items">
    <colgroup><col><col class="gq-c-qty"><col class="gq-c-unit"><col class="gq-c-price"><col class="gq-c-amount"><col class="gq-c-del"></colgroup>
    <thead><tr><th>รายการ</th><th class="gq-num">จำนวน</th><th>หน่วย</th><th class="gq-num" data-gq-price-head>ราคา / หน่วย</th><th class="gq-num">จำนวนเงิน</th><th><span class="sr-only">ลบ</span></th></tr></thead>
    <tbody data-gq-rows>
     <?php foreach ($items as $i => $item): ?>
     <tr data-gq-row><td><input name="items[<?= $i ?>][description]" maxlength="300" value="<?= e($item['description']) ?>" aria-label="รายการ" placeholder="เช่น ค่าทัวร์ผู้ใหญ่"></td><td><input name="items[<?= $i ?>][qty]" inputmode="decimal" value="<?= e($num($item['qty'])) ?>" aria-label="จำนวน" class="gq-num" data-gq-qty></td><td><input name="items[<?= $i ?>][unit]" maxlength="30" value="<?= e($item['unit']) ?>" aria-label="หน่วย" placeholder="ท่าน"></td><td><input name="items[<?= $i ?>][price]" inputmode="decimal" value="<?= e($num($item['price'])) ?>" aria-label="ราคาต่อหน่วย" class="gq-num" data-gq-price></td><td class="gq-num" data-gq-amount>0.00</td><td><button type="button" class="text-button danger" data-gq-remove aria-label="ลบรายการนี้">✕</button></td></tr>
     <?php endforeach ?>
    </tbody>
   </table></div>
   <template id="gq-row-template"><tr data-gq-row><td><input name="items[__i__][description]" maxlength="300" aria-label="รายการ" placeholder="ชื่อรายการ"></td><td><input name="items[__i__][qty]" inputmode="decimal" value="1" aria-label="จำนวน" class="gq-num" data-gq-qty></td><td><input name="items[__i__][unit]" maxlength="30" aria-label="หน่วย" placeholder="ท่าน"></td><td><input name="items[__i__][price]" inputmode="decimal" value="0" aria-label="ราคาต่อหน่วย" class="gq-num" data-gq-price></td><td class="gq-num" data-gq-amount>0.00</td><td><button type="button" class="text-button danger" data-gq-remove aria-label="ลบรายการนี้">✕</button></td></tr></template>
   <button type="button" class="outline-link gq-add" data-gq-add>＋ เพิ่มรายการ</button>
   <p class="fineprint">ตัวเลขด้านล่างคำนวณให้ดูระหว่างกรอก ยอดที่บันทึกจริงคำนวณใหม่ที่เซิร์ฟเวอร์ทุกครั้ง</p>
  </section>
  <section class="panel stack-form">
   <h2>04 / เงื่อนไข</h2>
   <div class="form-grid"><label>ราคานี้รวม (บรรทัดละ 1 ข้อ)<textarea name="includes" rows="6" maxlength="5000" placeholder="ตั๋วเครื่องบินไป–กลับ&#10;ที่พัก 4 คืน (พักห้องละ 2 ท่าน)&#10;รถบัสตลอดทริป"><?= $val('includes') ?></textarea></label><label>ราคานี้ไม่รวม (บรรทัดละ 1 ข้อ)<textarea name="excludes" rows="6" maxlength="5000" placeholder="ค่าทิปไกด์และคนขับ&#10;ค่าใช้จ่ายส่วนตัว"><?= $val('excludes') ?></textarea></label></div>
   <label>เงื่อนไขการชำระเงิน<textarea name="payment_terms" rows="3" maxlength="5000" placeholder="เช่น มัดจำ … ภายใน … วันหลังยืนยัน ส่วนที่เหลือชำระก่อนเดินทาง … วัน"><?= $val('payment_terms') ?></textarea></label>
   <label>หมายเหตุ<textarea name="notes" rows="3" maxlength="5000" placeholder="เช่น ราคาอาจเปลี่ยนตามราคาตั๋วเครื่องบิน ณ วันออกตั๋ว"><?= $val('notes') ?></textarea></label>
  </section>
 </div>
 <aside>
  <section class="panel stack-form">
   <h2>ใบเสนอราคา</h2>
   <?php if ($q['number']): ?><p><b><?= e($q['number']) ?></b></p><?php else: ?><p class="fineprint">เลขที่ออกให้อัตโนมัติเมื่อบันทึกครั้งแรก</p><?php endif ?>
   <label>สถานะ<select name="status"><?php foreach (Quotes::statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $q['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select><?php if ($request_row): ?><small>“ส่งลูกค้าแล้ว” จะเปลี่ยนคำขอเป็น “ส่งใบเสนอราคาแล้ว” และ “ลูกค้าตอบรับ” เปลี่ยนเป็น “ยืนยันเดินทาง” (ไม่ย้อนสถานะคำขอ)</small><?php endif ?></label>
   <div class="form-grid"><label>วันที่ออก<input type="date" name="issue_date" required value="<?= $val('issue_date') ?>"></label><label>ยืนราคาถึง<input type="date" name="valid_until" value="<?= $val('valid_until') ?>"></label></div>
   <label>ผู้เสนอราคา<input name="prepared_by" maxlength="120" value="<?= $val('prepared_by') ?>"></label>
  </section>
  <section class="panel stack-form gq-totals">
   <h2>สรุปยอด</h2>
   <label>VAT<select name="vat_mode" data-gq-vat-mode><?php foreach (Quotes::vat_modes() as $key => $label): ?><option value="<?= e($key) ?>"<?= $q['vat_mode'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label>
   <div class="form-grid"><label>อัตรา VAT (%)<input name="vat_rate" inputmode="decimal" value="<?= e($num($q['vat_rate'])) ?>" data-gq-vat-rate></label><label>ส่วนลด (บาท)<input name="discount" inputmode="decimal" value="<?= e($num($q['discount'])) ?>" data-gq-discount></label></div>
   <dl class="info-list gq-summary">
    <dt>รวมรายการ</dt><dd data-gq-sum>0.00</dd>
    <dt>ส่วนลด</dt><dd data-gq-discount-out>0.00</dd>
    <dt data-gq-subtotal-label>ยอดก่อน VAT</dt><dd data-gq-subtotal>0.00</dd>
    <dt>VAT</dt><dd data-gq-vat>0.00</dd>
    <dt><b>ยอดรวมทั้งสิ้น</b></dt><dd><b data-gq-total>0.00</b></dd>
    <dt data-gq-wht-row>หัก ณ ที่จ่าย</dt><dd data-gq-wht-row data-gq-wht>0.00</dd>
    <dt data-gq-wht-row><b>ยอดชำระสุทธิ</b></dt><dd data-gq-wht-row><b data-gq-net>0.00</b></dd>
   </dl>
   <label class="gq-check"><input type="checkbox" name="wht_enabled" value="1" data-gq-wht-on<?= ! empty($q['wht_enabled']) ? ' checked' : '' ?>> ลูกค้าหักภาษี ณ ที่จ่าย</label>
   <label data-gq-wht-rate-label>อัตราหัก ณ ที่จ่าย (%)<input name="wht_rate" inputmode="decimal" value="<?= e($num($q['wht_rate'] ?? 3)) ?>" data-gq-wht-rate><small>ใช้กับลูกค้านิติบุคคล (บริษัท หน่วยงาน) ที่ยอดตั้งแต่ 1,000 บาท คิดจากยอดก่อน VAT ค่าบริการทั่วไปมักเป็น 3% ควรยืนยันอัตรากับฝ่ายบัญชี</small></label>
   <label class="gq-check" data-gq-gross-label><input type="checkbox" name="wht_gross_up" value="1" data-gq-gross<?= ! empty($q['wht_gross_up']) ? ' checked' : '' ?>> บวกหัก ณ ที่จ่ายเข้าราคาต่อหน่วย</label>
   <small class="gq-hint" data-gq-gross-hint>กรอกราคาฐานในรายการ ระบบพิมพ์ราคาต่อหน่วยเป็น ราคาฐาน × (1 + อัตรา) เช่น 10,000 → 10,300.00 แล้วจึงคูณจำนวน</small>
   <button class="btn" type="submit">บันทึกใบเสนอราคา</button>
  </section>
 </aside>
</form>
<?php if ($q['id']): ?>
<?php $invStatuses = App\Repositories\GroupInvoiceRepository::statuses(); $billedTotal = array_sum(array_map(fn ($i) => $i['kind'] === 'installment' && $i['status'] !== 'cancelled' ? (float) $i['total'] : 0, $invoices)); ?>
<section class="panel" id="invoices">
 <div class="section-head"><h2>ใบแจ้งหนี้</h2><div class="actions"><a class="btn" href="<?= base_url().'admin/group-invoices/new?quote='.(int) $q['id'] ?>">＋ สร้างใบแจ้งหนี้</a><a class="outline-link" href="<?= base_url().'admin/group-invoices/new?kind=charges&quote='.(int) $q['id'] ?>">＋ ค่าใช้จ่ายอื่น (วีซ่า ฯลฯ)</a></div></div>
 <?php if ($invoices): ?><ul class="gq-quotes"><?php foreach ($invoices as $i): ?><li><span><a class="text-link" href="<?= base_url().'admin/group-invoices/'.(int) $i['id'] ?>"><b><?= e($i['number']) ?></b></a><small><?= $i['kind'] === 'charges' ? 'ค่าใช้จ่ายอื่น · ' : '' ?><?= e($i['label']) ?> · <?= e($invStatuses[$i['status']] ?? $i['status']) ?><?= $i['receipt_number'] ? ' · ใบเสร็จ '.e($i['receipt_number']) : '' ?></small></span><span>฿<?= money($i['total']) ?></span></li><?php endforeach ?></ul><?php endif ?>
 <p class="fineprint">ออกแล้ว ฿<?= money($billedTotal) ?> จาก ฿<?= money($q['total']) ?> · คงเหลือ ฿<?= money(max(0, (float) $q['total'] - $billedTotal)) ?> แบ่งเป็นงวด เช่น มัดจำ 30% แล้วตามด้วยส่วนที่เหลือ ใบแจ้งหนี้ค่าใช้จ่ายอื่นไม่นับรวมในยอดนี้</p>
</section>
<div class="admin-two-col">
 <form method="post" action="<?= base_url().'admin/group-quotes/'.(int) $q['id'].'/copy' ?>" class="panel"><?= csrf_field() ?><strong>ทำสำเนา / ฉบับแก้ไข</strong><p class="fineprint">สร้างใบใหม่ เลขที่ใหม่ สถานะฉบับร่าง โดยคัดลอกรายการทั้งหมดจากใบนี้</p><button type="submit" class="outline-link">ทำสำเนาใบนี้</button></form>
 <form method="post" action="<?= base_url().'admin/group-quotes/'.(int) $q['id'].'/delete' ?>" class="panel" onsubmit="return confirm('ลบใบเสนอราคา <?= e($q['number']) ?>? ลบแล้วกู้คืนไม่ได้')"><?= csrf_field() ?><strong>ลบใบเสนอราคา</strong><p class="fineprint">ไม่กระทบคำขอจัดกรุ๊ปที่เชื่อมไว้</p><button type="submit" class="text-button danger">ลบใบนี้</button></form>
</div>
<?php endif ?>
