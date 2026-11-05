<?php
$receipt=$payment ? json_decode($payment['receipt_snapshot'],true) : null;
$snap=$receipt ? $receipt['invoice'] : json_decode($invoice['snapshot'],true);
$b=$snap['booking']; $seller=$snap['seller'];
$singleRooms=$b['single_rooms'] ?? ($b['room_type']==='single' ? $b['traveler_count'] : 0);
$company=trim($settings['company_name'] ?? '') ?: (($seller['company_name'] ?? '') ?: 'RoamVenture');
?>
<section class="container billing-page invoice-page">
 <div class="actions invoice-toolbar no-print">
  <a class="outline-link" href="<?= (base_url() . ('booking/detail/'.$booking['access_token'])) ?>"><i class="material-icons" aria-hidden="true">arrow_back</i> รายละเอียดการจอง</a>
  <button type="button" class="btn" onclick="window.print()"><i class="material-icons" aria-hidden="true">print</i> พิมพ์ / บันทึก PDF</button>
 </div>
 <article class="panel invoice-document">
  <header class="invoice-header">
   <div class="invoice-seller">
    <div class="invoice-brand"><?= brand_logo($settings,$payment ? 'receipt' : 'invoice') ?></div>
    <b><?= e($company) ?></b>
    <?php if (!empty($seller['company_address'])): ?><p class="preline"><?= e($seller['company_address']) ?></p><?php endif ?>
    <?php if (!empty($seller['company_tax_id'])): ?><p>เลขประจำตัวผู้เสียภาษี <?= e($seller['company_tax_id']) ?></p><?php endif ?>
    <?php if (!empty($seller['phone'])): ?><p><?= e($seller['phone']) ?></p><?php endif ?>
    <?php if (!empty($seller['email'])): ?><p><?= e($seller['email']) ?></p><?php endif ?>
   </div>
   <div class="invoice-heading">
    <span class="invoice-kicker"><?= $payment ? 'RECEIPT' : 'INVOICE' ?></span>
    <h1><?= $payment ? 'ใบเสร็จรับเงิน' : 'ใบแจ้งหนี้' ?></h1>
    <span class="invoice-reference"><?= e($payment ? $payment['receipt_reference'] : $invoice['reference']) ?></span>
   </div>
  </header>
  <?php if ($booking['status']==='cancelled'): ?><p class="notice invoice-cancelled">การจองถูกยกเลิก · เอกสารนี้เก็บเป็นประวัติรายการเดิม</p><?php endif ?>
  <div class="invoice-parties">
   <section class="invoice-customer">
    <h2 class="invoice-label">ผู้จอง / ผู้ชำระเงิน</h2>
    <p class="invoice-customer-name"><?= e($b['customer_name']) ?></p>
    <p><?= e($b['phone']) ?></p>
    <?php if (!empty($b['email'])): ?><p><?= e($b['email']) ?></p><?php endif ?>
   </section>
   <dl class="invoice-meta">
    <div><dt>วันที่ออกเอกสาร</dt><dd><?= e($payment ? $payment['reviewed_at'] : $invoice['issued_at']) ?></dd></div>
    <div><dt>เลขที่การจอง</dt><dd><?= e($b['reference']) ?></dd></div>
    <?php if ($payment): ?><div><dt>อ้างอิงใบแจ้งหนี้</dt><dd><?= e($receipt['invoice_reference']) ?></dd></div><?php endif ?>
   </dl>
  </div>
  <section class="invoice-trip">
   <span class="invoice-label">รายละเอียดการเดินทาง</span>
   <h2><?= e($b['tour_title']) ?></h2>
   <div class="invoice-trip-meta"><span><?= thai_date($b['start_date']) ?> – <?= thai_date($b['end_date']) ?></span><span>ผู้เดินทาง <?= (int)$b['traveler_count'] ?> คน</span></div>
   <p><?= e(booking_rooms_label($b)) ?></p>
  </section>
  <table class="invoice-items">
   <caption class="invoice-label">รายละเอียดค่าใช้จ่าย <span>สกุลเงิน: บาท (THB)</span></caption>
   <thead><tr><th scope="col">รายการ</th><th scope="col">จำนวน</th><th scope="col">ราคา / หน่วย</th><th scope="col">จำนวนเงิน</th></tr></thead>
   <tbody>
    <tr><th scope="row">ค่าทัวร์<span><?= !empty($b['package_snapshot']) ? 'แพ็คเกจส่วนตัว · '.e((json_decode($b['package_snapshot'],true)['level'] ?? '')) : 'ตามโปรแกรมการเดินทาง' ?><?= isset($b['flight_included']) ? ' · '.($b['flight_included'] ? 'รวมตั๋วเครื่องบิน' : 'ไม่รวมตั๋วเครื่องบิน') : '' ?></span></th><td><?= (int)$b['traveler_count'] ?> คน</td><td><?= money($b['price_per_person']) ?></td><td><?= money($b['price_per_person']*$b['traveler_count']) ?></td></tr>
    <tr><th scope="row">ค่าพักเดี่ยวเพิ่ม</th><td><?= (int)$singleRooms ?> ห้อง</td><td><?= $singleRooms>0 ? money($b['single_supplement']) : '—' ?></td><td><?= money($b['supplement_total']) ?></td></tr>
   </tbody>
   <tfoot class="invoice-summary">
    <tr class="invoice-grand-total"><th scope="row" colspan="3">ยอดรวมทั้งสิ้น<small>ยอดรวมของการจอง</small></th><td>฿<?= money($b['total_price']) ?></td></tr>
    <?php if ($payment): ?><tr class="receipt-total"><th scope="row" colspan="3">รับชำระครั้งนี้</th><td>฿<?= money($receipt['amount']) ?></td></tr><?php endif ?>
   </tfoot>
  </table>
  <?php if ($payment): ?>
   <section class="invoice-payment-note"><h2 class="invoice-label">รายละเอียดการรับชำระ</h2><p>ช่องทาง: <?= $receipt['channel']==='bank' ? 'บัญชีธนาคาร' : 'PromptPay / QR' ?> · โอนเมื่อ <?= e($receipt['transferred_at']) ?></p></section>
   <footer class="invoice-footer"><p>ออกเอกสารอัตโนมัติหลังทีมงานตรวจสอบและยืนยันรับเงิน</p><span><?= e($company) ?> · RECEIPT</span></footer>
  <?php else: ?>
   <section class="invoice-payment-note">
    <h2 class="invoice-label">กำหนดและเงื่อนไขการชำระเงิน</h2>
    <p class="invoice-due">กำหนดชำระครั้งแรก <strong><?= e($invoice['payment_due_at']) ?></strong></p>
    <?php if ($invoice['deposit_enabled'] && $invoice['deposit_amount']>0): ?><p>มัดจำ <b>฿<?= money($invoice['deposit_amount']) ?></b> เมื่อเหลืออย่างน้อย <?= (int)$invoice['deposit_cutoff_days'] ?> วันก่อนเดินทาง ส่วนที่เหลือชำระภายใน <b><?= e($invoice['balance_due_at']) ?></b> หากไม่เข้าเงื่อนไขให้ชำระเต็มจำนวน</p><?php endif ?>
   </section>
   <footer class="invoice-footer"><p>ใบแจ้งหนี้ไม่ใช่หลักฐานการรับชำระเงิน<br>ตรวจยอดที่ชำระแล้วได้ในหน้ารายละเอียดการจอง</p><span><?= e($company) ?> · INVOICE</span></footer>
  <?php endif ?>
 </article>
</section>
