<?php $slides=home_hero_slides($settings,true); if (!$slides) { load_view('partials/page_hero',['hero_key'=>'home']); return; } ?>
<section class="hero type-hero home-slider hero-with-search" data-hero-slider role="region" aria-roledescription="สไลด์" aria-label="ไฮไลต์การเดินทาง">
 <div class="home-slides">
 <?php foreach ($slides as $i=>$slide): ?>
 <div class="home-slide <?= $i===0 ? 'is-active' : '' ?>" data-slide role="group" aria-roledescription="สไลด์" aria-label="<?= ($i+1).' จาก '.count($slides) ?>" <?= $i ? 'inert aria-hidden="true"' : 'aria-hidden="false"' ?>>
 <picture class="hero-picture"><source media="(max-width:767px)" srcset="<?= e(base_url().$slide['mobile']) ?>"><source media="(max-width:1023px)" srcset="<?= e(base_url().$slide['tablet']) ?>"><img src="<?= e(base_url().$slide['desktop']) ?>" alt="" <?= $i===0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>></picture><div class="hero-shade"></div>
 <div class="container hero-content">
  <div class="hero-caption"><span><i class="live-dot" aria-hidden="true"></i> ROAMVENTURE / A WORLD OF POSSIBILITIES</span><span>EST. FOR THE EXPLORERS <i class="material-icons" aria-hidden="true">public</i></span></div>
  <div class="adventure-heading"><span class="adventure-kicker">ให้การเดินทางครั้งต่อไป พาคุณไปไกลกว่าเดิม</span><div class="hero-display" aria-hidden="true">RoamVenture<span>BEGINS WITH YOU.</span></div></div>
  <div class="adventure-story">
  <span class="adventure-label"><i class="material-icons" aria-hidden="true">explore</i> YOUR NEXT GREAT ESCAPE</span>
  <<?= $i===0 ? 'h1' : 'h2' ?> class="slide-title"><?= nl2br(e($slide['title'])) ?></<?= $i===0 ? 'h1' : 'h2' ?>>
  <p><?= nl2br(e($slide['description'])) ?></p>
  <div class="hero-actions"><a class="btn btn-lime" href="#find-trip">ค้นหาทริปของคุณ <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><a class="hero-text-link" href="<?= base_url().'packages' ?>">แพ็คเกจส่วนตัว <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  </div>
  <div class="adventure-postcards" aria-hidden="true"><div class="adventure-postcard postcard-mountain"><img src="<?= base_url().'public/images/story.jpg' ?>" alt="" width="400" height="500" loading="lazy"><span>01 / INTO THE WILD</span></div><div class="adventure-postcard postcard-ocean"><img src="<?= base_url().'public/images/krabi.jpg' ?>" alt="" width="400" height="500" loading="lazy"><span>02 / CHASE THE HORIZON</span></div><span class="adventure-compass"><i class="material-icons" aria-hidden="true">explore</i></span></div>
  <div class="hero-bottom-note"><span>GOOD PEOPLE. GREAT PLACES. UNFORGETTABLE STORIES.</span><a href="#find-trip">เริ่มต้นการเดินทาง <span aria-hidden="true"><i class="material-icons" aria-hidden="true">arrow_downward</i></span></a></div>
 </div>
 </div><?php endforeach ?>
 </div>
 <?php if (count($slides)>1): ?><div class="slider-controls" hidden><button type="button" data-prev aria-label="สไลด์ก่อนหน้า"><i class="material-icons" aria-hidden="true">arrow_back</i></button><div class="slider-dots"><?php foreach ($slides as $i=>$slide): ?><button type="button" data-go="<?= $i ?>" aria-label="ไปสไลด์ <?= $i+1 ?>" aria-current="<?= $i===0 ? 'true' : 'false' ?>"></button><?php endforeach ?></div><button type="button" data-next aria-label="สไลด์ถัดไป"><i class="material-icons" aria-hidden="true">arrow_forward</i></button><span class="slider-status" aria-live="polite" aria-atomic="true"></span></div><?php endif ?>
 <div class="container search-container" id="find-trip"><?php load_view('partials/search', ['show_search_types' => true]); ?></div>
</section>
<link rel="stylesheet" href="<?= base_url().'public/hero-slider.css?v='.filemtime(base_path()."/".'public/hero-slider.css') ?>">
<script src="<?= base_url().'public/hero-slider.js?v='.filemtime(base_path()."/".'public/hero-slider.js') ?>" defer></script>
