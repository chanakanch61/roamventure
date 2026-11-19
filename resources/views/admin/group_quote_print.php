<?php
use App\Repositories\GroupQuoteRepository as Quotes;
$q = $quote;
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
$rate = $num($q['vat_rate']);
$includes = text_lines($q['includes']);
$excludes = text_lines($q['excludes']);
$address = trim((string) ($settings['company_address'] ?? ''));
$taxId = trim((string) ($settings['company_tax_id'] ?? ''));
$phone = trim((string) ($settings['phone'] ?? ''));
$email = trim((string) ($settings['email'] ?? ''));
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($q['number']) ?> · ใบเสนอราคา · <?= e($q['customer_company'] ?: $q['customer_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url().'public/material-icons.css?v='.filemtime(base_path('public/material-icons.css')) ?>">
<link rel="stylesheet" href="<?= base_url().'public/group-doc.css?v='.filemtime(base_path('public/group-doc.css')) ?>">
</head>
<body>
<div class="toolbar">
 <a href="<?= base_url().'admin/group-quotes/'.(int) $q['id'] ?>"><i class="material-icons" aria-hidden="true">arrow_back</i> กลับไปแก้ไข</a>
 <span class="hint">ในหน้าต่างพิมพ์ เลือก “บันทึกเป็น PDF” และปิด “ส่วนหัวและส่วนท้าย” เพื่อเอกสารที่สะอาด</span>
 <button type="button" onclick="window.print()"><i class="material-icons" aria-hidden="true">print</i> พิมพ์ / บันทึก PDF</button>
</div>
<article class="sheet">
 <header class="head">
  <div class="seller">
   <div class="logo"><?= brand_logo($settings, 'invoice') ?></div>
   <b><?= e($company) ?></b>
   <?php if ($address): ?><p><?= e($address) ?></p><?php endif ?>
   <?php if ($taxId): ?><p>เลขประจำตัวผู้เสียภาษี <?= e($taxId) ?></p><?php endif ?>
   <?php if ($phone || $email): ?><p><?= e(implode(' · ', array_filter([$phone ? 'โทร '.$phone : '', $email]))) ?></p><?php endif ?>
   <p>ใบอนุญาตประกอบธุรกิจนำเที่ยว เลขที่ <?= e($license['number']) ?></p>
  </div>
  <div class="doc">
   <span class="kicker">QUOTATION</span>
   <h1>ใบเสนอราคา</h1>
   <table>
    <tr><td>เลขที่</td><td><?= e($q['number']) ?></td></tr>
    <tr><td>วันที่</td><td><?= e(thai_date($q['issue_date'])) ?></td></tr>
    <?php if ($q['valid_until']): ?><tr><td>ยืนราคาถึง</td><td><?= e(thai_date($q['valid_until'])) ?></td></tr><?php endif ?>
    <?php if ($q['prepared_by']): ?><tr><td>ผู้เสนอราคา</td><td><?= e($q['prepared_by']) ?></td></tr><?php endif ?>
   </table>
  </div>
 </header>

 <div class="parties">
  <section class="box">
   <span class="label">ลูกค้า</span>
   <p class="name"><?= e($q['customer_company'] ?: $q['customer_name']) ?></p>
   <?php if ($q['customer_company']): ?><p>ผู้ติดต่อ: <?= e($q['customer_name']) ?></p><?php endif ?>
   <?php if ($q['customer_address']): ?><p style="white-space:pre-line"><?= e($q['customer_address']) ?></p><?php endif ?>
   <?php if ($q['customer_tax_id']): ?><p>เลขประจำตัวผู้เสียภาษี <?= e($q['customer_tax_id']) ?></p><?php endif ?>
   <?php if ($q['customer_phone'] || $q['customer_email']): ?><p><?= e(implode(' · ', array_filter([$q['customer_phone'], $q['customer_email']]))) ?></p><?php endif ?>
  </section>
  <section class="box">
   <span class="label">รายละเอียดการเดินทาง</span>
   <p class="name"><?= e($q['title']) ?></p>
   <?php if ($q['travel_dates']): ?><p>วันเดินทาง: <?= e($q['travel_dates']) ?></p><?php endif ?>
   <?php if ($q['travelers']): ?><p>ผู้เดินทาง: <?= (int) $q['travelers'] ?> ท่าน</p><?php endif ?>
  </section>
 </div>

 <table class="items">
  <thead><tr><th class="c">#</th><th>รายการ</th><th class="n">จำนวน</th><th>หน่วย</th><th class="n">ราคา / หน่วย</th><th class="n">จำนวนเงิน (บาท)</th></tr></thead>
  <tbody>
   <?php foreach ($q['items'] as $i => $item): ?>
   <?php $unitPrice = Quotes::unit_price((float) $item['price'], ! empty($q['wht_enabled']), ! empty($q['wht_gross_up']), (float) $q['wht_rate']); ?>
   <tr><td class="c"><?= $i + 1 ?></td><td><?= e($item['description']) ?></td><td class="n"><?= e($num($item['qty'])) ?></td><td><?= e($item['unit']) ?></td><td class="n"><?= number_format($unitPrice, 2) ?></td><td class="n"><?= number_format(round((float) $item['qty'] * $unitPrice, 2), 2) ?></td></tr>
   <?php endforeach ?>
  </tbody>
 </table>

 <div class="totals">
  <div class="totals-side">
   <div class="words"><span class="label">จำนวนเงินตัวอักษร</span>(<?= e(thai_baht_text($q['total'])) ?>)</div>
   <?php if ($includes): ?><p class="inline-terms"><span class="label">ราคานี้รวม</span><?= e(implode(' · ', $includes)) ?></p><?php endif ?>
   <?php if ($excludes): ?><p class="inline-terms"><span class="label">ราคานี้ไม่รวม</span><?= e(implode(' · ', $excludes)) ?></p><?php endif ?>
  </div>
  <table class="sum">
   <tr><td>รวมเป็นเงิน</td><td><?= number_format((float) $q['items_total'], 2) ?></td></tr>
   <?php if ((float) $q['discount'] > 0): ?><tr><td>หักส่วนลด</td><td>-<?= number_format((float) $q['discount'], 2) ?></td></tr><?php endif ?>
   <?php if ($q['vat_mode'] === 'excluded'): ?>
    <tr><td>ยอดก่อนภาษีมูลค่าเพิ่ม</td><td><?= number_format((float) $q['subtotal'], 2) ?></td></tr>
    <tr><td>ภาษีมูลค่าเพิ่ม <?= e($rate) ?>%</td><td><?= number_format((float) $q['vat_amount'], 2) ?></td></tr>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= number_format((float) $q['total'], 2) ?></td></tr>
   <?php elseif ($q['vat_mode'] === 'included'): ?>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= number_format((float) $q['total'], 2) ?></td></tr>
    <tr class="muted"><td>มูลค่าก่อนภาษีมูลค่าเพิ่ม</td><td><?= number_format((float) $q['subtotal'], 2) ?></td></tr>
    <tr class="muted"><td>ภาษีมูลค่าเพิ่ม <?= e($rate) ?>% (รวมอยู่ในราคาแล้ว)</td><td><?= number_format((float) $q['vat_amount'], 2) ?></td></tr>
   <?php else: ?>
    <tr class="grand"><td>ยอดรวมทั้งสิ้น</td><td>฿<?= number_format((float) $q['total'], 2) ?></td></tr>
   <?php endif ?>
   <?php if (! empty($q['wht_enabled'])): ?>
    <tr><td>หักภาษี ณ ที่จ่าย <?= e($num($q['wht_rate'])) ?>%<br><small style="color:#777">จากยอดก่อน VAT <?= number_format((float) $q['subtotal'], 2) ?></small></td><td>-<?= number_format((float) $q['wht_amount'], 2) ?></td></tr>
    <tr class="net"><td>ยอดชำระสุทธิ</td><td>฿<?= number_format((float) $q['net_payable'], 2) ?></td></tr>
   <?php endif ?>
  </table>
 </div>

 <?php if (! empty($q['wht_enabled'])): ?><section class="block"><span class="label">ภาษีหัก ณ ที่จ่าย</span><p>กรณีลูกค้าเป็นนิติบุคคล ชำระยอดสุทธิหลังหักภาษี ณ ที่จ่าย <?= e($num($q['wht_rate'])) ?>% และกรุณาส่งหนังสือรับรองการหักภาษี ณ ที่จ่าย (50 ทวิ) ให้ <?= e($company) ?><?= $taxId ? ' เลขประจำตัวผู้เสียภาษี '.e($taxId) : '' ?></p></section><?php endif ?>
 <?php if (trim((string) $q['payment_terms']) !== ''): ?><section class="block"><span class="label">เงื่อนไขการชำระเงิน</span><p><?= e($q['payment_terms']) ?></p></section><?php endif ?>
 <?php if (trim((string) $q['notes']) !== ''): ?><section class="block"><span class="label">หมายเหตุ</span><p><?= e($q['notes']) ?></p></section><?php endif ?>

 <div class="sign">
  <div><div class="line"></div><b>ผู้เสนอราคา</b><br><small><?= e($q['prepared_by'] ?: $company) ?></small><br><small>วันที่ ........./........./.........</small></div>
  <div><div class="line"></div><b>ผู้อนุมัติ / ยืนยันการสั่งซื้อ</b><br><small><?= e($q['customer_company'] ?: $q['customer_name']) ?></small><br><small>วันที่ ........./........./.........</small></div>
 </div>
</article>
</body>
</html>
