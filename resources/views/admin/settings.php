<div class="section-head"><div><span class="eyebrow">SITE SETTINGS</span><h1>ข้อมูลเว็บไซต์และช่องทางติดต่อ</h1></div></div>
<section class="panel narrow">
<p><a href="<?= base_url().'admin/site-access' ?>">จำกัดการเข้าชมหน้าบ้าน →</a> · <?= app(\App\Support\SiteGate::class)->enabled() ? 'ต้องเข้าสู่ระบบก่อนเข้าชม' : 'เปิดให้ทุกคนเข้าชม' ?></p>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="stack-form">
<?= csrf_field() ?>
<label>ชื่อบริษัท / แบรนด์<input name="company_name" maxlength="190" value="<?= e($settings['company_name'] ?? '') ?>"></label>
<?php load_view('admin/code_prefix_settings',['settings'=>$settings]); ?>
<label>LINE Official ID<input name="line_id" maxlength="81" placeholder="@your-line-id" value="<?= e($settings['line_id'] ?? '') ?>"><small>เมื่อกำหนดแล้ว ปุ่ม LINE จะปรากฏในหน้าโปรแกรมและหน้ารับคำขอสำเร็จ</small></label>
<label>เบอร์โทรศัพท์<input name="phone" maxlength="190" value="<?= e($settings['phone'] ?? '') ?>"></label>
<label>อีเมลติดต่อ<input name="email" type="email" maxlength="190" value="<?= e($settings['email'] ?? '') ?>"></label>
<label class="checkbox"><input type="checkbox" name="demo_mode" value="1" <?= !empty($settings['demo_mode']) ? 'checked' : '' ?>> แสดงแถบแจ้งข้อมูลตัวอย่าง (ปิดเมื่อข้อมูลพร้อมขายจริงแล้ว)</label>
<?php load_view('admin/contact_settings',['settings'=>$settings]); ?>
<?php load_view('admin/tracking_settings',['settings'=>$settings]); ?>
<?php load_view('admin/license_settings',['settings'=>$settings]); ?>
<?php load_view('admin/branding_settings',['settings'=>$settings]); ?>
<input type="hidden" name="form_complete" value="1"><button class="btn" type="submit">บันทึกข้อมูล</button>
</form></section>
