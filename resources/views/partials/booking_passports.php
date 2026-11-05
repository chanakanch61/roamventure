<?php
$passportDocuments=app(\App\Repositories\PassportRepository::class)->documents($booking['id']);
$passportAdmin=!empty($passport_admin);
$passportAllowed=app(\App\Repositories\PassportRepository::class)->eligible($booking,$billing ?? null);
?>
<section class="panel" id="passports">
 <h2>พาสปอร์ตผู้เดินทาง</h2>
 <p>ส่งแล้ว <?= count($passportDocuments) ?> / <?= (int)$booking['traveler_count'] ?> คน</p>
 <?php if (!$passportAdmin && !$passportAllowed): ?><p class="notice">เปิดให้อัปโหลดหลังทีมงานอนุมัติยอดมัดจำครบหรือชำระเต็มจำนวนแล้ว และการจองยังไม่ถูกยกเลิก</p><?php endif ?>
 <?php for ($number=1; $number<=(int)$booking['traveler_count']; $number++): $document=$passportDocuments[$number] ?? null; ?>
 <div class="payment-history">
  <h3>ผู้เดินทางคนที่ <?= $number ?></h3>
  <?php if ($document): ?>
   <p>อัปโหลดแล้ว · <?= e($document['uploaded_at']) ?></p>
   <a class="outline-link" target="_blank" rel="noopener noreferrer" href="<?= (base_url() . ($passportAdmin ? 'admin/bookings/'.$booking['id'].'/passport/'.$number : 'booking/passport/'.$booking['access_token'].'/'.$number)) ?>">ดูพาสปอร์ต <i class="material-icons" aria-hidden="true">north_east</i></a>
  <?php else: ?><p>ยังไม่ได้อัปโหลด</p><?php endif ?>
  <?php if (!$passportAdmin && $passportAllowed): ?>
  <form class="stack-form" method="post" enctype="multipart/form-data" action="<?= (base_url() . ('booking/passport/'.$booking['access_token'])) ?>">
   <?= csrf_field() ?><input type="hidden" name="traveler_number" value="<?= $number ?>">
   <label>ภาพหน้าพาสปอร์ตของผู้เดินทางคนที่ <?= $number ?><input type="file" name="passport" accept="image/jpeg,image/png" required><small>JPG หรือ PNG ไม่เกิน 5 MB และด้านละไม่เกิน 8,000 พิกเซล ถ่ายให้เห็นหน้าข้อมูลครบและชัดเจน</small></label>
   <button class="btn" type="submit"><?= $document ? 'อัปโหลดแทนไฟล์เดิม' : 'อัปโหลดพาสปอร์ต' ?></button>
  </form>
  <?php endif ?>
 </div>
 <?php endfor ?>
</section>
