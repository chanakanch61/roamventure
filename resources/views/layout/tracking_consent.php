<?php if (empty($tracking)) return; ?>
<section class="tracking-consent" id="tracking-consent" aria-label="การแจ้งเตือนการใช้คุกกี้" hidden>
<div><strong>การใช้คุกกี้</strong><p>เว็บไซต์นี้ใช้คุกกี้เพื่อพัฒนาประสบการณ์การใช้งาน วิเคราะห์การเข้าชม และวัดผลโฆษณา ศึกษารายละเอียดเพิ่มเติมได้ที่ <a href="<?= base_url().'privacy' ?>">การใช้ข้อมูลส่วนบุคคล</a></p></div>
<div class="tracking-consent-actions"><button type="button" data-tracking-consent="denied">ปฏิเสธ</button><button type="button" data-tracking-consent="granted">ยอมรับ</button></div>
</section>
<button class="tracking-preferences" type="button" id="tracking-preferences">ตั้งค่าคุกกี้</button>
