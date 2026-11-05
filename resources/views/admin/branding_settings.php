<section class="branding-settings">
<h2>โลโก้เว็บไซต์และเอกสาร</h2>
<p>Navbar และ Footer ใช้โลโก้ชุดเดียวกัน แยกรูปสำหรับ Desktop (ตั้งแต่ 1024 px), Tablet (768–1023 px) และ Mobile (ต่ำกว่า 768 px) หากไม่กำหนด Tablet/Mobile จะใช้รูป Desktop แทน หากยังไม่มี Desktop จะใช้รูปที่มีอยู่สำหรับเว็บไซต์ ส่วน Invoice และ Receipt ใช้รูป Desktop ทั้งบนหน้าจอและเวลาพิมพ์ กรุณาอัปโหลดรูป Desktop เพื่อแสดงโลโก้ในเอกสาร</p>
<p>รองรับ PNG, JPG, WebP ไม่เกิน 2 MB ต่อรูป แนะนำ PNG พื้นหลังโปร่งใส ขนาด Desktop 480 × 160, Tablet 400 × 140 และ Mobile 320 × 120 px รูปจะแสดงโดยรักษาสัดส่วน</p>
<?php foreach (['navbar'=>'โลโก้ร่วม Navbar / Footer','favicon'=>'Favicon / ไอคอนแท็บเบราว์เซอร์'] as $place=>$heading): ?>
<fieldset><legend><?= e($heading) ?></legend><div class="branding-grid">
<?php foreach ($place==='favicon' ? [''=>'Favicon'] : ['desktop'=>'Desktop','tablet'=>'Tablet','mobile'=>'Mobile'] as $device=>$label): $key=$place==='favicon' ? 'favicon' : 'logo_'.$place.'_'.$device; $path=branding_path($settings,$key); ?>
<div class="branding-slot">
<label for="<?= e($key) ?>"><?= e($label) ?></label>
<div class="branding-preview"><img <?= $path ? 'src="'.e(base_url().$path).'"' : 'hidden' ?> alt="ตัวอย่าง <?= e($label) ?>"><span <?= $path ? 'hidden' : '' ?>>ยังไม่มีรูป</span></div>
<input id="<?= e($key) ?>" name="<?= e($key) ?>" type="file" accept="image/png,image/jpeg,image/webp<?= $place==='favicon' ? ',image/x-icon,image/vnd.microsoft.icon,.ico' : '' ?>" data-branding-upload>
<?php if ($path): ?><label class="checkbox"><input type="checkbox" name="remove_<?= e($key) ?>" value="1"> ลบรูปนี้</label><?php endif ?>
<small><?= $place==='favicon' ? 'ภาพจัตุรัส แนะนำ 32 × 32 หรือ 48 × 48 px รองรับ ICO ด้วย' : 'รูปใหม่จะแทนรูปเดิมเมื่อกดบันทึก' ?></small>
</div><?php endforeach ?>
</div></fieldset><?php endforeach ?>
</section>
