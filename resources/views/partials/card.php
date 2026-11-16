<article class="tour-card">
 <a href="<?= (base_url() . ('tours/'.rawurlencode($item['code']))) ?>" class="card-image"><img src="<?= tour_image($item['cover']) ?>" alt="<?= e($item['title']) ?>" loading="lazy" width="680" height="500"><span class="card-badge"><?= e(program_channels()[tour_channel($item)]['badge']) ?></span><?php load_view('partials/card_discount_badge',['item'=>$item]); ?><span class="image-arrow"><i class="material-icons" aria-hidden="true">north_east</i></span></a>
 <div class="card-body">
  <div class="card-meta"><?php load_view('partials/card_destination',['item'=>$item]); ?><span><?= (int)$item['days'] ?> วัน <?= (int)$item['nights'] ?> คืน</span></div>
  <h3><a href="<?= (base_url() . ('tours/'.rawurlencode($item['code']))) ?>"><?= e($item['title']) ?></a></h3>
  <?php load_view('partials/card_route',['item'=>$item]); ?>
  <?php load_view('partials/card_airline',['item'=>$item]); ?>
  <p class="card-summary"><?= e($item['summary']) ?></p>
  <div class="card-services"><?php foreach (['hotel'=>'โรงแรม','transport'=>'รถส่วนตัว','guide'=>'ไกด์','photographer'=>'ช่างภาพ'] as $s=>$label): ?><?php if ($item[$s.'_status']==='included'): ?><span><i class="material-icons" aria-hidden="true">check</i> <?= $label ?><?= $s==='hotel' && !empty($item['hotel_stars']) && (int)$item['nights']>0 ? ' '.(int)$item['hotel_stars'].' ดาว' : '' ?></span><?php endif ?><?php endforeach ?></div>
  <?php load_view('partials/card_periods',['item'=>$item]); ?>
  <?php $cardWas=$item['min_price'] ? price_was($item['min_regular_price'] ?? null,$item['min_price']) : ''; ?>
  <div class="card-bottom<?= $cardWas ? ' has-discount' : '' ?>"><div><small><?= $item['min_price'] ? 'ราคาเริ่มต้น / ท่าน' : 'รอบเดินทาง' ?></small><?php if ($cardWas): ?><span class="card-price-was"><?= $cardWas ?><span class="price-save">ประหยัด ฿<?= money($item['min_regular_price']-$item['min_price']) ?></span></span><?php endif ?><strong><?= $item['min_price'] ? '฿'.money($item['min_price']) : 'สอบถามทีมงาน' ?></strong></div>
  <a class="card-detail-link" href="<?= base_url().'tours/'.rawurlencode($item['code']) ?>" aria-label="ดูรายละเอียดโปรแกรม <?= e($item['title']) ?>">ดูรายละเอียดโปรแกรม <i class="material-icons" aria-hidden="true">arrow_forward</i></a>
  </div>
  <?php if ($item['card_periods']): $cardMix=private_period_mix($item['card_periods']); ?><p class="fineprint"><?= $cardMix['anytime'] ? 'เดินทางได้ทุกวัน' : 'ราคาขึ้นอยู่กับรอบเดินทาง' ?> · <?= ($item['program_type'] ?? '')==='private' ? 'กรุ๊ปส่วนตัว · ราคาตามจำนวนคน' : 'จองได้ตั้งแต่ 1 ท่าน' ?></p><?php endif ?>
 </div>
</article>
