<?php
 $filtered=!empty($filters['q']) || !empty($filters['category']);
 // Page one of the unfiltered journal features the newest story as its cover; the grid still lists every story so "ทั้งหมด" is never empty.
 $lead=!$filtered && $page===1 && $posts ? $posts[0] : null;
 $leadUrl=$lead ? base_url().'blog/'.rawurlencode($lead['slug']) : '';
?>
<div class="rv-journal journal-index">
<section class="journal-cover<?= $lead ? ' has-story' : '' ?>">
 <img class="journal-cover-image" src="<?= e($lead && $lead['cover'] ? tour_image($lead['cover']) : base_url().'public/images/hero.jpg') ?>" alt="<?= e($lead['cover_alt'] ?? '') ?>" fetchpriority="high">
 <div class="journal-cover-shade" aria-hidden="true"></div>
 <div class="container journal-cover-inner">
  <div class="journal-cover-head"><span class="eyebrow light">THE JOURNAL / บทความ</span><h1>เรื่องเล่าระหว่างทาง</h1><?php if (!$lead): ?><p>แรงบันดาลใจ จุดหมาย และเคล็ดลับเตรียมตัวก่อนออกเดินทางครั้งถัดไป</p><?php endif ?></div>
  <?php if ($lead): ?><article class="journal-cover-story"><div class="journal-cover-meta"><span class="journal-chip"><?= e($lead['category_name'] ?: 'เรื่องเล่าการเดินทาง') ?></span><time datetime="<?= e(date('Y-m-d',strtotime($lead['published_at']))) ?>"><?= thai_date($lead['published_at']) ?></time><span class="journal-dot" aria-hidden="true"></span><span><i class="material-icons" aria-hidden="true">schedule</i> อ่าน <?= reading_minutes($lead['content']) ?> นาที</span></div><h2><a href="<?= e($leadUrl) ?>"><?= e($lead['title']) ?></a></h2><p><?= e($lead['excerpt'] ?: mb_substr(html_entity_decode(strip_tags($lead['content']),ENT_QUOTES,'UTF-8'),0,160)) ?></p><a class="journal-cover-read" href="<?= e($leadUrl) ?>" aria-label="<?= e('อ่านเรื่องราว '.$lead['title']) ?>">อ่านเรื่องราว <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a></article><?php endif ?>
 </div>
</section>
<div class="journal-body">
 <div class="journal-toolbar-wrap"><div class="container journal-toolbar">
  <nav class="journal-categories" aria-label="หมวดหมู่บทความ"><a class="<?= empty($filters['category']) ? 'active' : '' ?>" href="<?= e(base_url().'blog?'.http_build_query(['q'=>$filters['q']])) ?>#journal-stories" <?= empty($filters['category']) ? 'aria-current="page"' : '' ?>>ทั้งหมด <small><?= (int)$all_total ?></small></a><?php foreach ($categories as $c): $active=(string)$filters['category']===(string)$c['id']; ?><a class="<?= $active ? 'active' : '' ?>" href="<?= e(base_url().'blog?'.http_build_query(['category'=>$c['id'],'q'=>$filters['q']])) ?>#journal-stories" <?= $active ? 'aria-current="page"' : '' ?>><?= e($c['name']) ?> <small><?= (int)$c['total'] ?></small></a><?php endforeach ?></nav>
  <form method="get" class="journal-search" role="search" action="<?= base_url().'blog' ?>#journal-stories"><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><i class="material-icons" aria-hidden="true">search</i><label class="journal-sr" for="journal-query">ค้นหาบทความ</label><input id="journal-query" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="ค้นหาเรื่องราว จุดหมาย หรือแท็ก" maxlength="200" enterkeyhint="search" aria-keyshortcuts="/"><kbd aria-hidden="true">/</kbd><button type="submit">ค้นหา</button></form>
 </div></div>
 <section class="container journal-stories" id="journal-stories">
  <?php if ($filtered || $posts): ?><div class="journal-stories-head"><div><span class="eyebrow"><?= $filtered ? 'SEARCH & DISCOVER' : 'ALL STORIES' ?></span><h2><?= !empty($filters['q']) ? 'ผลการค้นหา “'.e($filters['q']).'”' : ($filtered ? 'เรื่องราวในหมวดหมู่นี้' : 'บันทึกการเดินทางล่าสุด') ?></h2></div><div class="journal-stories-count"><span><?= (int)$total ?> เรื่องราว</span><?php if ($filtered): ?><a href="<?= base_url().'blog' ?>#journal-stories">ล้างตัวกรอง <i class="material-icons" aria-hidden="true">close</i></a><?php endif ?></div></div><?php endif ?>
  <?php if (!$posts): ?><div class="journal-empty"><span class="journal-empty-mark" aria-hidden="true"><i class="material-icons" aria-hidden="true"><?= $filtered ? 'travel_explore' : 'auto_stories' ?></i></span><div><h2><?= $filtered ? 'ยังไม่พบเรื่องราวที่คุณตามหา' : 'ทุกการเดินทางมีเรื่องให้เล่า' ?></h2><p><?= $filtered ? 'ลองใช้คำค้นอื่น หรือสำรวจบทความในหมวดหมู่อื่น' : 'เรื่องราวใหม่กำลังจะมา ระหว่างนี้ออกไปค้นหาจุดหมายที่ใช่สำหรับคุณ' ?></p><a class="journal-pill" href="<?= base_url().($filtered ? 'blog' : 'tours') ?>"><?= $filtered ? 'ดูบทความทั้งหมด' : 'สำรวจโปรแกรมทัวร์' ?> <i class="material-icons" aria-hidden="true">north_east</i></a></div></div>
  <?php else: ?><div class="blog-grid journal-grid"><?php foreach ($posts as $p) load_view('blog/card',['p'=>$p]); ?></div><?php endif ?>
  <?php load_view('blog/pagination'); ?>
  <a class="journal-outro" href="<?= base_url().'tours' ?>"><div><span class="eyebrow light">FROM INSPIRATION TO EXPLORATION</span><h2>อ่านแล้วอยากไป? <span>ออกเดินทางด้วยกัน</span></h2></div><span class="journal-outro-cta">ดูโปรแกรมทัวร์ <i class="material-icons" aria-hidden="true">north_east</i></span></a>
 </section>
</div>
</div>
