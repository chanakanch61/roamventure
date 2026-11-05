<?php
 $rc=$route_card;
 $rcType=$rc['listing_type'];
 $rcTile=$route_card_size==='tile';
 $rcNoFlight=array_keys($rc['flight_states'])===[0];
 if ($rcType==='private') {
  $rcPriceNote=trim(($rc['lowest_rate'] ? 'เมื่อไป '.people_range($rc['lowest_rate']['min'],$rc['lowest_rate']['max']).' คน' : '').($rcNoFlight ? ' · ไม่รวมตั๋ว' : ''),' ·');
  $rcMeta=$rc['next_date'] ? 'เลือกวันได้ '.thai_date($rc['next_date']).' – '.thai_date($rc['last_date']).($rc['people'] ? ' · '.people_range(min($rc['people']),max($rc['people'])).' คน' : '') : 'สอบถามช่วงเดินทาง';
 } else {
  $rcPriceNote=['flight'=>'รวมตั๋วแล้ว','land'=>'ไม่รวมตั๋ว'][$rcType] ?? '';
  $rcMeta=($rcType==='flight' && $rc['airlines'] ? implode(' / ',$rc['airlines']) : $rc['total'].' โปรแกรม').($rc['next_date'] ? ' · รอบถัดไป '.thai_date($rc['next_date']) : '');
 }
?>
<a class="route-card route-card-<?= e($route_card_size) ?>" href="<?= e($rc['url']) ?>" aria-label="ดูเส้นทาง<?= e($rc['name']) ?>">
 <img src="<?= e(tour_image($rc['cover'])) ?>" alt="<?= e($rc['cover_alt']) ?>" loading="lazy" width="900" height="600">
 <?php if ($route_card_badge): ?><span class="route-card-badge"><?= e(program_channels()[$rcType]['label']) ?></span><?php endif ?>
 <span class="route-card-body">
  <span class="route-card-text">
   <?php if ($route_card_size==='main'): ?><span class="route-card-country"><?php if ($rc['country_flag']): ?><img class="country-flag" src="<?= e(base_url().$rc['country_flag']) ?>" alt="" width="24" height="18" loading="lazy"><?php else: ?><i class="material-icons" aria-hidden="true">location_on</i><?php endif ?><?= e($rc['country_name']) ?></span><?php endif ?>
   <h3><?= e($rc['name']) ?></h3>
   <span class="route-card-meta"><?= e($rcMeta) ?></span>
  </span>
  <span class="route-card-action">
   <span class="route-card-price"><?php if ($rc['min_price']): ?><small>เริ่มต้น<?= $rcPriceNote!=='' ? ' '.e($rcPriceNote) : '' ?></small><b>฿<?= money($rc['min_price']) ?></b><?php else: ?><small><?= e($rcPriceNote) ?></small><b class="route-card-ask">สอบถามทีมงาน</b><?php endif ?></span>
   <?php if (!$rcTile): ?><span class="route-card-arrow" aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span><?php endif ?>
  </span>
 </span>
</a>
