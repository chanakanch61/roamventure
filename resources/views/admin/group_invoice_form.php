<?php
use App\Repositories\GroupInvoiceRepository as Invoices;
use App\Repositories\GroupQuoteRepository as Quotes;
$inv = $invoice;
$charges = $inv['kind'] === 'charges';
$val = fn ($key) => e((string) ($inv[$key] ?? ''));
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$editable = $inv['status'] === 'unpaid' && ($charges || $quote);
$statuses = Invoices::statuses();
$pill = ['unpaid' => 'contacted', 'paid' => 'confirmed', 'cancelled' => 'cancelled'];
$left = $quote && ! $charges ? round((float) $quote['total'] - $billed['total'], 2) : 0;
$hasVat = ($inv['vat_mode'] ?? ($quote['vat_mode'] ?? 'none')) !== 'none';
$shareValue = $charges || $inv['share_value'] === '' || $inv['share_type'] === 'remaining' ? '' : $num($inv['share_value']);
$items = $charges ? ($inv['items'] ?: [['description' => '', 'qty' => 1, 'unit' => 'ท่าน', 'price' => 0, 'disbursement' => 0]]) : [];
$templates = $templates ?? [];
$known = array_column($templates, 'description');
// Description cell: a dropdown of saved templates plus "other", which shows the free-text box. The text box is what is posted.
$desc = function ($i, string $text) use ($templates, $known, $num) {
    if (! $templates) {
        return '<input name="items['.$i.'][description]" maxlength="300" value="'.e($text).'" aria-label="รายการ" placeholder="เช่น ค่าธรรมเนียมวีซ่าจีน">';
    }
    $other = $text !== '' && ! in_array($text, $known, true);
    $html = '<select data-gi-pick aria-label="เลือกรายการ"><option value="">— เลือกรายการ —</option>';
    foreach ($templates as $t) {
        $html .= '<option value="'.e($t['description']).'" data-unit="'.e($t['unit']).'" data-price="'.e($num($t['price'])).'" data-disb="'.(int) $t['disbursement'].'"'.($t['description'] === $text ? ' selected' : '').'>'.e($t['description']).'</option>';
    }
    $html .= '<option value="__other"'.($other ? ' selected' : '').'>อื่น ๆ (พิมพ์เอง)</option></select>';

    return $html.'<input name="items['.$i.'][description]" maxlength="300" value="'.e($text).'" aria-label="รายการ (พิมพ์เอง)" placeholder="พิมพ์ชื่อรายการ" data-gi-desc'.($other ? '' : ' hidden').'>';
};
$row = fn ($i, $item) => '<tr data-gq-row><td class="gi-desc">'.$desc($i, (string) $item['description']).'</td><td><input name="items['.$i.'][qty]" inputmode="decimal" value="'.e($num($item['qty'])).'" aria-label="จำนวน" class="gq-num" data-gq-qty></td><td><input name="items['.$i.'][unit]" maxlength="30" value="'.e($item['unit']).'" aria-label="หน่วย" placeholder="ท่าน"></td><td><input name="items['.$i.'][price]" inputmode="decimal" value="'.e($num($item['price'])).'" aria-label="ราคาต่อหน่วย" class="gq-num" data-gq-price></td><td class="gi-disb"><input type="checkbox" name="items['.$i.'][disbursement]" value="1" aria-label="เงินทดรองจ่าย ไม่คิด VAT" data-gi-disb'.(! empty($item['disbursement']) ? ' checked' : '').'></td><td class="gq-num" data-gq-amount>0.00</td><td><button type="button" class="text-button danger" data-gq-remove aria-label="ลบรายการนี้">✕</button></td></tr>';
?>
<div class="section-head"><div><a class="text-link" href="<?= base_url().'admin/group-invoices' ?>">← ใบแจ้งหนี้ทั้งหมด</a><h1><?= e($title) ?></h1>
<?php if ($charges): ?><p>ใบแจ้งหนี้ค่าใช้จ่ายอื่น<?php if ($inv['quotation_id']): ?> · อ้างอิงใบเสนอราคา <?= $quote ? '<a class="text-link" href="'.base_url().'admin/group-quotes/'.(int) $quote['id'].'">'.e($quote['number']).'</a>' : e($inv['quote_number']) ?><?php endif ?><?php if ($inv['booking_id']): ?> · อ้างอิงการจอง <a class="text-link" href="<?= base_url().'admin/bookings/'.(int) $inv['booking_id'] ?>"><?= e($inv['booking_reference']) ?></a><?php endif ?></p>
<?php elseif ($quote): ?><p>จากใบเสนอราคา <a class="text-link" href="<?= base_url().'admin/group-quotes/'.(int) $quote['id'] ?>"><?= e($quote['number']) ?></a> · <?= e($quote['title']) ?></p><?php endif ?></div>
<?php if ($inv['id']): ?><div class="actions"><span class="pill <?= e($pill[$inv['status']]) ?>"><?= e($statuses[$inv['status']]) ?></span><a class="btn" href="<?= base_url().'admin/group-invoices/'.(int) $inv['id'].'/print' ?>" target="_blank" rel="noopener">พิมพ์ใบแจ้งหนี้ ↗</a><?php if ($inv['receipt_number']): ?><a class="btn" href="<?= base_url().'admin/group-invoices/'.(int) $inv['id'].'/receipt' ?>" target="_blank" rel="noopener">พิมพ์<?= $hasVat ? 'ใบเสร็จ / ใบกำกับภาษี' : 'ใบเสร็จรับเงิน' ?> ↗</a><?php endif ?></div><?php endif ?></div>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach ?></div><?php endif ?>
<?php if ($inv['status'] === 'cancelled'): ?><p class="notice error">ใบแจ้งหนี้นี้ถูกยกเลิกแล้ว<?= $inv['receipt_number'] ? ' (รวมใบเสร็จ '.e($inv['receipt_number']).')' : '' ?> เอกสารที่พิมพ์จะมีตราประทับ “ยกเลิก” เลขที่ไม่ถูกนำกลับมาใช้</p><?php elseif ($inv['status'] === 'paid'): ?><p class="notice">รับชำระแล้ว ฿<?= money($inv['paid_amount']) ?> เมื่อ <?= e(thai_date($inv['paid_at'])) ?> · ใบเสร็จเลขที่ <b><?= e($inv['receipt_number']) ?></b> ใบแจ้งหนี้ที่ชำระแล้วแก้ไขไม่ได้ หากต้องแก้ ให้ยกเลิกแล้วออกใหม่</p><?php endif ?>

<form method="post" class="admin-two-col<?= $charges ? ' gi-wide' : '' ?>" id="gi-form"><?= csrf_field() ?>
 <fieldset <?= $editable ? '' : 'disabled' ?> style="border:0;padding:0;margin:0;min-width:0">
<?php if ($charges): ?>
  <section class="panel stack-form">
   <h2>01 / รายละเอียด</h2>
   <label>หัวข้อใบแจ้งหนี้ <span aria-hidden="true">*</span><input name="label" required maxlength="200" value="<?= $val('label') ?>" placeholder="เช่น ค่าวีซ่าและค่าใช้จ่ายเพิ่มเติม"></label>
   <label>ทริป / รายการเดินทาง<input name="title" maxlength="250" value="<?= $val('title') ?>" placeholder="ไม่บังคับ เช่น กรุ๊ปเหมา จีน เฉิงตู 6 วัน"></label>
   <div class="form-grid"><label>วันเดินทาง<input name="travel_dates" maxlength="150" value="<?= $val('travel_dates') ?>"></label><label>จำนวนผู้เดินทาง (ท่าน)<input name="travelers" type="number" min="1" max="9999" value="<?= $val('travelers') ?>"></label></div>
   <div class="form-grid"><label>วันที่ออก<input type="date" name="issue_date" required value="<?= $val('issue_date') ?>"></label><label>ครบกำหนดชำระ<input type="date" name="due_date" value="<?= $val('due_date') ?>"></label></div>
  </section>
  <section class="panel gq-items-panel" id="gi-charges">
   <div class="section-head"><h2>02 / รายการค่าใช้จ่าย</h2><span>ราคาเป็นบาท (THB) · <a class="text-link" href="<?= base_url().'admin/group-invoices/templates' ?>" target="_blank" rel="noopener">จัดการ template รายการ ↗</a></span></div>
   <div class="table-scroll"><table class="gq-items gi-items">
    <colgroup><col><col class="gq-c-qty"><col class="gq-c-unit"><col class="gq-c-price"><col class="gi-c-disb"><col class="gq-c-amount"><col class="gq-c-del"></colgroup>
    <thead><tr><th>รายการ</th><th class="gq-num">จำนวน</th><th>หน่วย</th><th class="gq-num">ราคา / หน่วย</th><th title="เงินทดรองจ่าย: ไม่คิด VAT และไม่หัก ณ ที่จ่าย">ทดรองจ่าย</th><th class="gq-num">จำนวนเงิน</th><th><span class="sr-only">ลบ</span></th></tr></thead>
    <tbody data-gq-rows><?php foreach ($items as $i => $item): ?><?= $row($i, $item) ?><?php endforeach ?></tbody>
   </table></div>
   <template id="gq-row-template"><?= $row('__i__', ['description' => '', 'qty' => 1, 'unit' => 'ท่าน', 'price' => 0, 'disbursement' => 0]) ?></template>
   <?php if ($editable): ?><button type="button" class="outline-link gq-add" data-gq-add>＋ เพิ่มรายการ</button><?php endif ?>
   <p class="fineprint">ติ๊ก “ทดรองจ่าย” สำหรับเงินที่จ่ายแทนลูกค้าตามจริง เช่น ค่าธรรมเนียมวีซ่าที่จ่ายให้สถานทูต รายการนี้ไม่คิด VAT และไม่หัก ณ ที่จ่าย ค่าบริการของบริษัทเอง (เช่น ค่าบริการยื่นวีซ่า) ไม่ต้องติ๊ก ควรยืนยันการแยกรายการกับฝ่ายบัญชี</p>
   <div class="form-grid">
    <label>VAT (เฉพาะรายการที่ไม่ใช่ทดรองจ่าย)<select name="vat_mode" data-gq-vat-mode><?php foreach (Quotes::vat_modes() as $key => $label): ?><option value="<?= e($key) ?>"<?= $inv['vat_mode'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label>
    <label>อัตรา VAT (%)<input name="vat_rate" inputmode="decimal" value="<?= e($num($inv['vat_rate'])) ?>" data-gq-vat-rate></label>
   </div>
   <label class="gq-check"><input type="checkbox" name="wht_enabled" value="1" data-gq-wht-on<?= ! empty($inv['wht_enabled']) ? ' checked' : '' ?>> ลูกค้าหักภาษี ณ ที่จ่าย</label>
   <label data-gq-wht-rate-label>อัตราหัก ณ ที่จ่าย (%)<input name="wht_rate" inputmode="decimal" value="<?= e($num($inv['wht_rate'] ?? 3)) ?>" data-gq-wht-rate><small class="gq-hint">คิดจากยอดค่าบริการก่อน VAT ไม่รวมรายการทดรองจ่าย</small></label>
   <dl class="info-list gq-summary">
    <dt>ค่าบริการก่อน VAT</dt><dd data-gq-subtotal>0.00</dd>
    <dt>VAT</dt><dd data-gq-vat>0.00</dd>
    <dt>เงินทดรองจ่าย (ไม่มี VAT)</dt><dd data-gi-exempt>0.00</dd>
    <dt><b>ยอดรวมทั้งสิ้น</b></dt><dd><b data-gq-total>0.00</b></dd>
    <dt data-gq-wht-row>หัก ณ ที่จ่าย</dt><dd data-gq-wht-row data-gq-wht>0.00</dd>
    <dt data-gq-wht-row><b>ยอดที่ต้องชำระ</b></dt><dd data-gq-wht-row><b data-gq-net>0.00</b></dd>
   </dl>
   <p class="fineprint">ตัวเลขคำนวณให้ดูระหว่างกรอก ยอดที่บันทึกจริงคำนวณใหม่ที่เซิร์ฟเวอร์ทุกครั้ง</p>
  </section>
<?php else: ?>
  <section class="panel stack-form">
   <h2>01 / งวดที่เรียกเก็บ</h2>
   <?php if ($quote): ?><dl class="info-list"><dt>มูลค่าใบเสนอราคา</dt><dd>฿<?= money($quote['total']) ?> (<?= e(Quotes::vat_modes()[$quote['vat_mode']]) ?><?= ! empty($quote['wht_enabled']) ? ' · หัก ณ ที่จ่าย '.e($num($quote['wht_rate'])).'%' : '' ?>)</dd><dt>ออกใบแจ้งหนี้อื่นแล้ว</dt><dd>฿<?= money($billed['total']) ?></dd><dt>ยอดคงเหลือที่ออกได้</dt><dd><b>฿<?= money($left) ?></b></dd></dl><?php endif ?>
   <?php if ($editable): ?><div class="actions gi-presets"><span class="fineprint">ตั้งค่าด่วน:</span><button type="button" class="outline-link" data-gi-preset="percent|30|งวดที่ 1 มัดจำ 30%">มัดจำ 30%</button><button type="button" class="outline-link" data-gi-preset="percent|50|งวดที่ 1 มัดจำ 50%">มัดจำ 50%</button><button type="button" class="outline-link" data-gi-preset="remaining||<?= e($others ? 'งวดที่ '.(count(array_filter($others, fn ($o) => $o['status'] !== 'cancelled')) + 1).' ส่วนที่เหลือ' : 'ชำระเต็มจำนวน') ?>">ส่วนที่เหลือทั้งหมด</button></div><?php endif ?>
   <label>ชื่องวด <span aria-hidden="true">*</span><input name="label" required maxlength="200" value="<?= $val('label') ?>" placeholder="เช่น งวดที่ 1 มัดจำ 30%"></label>
   <div class="form-grid"><label>เรียกเก็บแบบ<select name="share_type" data-gi-type><?php foreach (Invoices::share_types() as $key => $label): ?><option value="<?= e($key) ?>"<?= $inv['share_type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label><label data-gi-value-label><span data-gi-value-text><?= $inv['share_type'] === 'amount' ? 'จำนวนเงิน (บาท รวม VAT)' : 'เปอร์เซ็นต์ (%)' ?></span><input name="share_value" inputmode="decimal" value="<?= e($shareValue) ?>" data-gi-value></label></div>
   <?php if (isset($inv['total'])): ?><dl class="info-list gq-summary"><dt>ยอดก่อน VAT</dt><dd><?= money($inv['subtotal']) ?></dd><dt>VAT</dt><dd><?= money($inv['vat_amount']) ?></dd><dt><b>ยอดรวมงวดนี้</b></dt><dd><b>฿<?= money($inv['total']) ?></b></dd><?php if ((float) $inv['wht_amount'] > 0): ?><dt>หัก ณ ที่จ่าย</dt><dd>-<?= money($inv['wht_amount']) ?></dd><dt><b>ยอดที่ต้องชำระ</b></dt><dd><b>฿<?= money($inv['net_payable']) ?></b></dd><?php endif ?></dl><?php else: ?><p class="fineprint">ยอดเงิน VAT และหัก ณ ที่จ่ายของงวดนี้ ระบบคำนวณตามสัดส่วนของใบเสนอราคาเมื่อกดบันทึก งวด “ส่วนที่เหลือทั้งหมด” ปิดยอดให้ตรงถึงสตางค์</p><?php endif ?>
   <div class="form-grid"><label>วันที่ออก<input type="date" name="issue_date" required value="<?= $val('issue_date') ?>"></label><label>ครบกำหนดชำระ<input type="date" name="due_date" value="<?= $val('due_date') ?>"></label></div>
  </section>
<?php endif ?>
  <section class="panel stack-form">
   <h2><?= $charges ? '03' : '02' ?> / ลูกค้า (พิมพ์บนใบแจ้งหนี้และใบกำกับภาษี)</h2>
   <div class="form-grid"><label>ชื่อลูกค้า / ผู้ติดต่อ <span aria-hidden="true">*</span><input name="customer_name" required maxlength="180" value="<?= $val('customer_name') ?>"></label><label>บริษัท / หน่วยงาน<input name="customer_company" maxlength="200" value="<?= $val('customer_company') ?>"></label></div>
   <label>ที่อยู่<textarea name="customer_address" rows="2" maxlength="1000"><?= $val('customer_address') ?></textarea></label>
   <div class="form-grid"><label>เลขประจำตัวผู้เสียภาษี<input name="customer_tax_id" maxlength="30" value="<?= $val('customer_tax_id') ?>"></label><label>สาขาของลูกค้า<input name="customer_branch" maxlength="60" value="<?= $val('customer_branch') ?>" placeholder="สำนักงานใหญ่ หรือ สาขาที่ 00001"></label></div>
   <div class="form-grid"><label>โทรศัพท์<input name="customer_phone" maxlength="40" value="<?= $val('customer_phone') ?>"></label><label>อีเมล<input name="customer_email" type="email" maxlength="190" value="<?= $val('customer_email') ?>"></label></div>
   <label>สาขาของบริษัทเรา (ผู้ออกใบกำกับภาษี)<input name="seller_branch" maxlength="60" value="<?= $val('seller_branch') ?>"></label>
   <label>หมายเหตุ<textarea name="notes" rows="2" maxlength="3000"><?= $val('notes') ?></textarea></label>
   <?php if ($editable): ?><button class="btn" type="submit"><?= $inv['id'] ? 'บันทึกใบแจ้งหนี้' : 'สร้างใบแจ้งหนี้' ?></button><?php endif ?>
  </section>
 </fieldset>
 <aside>
  <?php if ($inv['id'] && $inv['status'] === 'unpaid'): ?>
  <section class="panel stack-form" id="payment">
   <h2>บันทึกรับชำระ</h2>
   <p class="fineprint">เมื่อบันทึก ระบบออก<?= $hasVat ? 'ใบเสร็จรับเงิน / ใบกำกับภาษี' : 'ใบเสร็จรับเงิน' ?>เลขที่ถัดไปให้ และแก้ใบแจ้งหนี้นี้ไม่ได้อีก</p>
   <div class="form-grid"><label>วันที่รับชำระ<input type="date" name="paid_at" form="gi-pay" required max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>"></label><label>ยอดที่ได้รับ (บาท)<input name="paid_amount" form="gi-pay" inputmode="decimal" required value="<?= e(number_format((float) $inv['net_payable'], 2, '.', '')) ?>"></label></div>
   <label>ช่องทาง<select name="payment_method" form="gi-pay"><?php foreach (Invoices::payment_methods() as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach ?></select></label>
   <label>หมายเหตุการรับเงิน<input name="payment_note" form="gi-pay" maxlength="255" placeholder="เช่น โอนจาก KBank เวลา 10:32"></label>
   <button class="btn" type="submit" form="gi-pay" onclick="return confirm('ยืนยันรับชำระและออกใบเสร็จ? บันทึกแล้วแก้ไขไม่ได้')">รับชำระแล้ว ออกใบเสร็จ</button>
  </section>
  <?php endif ?>
  <?php if ($others): ?>
  <section class="panel">
   <h2><?= $charges ? 'ใบแจ้งหนี้อื่นของ'.($inv['quotation_id'] ? 'ใบเสนอราคา' : 'การจอง').'นี้' : 'งวดอื่นของใบเสนอราคานี้' ?></h2>
   <ul class="gq-quotes"><?php foreach ($others as $o): ?><li><span><a class="text-link" href="<?= base_url().'admin/group-invoices/'.(int) $o['id'] ?>"><b><?= e($o['number']) ?></b></a><small><?= e($o['label']) ?> · <?= e($statuses[$o['status']]) ?></small></span><span>฿<?= money($o['total']) ?></span></li><?php endforeach ?></ul>
  </section>
  <?php endif ?>
  <?php if ($inv['id'] && $inv['status'] !== 'cancelled'): ?>
  <section class="panel stack-form">
   <h2>ยกเลิก<?= $inv['receipt_number'] ? 'ใบแจ้งหนี้และใบเสร็จ' : 'ใบแจ้งหนี้' ?></h2>
   <p class="fineprint"><?= $charges ? 'ยกเลิกแล้วออกใบใหม่ได้' : 'ยอดของใบที่ยกเลิกจะกลับไปเป็นยอดคงเหลือให้ออกใบใหม่ได้' ?> เลขที่เดิมไม่ถูกนำกลับมาใช้<?= $inv['receipt_number'] ? ' ใบกำกับภาษีที่ยกเลิกควรเรียกต้นฉบับคืนจากลูกค้าตามระเบียบบัญชี' : '' ?></p>
   <label>เหตุผล<input name="cancel_reason" form="gi-cancel" maxlength="200" required placeholder="เช่น ลูกค้าขอเปลี่ยนชื่อผู้ซื้อ"></label>
   <button class="text-button danger" type="submit" form="gi-cancel" onclick="return confirm('ยืนยันยกเลิก <?= e($inv['number']) ?>?')">ยกเลิกเอกสาร</button>
  </section>
  <?php endif ?>
 </aside>
</form>
<?php if ($inv['id']): ?>
<form method="post" action="<?= base_url().'admin/group-invoices/'.(int) $inv['id'].'/pay' ?>" id="gi-pay"><?= csrf_field() ?></form>
<form method="post" action="<?= base_url().'admin/group-invoices/'.(int) $inv['id'].'/cancel' ?>" id="gi-cancel"><?= csrf_field() ?></form>
<?php endif ?>
