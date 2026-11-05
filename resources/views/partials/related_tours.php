<?php if (!empty($related_tours)): ?>
<?php $related_tours=array_slice($related_tours,0,8); $relatedSlider=count($related_tours)>3; ?>
<section class="section container related-tours" aria-labelledby="related-tours-heading">
 <div class="section-head">
  <div><span class="eyebrow">MORE JOURNEYS TO EXPLORE</span><h2 id="related-tours-heading">ทัวร์ที่เกี่ยวข้อง<span class="green-dot">.</span></h2><p>ค้นพบทริปอื่นที่น่าสนใจ สำหรับการเดินทางครั้งต่อไปของคุณ</p></div>
  <a class="outline-link" href="<?= base_url().($isPrivate ? 'packages' : 'tours') ?>">ดู<?= $isPrivate ? 'แพ็คเกจ' : 'ทัวร์' ?>ทั้งหมด <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a>
 </div>
 <?php if ($relatedSlider): ?>
 <div class="related-slider-controls" hidden>
  <span class="related-slider-status" aria-live="polite" aria-atomic="true"></span>
  <button type="button" data-related-prev aria-label="ทัวร์ก่อนหน้า" aria-controls="related-tour-list"><i class="material-icons" aria-hidden="true">arrow_back</i></button>
  <button type="button" data-related-next aria-label="ทัวร์ถัดไป" aria-controls="related-tour-list"><i class="material-icons" aria-hidden="true">arrow_forward</i></button>
 </div>
 <?php endif ?>
 <div id="related-tour-list" class="tour-grid<?= $relatedSlider ? ' related-slider' : '' ?>"<?= $relatedSlider ? ' tabindex="0" role="region" aria-label="เลื่อนดูทัวร์ที่เกี่ยวข้อง"' : '' ?>><?php foreach ($related_tours as $item) load_view('partials/card',['item'=>$item]); ?></div>
</section>
<?php endif ?>
