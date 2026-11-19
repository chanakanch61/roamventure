<?php
use App\Repositories\GroupTourRepository as Groups;
$months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$when = $trip['travel_date'] ? $months[(int) date('n', strtotime($trip['travel_date']))].' '.(date('Y', strtotime($trip['travel_date'])) + 543) : '';
$paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', (string) $trip['story']))));
$highlights = text_lines($trip['highlights']);
$requestUrl = base_url().'grouptour?ref='.rawurlencode($trip['slug']).'#request';
?>
<div class="rv-company gt-page gt-detail">
 <section class="rv-company-hero gt-detail-hero">
  <img class="rv-hero-photo" src="<?= e(base_url().$trip['image']) ?>" alt="<?= e($trip['title']) ?>" width="1400" height="1000" fetchpriority="high">
  <div class="rv-hero-shade"></div>
  <div class="container rv-hero-inner">
   <nav class="rv-breadcrumb" aria-label="เส้นทางหน้าเว็บ"><a href="<?= base_url() ?>">หน้าแรก</a><span aria-hidden="true">/</span><a href="<?= base_url() ?>grouptour">รับจัดกรุ๊ปเหมา</a><span aria-hidden="true">/</span><a href="<?= base_url() ?>grouptour#past-groups">ผลงาน</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($trip['title']) ?></span></nav>
   <div class="rv-kicker"><span></span> PAST GROUP · <?= e(mb_strtoupper(Groups::group_type_short($trip['group_type']))) ?></div>
   <h1 class="gt-detail-title"><?= e($trip['title']) ?></h1>
   <?php if ($trip['summary']): ?><p class="rv-hero-description gt-detail-summary"><?= e($trip['summary']) ?></p><?php endif ?>
   <dl class="gt-detail-facts">
    <div><dt>จุดหมาย</dt><dd><?= e($trip['destination']) ?></dd></div>
    <div><dt>ประเภทกรุ๊ป</dt><dd><?= e($type_label) ?></dd></div>
    <?php if ($when): ?><div><dt>เดินทาง</dt><dd><time datetime="<?= e(substr($trip['travel_date'], 0, 7)) ?>"><?= e($when) ?></time></dd></div><?php endif ?>
    <?php if ($trip['days']): ?><div><dt>ระยะเวลา</dt><dd><?= (int) $trip['days'] ?> วัน</dd></div><?php endif ?>
    <?php if ($trip['travelers']): ?><div><dt>ผู้เดินทาง</dt><dd><?= (int) $trip['travelers'] ?> ท่าน</dd></div><?php endif ?>
    <?php if ($trip['organization']): ?><div><dt>ลูกค้า</dt><dd><?= e($trip['organization']) ?></dd></div><?php endif ?>
   </dl>
  </div>
 </section>

 <div class="container rv-section gt-detail-body">
  <article class="gt-story">
   <?php if ($paragraphs): ?><h2>เรื่องราวของทริปนี้</h2><?php foreach ($paragraphs as $p): ?><p><?= nl2br(e($p)) ?></p><?php endforeach ?><?php endif ?>
   <?php if ($highlights): ?><h2>ไฮไลต์ที่จัดให้กรุ๊ปนี้</h2><ul class="gt-highlights"><?php foreach ($highlights as $line): ?><li><i class="material-icons" aria-hidden="true">check</i><span><?= e($line) ?></span></li><?php endforeach ?></ul><?php endif ?>
   <?php if (trim((string) $trip['testimonial']) !== ''): ?><figure class="gt-quote"><blockquote><p><?= nl2br(e($trip['testimonial'])) ?></p></blockquote><?php if ($trip['testimonial_by']): ?><figcaption>— <?= e($trip['testimonial_by']) ?></figcaption><?php endif ?></figure><?php endif ?>
   <?php if ($photos): ?>
   <h2>ภาพจากทริป</h2>
   <div class="gt-photos"><?php foreach (array_slice($photos, 0, 9) as $photo): ?><figure><img src="<?= e(base_url().$photo['path']) ?>" alt="<?= e($photo['caption'] ?: $trip['title']) ?>" loading="lazy"><?php if ($photo['caption']): ?><figcaption><?= e($photo['caption']) ?></figcaption><?php endif ?></figure><?php endforeach ?></div>
   <a class="rv-link" href="<?= e(base_url().'gallery/'.(int) $trip['album_id']) ?>">ดูภาพทั้งหมด <?= count($photos) ?> ภาพในแกลเลอรี่ <i class="material-icons" aria-hidden="true">north_east</i></a>
   <?php endif ?>
  </article>
  <aside class="gt-detail-aside">
   <div class="gt-aside-card">
    <span class="rv-kicker">PLAN A TRIP LIKE THIS</span>
    <h2>อยากจัดกรุ๊ป<br>แบบนี้บ้าง?</h2>
    <p>ส่งรายละเอียดกรุ๊ปของคุณ ทีมงานจะปรับโปรแกรมให้ตรงกับจำนวนคน วันเดินทาง และงบประมาณ</p>
    <a class="btn" href="<?= e($requestUrl) ?>">ขอใบเสนอราคา <i class="material-icons" aria-hidden="true">north_east</i></a>
   </div>
  </aside>
 </div>

 <?php if ($others): ?>
 <section class="container rv-section gt-past gt-others" aria-labelledby="gt-others-heading">
  <div class="rv-section-heading"><div><span class="rv-kicker">MORE PAST GROUPS</span><h2 id="gt-others-heading">ผลงานกรุ๊ปอื่น ๆ</h2></div><a class="rv-link" href="<?= base_url() ?>grouptour#past-groups">ดูผลงานทั้งหมด <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <div class="gt-trip-grid">
   <?php foreach ($others as $other): ?>
   <article class="gt-trip"><a href="<?= e(base_url().'grouptour/'.rawurlencode($other['slug'])) ?>" class="gt-trip-media"><img src="<?= e(base_url().$other['image']) ?>" alt="<?= e($other['title']) ?>" loading="lazy" width="640" height="480"><span class="gt-badge"><?= e(Groups::group_type_short($other['group_type'])) ?></span></a><div class="gt-trip-body"><ul class="gt-trip-meta"><li><i class="material-icons" aria-hidden="true">place</i><?= e($other['destination']) ?></li><?php if ($other['travelers']): ?><li><i class="material-icons" aria-hidden="true">group</i><?= (int) $other['travelers'] ?> ท่าน</li><?php endif ?></ul><h3><a href="<?= e(base_url().'grouptour/'.rawurlencode($other['slug'])) ?>"><?= e($other['title']) ?></a></h3></div></article>
   <?php endforeach ?>
  </div>
 </section>
 <?php endif ?>
</div>
