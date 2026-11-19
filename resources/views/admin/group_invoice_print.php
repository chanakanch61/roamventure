<?php
use App\Repositories\GroupInvoiceRepository as Invoices;
$i = $invoice;
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
$money2 = fn ($v) => number_format((float) $v, 2);
$hasVat = $i['vat_mode'] !== 'none';
$rate = $num($i['vat_rate']);
$address = trim((string) ($settings['company_address'] ?? ''));
$taxId = trim((string) ($settings['company_tax_id'] ?? ''));
$phone = trim((string) ($settings['phone'] ?? ''));
$email = trim((string) ($settings['email'] ?? ''));
if ($receipt) {
    $kicker = $hasVat ? 'RECEIPT / TAX INVOICE' : 'RECEIPT';
    $heading = $hasVat ? 'ใบเสร็จรับเงิน / ใบกำกับภาษี' : 'ใบเสร็จรับเงิน';
    $docNo = $i['receipt_number'];
    $docDate = $i['paid_at'];
} else {
    $kicker = 'INVOICE';
    $heading = 'ใบแจ้งหนี้';
    $docNo = $i['number'];
    $docDate = $i['issue_date'];
}
$charges = ($i['kind'] ?? 'installment') === 'charges';
$exempt = (float) ($i['exempt_amount'] ?? 0);
$lineAmount = $i['vat_mode'] === 'excluded' ? $i['subtotal'] : $i['total'];
$bank = array_filter([trim((string) ($account['bank_name'] ?? '')), trim((string) ($account['bank_account'] ?? ''))]);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($docNo) ?> · <?= e($heading) ?> · <?= e($i['customer_company'] ?: $i['customer_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url().'public/material-icons.css?v='.filemtime(base_path('public/material-icons.css')) ?>">
<link rel="stylesheet" href="<?= base_url().'public/group-doc.css?v='.filemtime(base_path('public/group-doc.css')) ?>">
</head>
<body>
<div class="toolbar">
 <a href="<?= base_url().'admin/group-invoices/'.(int) $i['id'] ?>"><i class="material-icons" aria-hidden="true">arrow_back</i> กลับไปใบแจ้งหนี้</a>
 <span class="hint">ในหน้าต่างพิมพ์ เลือก “บันทึกเป็น PDF” และปิด “ส่วนหัวและส่วนท้าย”</span>
 <button type="button" onclick="window.print()"><i class="material-icons" aria-hidden="true">print</i> พิมพ์ / บันทึก PDF</button>
</div>
<?php if ($i['status'] === 'cancelled'): ?><div class="void" aria-hidden="true">ยกเลิก</div><?php endif ?>
<article class="sheet">
 <header class="head">
  <div class="seller">
   <div class="logo"><?= brand_logo($settings, 'invoice') ?></div>
   <b><?= e($company) ?></b><?php if ($receipt && $hasVat && $i['seller_branch']): ?> <span class="branch">(<?= e($i['seller_branch']) ?>)</span><?php endif ?>
   <?php if ($address): ?><p><?= e($address) ?></p><?php endif ?>
   <?php if ($taxId): ?><p>เลขประจำตัวผู้เสียภาษี <?= e($taxId) ?></p><?php endif ?>
   <?php if ($phone || $email): ?><p><?= e(implode(' · ', array_filter([$phone ? 'โทร '.$phone : '', $email]))) ?></p><?php endif ?>
   <p>ใบอนุญาตประกอบธุรกิจนำเที่ยว เลขที่ <?= e($license['number']) ?></p>
  </div>
  <div class="doc">
   <span class="kicker"><?= e($kicker) ?></span>
   <h1><?= e($heading) ?></h1>
   <table>
    <tr><td>เลขที่</td><td><?= e($docNo) ?></td></tr>
    <tr><td>วันที่</td><td><?= e(thai_date($docDate)) ?></td></tr>
    <?php if (! $receipt && $i['due_date']): ?><tr><td>ครบกำหนดชำระ</td><td><?= e(thai_date($i['due_date'])) ?></td></tr><?php endif ?>
    <?php if ($receipt): ?><tr><td>อ้างอิงใบแจ้งหนี้</td><td><?= e($i['number']) ?></td></tr><?php endif ?>
    <?php if ($i['quote_number'] !== ''): ?><tr><td>อ้างอิงใบเสนอราคา</td><td><?= e($i['quote_number']) ?></td></tr><?php endif ?>
    <?php if (($i['booking_reference'] ?? '') !== ''): ?><tr><td>อ้างอิงการจอง</td><td><?= e($i['booking_reference']) ?></td></tr><?php endif ?>
   </table>
  </div>
 </header>

 <div class="parties">
  <section class="box">
   <span class="label"><?= $receipt ? 'ผู้ซื้อ / ผู้รับบริการ' : 'ลูกค้า' ?></span>
   <p class="name"><?= e($i['customer_company'] ?: $i['customer_name']) ?><?php if ($i['customer_branch']): ?> <span class="branch">(<?= e($i['customer_branch']) ?>)</span><?php endif ?></p>
   <?php if ($i['customer_company']): ?><p>ผู้ติดต่อ: <?= e($i['customer_name']) ?></p><?php endif ?>
   <?php if ($i['customer_address']): ?><p style="white-space:pre-line"><?= e($i['customer_address']) ?></p><?php endif ?>
   <?php if ($i['customer_tax_id']): ?><p>เลขประจำตัวผู้เสียภาษี <?= e($i['customer_tax_id']) ?></p><?php endif ?>
   <?php if ($i['customer_phone'] || $i['customer_email']): ?><p><?= e(implode(' · ', array_filter([$i['customer_phone'], $i['customer_email']]))) ?></p><?php endif ?>
  </section>
  <section class="box">
   <span class="label"><?= $charges ? 'เรื่อง' : 'รายละเอียดการเดินทาง' ?></span>
   <p class="name"><?= e($charges ? $i['label'] : $i['title']) ?></p>
   <?php if ($charges && $i['title'] !== ''): ?><p><?= e($i['title']) ?></p><?php endif ?>
   <?php if ($i['travel_dates']): ?><p>วันเดินทาง: <?= e($i['travel_dates']) ?></p><?php endif ?>
   <?php if ($i['travelers']): ?><p>ผู้เดินทาง: <?= (int) $i['travelers'] ?> ท่าน</p><?php endif ?>
   <?php if (! $charges): ?><p>มูลค่าตามใบเสนอราคา ฿<?= $money2($i['quote_total']) ?></p><?php endif ?>
  </section>
 </div>

 <?php if ($charges): ?>
 <table class="items">
  <thead><tr><th class="c">#</th><th>รายการ</th><th class="n">จำนวน</th><th>หน่วย</th><th class="n">ราคา / หน่วย</th><th class="n">จำนวนเงิน (บาท)</th></tr></thead>
  <tbody>
   <?php foreach ($i['items'] as $n => $item): ?>
   <tr><td class="c"><?= $n + 1 ?></td><td><?= e($item['description']) ?><?php if (! empty($item['disbursement'])): ?><br><small style="color:#777">เงินทดรองจ่าย (ไม่มี VAT)</small><?php endif ?></td><td class="n"><?= e($num($item['qty'])) ?></td><td><?= e($item['unit']) ?></td><td class="n"><?= $money2($item['price']) ?></td><td class="n"><?= $money2(round((float) $item['qty'] * round((float) $item['price'], 2), 2)) ?></td></tr>
   <?php endforeach ?>
  </tbody>
 </table>
 <?php else: ?>
 <table class="items">
  <thead><tr><th class="c">#</th><th>รายการ</th><th class="n">จำนวนเงิน (บาท)</th></tr></thead>
  <tbody><tr><td class="c">1</td><td><b><?= e($i['label']) ?></b><br><?= e($i['title']) ?> ตามใบเสนอราคาเลขที่ <?= e($i['quote_number']) ?><?php if ($i['share_type'] === 'percent'): ?> (<?= e($num($i['share_value'])) ?>% ของมูลค่า ฿<?= $money2($i['quote_total']) ?>)<?php endif ?></td><td class="n"><?= $money2($lineAmount) ?></td></tr></tbody>
 </table>
 <?php endif ?>

 <div class="totals">
  <div class="totals-side">
   <div class="words"><span class="label">จำนวนเงินตัวอักษร</span>(<?= e(thai_baht_text($i['total'])) ?>)</div>
  </div>
  <table class="sum">
   <?php if ($i['vat_mode'] === 'excluded'): ?>
    <tr><td>มูลค่าก่อนภาษีมูลค่าเพิ่ม</td><td><?= $money2($i['subtotal']) ?></td></tr>
    <tr><td>ภาษีมูลค่าเพิ่ม <?= e($rate) ?>%</td><td><?= $money2($i['vat_amount']) ?></td></tr>
    <?php if ($exempt > 0): ?><tr><td>เงินทดรองจ่าย (ไม่มี VAT)</td><td><?= $money2($exempt) ?></td></tr><?php endif ?>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= $money2($i['total']) ?></td></tr>
   <?php elseif ($i['vat_mode'] === 'included'): ?>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= $money2($i['total']) ?></td></tr>
    <tr class="muted"><td>มูลค่าก่อนภาษีมูลค่าเพิ่ม</td><td><?= $money2($i['subtotal']) ?></td></tr>
    <tr class="muted"><td>ภาษีมูลค่าเพิ่ม <?= e($rate) ?>% (รวมอยู่ในราคาแล้ว)</td><td><?= $money2($i['vat_amount']) ?></td></tr>
    <?php if ($exempt > 0): ?><tr class="muted"><td>เงินทดรองจ่าย (ไม่มี VAT)</td><td><?= $money2($exempt) ?></td></tr><?php endif ?>
   <?php else: ?>
    <?php if ($exempt > 0 && (float) $i['subtotal'] > 0): ?><tr><td>ค่าบริการ</td><td><?= $money2($i['subtotal']) ?></td></tr><tr><td>เงินทดรองจ่าย</td><td><?= $money2($exempt) ?></td></tr><?php endif ?>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= $money2($i['total']) ?></td></tr>
   <?php endif ?>
   <?php if (! empty($i['wht_enabled'])): ?>
    <tr><td>หักภาษี ณ ที่จ่าย <?= e($num($i['wht_rate'])) ?>%<br><small style="color:#777">จากยอดก่อน VAT <?= $money2($i['subtotal']) ?><?= $exempt > 0 ? ' ไม่รวมเงินทดรองจ่าย' : '' ?></small></td><td>-<?= $money2($i['wht_amount']) ?></td></tr>
    <tr class="net"><td><?= $receipt ? 'ยอดชำระสุทธิ' : 'ยอดที่ต้องชำระ' ?></td><td>฿<?= $money2($i['net_payable']) ?></td></tr>
   <?php endif ?>
  </table>
 </div>

 <?php if ($receipt): ?>
  <div class="paid-box">ได้รับชำระเงินแล้ว <b>฿<?= $money2($i['paid_amount']) ?></b> เมื่อ <?= e(thai_date($i['paid_at'])) ?> · <?= e(Invoices::payment_methods()[$i['payment_method']] ?? $i['payment_method']) ?><?= $i['payment_note'] !== '' ? ' · '.e($i['payment_note']) : '' ?></div>
 <?php else: ?>
  <?php if ($bank || ! empty($account['promptpay_number']) || ! empty($account['payment_qr'])): ?>
  <section class="pay box">
   <div>
    <span class="label">ช่องทางชำระเงิน</span>
    <?php if ($bank): ?><p>โอนเข้าบัญชี <?= e(implode(' เลขที่ ', $bank)) ?><?= ! empty($account['bank_holder']) ? ' ชื่อบัญชี '.e($account['bank_holder']) : '' ?></p><?php endif ?>
    <?php if (! empty($account['promptpay_number'])): ?><p>PromptPay <?= e($account['promptpay_number']) ?></p><?php endif ?>
    <p>โปรดชำระ<?= $i['due_date'] ? 'ภายใน '.e(thai_date($i['due_date'])) : '' ?> และแจ้งหลักฐานการโอนพร้อมเลขที่ <?= e($i['number']) ?></p>
   </div>
   <?php if (! empty($account['payment_qr'])): ?><img src="<?= e(base_url().$account['payment_qr']) ?>" alt="QR สำหรับชำระเงิน"><?php endif ?>
  </section>
  <?php endif ?>
 <?php endif ?>
 <?php if (! empty($i['wht_enabled'])): ?><section class="block"><span class="label">ภาษีหัก ณ ที่จ่าย</span><p>กรุณาส่งหนังสือรับรองการหักภาษี ณ ที่จ่าย (50 ทวิ) ให้ <?= e($company) ?><?= $taxId ? ' เลขประจำตัวผู้เสียภาษี '.e($taxId) : '' ?></p></section><?php endif ?>
 <?php if (trim((string) $i['notes']) !== ''): ?><section class="block"><span class="label">หมายเหตุ</span><p><?= e($i['notes']) ?></p></section><?php endif ?>

 <div class="sign">
  <?php if ($receipt): ?>
  <div><div class="line"></div><b>ผู้รับเงิน</b><br><small><?= e($company) ?></small><br><small>วันที่ ........./........./.........</small></div>
  <div><div class="line"></div><b>ผู้มีอำนาจลงนาม</b><br><small><?= e($company) ?></small><br><small>วันที่ ........./........./.........</small></div>
  <?php else: ?>
  <div><div class="line"></div><b>ผู้ออกใบแจ้งหนี้</b><br><small><?= e($i['created_by'] ?: $company) ?></small><br><small>วันที่ ........./........./.........</small></div>
  <div><div class="line"></div><b>ผู้รับวางบิล</b><br><small><?= e($i['customer_company'] ?: $i['customer_name']) ?></small><br><small>วันที่ ........./........./.........</small></div>
  <?php endif ?>
 </div>
</article>
</body>
</html>
