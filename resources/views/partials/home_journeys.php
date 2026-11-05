<?php $journeyFirst=array_key_first($home_channels); ?>
<section class="section container home-journeys" id="featured"<?= $home_channels ? ' data-home-journeys' : '' ?>>
 <div class="section-head"><div><span class="eyebrow">01 / THE SELECTED JOURNEYS</span><h2>ทริปที่ใช่ สำหรับคนของคุณ<span class="green-dot">.</span></h2><p>จุดหมายที่น่าไป กับประสบการณ์ที่อยากให้คุณได้ลอง</p></div><a href="<?= base_url() . 'tours' ?>" class="outline-link">ดูโปรแกรมทัวร์ <span><i class="material-icons" aria-hidden="true">north_east</i></span></a></div>
 <?php if ($home_channels): ?>
 <div class="journey-layout">
  <div class="journey-side">
   <div class="journey-tabs" role="tablist" aria-label="ประเภทโปรแกรม">
    <?php foreach ($home_channels as $key=>$channel): $isFirst=$key===$journeyFirst; ?>
    <button class="journey-tab" type="button" role="tab" id="journey-tab-<?= e($key) ?>" aria-controls="journey-panel-<?= e($key) ?>" aria-selected="<?= $isFirst ? 'true' : 'false' ?>" tabindex="<?= $isFirst ? '0' : '-1' ?>" data-blurb="<?= e($channel['blurb']) ?>"><span class="journey-tab-top"><span class="journey-tab-eyebrow"><?= e($channel['eyebrow']) ?></span><span class="journey-tab-count"><?= (int)$channel['total'] ?><span class="journey-tab-unit"> โปรแกรม</span></span></span><span class="journey-tab-label"><?= e($channel['label']) ?></span><span class="journey-tab-blurb"><?= e($channel['blurb']) ?></span></button>
    <?php endforeach ?>
   </div>
   <div class="journey-meta"><p class="journey-blurb" data-journey-blurb><?= e($home_channels[$journeyFirst]['blurb']) ?></p><div class="journey-controls" data-journey-controls hidden><span class="journey-status" data-journey-status aria-atomic="true"></span><span class="journey-arrows"><button type="button" data-journey-prev aria-label="ทัวร์ก่อนหน้า"><i class="material-icons" aria-hidden="true">chevron_left</i></button><button type="button" data-journey-next aria-label="ทัวร์ถัดไป"><i class="material-icons" aria-hidden="true">chevron_right</i></button></span></div></div>
   <?php foreach ($home_channels as $key=>$channel): ?>
   <a class="btn full journey-all" href="<?= e(base_url().$channel['path']) ?>" data-journey-all<?= $key===$journeyFirst ? '' : ' hidden' ?>>ดู<?= e($channel['label']) ?>ทั้งหมด <span><i class="material-icons" aria-hidden="true">north_east</i></span></a>
   <?php endforeach ?>
  </div>
  <div class="journey-stage">
   <?php foreach ($home_channels as $key=>$channel): ?>
   <div class="journey-panel" id="journey-panel-<?= e($key) ?>" role="tabpanel" aria-labelledby="journey-tab-<?= e($key) ?>"<?= $key===$journeyFirst ? '' : ' hidden' ?>>
    <div class="journey-track" tabindex="0" role="region" aria-label="เลื่อนดู<?= e($channel['label']) ?>"><?php foreach ($channel['tours'] as $item) load_view('partials/card',['item'=>$item,'compact'=>true]); ?><?php if (count($channel['tours'])===1): ?><div class="journey-soon"><p>กำลังเตรียมทริปใหม่ให้คุณ</p><small>กลับมาพบกันเร็ว ๆ นี้</small></div><?php endif ?></div>
   </div>
   <?php endforeach ?>
  </div>
 </div>
 <?php else: ?>
 <div class="empty">กำลังเตรียมทริปใหม่ให้คุณ กลับมาพบกันเร็ว ๆ นี้</div>
 <?php endif ?>
</section>
