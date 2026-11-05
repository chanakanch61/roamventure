<div class="section-head"><h1><?= e($title) ?></h1><a class="outline-link" href="<?= base_url().'admin/airlines' ?>">กลับรายการ</a></div>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach (array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="panel stack-form" id="airline-form"><?= csrf_field() ?>
 <label>ชื่อสายการบิน *<input name="name" required maxlength="120" value="<?= e($airline['name']) ?>" placeholder="เช่น Kunming Airlines"></label>
 <div class="form-grid"><label>รหัสสายการบิน<input name="code" maxlength="3" value="<?= e($airline['code']) ?>" placeholder="เช่น KY"><small>แสดงแทนโลโก้เมื่อยังไม่ได้อัปโหลด</small></label><label>ลำดับแสดงผล<input type="number" name="sort_order" required min="0" max="9999" value="<?= (int)$airline['sort_order'] ?>"></label></div>
 <div class="branding-slot airline-logo-slot">
  <div class="branding-preview airline-logo airline-logo-large"><img src="<?= $airline['logo_path'] ? e(base_url().$airline['logo_path']) : '' ?>" alt="" <?= $airline['logo_path'] ? '' : 'hidden' ?>><span <?= $airline['logo_path'] ? 'hidden' : '' ?>>ยังไม่มีโลโก้</span></div>
  <label>โลโก้<input type="file" name="logo_file" accept="image/png,image/webp,image/jpeg" data-branding-upload><small><strong>แนะนำ 600 × 200 พิกเซล (แนวนอน 3:1) พื้นโปร่งใส</strong><br>PNG, WebP หรือ JPG ไม่เกิน 1 MB · ตัดพื้นที่ว่างรอบโลโก้ออกก่อนอัปโหลด</small></label>
  <?php if ($airline['logo_path']): ?><label class="checkbox"><input type="checkbox" name="remove_logo" value="1"> นำโลโก้ออก</label><?php endif ?>
 </div>
 <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= $airline['is_active'] ? 'checked' : '' ?>> เปิดให้เลือกใช้ในโปรแกรม</label>
 <input type="hidden" name="form_complete" value="1">
 <button type="submit" class="btn">บันทึกสายการบิน</button>
</form>
