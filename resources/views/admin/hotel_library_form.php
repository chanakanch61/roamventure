<div class="section-head"><h1><?= e($title) ?></h1><a class="outline-link" href="<?= base_url().'admin/hotel-library' ?>">กลับรายการ</a></div>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach (array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" class="panel stack-form" id="hotel-library-form" data-upload-url="<?= e(base_url().'admin/hotel-library/photo') ?>" data-asset-base="<?= e(base_url()) ?>"><?= csrf_field() ?>
 <label>ชื่อโรงแรม *<input name="name" required maxlength="200" value="<?= e($hotel['name']) ?>" placeholder="เช่น Vala Toms Cliff Hotel"><small>ชื่อนี้จะแสดงบนหน้าโปรแกรม ถ้ามีหลายสาขาให้ใส่ชื่อเมืองต่อท้ายเพื่อค้นหาได้ง่าย</small></label>
 <input type="hidden" name="images_json" value="<?= e(json_encode($hotel['images'],JSON_UNESCAPED_SLASHES)) ?>" data-library-images>
 <div class="day-hotel-editor"><b>รูปโรงแรม</b><small>JPG, PNG, WebP ไม่เกิน 5 MB ต่อรูป เลือกได้ทีละหลายรูป รูปแรกใช้เป็นรูปตัวอย่างในคลัง</small>
  <div class="day-hotel-photos" data-library-photos></div>
  <label class="outline-link day-hotel-pick" data-library-pick>＋ เพิ่มรูป<input type="file" multiple accept="image/jpeg,image/png,image/webp" data-library-files></label>
  <p class="fineprint" data-library-status aria-live="polite"></p>
 </div>
 <button type="submit" class="btn">บันทึกโรงแรม</button>
</form>
