<?php $filtered=$filters['q'] || $filters['category'];$link=function($category) use ($filters) { $q=http_build_query(array_filter(['category'=>$category,'q'=>$filters['q']]));return base_url().'gallery'.($q!=='' ? '?'.$q : '').'#albums'; };
 $places=$stats['places'] ?: ['Travel stories','Moments','Places','People','Journeys'];while (count($places)<6) $places=array_merge($places,$places); ?>
<div class="gx gx-index" data-gx>
 <header class="gx-hero">
  <div class="gx-collage" aria-hidden="true" data-gx-collage><?php foreach ($collage as $i=>$path): ?><span class="gx-float gx-float-<?= $i+1 ?>"><img src="<?= e(base_url().$path) ?>" alt="" <?= $i<2 ? 'fetchpriority="high"' : '' ?> draggable="false"></span><?php endforeach ?></div>
  <div class="container gx-hero-copy">
   <div class="gx-kicker"><span><i></i>ROAMVENTURE / VISUAL JOURNAL</span><span>01 / THE GALLERY</span></div>
   <h1 class="gx-title"><span><span>THE</span></span><span><span><em>GALLERY</em>.</span></span></h1>
   <div class="gx-hero-foot">
    <p>ทุกภาพคือเรื่องราว ที่เก็บไว้ระหว่างการเดินทาง<br>เลือกอัลบั้มที่ชอบ แล้วออกเดินทางไปด้วยกันผ่านทุกภาพ</p>
    <dl class="gx-stats"><div><dt>อัลบั้ม</dt><dd><?= str_pad((string)$stats['albums'],2,'0',STR_PAD_LEFT) ?></dd></div><div><dt>ภาพถ่าย</dt><dd><?= str_pad((string)$stats['photos'],2,'0',STR_PAD_LEFT) ?></dd></div><div><dt>จุดหมาย</dt><dd><?= str_pad((string)count($stats['places']),2,'0',STR_PAD_LEFT) ?></dd></div></dl>
    <a class="gx-scroll" href="#albums" aria-label="ไปยังอัลบั้มทั้งหมด"><svg viewBox="0 0 100 100" aria-hidden="true"><defs><path id="gx-ring" d="M50 50m-38 0a38 38 0 1 1 76 0a38 38 0 1 1-76 0"/></defs><text><textPath href="#gx-ring">SCROLL TO EXPLORE · SCROLL TO EXPLORE · </textPath></text></svg><i class="material-icons" aria-hidden="true">arrow_downward</i></a>
   </div>
  </div>
 </header>
 <div class="gx-marquee" aria-hidden="true"><div><?php for ($r=0;$r<2;$r++): ?><span><?php foreach ($places as $j=>$place): ?><b<?= $j%2 ? ' class="is-outline"' : '' ?>><?= e($place) ?></b><i>✳</i><?php endforeach ?></span><?php endfor ?></div></div>
 <section class="container gx-collection" id="albums">
  <div class="gx-section-head"><div><span class="gx-overline">02 / THE COLLECTION</span><h2>Stories <em>in frames.</em></h2></div><span class="gx-total"><?= str_pad((string)$total,2,'0',STR_PAD_LEFT) ?><small>อัลบั้ม<?= $filtered ? 'ที่พบ' : '' ?></small></span></div>
  <div class="gx-bar">
   <nav class="gx-chips" aria-label="หมวดหมู่อัลบั้ม"><a href="<?= e($link('')) ?>" <?= $filters['category']==='' ? 'aria-current="page"' : '' ?>>ทั้งหมด</a><?php foreach ($categories as $c): ?><a href="<?= e($link($c)) ?>" <?= $filters['category']===$c ? 'aria-current="page"' : '' ?>><?= e($c) ?></a><?php endforeach ?></nav>
   <form class="gx-search" method="get" action="<?= base_url().'gallery#albums' ?>" role="search"><?php if ($filters['category']): ?><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><?php endif ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="ค้นหาอัลบั้ม หรือสถานที่…" maxlength="180" aria-label="ค้นหาอัลบั้ม"><button type="submit" aria-label="ค้นหา"><i class="material-icons" aria-hidden="true">search</i></button></form>
  </div>
  <?php if ($filtered): ?><p class="gx-result">พบ <?= (int)$total ?> อัลบั้ม<?= $filters['q'] ? ' สำหรับ “'.e($filters['q']).'”' : '' ?> · <a href="<?= base_url().'gallery#albums' ?>">ล้างตัวกรอง</a></p><?php endif ?>
  <?php if (!$albums): ?>
  <div class="gx-empty"><i class="material-icons" aria-hidden="true">photo_library</i><h3><?= $filtered ? 'ยังไม่พบอัลบั้มที่คุณค้นหา' : 'เตรียมพบกับบันทึกการเดินทางบทใหม่' ?></h3><p><?= $filtered ? 'ลองเปลี่ยนคำค้น หรือเลือกดูทุกหมวดหมู่' : 'อัลบั้มภาพจากทริปของเรากำลังจะมาเร็ว ๆ นี้' ?></p><a class="gx-pill" href="<?= base_url().($filtered ? 'gallery#albums' : 'tours') ?>"><?= $filtered ? 'ดูทุกอัลบั้ม' : 'สำรวจการเดินทาง' ?> <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <?php else: ?>
  <div class="gx-masonry" data-count="<?= min(3,count($albums)) ?>">
   <?php foreach ($albums as $i=>$a): $frames=$a['previews'] ?: ['public/images/story.jpg']; ?><a class="gx-tile" href="<?= base_url().'gallery/'.$a['id'] ?>" data-gx-tile><span class="gx-tile-frames"><?php foreach ($frames as $f=>$path): ?><img src="<?= e(base_url().$path) ?>" alt="" loading="lazy" <?= $f===0 ? 'class="is-active"' : '' ?> draggable="false"><?php endforeach ?></span><?php if (count($frames)>1): ?><span class="gx-tile-bars" aria-hidden="true"><?php foreach ($frames as $f=>$path): ?><i></i><?php endforeach ?></span><?php endif ?><span class="gx-tile-no" aria-hidden="true"><?= str_pad((string)(($page-1)*12+$i+1),2,'0',STR_PAD_LEFT) ?></span><span class="gx-tile-go" aria-hidden="true"><i class="material-icons">north_east</i></span><span class="gx-tile-body"><small><?= e($a['category'] ?: 'Travel diary') ?> · <?= (int)$a['photo_count'] ?> ภาพ</small><strong><?= e($a['title']) ?></strong><?php if ($a['location']): ?><span class="gx-tile-place"><i class="material-icons" aria-hidden="true">place</i><?= e($a['location']) ?></span><?php endif ?></span></a><?php endforeach ?>
  </div>
  <?php endif ?>
  <?php load_view('gallery/pagination',['base'=>'gallery']); ?>
 </section>
</div>
