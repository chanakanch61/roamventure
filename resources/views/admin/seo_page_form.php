<?php $seoMeta=seo_page_meta($settings,$seo_key); $seoHasImage=!empty($seoMeta['image_size']); $seoUrl=base_url().$seo_page['path']; ?>
<link rel="stylesheet" href="<?= base_url().'public/seo-admin.css?v='.filemtime(base_path()."/".'public/seo-admin.css') ?>">
<div class="section-head"><div><span class="eyebrow">PAGE META TAGS</span><h1><?= e($title) ?></h1><p><a class="text-link" href="<?= e($seoUrl) ?>" target="_blank" rel="noopener">/<?= e($seo_page['path']) ?> ↗</a></p></div><a class="outline-link" href="<?= base_url().'admin/seo' ?>">กลับรายการ</a></div>
<?php foreach ($errors as $error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endforeach ?>
<?php if (app(\App\Support\SiteGate::class)->enabled()): ?><p class="notice">เว็บไซต์ยังเปิดจำกัดการเข้าชมอยู่ Facebook, LINE และ Google จึงยังอ่าน Meta Tag ไม่ได้จนกว่าจะปิดการจำกัด</p><?php endif ?>
<form method="post" enctype="multipart/form-data" class="seo-editor" data-seo-form data-width="<?= \App\Support\ShareImage::WIDTH ?>" data-height="<?= \App\Support\ShareImage::HEIGHT ?>">
 <?= csrf_field() ?>
 <div class="panel stack-form">
  <h2>ข้อความ</h2>
  <label>ชื่อหน้า (Meta title)<input name="title" maxlength="200" value="<?= e($settings['seo_'.$seo_key.'_title'] ?? '') ?>" placeholder="<?= e($seo_defaults['title']) ?>" data-seo-text data-fallback="<?= e($seo_defaults['title']) ?>" data-advice="60"></label><small data-seo-count="title"></small>
  <label>คำอธิบาย (Meta description)<textarea name="description" maxlength="300" rows="4" placeholder="<?= e($seo_defaults['description']) ?>" data-seo-text data-fallback="<?= e($seo_defaults['description']) ?>" data-advice="160"><?= e($settings['seo_'.$seo_key.'_description'] ?? '') ?></textarea></label><small data-seo-count="description"></small>
  <label>คำค้น (Meta keywords)<input name="keywords" maxlength="300" value="<?= e($settings['seo_'.$seo_key.'_keywords'] ?? '') ?>" placeholder="คั่นแต่ละคำด้วยเครื่องหมายจุลภาค"></label>
  <small>เว้นข้อความว่างเพื่อใช้ค่าเริ่มต้นของหน้านี้</small>
  <h2>รูปแชร์ (Open Graph)</h2>
  <label for="seo-image">เลือกรูป</label><input id="seo-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
  <small data-seo-image-note>ระบบครอปกึ่งกลางและปรับเป็น <?= \App\Support\ShareImage::WIDTH ?> × <?= \App\Support\ShareImage::HEIGHT ?> px ให้อัตโนมัติ · ต้องมีขนาดอย่างน้อย <?= \App\Support\ShareImage::WIDTH ?> × <?= \App\Support\ShareImage::HEIGHT ?> px · JPG, PNG หรือ WebP ไม่เกิน 5 MB</small>
  <?php if ($seoHasImage): ?><label class="checkbox"><input type="checkbox" name="remove_image" value="1">นำรูปนี้ออกและใช้รูปเริ่มต้น</label><?php endif ?>
  <input type="hidden" name="form_complete" value="1"><button class="btn" type="submit">บันทึก Meta Tag</button>
 </div>
 <aside class="seo-previews">
  <div class="panel"><h2>ตัวอย่างเมื่อแชร์</h2><div class="seo-share-card"><img src="<?= e($seoMeta['image']) ?>" alt="ตัวอย่างรูปแชร์" data-seo-image data-fallback="<?= e(base_url().$seo_defaults['image']) ?>"><div><span><?= e(parse_url(base_url(), PHP_URL_HOST)) ?></span><strong data-seo-preview="title"></strong><p data-seo-preview="description"></p></div></div>
  <small><?= $seoHasImage ? 'รูปนี้บันทึกเป็นขนาดแชร์แล้ว' : 'ตอนนี้ใช้รูปเริ่มต้น ซึ่งอาจถูกตัดต่างจากตัวอย่างนี้ในแต่ละแพลตฟอร์ม' ?></small></div>
  <div class="panel"><h2>ตัวอย่างบน Google</h2><div class="seo-search-card"><span><?= e($seoUrl) ?></span><strong data-seo-preview="title"></strong><p data-seo-preview="description"></p></div></div>
  <p class="seo-debugger">แก้แล้ว Facebook ยังแสดงของเดิม? กด “Scrape Again” ใน <a class="text-link" href="https://developers.facebook.com/tools/debug/?q=<?= e(rawurlencode($seoUrl)) ?>" target="_blank" rel="noopener">Sharing Debugger ↗</a></p>
 </aside>
</form>
<script src="<?= base_url().'public/seo-admin.js?v='.filemtime(base_path()."/".'public/seo-admin.js') ?>" defer></script>
