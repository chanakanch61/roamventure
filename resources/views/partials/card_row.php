<article class="tour-card tour-card-row">
 <a href="<?= (base_url() . ('tours/'.rawurlencode($item['code']))) ?>" class="card-image">
  <img src="<?= tour_image($item['cover']) ?>" alt="<?= e($item['title']) ?>" loading="lazy" width="680" height="750">
  <span class="card-badge"><?= e(program_channels()[tour_channel($item)]['badge']) ?></span><span class="image-arrow"><i class="material-icons" aria-hidden="true">north_east</i></span>
 </a>
 <div class="card-body row-tour-info">
  <div class="card-meta"><?php load_view('partials/card_destination',['item'=>$item]); ?><span><?= (int)$item['days'] ?> วัน <?= (int)$item['nights'] ?> คืน</span></div>
  <h3><a href="<?= (base_url() . ('tours/'.rawurlencode($item['code']))) ?>"><?= e($item['title']) ?></a></h3>
  <?php load_view('partials/card_route',['item'=>$item]); ?>
  <?php load_view('partials/card_airline',['item'=>$item]); ?>
  <p class="card-summary"><?= e($item['summary']) ?></p>
  <?php $highlights=array_slice(array_values(array_filter(preg_split('/\R/u',$item['highlights']),function($line){return trim($line)!=='';})),0,3); ?>
  <?php $itemPrivate=($item['program_type'] ?? '')==='private'; $itemLowest=$itemPrivate ? private_lowest_rate($item['card_periods']) : null; ?>
  <?php if ($itemPrivate && $item['card_periods']): ?><?php load_view('partials/private_rate_table',['item'=>$item]); ?><?php elseif ($highlights): ?><ul class="row-highlights"><?php foreach ($highlights as $highlight): ?><li><?= e($highlight) ?></li><?php endforeach ?></ul><?php endif ?>
  <div class="card-services"><?php foreach (['hotel'=>'โรงแรม','transport'=>'รถส่วนตัว','guide'=>'ไกด์','photographer'=>'ช่างภาพ'] as $s=>$label): ?><?php if ($item[$s.'_status']==='included'): ?><span><i class="material-icons" aria-hidden="true">check</i> <?= $label ?></span><?php endif ?><?php endforeach ?></div>
  <p class="row-tour-note"><?= ($item['program_type'] ?? '')==='private' ? 'กรุ๊ปส่วนตัว · ราคาตามจำนวนคน' : 'จองได้ตั้งแต่ 1 คน' ?> <span>·</span> รหัส <?= e($item['code']) ?></p>
 </div>
 <div class="row-tour-booking">
  <div class="row-tour-price"><span><?= $item['min_price'] ? 'ราคาเริ่มต้น / คน' : 'รอบเดินทาง' ?></span><strong><?= $item['min_price'] ? '฿'.money($item['min_price']) : 'สอบถามทีมงาน' ?></strong><?php if ($itemLowest): ?><small><?= e($itemLowest['level']) ?> เมื่อไป <?= people_range($itemLowest['min'],$itemLowest['max']) ?> คน</small><?php endif ?><small class="row-tour-flight"><?= e(flight_note($item)) ?></small></div>
  <?php load_view('partials/card_periods',['item'=>$item]); ?>
  <p class="fineprint"><?= $item['card_periods'] ? 'ราคาขึ้นอยู่กับรอบเดินทาง · เลือกพีเรียดเพื่อจอง' : 'ติดต่อทีมงานเพื่อสอบถามรอบใหม่' ?></p>
  <a class="btn full" href="<?= (base_url() . ('tours/'.rawurlencode($item['code']))) ?>" aria-label="ดูโปรแกรมและจอง <?= e($item['title']) ?>"><?= $itemPrivate ? 'เลือกวันและจำนวนคน' : 'ดูโปรแกรม / จองทัวร์' ?> <span><i class="material-icons" aria-hidden="true">north_east</i></span></a>
 </div>
</article>
