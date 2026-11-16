<?php
/*
 * Customer email: booking request received. Email clients ignore <style> blocks unevenly,
 * so layout is tables with inline styles; the <style> block only adds mobile and font tweaks.
 */
$font = "'Plus Jakarta Sans','Noto Sans Thai','Leelawadee UI',Tahoma,Arial,sans-serif";
$row = function ($label, $value) use ($font) {
    return '<tr><td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-family:'.$font.';font-size:13px;color:#8a8a8a;width:38%;vertical-align:top">'.e($label).'</td>'
        .'<td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-family:'.$font.';font-size:14px;color:#1a1a1a;font-weight:600;text-align:right;vertical-align:top">'.$value.'</td></tr>';
};
?>
<!doctype html>
<html lang="th" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>ได้รับคำขอจองแล้ว · <?= e($b['reference']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
body{margin:0;padding:0;background:#efede8}
a{color:inherit}
@media (max-width:620px){
 .wrap{width:100%!important}
 .px{padding-left:22px!important;padding-right:22px!important}
 .h1{font-size:26px!important}
 .btn a{display:block!important}
 .step{display:block!important;width:100%!important;padding:0 0 14px!important}
}
</style>
</head>
<body style="margin:0;padding:0;background:#efede8;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#efede8">เราได้รับคำขอจอง <?= e($b['reference']) ?> แล้ว ทีมงานจะตรวจสอบที่ว่างและติดต่อกลับโดยเร็ว&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#efede8">
<tr><td align="center" style="padding:28px 12px">

<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">

  <!-- Header + hero -->
  <tr><td style="background:#111111;border-radius:22px 22px 0 0;padding:30px 40px 0" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <td style="font-family:<?= $font ?>">
        <a href="<?= e($site_url) ?>" style="text-decoration:none;color:#ffffff">
        <?php if ($logo_png): ?>
          <?php
          // Attached inline (cid:) when sending, so it shows without loading remote images; a data URI for previews.
          $logoSrc = isset($message) && $message instanceof Illuminate\Mail\Message
              ? $message->embedData($logo_png, 'roamventure-logo.png', 'image/png')
              : 'data:image/png;base64,'.base64_encode($logo_png);
          $logoSize = getimagesizefromstring($logo_png);
          $logoW = (int) round($logoSize[0] / 2);
          ?>
          <img src="<?= e($logoSrc) ?>" alt="<?= e($brand) ?>" width="<?= $logoW ?>" height="72" style="display:block;width:<?= $logoW ?>px;height:72px;border:0">
        <?php else: ?>
          <span style="font-size:22px;font-weight:800;letter-spacing:-0.8px;color:#ffffff">ROAM</span><span style="font-size:22px;font-weight:400;letter-spacing:-0.8px;color:#ffffff">VENTURE</span><span style="font-size:22px;color:#8a8a8a">&nbsp;&#8599;</span>
        <?php endif ?>
        </a>
      </td>
      <td align="right" style="font-family:<?= $font ?>;font-size:11px;letter-spacing:1.6px;color:#8a8a8a;text-transform:uppercase">Booking request</td>
    </tr></table>
  </td></tr>
  <tr><td style="background:#111111;padding:44px 40px 40px" class="px">
    <p style="margin:0 0 14px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.6px;color:#9a9a9a">YOUR NEXT CHAPTER STARTS HERE</p>
    <h1 class="h1" style="margin:0 0 14px;font-family:<?= $font ?>;font-size:32px;line-height:1.35;font-weight:700;color:#ffffff;letter-spacing:-0.5px">ได้รับคำขอจองแล้ว</h1>
    <p style="margin:0 0 28px;font-family:<?= $font ?>;font-size:15px;line-height:1.75;color:#bdbdbd">สวัสดีคุณ<?= e($b['customer_name']) ?> ขอบคุณที่เลือกเดินทางกับเรา ทีมงานได้รับคำขอของคุณแล้ว และจะตรวจสอบที่ว่างแล้วติดต่อกลับโดยเร็ว</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #3a3a3a;border-radius:14px"><tr>
      <td style="padding:16px 22px;font-family:<?= $font ?>">
        <span style="display:inline-block;margin-bottom:10px;padding:5px 12px;border-radius:999px;background:#2a2a2a;color:#e2e2e2;font-size:12px;font-weight:600;white-space:nowrap">&#9679;&nbsp; รอทีมงานยืนยัน</span>
        <span style="display:block;font-size:11px;letter-spacing:1.4px;color:#8a8a8a">หมายเลขคำขอ</span>
        <span style="display:block;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:1px;white-space:nowrap"><?= e($b['reference']) ?></span>
      </td>
    </tr></table>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:36px 40px 8px" class="px">
    <p style="margin:0 0 6px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.2px;color:#9a9a9a">YOUR TRIP</p>
    <h2 style="margin:0 0 18px;font-family:<?= $font ?>;font-size:22px;line-height:1.4;font-weight:700;color:#1a1a1a"><?= e($b['tour_title']) ?></h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <?= $row('วันเดินทาง', e(thai_date($b['start_date'])).' – '.e(thai_date($b['end_date']))) ?>
      <?= $row('ผู้เดินทาง', (int) $b['traveler_count'].' ท่าน') ?>
      <?php if ($package): ?><?= $row('ระดับบริการ', 'แพ็คเกจส่วนตัว · '.e($package['level'] ?? '')) ?><?php endif ?>
      <?= $row('ห้องพัก', e($rooms)) ?>
      <?php if (! empty($b['ticket_help'])): ?><?= $row('ตั๋วเครื่องบิน', 'ให้ทีมงานช่วยจอง') ?><?php endif ?>
      <?= $row('ผู้ติดต่อ', e($b['customer_name']).'<br><span style="font-weight:400;color:#6b6b6b">'.e($b['phone']).'</span>') ?>
    </table>

    <!-- Price -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:26px;background:#f6f5f2;border-radius:16px">
      <tr><td style="padding:22px 24px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="padding:4px 0;font-family:<?= $font ?>;font-size:13px;color:#6b6b6b">ค่าทัวร์ <?= (int) $b['traveler_count'] ?> × ฿<?= money($b['price_per_person']) ?></td>
            <td align="right" style="padding:4px 0;font-family:<?= $font ?>;font-size:14px;color:#1a1a1a">฿<?= money($tour_total) ?></td>
          </tr>
          <?php if ((float) $b['supplement_total'] > 0): ?>
          <tr>
            <td style="padding:4px 0;font-family:<?= $font ?>;font-size:13px;color:#6b6b6b">ค่าพักเดี่ยวเพิ่ม <?= $single_rooms ?> ห้อง × ฿<?= money($b['single_supplement']) ?></td>
            <td align="right" style="padding:4px 0;font-family:<?= $font ?>;font-size:14px;color:#1a1a1a">฿<?= money($b['supplement_total']) ?></td>
          </tr>
          <?php endif ?>
          <tr><td colspan="2" style="padding:12px 0 0"><div style="border-top:1px dashed #cfcfcf;height:1px;line-height:1px;font-size:1px">&nbsp;</div></td></tr>
          <tr>
            <td style="padding:12px 0 0;font-family:<?= $font ?>;font-size:14px;font-weight:700;color:#1a1a1a">ยอดรวมตามคำขอ</td>
            <td align="right" style="padding:12px 0 0;font-family:<?= $font ?>;font-size:26px;font-weight:800;color:#111111;letter-spacing:-0.5px">฿<?= money($b['total_price']) ?></td>
          </tr>
        </table>
        <p style="margin:10px 0 0;font-family:<?= $font ?>;font-size:12px;line-height:1.6;color:#8a8a8a">ราคานี้ทีมงานจะยืนยันอีกครั้งเมื่อตรวจสอบที่ว่างแล้ว</p>
      </td></tr>
    </table>

    <?php if (trim((string) ($b['note'] ?? '')) !== ''): ?>
    <p style="margin:24px 0 6px;font-family:<?= $font ?>;font-size:13px;color:#8a8a8a">หมายเหตุของคุณ</p>
    <p style="margin:0;font-family:<?= $font ?>;font-size:14px;line-height:1.7;color:#1a1a1a"><?= nl2br(e($b['note'])) ?></p>
    <?php endif ?>

    <!-- Not confirmed yet -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:26px">
      <tr><td style="border-left:3px solid #111111;background:#fafafa;padding:14px 18px;font-family:<?= $font ?>;font-size:13px;line-height:1.7;color:#3a3a3a">
        <b style="color:#111111">คำขอนี้ยังไม่ใช่การยืนยันการจอง</b> และยังไม่มีการเรียกเก็บเงิน ทีมงานจะแจ้งยืนยันและขั้นตอนชำระเงินให้ทราบก่อนทุกครั้ง
      </td></tr>
    </table>
  </td></tr>

  <!-- Next steps -->
  <tr><td style="background:#ffffff;padding:30px 40px 6px" class="px">
    <p style="margin:0 0 18px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.2px;color:#9a9a9a">WHAT HAPPENS NEXT</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <?php foreach ([['ตรวจสอบที่ว่าง', 'ทีมงานเช็กรอบเดินทาง ที่พัก และราคาให้'], ['ติดต่อยืนยัน', 'โทรหรือทักไปตามข้อมูลที่คุณให้ไว้'], ['ชำระเงิน', 'ผ่านหน้าการจองของคุณ พร้อมใบแจ้งหนี้']] as $i => $step): ?>
      <td class="step" width="33%" style="vertical-align:top;padding-right:<?= $i < 2 ? '14px' : '0' ?>;font-family:<?= $font ?>">
        <span style="display:inline-block;width:28px;height:28px;line-height:28px;border-radius:50%;background:#111111;color:#ffffff;text-align:center;font-size:13px;font-weight:700"><?= $i + 1 ?></span>
        <p style="margin:10px 0 4px;font-size:14px;font-weight:700;color:#1a1a1a"><?= e($step[0]) ?></p>
        <p style="margin:0;font-size:12px;line-height:1.6;color:#7a7a7a"><?= e($step[1]) ?></p>
      </td>
      <?php endforeach ?>
    </tr></table>
  </td></tr>

  <!-- CTA -->
  <tr><td style="background:#ffffff;padding:30px 40px 38px;border-radius:0 0 22px 22px" class="px">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td class="btn" align="center">
      <a href="<?= e($detail_url) ?>" style="display:inline-block;padding:15px 34px;border-radius:999px;background:#111111;color:#ffffff;font-family:<?= $font ?>;font-size:15px;font-weight:700;text-decoration:none">ดูรายละเอียดการจอง&nbsp;&#8599;</a>
      <?php if ($line_url): ?>
      <div style="height:12px;line-height:12px;font-size:12px">&nbsp;</div>
      <a href="<?= e($line_url) ?>" style="display:inline-block;padding:13px 30px;border-radius:999px;border:1px solid #d6d6d6;color:#1a1a1a;font-family:<?= $font ?>;font-size:14px;font-weight:600;text-decoration:none">คุยกับทีมงานทาง LINE</a>
      <?php endif ?>
    </td></tr></table>
    <p style="margin:20px 0 0;font-family:<?= $font ?>;font-size:12px;line-height:1.6;color:#9a9a9a;text-align:center">ลิงก์นี้ใช้เปิดหน้าการจองของคุณ โปรดอย่าส่งต่อให้ผู้อื่น</p>
  </td></tr>

  <!-- Footer -->
  <tr><td style="padding:28px 40px 8px;font-family:<?= $font ?>;font-size:12px;line-height:1.8;color:#7a7a7a;text-align:center" class="px">
    <?php $contacts = array_filter([$phone ? 'โทร '.e($phone) : '', $line_id ? 'LINE '.e($line_id) : '', $contact_email ? '<a href="mailto:'.e($contact_email).'" style="color:#7a7a7a">'.e($contact_email).'</a>' : '']); ?>
    <?php if ($contacts): ?><p style="margin:0 0 10px;color:#3a3a3a;font-weight:600"><?= implode(' &nbsp;·&nbsp; ', $contacts) ?></p><?php endif ?>
    <p style="margin:0">ใบอนุญาตนำเที่ยว เลขที่ <?= e($license['number']) ?> · <?= e($license['holder']) ?></p>
    <p style="margin:10px 0 0;color:#a0a0a0">คุณได้รับอีเมลนี้เพราะส่งคำขอจองที่ <a href="<?= e($site_url) ?>" style="color:#a0a0a0"><?= e(parse_url($site_url, PHP_URL_HOST)) ?></a><br>© <?= date('Y') ?> <?= e($brand) ?></p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
