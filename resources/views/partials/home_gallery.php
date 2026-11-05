<?php
$galleryPhotos=$home_gallery ?? [];
$galleryInspiration=!$galleryPhotos;
if ($galleryInspiration) {
 foreach ([['hero','เหนือขอบฟ้า ยังมีเรื่องราว','THE MOUNTAINS'],['story','หยุดเวลาไว้ตรงนี้','SLOW MOMENTS'],['krabi','ระหว่างสายน้ำกับขุนเขา','OCEAN ESCAPE'],['nan','ออกไปพบโลกกว้าง','INTO THE WILD'],['chiangmai','ความงามในทุกรายละเอียด','LOCAL STORIES'],['khaoyai','ปล่อยใจไปกับธรรมชาติ','BREATHE AGAIN']] as $inspiration) {
  $galleryPhotos[]=['path'=>'public/images/'.$inspiration[0].'.jpg','caption'=>$inspiration[1],'location'=>$inspiration[2],'album_title'=>'แรงบันดาลใจสำหรับทริปต่อไป','category'=>'TRAVEL INSPIRATION','album_id'=>null,'photo_count'=>null];
 }
}
?>
<section class="home-popular-gallery populer-section" id="home-gallery" aria-labelledby="home-gallery-title" data-home-gallery>
 <div class="hpg-landscape" aria-hidden="true"></div><div class="hpg-grid-lines" aria-hidden="true"></div>
 <div class="container hpg-heading" data-hpg-reveal>
  <div class="hpg-overline"><span><i aria-hidden="true"></i> ROAMVENTURE / THE VISUAL JOURNAL</span><span>COLLECT MOMENTS, NOT THINGS <i class="material-icons" aria-hidden="true">north_east</i></span></div>
  <span class="hpg-script" aria-hidden="true">Wander a little.</span>
  <h2 id="home-gallery-title"><span>บางภาพ… ทำให้เรา</span><span>อยาก<span class="hpg-outline">ออกเดินทาง</span><i class="material-icons" aria-hidden="true">auto_awesome</i></span></h2>
  <p><?= $galleryInspiration ? 'เก็บภาพเหล่านี้ไว้เป็นแรงบันดาลใจ แล้วออกไปสร้างเรื่องราวของคุณ' : 'เสี้ยวเวลาจากอัลบั้มการเดินทาง ที่อยากชวนคุณเข้าไปสัมผัส' ?></p>
 </div>
 <div class="hpg-carousel" role="region" aria-roledescription="สไลเดอร์" aria-label="ภาพการเดินทาง เลื่อนเพื่อดูภาพเพิ่มเติม">
  <div class="hpg-track" id="home-gallery-track" tabindex="0" aria-label="แกลเลอรี่ ใช้ปุ่มลูกศรซ้ายขวาหรือปัดเพื่อเลื่อน">
   <?php foreach ($galleryPhotos as $i=>$photo): $photoTitle=$photo['caption'] ?: $photo['album_title']; $albumUrl=base_url().'gallery'.($photo['album_id'] ? '/'.(int)$photo['album_id'] : ''); ?>
   <article class="hpg-card" data-hpg-reveal style="--hpg-delay:<?= ($i%4)*90 ?>ms">
    <div class="hpg-card-top"><span class="hpg-chapter">FRAME / <?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span><i class="material-icons" aria-hidden="true">explore</i></div>
    <h3 class="hpg-place"><?= e($photo['location'] ?: ($photo['category'] ?: 'THE JOURNEY')) ?></h3>
    <button class="hpg-photo" type="button" data-hpg-photo data-src="<?= e(base_url().$photo['path']) ?>" data-caption="<?= e($photoTitle) ?>" data-album="<?= e($albumUrl) ?>" aria-label="<?= e('ขยายภาพ '.$photoTitle) ?>">
     <img src="<?= e(base_url().$photo['path']) ?>" alt="<?= e($photoTitle) ?>" width="600" height="750" loading="lazy" decoding="async">
     <span class="hpg-photo-line" aria-hidden="true"></span><span class="hpg-zoom" aria-hidden="true"><i class="material-icons">open_in_full</i></span><span class="hpg-photo-tag"><?= $galleryInspiration ? 'INSPIRATION' : 'FROM THE GALLERY' ?></span>
    </button>
    <div class="hpg-card-copy"><span class="hpg-category"><?= e($photo['category'] ?: 'TRAVEL DIARY') ?></span><h4><?= e($photoTitle) ?></h4><p class="hpg-album-name"><i class="material-icons" aria-hidden="true">collections_bookmark</i><span><?= e($photo['album_title']) ?></span></p>
     <a href="<?= e($albumUrl) ?>" class="hpg-card-link"><span class="hpg-card-count"><i class="material-icons" aria-hidden="true">photo_library</i><?= $galleryInspiration ? 'ภาพแรงบันดาลใจ' : (int)$photo['photo_count'].' ภาพในอัลบั้ม' ?></span><span class="hpg-card-action"><?= $galleryInspiration ? 'สำรวจแกลเลอรี่' : 'เปิดอัลบั้มนี้' ?> <i class="material-icons" aria-hidden="true">arrow_forward</i></span><span class="hpg-link-arrow" aria-hidden="true"><i class="material-icons">north_east</i></span></a>
    </div>
   </article>
   <?php endforeach ?>
  </div>
  <span class="hpg-cursor" aria-hidden="true"><i class="material-icons">drag_indicator</i><span>DRAG</span></span>
 </div>
 <div class="container hpg-footer">
  <div class="hpg-navigation" hidden><span class="hpg-counter"><b data-hpg-current>01</b><span>/ <?= str_pad(count($galleryPhotos),2,'0',STR_PAD_LEFT) ?></span></span><div class="hpg-progress" aria-hidden="true"><span></span></div><div class="hpg-arrows"><button type="button" data-hpg-prev aria-controls="home-gallery-track" aria-label="เลื่อนแกลเลอรี่ไปภาพก่อนหน้า"><i class="material-icons" aria-hidden="true">arrow_back</i></button><button type="button" data-hpg-next aria-controls="home-gallery-track" aria-label="เลื่อนแกลเลอรี่ไปภาพถัดไป"><i class="material-icons" aria-hidden="true">arrow_forward</i></button></div></div>
  <div class="hpg-bottom"><span><i class="material-icons" aria-hidden="true">swipe</i> ลากหรือปัด เพื่อค้นพบมุมมองใหม่</span><a class="outline-link" href="<?= base_url().'gallery' ?>">ชมแกลเลอรี่ทั้งหมด <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <span class="hpg-live" role="status" aria-live="polite" aria-atomic="true"></span>
 </div>
 <dialog class="hpg-lightbox" aria-label="ชมภาพการเดินทางเต็มจอ">
  <div class="hpg-lightbox-top"><span>ROAMVENTURE <small>THE VISUAL JOURNAL</small></span><span data-hpg-count aria-live="polite"></span><button type="button" data-hpg-close aria-label="ปิดภาพเต็มจอ"><i class="material-icons" aria-hidden="true">close</i></button></div>
  <div class="hpg-lightbox-stage"><button type="button" data-hpg-image-prev aria-label="ชมภาพก่อนหน้า"><i class="material-icons" aria-hidden="true">arrow_back</i></button><img alt=""><button type="button" data-hpg-image-next aria-label="ชมภาพถัดไป"><i class="material-icons" aria-hidden="true">arrow_forward</i></button></div>
  <div class="hpg-lightbox-caption"><p data-hpg-caption></p><a data-hpg-album href="<?= base_url().'gallery' ?>">เปิดอัลบั้ม <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <div class="hpg-thumbs" aria-label="เลือกภาพ"></div><p class="hpg-lightbox-help">← → เปลี่ยนภาพ · ปัดบนภาพเพื่อเลื่อน · Esc ปิด</p>
 </dialog>
</section>
