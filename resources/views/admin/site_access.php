<div class="section-head"><div><span class="eyebrow">WEBSITE ACCESS</span><h1>จำกัดการเข้าชม</h1><p>ควบคุมการเข้าถึงหน้าบ้านระหว่างเตรียมเว็บไซต์</p></div></div>
<section class="panel narrow">
<?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif ?>
<div class="notice"><?= app(\App\Support\SiteGate::class)->enabled() ? 'สถานะปัจจุบัน: ต้องเข้าสู่ระบบก่อนเข้าชม' : 'สถานะปัจจุบัน: เปิดให้ทุกคนเข้าชม' ?></div>
<?php if (!app(\App\Support\SiteGate::class)->configured() && app(\App\Support\SiteGate::class)->enabled()): ?><p>ขณะนี้เข้าชมได้เฉพาะแอดมินที่เข้าสู่ระบบแล้ว ตั้งบัญชีด้านล่างเพื่อให้ทีมงานหรือผู้รับเชิญเข้าชมหน้าบ้านได้</p><?php endif ?>
<form method="post" class="stack-form" id="site-access-settings">
<?= csrf_field() ?>
<label class="checkbox"><input type="checkbox" role="switch" name="site_access_enabled" value="1" <?= ($gate_form['site_access_enabled'] ?? '1')!=='0' ? 'checked' : '' ?>> เปิดจำกัดการเข้าชมหน้าบ้าน</label>
<p class="fineprint">เปิด: ผู้เข้าชมต้องเข้าสู่ระบบ · ปิด: ทุกคนเข้าชมได้ตามปกติ แอดมินที่เข้าสู่ระบบแล้วเข้าชมได้เสมอ</p>
<label>ชื่อผู้ใช้สำหรับเข้าชม<input name="site_access_username" value="<?= e($gate_form['site_access_username'] ?? 'preview') ?>" minlength="3" maxlength="100" pattern="[a-zA-Z0-9@._\-]{3,100}" autocomplete="off" required><small>บัญชีร่วมสำหรับผู้รับเชิญ ใช้เข้าชมหน้าบ้านเท่านั้น ไม่มีสิทธิ์เข้าแอดมิน</small></label>
<label><?= app(\App\Support\SiteGate::class)->configured() ? 'รหัสผ่านใหม่' : 'รหัสผ่านสำหรับเข้าชม' ?><input type="password" name="site_access_password" minlength="12" maxlength="72" autocomplete="new-password"><small>อย่างน้อย 12 ตัวอักษร ไม่เกิน 72 ไบต์<?= app(\App\Support\SiteGate::class)->configured() ? ' · เว้นว่างเพื่อใช้รหัสผ่านเดิม' : '' ?></small></label>
<label>ยืนยันรหัสผ่าน<input type="password" name="site_access_password_confirm" minlength="12" maxlength="72" autocomplete="new-password"></label>
<p class="fineprint">การบันทึกจะให้ผู้เข้าชมที่เคยเข้าสู่ระบบเข้าสู่ระบบใหม่อีกครั้ง บัญชีแอดมินยังใช้งานได้ตามปกติ</p>
<p class="fineprint">ระบบจำกัดหน้าเว็บและลิงก์หน้าย่อย ส่วนไฟล์รูปภาพและเอกสารใน public ยังเข้าถึงได้ด้วย URL ของไฟล์โดยตรง</p>
<button class="btn" type="submit">บันทึกการเข้าชม</button>
<a href="<?= base_url().'site-login' ?>" target="_blank" rel="noopener">เปิดหน้าเข้าสู่ระบบผู้เข้าชม ↗</a>
</form></section>
