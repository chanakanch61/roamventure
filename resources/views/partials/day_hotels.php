<?php $dayHotels=day_hotels($day); if ($dayHotels): $hotelTotal=count($dayHotels); ?>
<div class="day-hotels">
 <div class="day-hotels-head"><b>โรงแรมที่พักคืนนี้</b><?php if ($hotelTotal>1): ?><small>พักโรงแรมที่ 1 เป็นหลัก หากไม่ได้จะเปลี่ยนเป็นโรงแรมถัดไปตามลำดับ</small><?php endif ?></div>
 <?php foreach ($dayHotels as $hotelIndex=>$hotel): $hotelImages=$hotel['images']; $hotelCount=count($hotelImages); ?>
 <article class="day-hotel<?= $hotelIndex ? ' is-fallback' : '' ?>">
  <h4><?php if ($hotelTotal>1): ?><span><?= $hotelIndex ? 'โรงแรมที่ '.($hotelIndex+1).' · สำรอง' : 'โรงแรมที่ 1 · หลัก' ?></span><?php endif ?><?= e($hotel['name']) ?></h4>
  <?php if ($hotelCount): ?><div class="day-hotel-strip" data-photo-group="<?= e(json_encode(array_values($hotelPhotoIndexes[$i][$hotelIndex]))) ?>">
  <?php foreach (array_slice($hotelImages,0,3) as $imageIndex=>$path): ?><a href="<?= tour_image($path) ?>" data-photo="<?= $hotelPhotoIndexes[$i][$hotelIndex][$imageIndex] ?>" aria-label="เปิดภาพ <?= e($hotel['name']) ?> (<?= $imageIndex+1 ?> จาก <?= $hotelCount ?>)"><img src="<?= tour_image($path) ?>" alt="<?= e($hotel['name']) ?>" loading="lazy" width="480" height="320"><?php if ($imageIndex===2 && $hotelCount>3): ?><span class="day-hotel-more">+<?= $hotelCount-3 ?></span><?php endif ?></a><?php endforeach ?>
  </div><?php endif ?>
 </article>
 <?php endforeach ?>
</div>
<?php endif ?>
