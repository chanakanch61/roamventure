<?php if (!empty($home_posts)): ?>
<section class="home-journal container" id="home-journal" aria-labelledby="home-journal-title">
 <div class="home-journal-heading"><div><span class="eyebrow">06 / THE JOURNAL</span><h2 id="home-journal-title">เรื่องเล่าระหว่างทาง<span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></h2><p>เก็บไอเดียดี ๆ ก่อนออกไปสร้างเรื่องราวของคุณ</p></div><a class="outline-link" href="<?= base_url().'blog' ?>">ดูบทความทั้งหมด <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a></div>
 <div class="home-journal-track" tabindex="0" role="region" aria-label="บทความล่าสุด เลื่อนเพื่อดูเรื่องถัดไป">
 <?php foreach ($home_posts as $index=>$post): $url=base_url().'blog/'.rawurlencode($post['slug']); ?>
 <article class="home-journal-card">
  <a class="home-journal-photo" href="<?= e($url) ?>" aria-label="<?= e('อ่าน '.$post['title']) ?>"><img src="<?= e(tour_image($post['cover'])) ?>" alt="<?= e($post['cover_alt']) ?>" loading="lazy" width="720" height="540"><span class="home-journal-number" aria-hidden="true">FIELD NOTE / <?= sprintf('%02d',$index+1) ?></span><span class="home-journal-arrow" aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a>
  <div class="home-journal-meta"><span><?= e($post['category_name'] ?: 'เรื่องเล่าการเดินทาง') ?></span><time datetime="<?= e(date('Y-m-d',strtotime($post['published_at']))) ?>"><?= thai_date($post['published_at']) ?></time></div>
  <h3><a href="<?= e($url) ?>"><?= e($post['title']) ?></a></h3>
  <?php if ($post['excerpt']): ?><p><?= e($post['excerpt']) ?></p><?php endif ?>
  <a class="home-journal-read" href="<?= e($url) ?>" aria-label="<?= e('อ่านเรื่องราว '.$post['title']) ?>">อ่านเรื่องราว <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a>
 </article>
 <?php endforeach ?>
 </div>
 <?php if (count($home_posts)>1): ?><p class="home-journal-hint">เลื่อนเพื่ออ่านเรื่องราวถัดไป <span aria-hidden="true"><i class="material-icons" aria-hidden="true">arrow_forward</i></span></p><?php endif ?>
</section>
<?php endif ?>
