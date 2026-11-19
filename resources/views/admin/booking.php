<div class="section-head"><div><span class="eyebrow">BOOKING REQUEST</span><h1><?= e($booking['reference']) ?></h1><p>รับคำขอเมื่อ <?= e($booking['created_at']) ?></p></div><a class="outline-link" href="<?= base_url() . 'admin/bookings' ?>">กลับรายการ</a></div><div class="admin-two-col"><section class="panel"><span class="pill <?= e($booking['status']) ?>"><?= status_label($booking['status']) ?></span><h2><?= e($booking['tour_title']) ?></h2><p><?= thai_date($booking['start_date']) ?> – <?= thai_date($booking['end_date']) ?></p><?php load_view('partials/booking_amounts',['booking'=>$booking]); ?><p class="fineprint">ราคาและข้อมูลโปรแกรม ณ เวลาส่งคำขอ ไม่เปลี่ยนตามการแก้ไขโปรแกรมภายหลัง</p><?php if ($departure && empty($booking['package_snapshot'])): ?><div class="seat-summary"><div><span>ไซส์กรุ๊ปของรอบ</span><strong><?= (int)$departure['capacity'] ?> คน</strong></div><div><span>ที่นั่งว่างตอนนี้</span><strong><?= (int)$departure['seats_available'] ?> ที่นั่ง</strong></div></div><?php endif ?><p class="fineprint"><?= !empty($booking['package_snapshot']) ? 'ตรวจสอบรถ โรงแรม และบริการให้พร้อมก่อนยืนยันกรุ๊ปส่วนตัวนี้' : 'ยืนยันแล้วจึงตัดที่นั่ง · ยกเลิกหรือเปลี่ยนออกจากยืนยันแล้วจะคืนที่นั่ง' ?></p><h3>ข้อมูลผู้ติดต่อ</h3><dl class="info-list"><?php foreach (['customer_name'=>'ชื่อ','phone'=>'โทรศัพท์','email'=>'อีเมล','line_id'=>'LINE ID','note'=>'รายละเอียดเพิ่มเติม'] as $key=>$label): ?><dt><?= $label ?></dt><dd class="preline"><?= e($booking[$key] ?: '—') ?></dd><?php endforeach ?></dl></section><section class="panel"><h2>ติดตามคำขอ</h2><form method="post" class="stack-form"><?= csrf_field() ?><label>สถานะ<select name="status"><?php foreach (['new','contacted','confirmed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $booking['status']===$s ? 'selected' : '' ?>><?= status_label($s) ?></option><?php endforeach ?></select></label><label>เวลาชำระหลังยืนยัน (ชั่วโมง)<input type="number" name="payment_hours" min="1" max="720" value="24" required><small>ใช้เมื่อออก Invoice ครั้งแรก</small></label><label>บันทึกภายใน<textarea name="admin_note" rows="7" maxlength="5000" placeholder="ผลการติดต่อและรายละเอียดที่ยืนยันกับลูกค้า"><?= e($booking['admin_note']) ?></textarea></label><p class="fineprint">การเปลี่ยนสถานะไม่ส่งข้อความ LINE หรืออีเมลอัตโนมัติ</p><button class="btn" type="submit">บันทึกการติดตาม</button></form></section></div>

<?php if (!empty($booking['ticket_help']) || !empty($booking['flight_snapshot'])): ?><section class="panel"><?php load_view('partials/booking_flights',['booking'=>$booking,'booking_flights_admin'=>true]); ?></section><?php endif ?>
<?php load_view('admin/booking_billing',['booking'=>$booking]); ?>
<?php load_view('partials/booking_passports',['booking'=>$booking,'passport_admin'=>true]); ?>
<?php
$charges = $booking_charges ?? [];
$chargeTotal = array_sum(array_map(fn ($c) => (float) $c['amount'], $charges));
$templates = $item_templates ?? [];
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
?>
<section class="panel" id="extra-charges">
 <div class="section-head"><div><h2>ค่าใช้จ่ายเพิ่มเติม</h2><p class="fineprint">ค่าใช้จ่ายนอกเหนือจากราคาทัวร์ เช่น ค่าวีซ่า ค่าจิปาถะ เลือกได้ว่าจะรวมในยอดการจอง หรือออกใบแจ้งหนี้แยก</p></div></div>
 <h3>รวมในยอดการจอง</h3>
 <p class="fineprint">ยอดจะบวกเข้ากับค่าทัวร์ ลูกค้าเห็นรายการในหน้ารายละเอียดการจองและใบแจ้งหนี้ <?= $booking['access_token'] ? '' : '(เมื่อยืนยันการจอง) ' ?>แล้วชำระผ่านลิงก์เดิมพร้อมกัน ไม่มี VAT / หัก ณ ที่จ่าย เหมือนยอดการจอง หากลูกค้าต้องการใบกำกับภาษีให้ออกใบแจ้งหนี้แยกด้านล่าง</p>
 <?php if ($charges): ?>
 <div class="table-scroll"><table><thead><tr><th>รายการ</th><th>จำนวน</th><th>ราคา / หน่วย</th><th>จำนวนเงิน</th><th></th></tr></thead><tbody>
  <?php foreach ($charges as $c): ?><tr><td><?= e($c['description']) ?></td><td><?= e($num($c['qty']).' '.$c['unit']) ?></td><td><?= money($c['price']) ?></td><td><b><?= money($c['amount']) ?></b></td><td><form method="post" action="<?= base_url().'admin/bookings/'.(int) $booking['id'].'/charges/'.(int) $c['id'].'/delete' ?>" onsubmit="return confirm('ลบ “<?= e($c['description']) ?>” ออกจากยอดการจอง?')"><?= csrf_field() ?><button class="text-button danger" type="submit">ลบ</button></form></td></tr><?php endforeach ?>
 </tbody><tfoot><tr><th colspan="3">รวมค่าใช้จ่ายเพิ่มเติม</th><th>฿<?= money($chargeTotal) ?></th><th></th></tr></tfoot></table></div>
 <?php endif ?>
 <?php if ($booking['status'] !== 'cancelled'): ?>
 <form method="post" action="<?= base_url().'admin/bookings/'.(int) $booking['id'].'/charges' ?>" class="stack-form gi-line" data-gi-line><?= csrf_field() ?>
  <label>รายการ
   <?php if ($templates): ?><select data-gi-pick aria-label="เลือกรายการ"><option value="">— เลือกรายการ —</option><?php foreach ($templates as $t): ?><option value="<?= e($t['description']) ?>" data-unit="<?= e($t['unit']) ?>" data-price="<?= e($num($t['price'])) ?>"><?= e($t['description']) ?></option><?php endforeach ?><option value="__other">อื่น ๆ (พิมพ์เอง)</option></select><?php endif ?>
   <input name="description" maxlength="300" placeholder="เช่น ค่าธรรมเนียมวีซ่าจีน" data-gi-desc<?= $templates ? ' hidden' : ' required' ?>>
  </label>
  <div class="form-grid gi-line-nums"><label>จำนวน<input name="qty" inputmode="decimal" value="<?= (int) ($booking['traveler_count'] ?: 1) ?>" required></label><label>หน่วย<input name="unit" maxlength="30" value="ท่าน" data-gi-unit></label><label>ราคา / หน่วย (บาท)<input name="price" inputmode="decimal" required data-gq-price></label><label>ให้ชำระภายใน (ชั่วโมง)<input type="number" name="hours" min="1" max="720" value="72" required></label></div>
  <p class="fineprint">ถ้าการจองชำระครบแล้วหรือใกล้ครบกำหนด ระบบเลื่อนกำหนดชำระให้ตามจำนวนชั่วโมงนี้ (ไม่เกินวันเดินทาง)<?= $booking['start_date'] < date('Y-m-d') ? ' · <b>ผ่านวันเดินทางแล้ว ลูกค้าชำระผ่านลิงก์ไม่ได้ ควรออกใบแจ้งหนี้แยก</b>' : '' ?></p>
  <button class="btn" type="submit">เพิ่มเข้ายอดการจอง</button>
 </form>
 <?php endif ?>
<?php if (isset($extra_invoices)): $invStatuses = App\Repositories\GroupInvoiceRepository::statuses(); ?>
 <h3>ออกใบแจ้งหนี้แยก</h3>
 <div class="section-head"><p class="fineprint">ใบแจ้งหนี้คนละใบกับการจอง แยก VAT / หัก ณ ที่จ่าย / เงินทดรองจ่ายได้ ออกใบเสร็จหรือใบกำกับภาษีได้ ลูกค้าชำระและทีมงานบันทึกรับชำระในหน้าใบแจ้งหนี้</p><a class="outline-link" href="<?= base_url().'admin/group-invoices/new?kind=charges&booking='.(int) $booking['id'] ?>">＋ สร้างใบแจ้งหนี้แยก</a></div>
 <?php if ($extra_invoices): ?><ul class="gq-quotes"><?php foreach ($extra_invoices as $x): ?><li><span><a class="text-link" href="<?= base_url().'admin/group-invoices/'.(int) $x['id'] ?>"><b><?= e($x['number']) ?></b></a><small><?= e($x['label']) ?> · <?= e($invStatuses[$x['status']] ?? $x['status']) ?><?= $x['receipt_number'] ? ' · ใบเสร็จ '.e($x['receipt_number']) : '' ?></small></span><span>฿<?= money($x['total']) ?></span></li><?php endforeach ?></ul><?php endif ?>
<?php endif ?>
</section>
