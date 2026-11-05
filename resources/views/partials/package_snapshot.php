<?php $package=json_decode($booking['package_snapshot'] ?? 'null',true); if ($package): ?>
<div class="notice"><b>แพ็คเกจส่วนตัว · <?= e($package['level']) ?></b><p>กรุ๊ปเฉพาะการจองนี้ · ราคาเรต <?= (int)$package['min'] ?>–<?= (int)$package['max'] ?> คน</p><?php if ($package['details']): ?><p class="preline"><?= e($package['details']) ?></p><?php endif ?></div>
<?php endif ?>
