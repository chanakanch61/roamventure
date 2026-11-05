<!doctype html>
<html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive">
<title>เข้าสู่ระบบเพื่อเข้าชม · RoamVenture</title>
<link rel="stylesheet" href="<?= base_url().'public/site-access.css?v='.filemtime(base_path()."/".'public/site-access.css') ?>">
<link rel="stylesheet" href="<?= base_url().'public/travel-experience.css?v='.filemtime(base_path()."/".'public/travel-experience.css') ?>">
</head><body class="site-access-page travel-experience travel-access">
<main class="site-access-card">
<a class="site-access-brand" href="<?= base_url().'site-login' ?>" aria-label="RoamVenture">ROAM<span>VENTURE</span><span aria-hidden="true">↗</span></a>
<div class="site-access-lock" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="10" width="14" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg></div>
<p class="site-access-eyebrow">PRIVATE PREVIEW</p><h1>เข้าสู่ระบบเพื่อเข้าชม</h1>
<p class="site-access-intro">เว็บไซต์อยู่ระหว่างเตรียมความพร้อม<br>สำหรับทีมงานและผู้ที่ได้รับอนุญาตเท่านั้น</p>
<?php if ($error): ?><div class="site-access-error" role="alert"><?= e($error) ?></div><?php endif ?>
<form method="post" action="<?= base_url().'site-login' ?>">
<?= csrf_field() ?>
<label for="site-username">ชื่อผู้ใช้</label><input id="site-username" name="username" value="<?= e($username) ?>" maxlength="100" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
<label for="site-password">รหัสผ่าน</label><input id="site-password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
<button type="submit">เข้าสู่ระบบ <span aria-hidden="true">↗</span></button>
</form>
<p class="site-access-help">หากยังไม่มีบัญชี กรุณาติดต่อผู้ดูแลเว็บไซต์</p>
<a class="site-access-admin" href="<?= base_url().'admin/login' ?>">เข้าสู่ระบบผู้ดูแล</a>
</main><p class="site-access-copyright">© <?= date('Y') ?> RoamVenture</p>
</body></html>
