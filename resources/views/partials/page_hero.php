<?php
 $banner=page_banner($settings,$hero_key);
 if (!empty($hero_destination)) {
  $banner['eyebrow']=['zone'=>'โซนท่องเที่ยว','country'=>'ประเทศ','route'=>'เส้นทางท่องเที่ยว'][$hero_destination['kind']];
  $banner['title']='ทัวร์'.$hero_destination['name'];
  $banner['description']=$hero_destination['description'] ?: 'เลือกโปรแกรม วันเดินทาง และจำนวนผู้เดินทางในแบบของคุณ';
  // A route without its own cover borrows its first program's photo rather than the generic banner.
  $heroPhoto=$hero_destination['cover_path'] ?: ($hero_destination['kind']==='route' ? ($hero_cover ?? '') : '');
  if ($heroPhoto) $banner['desktop']=$banner['mobile']=$heroPhoto;
  $heroRoot=program_channels()[$listing_type ?? ''] ?? null;
 }
?>
<section class="hero type-hero<?= $hero_key==='home' ? ' hero-with-search' : '' ?>">
 <picture class="hero-picture"><source media="(max-width: 767px)" srcset="<?= e(base_url().$banner['mobile']) ?>"><img<?= !empty($heroPhoto) ? ' class="destination-cover"' : '' ?> src="<?= e(base_url().$banner['desktop']) ?>" alt="<?= e($hero_destination['cover_alt'] ?? '') ?>" fetchpriority="high"></picture>
 <div class="hero-shade"></div>
 <div class="container hero-content">
 <?php if (!empty($hero_destination)): ?><nav class="breadcrumb listing-breadcrumb" aria-label="เส้นทางนำทาง"><a href="<?= e(base_url().($heroRoot['path'] ?? 'tours')) ?>"><?= e($heroRoot['label'] ?? 'โปรแกรมทัวร์') ?></a><?php foreach ($hero_trail as $crumb): ?><span aria-hidden="true">/</span><?php if ($crumb['id']===$hero_destination['id']): ?><span aria-current="page"><?= e($crumb['name']) ?></span><?php else: ?><a href="<?= e(destination_url($crumb)) ?>"><?= e($crumb['name']) ?></a><?php endif ?><?php endforeach ?></nav><?php endif ?>
 <span class="eyebrow light"><?= e($banner['eyebrow']) ?></span><h1><?= nl2br(e($banner['title'])) ?></h1><p><?= nl2br(e($banner['description'])) ?></p>
 <?php if ($hero_key==='home'): ?><div class="hero-actions"><a class="btn btn-lime" href="<?= base_url().'tours' ?>">ค้นหาทริปของคุณ <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><a class="hero-text-link" href="<?= base_url().'packages' ?>">ออกแบบวันเดินทางเอง <i class="material-icons" aria-hidden="true">north_east</i></a></div><?php endif ?>
 </div>
 <?php if ($hero_key==='home'): ?><div class="container search-container" id="find-trip"><?php load_view('partials/search', ['show_search_types' => true]); ?></div><?php endif ?>
</section>
