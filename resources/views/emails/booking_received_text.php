ได้รับคำขอจองแล้ว · <?= $b['reference'] ?>


สวัสดีคุณ<?= $b['customer_name'] ?>

ขอบคุณที่เลือกเดินทางกับ <?= $brand ?> ทีมงานได้รับคำขอของคุณแล้ว และจะตรวจสอบที่ว่างแล้วติดต่อกลับโดยเร็ว

หมายเลขคำขอ: <?= $b['reference'] ?>

โปรแกรม: <?= $b['tour_title'] ?>

วันเดินทาง: <?= thai_date($b['start_date']) ?> – <?= thai_date($b['end_date']) ?>

ผู้เดินทาง: <?= (int) $b['traveler_count'] ?> คน
<?php if ($package): ?>ระดับบริการ: แพ็คเกจส่วนตัว · <?= $package['level'] ?? '' ?>

<?php endif ?>
ห้องพัก: <?= $rooms ?>

<?php if (! empty($b['ticket_help'])): ?>ตั๋วเครื่องบิน: ให้ทีมงานช่วยจอง
<?php endif ?>
ยอดรวมตามคำขอ: ฿<?= money($b['total_price']) ?> (ทีมงานจะยืนยันอีกครั้ง)

คำขอนี้ยังไม่ใช่การยืนยันการจอง และยังไม่มีการเรียกเก็บเงิน

ดูรายละเอียดการจอง:
<?= $detail_url ?>

(ลิงก์นี้ใช้เปิดหน้าการจองของคุณ โปรดอย่าส่งต่อให้ผู้อื่น)

<?php if ($phone): ?>โทร <?= $phone ?>

<?php endif ?>
<?php if ($line_id): ?>LINE <?= $line_id ?>

<?php endif ?>
<?php if ($contact_email): ?><?= $contact_email ?>

<?php endif ?>
ใบอนุญาตนำเที่ยว เลขที่ <?= $license['number'] ?> · <?= $license['holder'] ?>

