<?php $vehicleTypes=\App\Support\VehicleOptions::types(); ?>
<?php if ($vehicleRows): ?>
<section id="vehicles" class="trip-vehicles"><span class="eyebrow">GETTING AROUND</span><h2>รถที่เหมาะกับจำนวนผู้เดินทาง</h2><p><?= $isPrivate ? 'ทริปส่วนตัวของคุณใช้รถตามจำนวนคนในกรุ๊ป' : 'รถที่ใช้ขึ้นกับจำนวนผู้เดินทางทั้งหมดของรอบ' ?></p>
 <ul class="vehicle-list"<?= $vehiclePhotoIndexes ? ' data-photo-group="'.e(json_encode(array_values($vehiclePhotoIndexes))).'"' : '' ?>><?php foreach ($vehicleRows as $vehicleIndex=>$vehicle): $vehicleType=$vehicleTypes[$vehicle['type']] ?? null; if (!$vehicleType) continue; $vehicleLabel=\App\Support\VehicleOptions::label($vehicle); ?>
  <li><?php if (!empty($vehicle['image'])): ?><a href="<?= e(tour_image($vehicle['image'])) ?>" data-photo="<?= (int)$vehiclePhotoIndexes[$vehicleIndex] ?>" aria-label="เปิดภาพ <?= e($vehicleLabel) ?>"><img src="<?= e(tour_image($vehicle['image'])) ?>" alt="" width="160" height="90" loading="lazy"></a><?php else: ?><img src="<?= e(base_url().$vehicleType['image']) ?>" alt="" width="160" height="90" loading="lazy"><?php endif ?><b><?= people_range($vehicle['min'],$vehicle['max']) ?> คน</b><span><?= e($vehicleLabel) ?></span></li>
 <?php endforeach ?></ul>
 <p class="fineprint">รุ่นและสีของรถจริงอาจต่างจากภาพ</p>
</section>
<?php endif ?>
