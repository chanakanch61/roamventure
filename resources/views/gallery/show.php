<article class="gx gx-album" data-gx>
 <header class="gx-cover">
  <div class="gx-cover-media" aria-hidden="true" data-gx-cover><img src="<?= e(base_url().$cover) ?>" alt="" fetchpriority="high" draggable="false"></div>
  <div class="container gx-cover-top"><a class="gx-back" href="<?= base_url().'gallery#albums' ?>"><i class="material-icons" aria-hidden="true">arrow_back</i>THE GALLERY</a><span><?= str_pad((string)count($photos),2,'0',STR_PAD_LEFT) ?> PHOTOGRAPHS</span></div>
  <div class="container gx-cover-copy">
   <span class="gx-overline"><?= e($album['category'] ?: 'TRAVEL DIARY') ?></span>
   <h1 class="gx-title"><span><span><?= e($album['title']) ?></span></span></h1>
   <?php if ($album['location']): ?><p class="gx-cover-place"><i class="material-icons" aria-hidden="true">place</i><?= e($album['location']) ?></p><?php endif ?>
  </div>
  <a class="gx-cover-down" href="#frames" aria-label="ไปยังภาพในอัลบั้ม"><i class="material-icons" aria-hidden="true">arrow_downward</i></a>
 </header>
 <section class="container gx-story">
  <span class="gx-overline">THE STORY<br>BEHIND THE FRAMES</span>
  <div><p class="gx-story-text"><?= nl2br(e($album['description'] ?: 'เก็บช่วงเวลาที่ประทับใจ แล้วออกเดินทางไปด้วยกันผ่านทุกภาพในอัลบั้มนี้')) ?></p>
   <dl class="gx-facts"><div><dt>สถานที่</dt><dd><?= e($album['location'] ?: '—') ?></dd></div><div><dt>หมวดหมู่</dt><dd><?= e($album['category'] ?: 'Travel diary') ?></dd></div><div><dt>จำนวนภาพ</dt><dd><?= count($photos) ?> ภาพ</dd></div></dl></div>
 </section>
 <section class="container gx-frames" id="frames" aria-label="ภาพในอัลบั้ม">
  <?php if (!$photos): ?>
  <div class="gx-empty"><i class="material-icons" aria-hidden="true">photo_library</i><h3>อัลบั้มนี้ยังไม่มีภาพ</h3><p>กลับมาชมอีกครั้งเร็ว ๆ นี้</p></div>
  <?php else: ?>
  <div class="gx-photos" data-gx-masonry>
   <?php foreach ($photos as $i=>$p): $caption=$p['caption'] ?: $album['title']; ?><figure class="gx-shot"><div><button type="button" data-gallery-photo data-src="<?= e(base_url().$p['path']) ?>" data-caption="<?= e($caption) ?>" aria-label="เปิดภาพเต็มจอ: <?= e($caption) ?>"><img src="<?= e(base_url().$p['path']) ?>" alt="<?= e($caption) ?>" loading="<?= $i<3 ? 'eager' : 'lazy' ?>" draggable="false"><span class="gx-shot-no" aria-hidden="true"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?> / <?= str_pad((string)count($photos),2,'0',STR_PAD_LEFT) ?></span><span class="gx-shot-zoom" aria-hidden="true"><i class="material-icons">open_in_full</i></span></button><?php if ($p['caption']): ?><figcaption><?= e($p['caption']) ?></figcaption><?php endif ?></div></figure><?php endforeach ?>
  </div>
  <?php endif ?>
 </section>
 <?php if ($next): ?>
 <a class="gx-next" href="<?= base_url().'gallery/'.$next['id'] ?>"><img src="<?= e(base_url().($next['cover'] ?: 'public/images/story.jpg')) ?>" alt="" loading="lazy"><span class="container gx-next-copy"><span class="gx-overline">NEXT CHAPTER <i class="material-icons" aria-hidden="true">east</i></span><strong><?= e($next['title']) ?></strong><?php if ($next['location']): ?><small><i class="material-icons" aria-hidden="true">place</i><?= e($next['location']) ?></small><?php endif ?></span><span class="gx-next-go" aria-hidden="true"><i class="material-icons">north_east</i></span></a>
 <?php else: ?>
 <footer class="container gx-album-end"><span class="gx-overline">END OF THIS CHAPTER</span><h2>Keep <em>exploring.</em></h2><a class="gx-pill" href="<?= base_url().'gallery#albums' ?>">กลับไปที่แกลเลอรี่ <i class="material-icons" aria-hidden="true">north_east</i></a></footer>
 <?php endif ?>
</article>
<?php load_view('gallery/lightbox'); ?>
