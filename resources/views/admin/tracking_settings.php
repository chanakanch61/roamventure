<section class="stack-form" id="tracking-settings"><h2>Tracking — GTM / Facebook / LINE</h2>
<label>วิธีติดตั้ง<select name="tracking_mode">
<?php foreach (['off'=>'ปิด Tracking','gtm'=>'จัดการ Tag ทั้งหมดผ่าน GTM','direct'=>'ติดตั้ง Meta Pixel และ LINE Tag โดยตรง'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($settings['tracking_mode'] ?? 'off')===$value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
</select></label>
<?php foreach (tracking_fields() as $key=>$field): ?><label><?= e($field['label']) ?><input name="<?= e($key) ?>" maxlength="50" autocomplete="off" placeholder="<?= e($field['placeholder']) ?>" value="<?= e($settings[$key] ?? '') ?>"></label><?php endforeach ?>
<p class="fineprint">กรอกเฉพาะ ID ไม่ต้องวางโค้ด script · LINE Tag ID เป็นคนละค่ากับ LINE Official ID สำหรับแชต</p>
<p class="fineprint">โหมด GTM โหลดเฉพาะ Container ให้ตั้ง Meta / LINE และ Trigger ภายใน GTM ส่วนโหมดติดตั้งโดยตรงจะไม่โหลด GTM เพื่อป้องกัน event ซ้ำ</p>
<p class="fineprint">Tag เริ่มทำงานหลังผู้เข้าชมยินยอมเท่านั้น และไม่โหลดในหลังบ้าน หน้า preview หน้าชำระเงิน หรือหน้าข้อมูลการจองส่วนตัว</p>
<details><summary>Event และจุดที่นับผล</summary><ul>
<li><code>page_view</code> — เปิดหน้าสาธารณะ</li>
<li><code>view_item</code> — ดูรายละเอียดทัวร์</li>
<li><code>search</code> — ส่งแบบค้นหา (ไม่ส่งคำค้นที่ผู้ใช้พิมพ์)</li>
<li><code>begin_checkout</code> — เริ่มเลือกรายละเอียดการจอง</li>
<li><code>booking_submit</code> — ส่งแบบฟอร์มที่ผ่านการตรวจในเบราว์เซอร์</li>
<li><code>contact_click</code> — คลิก LINE, Facebook, Messenger, โทรศัพท์ หรืออีเมล</li>
<li><code>generate_lead</code> — ระบบบันทึกคำขอจองสำเร็จเท่านั้น ไม่ใช่ยอดชำระเงิน</li>
</ul><p class="fineprint">GTM รับ event ผ่าน dataLayer · Meta ใช้ PageView / ViewContent / InitiateCheckout / Contact / Lead · LINE ใช้ PageView, event กำหนดเอง และ Conversion เมื่อบันทึกคำขอสำเร็จ</p></details>
</section>
