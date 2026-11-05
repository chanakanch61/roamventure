<?php
 $coverageList=static function(array $lines,array $services,$icon) {
  foreach ($lines as $line): ?><li><i class="material-icons" aria-hidden="true"><?= $icon ?></i><span><?= e($line) ?></span></li><?php endforeach;
  foreach ($services as $service): ?><li><i class="material-icons" aria-hidden="true"><?= $icon ?></i><span><b><?= e($service['label']) ?></b><?php if ($service['status']==='extra'): ?> <span class="pill draft"><?= service_label($service['status']) ?></span><?php endif ?><?php if ($service['details']!==''): ?><span class="preline trip-service-note"><?= e($service['details']) ?></span><?php endif ?></span></li><?php endforeach;
 };
 $hasIncluded=$coverage['included_lines'] || $coverage['included_services'];
 $hasExcluded=$coverage['excluded_lines'] || $coverage['excluded_services'];
?>
<?php if ($hasIncluded || $hasExcluded || $coverage['has_terms'] || $coverage['unspecified_services']): ?>
<section class="trip-coverage"><span class="eyebrow">PRICE &amp; TERMS</span><h2>ราคาและเงื่อนไข</h2>
<?php if ($hasIncluded || $hasExcluded): ?>
<div class="trip-accordion" id="services">
<?php if ($hasIncluded): ?><details open><summary><i class="material-icons" aria-hidden="true">check</i>ราคารวม</summary><div><ul class="trip-list"><?php $coverageList($coverage['included_lines'],$coverage['included_services'],'check'); ?></ul><p class="muted">อาหาร: ตรวจสอบมื้อที่รวมในโปรแกรมรายวันและรายละเอียดบริการด้านบน</p></div></details><?php endif ?>
<?php if ($hasExcluded): ?><details><summary><i class="material-icons" aria-hidden="true">remove</i>ราคาไม่รวม</summary><div><ul class="trip-list"><?php $coverageList($coverage['excluded_lines'],$coverage['excluded_services'],'remove'); ?></ul></div></details><?php endif ?>
</div>
<?php endif ?>
<?php if ($coverage['has_terms']): ?>
<div class="trip-accordion" id="conditions"><details><summary><i class="material-icons" aria-hidden="true">info</i>เงื่อนไขและข้อกำหนด</summary><div>
<?php foreach ($coverage['policies'] as $policyHeading=>$policyLines): ?><h3><?= e($policyHeading) ?></h3><ul class="trip-list"><?php foreach ($policyLines as $policyLine): ?><li><i class="material-icons" aria-hidden="true">chevron_right</i><span><?= e($policyLine) ?></span></li><?php endforeach ?></ul><?php endforeach ?>
<?php if ($coverage['terms']): ?><?php if ($coverage['policies']): ?><h3>เงื่อนไขอื่น</h3><?php endif ?><ul class="trip-list"><?php foreach ($coverage['terms'] as $termLine): ?><li><i class="material-icons" aria-hidden="true">chevron_right</i><span><?= e($termLine) ?></span></li><?php endforeach ?></ul><?php endif ?>
</div></details></div>
<?php endif ?>
<?php if ($coverage['unspecified_services']): ?><div class="service-grid"><?php foreach ($coverage['unspecified_services'] as $service): ?><div class="service-box"><span class="pill draft"><?= service_label($service['status']) ?></span><h3><?= e($service['label']) ?></h3><p class="preline"><?= e($service['details']) ?></p></div><?php endforeach ?></div><?php endif ?>
</section>
<?php endif ?>
