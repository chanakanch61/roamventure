<div class="section-head"><div><span class="eyebrow">WEBSITE BANNERS</span><h1>แบนเนอร์เว็บไซต์</h1><p>จัดการรูปและข้อความแยกแต่ละหน้า บันทึกทีละประเภท</p></div></div>
<?php foreach ($errors as $error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endforeach ?>
<?php load_view('admin/hero_slides'); ?>
<?php foreach (page_banner_defaults() as $type=>$defaults): if ($type==='home') continue; $banner=page_banner($settings,$type); ?>
<section class="panel banner-editor" id="<?= e($type) ?>"><div class="section-head"><h2><?= e($defaults['label']) ?></h2><a class="text-link" href="<?= base_url().($type==='fixed' ? 'tours' : program_channels()[$type]['path']) ?>" target="_blank" rel="noopener">ดูหน้าเว็บไซต์ ↗</a></div>
<form method="post" enctype="multipart/form-data" class="stack-form">
 <?= csrf_field() ?><input type="hidden" name="banner_type" value="<?= e($type) ?>">
 <label>หัวข้อ Hero<textarea name="title" maxlength="120" rows="2" placeholder="<?= e($defaults['title']) ?>"><?= e($settings['banner_'.$type.'_title'] ?? '') ?></textarea></label>
 <label>คำโปรย<textarea name="description" maxlength="400" rows="3" placeholder="<?= e($defaults['description']) ?>"><?= e($settings['banner_'.$type.'_description'] ?? '') ?></textarea></label><small>เว้นข้อความว่างเพื่อใช้ข้อความเริ่มต้นของประเภทนี้</small>
 <div class="form-grid"><?php foreach (['desktop'=>'Desktop','mobile'=>'Mobile'] as $device=>$label): ?>
 <div><label for="<?= $type.'_'.$device ?>">รูป <?= $label ?></label><img class="banner-preview" src="<?= e(base_url().$banner[$device]) ?>" alt="ตัวอย่าง <?= e($defaults['label'].' '.$label) ?>"><input id="<?= $type.'_'.$device ?>" type="file" name="<?= $device ?>" accept="image/jpeg,image/png,image/webp" data-banner-upload><small><?= $device==='desktop' ? 'แนะนำ 1920 × 800 px' : 'แนะนำ 900 × 1000 px · หากไม่ใส่ จะใช้รูป Desktop' ?> · ไม่เกิน 5 MB</small>
 <?php if (branding_path($settings,'banner_'.$type.'_'.$device)): ?><label class="checkbox"><input type="checkbox" name="remove_<?= $device ?>" value="1">นำรูปนี้ออกและใช้รูปเริ่มต้น</label><?php endif ?></div>
 <?php endforeach ?></div><input type="hidden" name="form_complete" value="1"><button class="btn" type="submit">บันทึก<?= e($defaults['label']) ?></button>
</form></section><?php endforeach ?>
<script src="<?= base_url().'public/banners.js' ?>" defer></script>
