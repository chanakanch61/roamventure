<?php $dailyPhotos=day_photos($day); if ($dailyPhotos): $total=count($dailyPhotos); ?>
<div class="day-moments" data-photo-group="<?= e(json_encode(array_values($dayPhotoIndexes[$i]))) ?>">
 <div class="day-moments-heading"><span><b>DAY <?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></b> ภาพการเดินทาง</span><span><?= $total ?> ภาพ</span></div>
 <div class="day-mosaic day-mosaic-<?= min($total,3) ?>">
 <?php foreach (array_slice($dailyPhotos,0,3) as $photoIndex=>$photo): ?>
 <a class="day-mosaic-photo" href="<?= tour_image($photo['path']) ?>" data-photo="<?= $dayPhotoIndexes[$i][$photoIndex] ?>" aria-label="เปิดภาพ <?= e($photo['caption'] ?: $day['title']) ?> (<?= $photoIndex+1 ?> จาก <?= $total ?>)">
  <img src="<?= tour_image($photo['path']) ?>" alt="<?= e($photo['caption'] ?: $day['title']) ?>" loading="lazy" width="960" height="600">
  <?php if ($photoIndex===0): ?><span class="day-mosaic-story"><small>THE JOURNEY · <?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></small><?php if ($photo['caption']): ?><strong><?= e($photo['caption']) ?></strong><?php endif ?><span>สำรวจภาพ <b><i class="material-icons" aria-hidden="true">north_east</i></b></span></span>
  <?php elseif ($photoIndex===2 && $total>3): ?><span class="day-mosaic-more"><b>+<?= $total-3 ?></b><span>ดูครบ <?= $total ?> รูป <i class="material-icons" aria-hidden="true">north_east</i></span></span><?php else: ?><span class="day-mosaic-zoom" aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span><?php endif ?>
 </a>
 <?php endforeach ?>
 </div>
 <div class="day-moments-footer"><span>แต่ละภาพ อีกมุมของการเดินทาง</span><button type="button" data-photo="<?= $dayPhotoIndexes[$i][0] ?>">ดูภาพทั้งหมด <b><i class="material-icons" aria-hidden="true">north_east</i></b></button></div>
</div>
<?php endif ?>
