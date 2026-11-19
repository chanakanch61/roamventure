ได้รับคำขอจัดกรุ๊ปแล้ว · <?= $r['reference'] ?>


สวัสดีคุณ<?= $r['contact_name'] ?>

ขอบคุณที่ให้ <?= $brand ?> ดูแลทริปของกรุ๊ปคุณ ทีมงานได้รับรายละเอียดแล้ว และจะติดต่อกลับเพื่อคุยโจทย์และส่งร่างโปรแกรมพร้อมใบเสนอราคา

เลขที่คำขอ: <?= $r['reference'] ?>

จุดหมาย: <?= $r['destination'] ?>

ประเภทกรุ๊ป: <?= $group_type ?>

ผู้เดินทาง: <?= $travelers ?>

ช่วงเดินทาง: <?= $when ?: 'ยังไม่ระบุ' ?>

<?php if ($r['days']): ?>จำนวนวัน: <?= (int) $r['days'].' วัน' ?>

<?php endif ?>
<?php if ($budget): ?>งบประมาณต่อคน: <?= $budget ?>

<?php endif ?>
<?php if ($hotel): ?>ระดับที่พัก: <?= $hotel ?>

<?php endif ?>
<?php if ($services): ?>บริการที่ต้องการ: <?= $services ?>

<?php endif ?>
<?php if (trim((string) ($r['message'] ?? '')) !== ''): ?>รายละเอียดเพิ่มเติม:
<?= $r['message'] ?>


<?php endif ?>
คำขอนี้ยังไม่ใช่การจอง และยังไม่มีค่าใช้จ่าย ราคาและเงื่อนไขจะเป็นไปตามใบเสนอราคาที่ทีมงานส่งให้
แจ้งเลขที่คำขอ <?= $r['reference'] ?> ทุกครั้งที่ติดต่อทีมงาน

<?php if ($line_url): ?>คุยกับทีมงานทาง LINE:
<?= $line_url ?>


<?php endif ?>
ดูผลงานกรุ๊ปที่ผ่านมา:
<?= $past_groups_url ?>


<?php if ($phone): ?>โทร <?= $phone ?>

<?php endif ?>
<?php if ($line_id): ?>LINE <?= $line_id ?>

<?php endif ?>
<?php if ($contact_email): ?><?= $contact_email ?>

<?php endif ?>
ใบอนุญาตนำเที่ยว เลขที่ <?= $license['number'] ?> · <?= $license['holder'] ?>

