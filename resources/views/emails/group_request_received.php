<?php
/*
 * Customer email: charter-group request received (/grouptour). Same layout rules as booking_received:
 * tables with inline styles, the <style> block only adds mobile and font tweaks.
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
<title>ได้รับคำขอจัดกรุ๊ปแล้ว · <?= e($r['reference']) ?></title>
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
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#efede8">เราได้รับคำขอจัดกรุ๊ป <?= e($r['reference']) ?> แล้ว ทีมงานจะติดต่อกลับเพื่อคุยรายละเอียดและส่งใบเสนอราคา&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
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
      <td align="right" style="font-family:<?= $font ?>;font-size:11px;letter-spacing:1.6px;color:#8a8a8a;text-transform:uppercase">Group tour request</td>
    </tr></table>
  </td></tr>
  <tr><td style="background:#111111;padding:44px 40px 40px" class="px">
    <p style="margin:0 0 14px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.6px;color:#9a9a9a">YOUR GROUP. YOUR WAY.</p>
    <h1 class="h1" style="margin:0 0 14px;font-family:<?= $font ?>;font-size:32px;line-height:1.35;font-weight:700;color:#ffffff;letter-spacing:-0.5px">ได้รับคำขอจัดกรุ๊ปแล้ว</h1>
    <p style="margin:0 0 28px;font-family:<?= $font ?>;font-size:15px;line-height:1.75;color:#bdbdbd">สวัสดีคุณ<?= e($r['contact_name']) ?> ขอบคุณที่ให้ <?= e($brand) ?> ดูแลทริปของกรุ๊ปคุณ ทีมงานได้รับรายละเอียดแล้ว และจะติดต่อกลับเพื่อคุยโจทย์และส่งร่างโปรแกรมพร้อมใบเสนอราคา</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #3a3a3a;border-radius:14px"><tr>
      <td style="padding:16px 22px;font-family:<?= $font ?>">
        <span style="display:inline-block;margin-bottom:10px;padding:5px 12px;border-radius:999px;background:#2a2a2a;color:#e2e2e2;font-size:12px;font-weight:600;white-space:nowrap">&#9679;&nbsp; รอทีมงานติดต่อกลับ</span>
        <span style="display:block;font-size:11px;letter-spacing:1.4px;color:#8a8a8a">เลขที่คำขอ</span>
        <span style="display:block;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:1px;white-space:nowrap"><?= e($r['reference']) ?></span>
      </td>
    </tr></table>
  </td></tr>

  <!-- Body -->
  <tr><td style="background:#ffffff;padding:36px 40px 8px" class="px">
    <p style="margin:0 0 6px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.2px;color:#9a9a9a">YOUR GROUP</p>
    <h2 style="margin:0 0 18px;font-family:<?= $font ?>;font-size:22px;line-height:1.4;font-weight:700;color:#1a1a1a"><?= e($r['destination']) ?></h2>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <?= $row('ประเภทกรุ๊ป', e($group_type)) ?>
      <?= $row('ผู้เดินทาง', e($travelers)) ?>
      <?= $row('ช่วงเดินทาง', e($when ?: 'ยังไม่ระบุ')) ?>
      <?php if ($r['days']): ?><?= $row('จำนวนวัน', (int) $r['days'].' วัน') ?><?php endif ?>
      <?php if ($budget): ?><?= $row('งบประมาณต่อคน', e($budget)) ?><?php endif ?>
      <?php if ($hotel): ?><?= $row('ระดับที่พัก', e($hotel)) ?><?php endif ?>
      <?php if ($services): ?><?= $row('บริการที่ต้องการ', e($services)) ?><?php endif ?>
      <?= $row('ผู้ติดต่อ', e($r['contact_name']).($r['organization'] ? '<br><span style="font-weight:400;color:#6b6b6b">'.e($r['organization']).'</span>' : '').'<br><span style="font-weight:400;color:#6b6b6b">'.e($r['phone']).'</span>') ?>
    </table>

    <?php if (trim((string) ($r['message'] ?? '')) !== ''): ?>
    <p style="margin:24px 0 6px;font-family:<?= $font ?>;font-size:13px;color:#8a8a8a">รายละเอียดเพิ่มเติมของคุณ</p>
    <p style="margin:0;font-family:<?= $font ?>;font-size:14px;line-height:1.7;color:#1a1a1a"><?= nl2br(e($r['message'])) ?></p>
    <?php endif ?>

    <!-- Not a booking yet -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:26px">
      <tr><td style="border-left:3px solid #111111;background:#fafafa;padding:14px 18px;font-family:<?= $font ?>;font-size:13px;line-height:1.7;color:#3a3a3a">
        <b style="color:#111111">คำขอนี้ยังไม่ใช่การจอง</b> และยังไม่มีค่าใช้จ่าย ราคาและเงื่อนไขจะเป็นไปตามใบเสนอราคาที่ทีมงานส่งให้ หากข้อมูลด้านบนไม่ถูกต้อง ตอบกลับอีเมลนี้หรือแจ้งทีมงานได้เลย
      </td></tr>
    </table>
  </td></tr>

  <!-- Next steps -->
  <tr><td style="background:#ffffff;padding:30px 40px 6px" class="px">
    <p style="margin:0 0 18px;font-family:<?= $font ?>;font-size:11px;font-weight:700;letter-spacing:2.2px;color:#9a9a9a">WHAT HAPPENS NEXT</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <?php foreach ([['คุยโจทย์กับทีมงาน', 'ทีมงานติดต่อกลับตามข้อมูลที่คุณให้ไว้'], ['รับใบเสนอราคา', 'ร่างโปรแกรมรายวัน ราคาต่อคน และรายการที่รวม'], ['ยืนยันและเตรียมเดินทาง', 'ปรับแก้จนลงตัว แล้วยืนยันตามเงื่อนไข']] as $i => $step): ?>
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
      <?php if ($line_url): ?>
      <a href="<?= e($line_url) ?>" style="display:inline-block;padding:15px 34px;border-radius:999px;background:#111111;color:#ffffff;font-family:<?= $font ?>;font-size:15px;font-weight:700;text-decoration:none">คุยกับทีมงานทาง LINE&nbsp;&#8599;</a>
      <div style="height:12px;line-height:12px;font-size:12px">&nbsp;</div>
      <?php endif ?>
      <a href="<?= e($past_groups_url) ?>" style="display:inline-block;padding:13px 30px;border-radius:999px;border:1px solid #d6d6d6;color:#1a1a1a;font-family:<?= $font ?>;font-size:14px;font-weight:600;text-decoration:none">ดูผลงานกรุ๊ปที่ผ่านมา</a>
    </td></tr></table>
    <p style="margin:20px 0 0;font-family:<?= $font ?>;font-size:12px;line-height:1.6;color:#9a9a9a;text-align:center">แจ้งเลขที่คำขอ <?= e($r['reference']) ?> ทุกครั้งที่ติดต่อทีมงาน</p>
  </td></tr>

  <!-- Footer -->
  <tr><td style="padding:28px 40px 8px;font-family:<?= $font ?>;font-size:12px;line-height:1.8;color:#7a7a7a;text-align:center" class="px">
    <?php $contacts = array_filter([$phone ? 'โทร '.e($phone) : '', $line_id ? 'LINE '.e($line_id) : '', $contact_email ? '<a href="mailto:'.e($contact_email).'" style="color:#7a7a7a">'.e($contact_email).'</a>' : '']); ?>
    <?php if ($contacts): ?><p style="margin:0 0 10px;color:#3a3a3a;font-weight:600"><?= implode(' &nbsp;·&nbsp; ', $contacts) ?></p><?php endif ?>
    <p style="margin:0">ใบอนุญาตนำเที่ยว เลขที่ <?= e($license['number']) ?> · <?= e($license['holder']) ?></p>
    <p style="margin:10px 0 0;color:#a0a0a0">คุณได้รับอีเมลนี้เพราะส่งคำขอจัดกรุ๊ปที่ <a href="<?= e($site_url) ?>grouptour" style="color:#a0a0a0"><?= e(parse_url($site_url, PHP_URL_HOST)) ?></a><br>© <?= date('Y') ?> <?= e($brand) ?></p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
