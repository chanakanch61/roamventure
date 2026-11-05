<div class="section-head"><div><span class="eyebrow">TEAM ACCESS</span><h1><?= e($title) ?></h1><p>ปิดใช้งานบัญชีเพื่อระงับการเข้าถึง โดยเก็บประวัติการทำงานไว้</p></div><a class="text-link" href="<?= base_url().'admin/admins' ?>">← กลับรายการ</a></div>
<?php foreach ($errors as $error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endforeach ?>
<form method="post" class="panel access-form"><?= csrf_field() ?>
<label for="access-name">ชื่อแอดมิน</label><input id="access-name" name="name" value="<?= e($account['name']) ?>" maxlength="100" required autocomplete="name">
<label for="access-email">อีเมลเข้าสู่ระบบ</label><input id="access-email" name="email" type="email" value="<?= e($account['email']) ?>" maxlength="190" required autocomplete="username">
<label for="access-password"><?= $id ? 'รหัสผ่านใหม่' : 'รหัสผ่าน' ?></label><input id="access-password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" <?= $id ? '' : 'required' ?> aria-describedby="password-help"><small id="password-help">อย่างน้อย 12 ตัวอักษร (ไม่เกิน 72 ไบต์)<?= $id ? ' · เว้นว่างเพื่อใช้รหัสผ่านเดิม' : '' ?></small>
<label for="access-role">บทบาท</label><select id="access-role" name="role_id" required><option value="">เลือกบทบาท</option><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$account['role_id']===(int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach ?></select>
<label for="access-state">สถานะบัญชี</label><select id="access-state" name="is_active"><option value="1" <?= $account['is_active'] ? 'selected' : '' ?>>เปิดใช้งาน</option><option value="0" <?= !$account['is_active'] ? 'selected' : '' ?>>ปิดใช้งาน</option></select>
<p class="notice">ไม่สามารถปิดใช้งานหรือเปลี่ยนบทบาทของตนเองได้ การเปลี่ยนรหัสผ่านจะยกเลิกเซสชันเดิมของบัญชีนั้น</p><button class="btn" type="submit">บันทึกแอดมิน</button>
</form>
