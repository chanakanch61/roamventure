<section class="branding-settings">
<h2>ใบอนุญาตประกอบธุรกิจนำเที่ยว</h2>
<p>แสดงใน Footer ทุกหน้า และใต้ปุ่มจองในหน้าโปรแกรม ลูกค้ากดดูรูปใบอนุญาตได้จาก Footer</p>
<?php $license=tour_license($settings); $licenseUpload=branding_path($settings,'license_image'); ?>
<label>เลขที่ใบอนุญาต *<input name="license_number" required maxlength="20" placeholder="11/08038" value="<?= e($license['number']) ?>"><small>หากเปลี่ยนเลข ต้องอัปโหลดรูปใบอนุญาตใหม่ หรือเลือกลบรูปเดิม</small></label>
<label>ชื่อผู้ได้รับใบอนุญาต *<input name="license_holder" required maxlength="190" value="<?= e($license['holder']) ?>"></label>
<div class="branding-grid"><div class="branding-slot">
<label for="license_image">รูปใบอนุญาต</label>
<div class="branding-preview"><img <?= $license['image'] ? 'src="'.e(base_url().$license['image']).'"' : 'hidden' ?> alt="ตัวอย่างรูปใบอนุญาต"><span <?= $license['image'] ? 'hidden' : '' ?>>ยังไม่มีรูป ปุ่มดูใบอนุญาตจะไม่แสดง</span></div>
<input id="license_image" name="license_image" type="file" accept="image/png,image/jpeg,image/webp" data-branding-upload>
<?php if ($licenseUpload): ?><label class="checkbox"><input type="checkbox" name="remove_license_image" value="1"> ลบรูปนี้</label><?php endif ?>
<small>PNG, JPG หรือ WebP ไม่เกิน 2 MB รูปใหม่จะแทนรูปเดิมเมื่อกดบันทึก</small>
</div></div>
</section>
