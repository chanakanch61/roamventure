<div class="rv-company gt-page">
 <section class="container gt-success">
  <div class="success-icon"><i class="material-icons" aria-hidden="true">check</i></div>
  <span class="rv-kicker">REQUEST RECEIVED</span>
  <h1>ได้รับคำขอจัดกรุ๊ปแล้ว</h1>
  <p class="gt-success-ref">เลขที่คำขอ <strong><?= e($reference) ?></strong></p>
  <p>ทีมงานจะตรวจรายละเอียดแล้วติดต่อกลับตามช่องทางที่ให้ไว้ เพื่อคุยโจทย์ของกรุ๊ปและส่งร่างโปรแกรมพร้อมใบเสนอราคา<br>คำขอนี้ยังไม่ใช่การจอง และยังไม่มีค่าใช้จ่าย</p>
  <div class="rv-actions gt-success-actions">
   <?php if ($line_message_url): ?><a class="btn" href="<?= e($line_message_url) ?>" target="_blank" rel="noopener">แจ้งเลขที่คำขอทาง LINE <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?>
   <?php if ($contact['tel']): ?><a class="rv-link" href="tel:<?= e($contact['tel']) ?>">โทร <?= e($contact['phone']) ?> <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?>
   <a class="rv-link" href="<?= base_url() ?>grouptour#past-groups">ดูผลงานกรุ๊ปที่ผ่านมา <i class="material-icons" aria-hidden="true">north_east</i></a>
  </div>
 </section>
</div>
