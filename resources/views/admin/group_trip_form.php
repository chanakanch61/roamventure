<?php
use App\Repositories\GroupTourRepository as Groups;
$t = $trip;
$val = fn ($key) => e((string) ($t[$key] ?? ''));
?>
<div class="section-head"><div><a class="text-link" href="<?= base_url().'admin/group-trips' ?>">← ผลงานทั้งหมด</a><h1><?= e($title) ?></h1><?php if ($t['request_id']): ?><p>สร้างจาก <a class="text-link" href="<?= base_url().'admin/group-requests/'.(int) $t['request_id'] ?>">คำขอ #<?= (int) $t['request_id'] ?></a></p><?php endif ?></div><?php if ($t['id'] && $t['status'] === 'published'): ?><a class="outline-link" href="<?= e(base_url().'grouptour/'.rawurlencode($t['slug'])) ?>" target="_blank" rel="noopener">ดูหน้าเว็บ ↗</a><?php endif ?></div>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach ?><?php if (! empty($_FILES['cover_file']['name'])): ?><p>กรุณาเลือกรูปปกใหม่ก่อนบันทึกอีกครั้ง</p><?php endif ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="admin-two-col"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int) $t['request_id'] ?>">
 <div>
  <section class="panel stack-form">
   <h2>01 / ข้อมูลกรุ๊ป</h2>
   <label>ชื่อผลงาน <span aria-hidden="true">*</span><input name="title" required maxlength="200" value="<?= $val('title') ?>" placeholder="เช่น สัมมนาบริษัท 40 ท่าน ญี่ปุ่น โตเกียว–ฟูจิ 5 วัน"></label>
   <label>URL (slug)<input name="slug" maxlength="180" value="<?= $val('slug') ?>" placeholder="เว้นว่างเพื่อสร้างจากชื่อผลงาน"><small>หน้าเว็บ: <?= e(base_url()) ?>grouptour/<b><?= $val('slug') ?: '…' ?></b> · ใช้ตัวอักษร ตัวเลข และขีด (-)</small></label>
   <label>ประเภทกรุ๊ป <span aria-hidden="true">*</span><select name="group_type"><?php foreach (Groups::group_types() as $key => $label): ?><option value="<?= e($key) ?>"<?= $t['group_type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label>
   <label>จุดหมาย <span aria-hidden="true">*</span><input name="destination" required maxlength="200" value="<?= $val('destination') ?>" placeholder="เช่น ญี่ปุ่น (โตเกียว–ฟูจิ)"></label>
   <label>ชื่อลูกค้า / องค์กร<input name="organization" maxlength="180" value="<?= $val('organization') ?>" placeholder="แสดงบนเว็บ ใส่เมื่อได้รับอนุญาตจากลูกค้าเท่านั้น"></label>
   <div class="form-grid"><label>วันที่เดินทาง<input type="date" name="travel_date" value="<?= $val('travel_date') ?>"></label><label>จำนวนวัน<input type="number" name="days" min="1" max="60" value="<?= $val('days') ?>"></label><label>ผู้เดินทาง (ท่าน)<input type="number" name="travelers" min="1" max="9999" value="<?= $val('travelers') ?>"></label></div>
  </section>
  <section class="panel stack-form">
   <h2>02 / เนื้อหา</h2>
   <label>สรุปผลงาน (1–2 ประโยค) <span aria-hidden="true">*</span><textarea name="summary" rows="3" maxlength="400" placeholder="เช่น จัดสัมมนาและทริป Incentive ให้พนักงาน 40 ท่าน 5 วัน พร้อมห้องประชุมที่โตเกียวและงานเลี้ยงริมทะเลสาบคาวากูจิ"><?= $val('summary') ?></textarea><small>แสดงบนการ์ดและใช้เป็นคำอธิบายในผลค้นหา เขียนให้ตอบได้ว่า ใคร ไปไหน กี่คน กี่วัน และจัดอะไรให้</small></label>
   <label>เรื่องราวของทริป<textarea name="story" rows="8" maxlength="20000" placeholder="เล่าโจทย์ของลูกค้า สิ่งที่วางแผนให้ และผลลัพธ์ เว้นบรรทัดว่างเพื่อขึ้นย่อหน้าใหม่"><?= $val('story') ?></textarea></label>
   <label>ไฮไลต์ที่จัดให้ (บรรทัดละ 1 ข้อ)<textarea name="highlights" rows="5" maxlength="5000" placeholder="ห้องประชุม 50 ที่นั่งพร้อมอุปกรณ์&#10;รถบัสส่วนตัวตลอดทริป&#10;งานเลี้ยงปิดทริป"><?= $val('highlights') ?></textarea></label>
   <label>คำรีวิวจากลูกค้า<textarea name="testimonial" rows="3" maxlength="3000" placeholder="ใส่เฉพาะคำรีวิวจริงที่ลูกค้าอนุญาตให้เผยแพร่"><?= $val('testimonial') ?></textarea></label>
   <label>ผู้รีวิว<input name="testimonial_by" maxlength="180" value="<?= $val('testimonial_by') ?>" placeholder="เช่น คุณเอ ฝ่ายบุคคล บริษัท …"></label>
  </section>
  <section class="panel stack-form">
   <h2>03 / SEO</h2>
   <label>SEO Title<input name="seo_title" maxlength="200" value="<?= $val('seo_title') ?>" placeholder="เว้นว่างเพื่อใช้ “ชื่อผลงาน | ผลงานกรุ๊ปเหมา”"></label>
   <label>Meta description<textarea name="seo_description" rows="2" maxlength="300" placeholder="เว้นว่างเพื่อใช้สรุปผลงาน"><?= $val('seo_description') ?></textarea></label>
  </section>
 </div>
 <aside>
  <section class="panel stack-form">
   <h2>การเผยแพร่</h2>
   <label>สถานะ<select name="status"><option value="draft"<?= $t['status'] === 'draft' ? ' selected' : '' ?>>ฉบับร่าง</option><option value="published"<?= $t['status'] === 'published' ? ' selected' : '' ?>>เผยแพร่บนเว็บไซต์</option></select></label>
   <label>ลำดับ<input type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $t['sort_order'] ?>"><small>เลขน้อยแสดงก่อน ถ้าเท่ากันเรียงตามวันเดินทางล่าสุด</small></label>
   <button class="btn" type="submit">บันทึกผลงาน</button>
  </section>
  <section class="panel stack-form">
   <h2>รูปภาพ</h2>
   <?php if ($t['cover']): ?><img src="<?= e(base_url().$t['cover']) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px"><label><input type="checkbox" name="remove_cover" value="1"> นำรูปปกออก</label><?php endif ?>
   <label>รูปปก<input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, WebP ไม่เกิน 5 MB แนวนอนอย่างน้อย 1200 px</small></label>
   <label>อัลบั้ม Gallery ของทริปนี้<select name="album_id"><option value="">— ไม่เชื่อมอัลบั้ม —</option><?php foreach ($albums as $album): ?><option value="<?= (int) $album['id'] ?>"<?= (int) $t['album_id'] === (int) $album['id'] ? ' selected' : '' ?>><?= e($album['title']) ?><?= $album['status'] !== 'published' ? ' (ฉบับร่าง)' : '' ?></option><?php endforeach ?></select><small>หน้าผลงานแสดงภาพจากอัลบั้มที่เผยแพร่แล้ว ถ้าไม่มีรูปปกจะใช้ภาพปกของอัลบั้ม จัดการภาพได้ที่ <a class="text-link" href="<?= base_url().'admin/gallery' ?>">Gallery</a></small></label>
  </section>
 </aside>
</form>
<?php if ($t['id']): ?><form method="post" action="<?= base_url().'admin/group-trips/'.(int) $t['id'].'/delete' ?>" class="panel" onsubmit="return confirm('ลบผลงานนี้? หน้าเว็บของผลงานจะหายไป')"><?= csrf_field() ?><strong>ลบผลงานนี้</strong><p class="fineprint">ลบผลงานและรูปปก ไม่ลบอัลบั้ม Gallery และคำขอที่เชื่อมไว้</p><button type="submit" class="text-button danger">ลบผลงาน</button></form><?php endif ?>
