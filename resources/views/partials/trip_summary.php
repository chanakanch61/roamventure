<?php
// The summary is built in the browser from the customer's selections: see updatePrice in public/app.js.
$summaryLine=!empty($settings['line_id']) ? tour_line_url($settings['line_id'],'') : '';
$summaryMessenger=contact_https_url($settings['messenger_url'] ?? '');
?>
<section class="trip-summary" id="trip-summary" aria-labelledby="trip-summary-title" data-program="<?= $isPrivate ? 'แพ็กเกจ' : 'ทัวร์' ?>" data-title="<?= e($tour['title']) ?>" data-code="<?= e($tour['code']) ?>" data-duration="<?= (int)$tour['days'] ?> วัน <?= (int)$tour['nights'] ?> คืน">
 <div class="trip-summary-head"><h3 id="trip-summary-title">สรุปทริปของคุณ</h3><button type="button" class="trip-summary-copy" id="trip-summary-copy"><i class="material-icons" aria-hidden="true">content_copy</i><span aria-live="polite">คัดลอกสรุป</span></button></div>
 <div class="trip-summary-body" id="trip-summary-body"></div>
 <p class="trip-summary-hint">อยากคุยกับเซลล์ก่อน? คัดลอกสรุปนี้แล้ววางในแชท ไม่ต้องกรอกฟอร์ม</p>
 <div class="trip-summary-chat"><?php if ($summaryLine): ?><a href="<?= e($summaryLine) ?>" data-line-id="<?= e($settings['line_id']) ?>" target="_blank" rel="noopener">เปิด LINE <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?><?php if ($summaryMessenger): ?><a href="<?= e($summaryMessenger) ?>" target="_blank" rel="noopener">เปิด Messenger <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?><?php if (!$summaryLine && !$summaryMessenger): ?><a href="<?= base_url() ?>contactus">ช่องทางติดต่อ <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?></div>
</section>
