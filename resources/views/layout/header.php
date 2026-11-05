<!doctype html>
<html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php load_view('layout/tracking'); ?>
<?php $meta=$meta ?? []; $pageTitle=$meta['title'] ?? (!empty($tour['seo_title']) ? $tour['seo_title'] : ($title ?? 'RoamVenture').' · RoamVenture'); $pageDescription=$meta['description'] ?? (!empty($tour['seo_description']) ? $tour['seo_description'] : (!empty($tour['summary']) ? text_excerpt($tour['summary']) : seo_default_description())); $pageKeywords=$meta['keywords'] ?? ($tour['seo_keywords'] ?? ''); $privatePage=!empty($blog_preview) || $admin_layout || strpos(uri_string(),'admin')===0 || strpos(uri_string(),'booking/')===0 || strpos(uri_string(),'billing_admin')===0 || strpos(uri_string(),'destinations_admin')===0; $publicTheme=!$privatePage || !empty($preview); ?>
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<?php if ($pageKeywords): ?><meta name="keywords" content="<?= e($pageKeywords) ?>"><?php endif ?>
<?php if (!$privatePage): ?>
<link rel="canonical" href="<?= e($meta['canonical'] ?? (base_url() . uri_string())) ?>">
<meta property="og:type" content="website"><meta property="og:site_name" content="RoamVenture"><meta property="og:locale" content="th_TH">
<meta property="og:title" content="<?= e($pageTitle) ?>"><meta property="og:description" content="<?= e($pageDescription) ?>"><meta property="og:url" content="<?= e($meta['canonical'] ?? (base_url() . uri_string())) ?>"><meta property="og:image" content="<?= e($meta['image'] ?? tour_image($tour['cover'] ?? '')) ?>">
<?php if (!empty($meta['image_size'])): ?><meta property="og:image:width" content="<?= $meta['image_size'][0] ?>"><meta property="og:image:height" content="<?= $meta['image_size'][1] ?>"><?php endif ?>
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= e($pageTitle) ?>"><meta name="twitter:description" content="<?= e($pageDescription) ?>"><meta name="twitter:image" content="<?= e($meta['image'] ?? tour_image($tour['cover'] ?? '')) ?>">
<?php endif ?>
<?php if ($privatePage): ?><meta name="robots" content="noindex,nofollow"><?php endif ?>
<style>:root { --hero-image: url(<?= json_encode(base_url() . 'public/images/hero.jpg', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>); --story-image: url(<?= json_encode(base_url() . 'public/images/story.jpg', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>); }</style>
<link rel="icon" href="<?= e(base_url().(branding_path($settings,'favicon') ?: 'public/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url() . 'public/app.css'.'?v='.filemtime(base_path()."/".'public/app.css') ?>">
<script src="<?= base_url() . 'public/slider-drag.js'.'?v='.filemtime(base_path()."/".'public/slider-drag.js') ?>" defer></script>
<script src="<?= base_url() . 'public/app.js'.'?v='.filemtime(base_path()."/".'public/app.js') ?>" defer></script>
<link rel="stylesheet" href="<?= base_url() . 'public/billing.css'.'?v='.filemtime(base_path()."/".'public/billing.css') ?>">
<?php if (isset($tour)): ?><link rel="stylesheet" href="<?= base_url().'public/day-photos.css?v='.filemtime(base_path()."/".'public/day-photos.css') ?>"><link rel="stylesheet" href="<?= base_url().'public/day-gallery.css?v='.filemtime(base_path()."/".'public/day-gallery.css') ?>"><?php if ($admin_layout): ?><script src="<?= base_url().'public/day-hotels.js?v='.filemtime(base_path()."/".'public/day-hotels.js') ?>" defer></script><script src="<?= base_url().'public/day-gallery.js?v='.filemtime(base_path()."/".'public/day-gallery.js') ?>" defer></script><?php endif ?><?php endif ?>
<?php if (isset($preview, $tour)): ?>
<link rel="stylesheet" href="<?= base_url().'public/tour-detail.css?v='.filemtime(base_path()."/".'public/tour-detail.css') ?>">
<link rel="stylesheet" href="<?= base_url().'public/day-mosaic.css?v='.filemtime(base_path()."/".'public/day-mosaic.css') ?>">
<script src="<?= base_url().'public/tour-detail.js?v='.filemtime(base_path()."/".'public/tour-detail.js') ?>" defer></script>
<?php endif ?>
<link rel="stylesheet" href="<?= base_url().'public/monochrome.css?v='.filemtime(base_path()."/".'public/monochrome.css') ?>">
<script src="<?= base_url().'public/motion.js?v='.filemtime(base_path()."/".'public/motion.js') ?>" defer></script>
<?php if ($publicTheme): ?><link rel="stylesheet" href="<?= base_url().'public/dark-experience.css?v='.filemtime(base_path()."/".'public/dark-experience.css') ?>"><?php endif ?>
<?php if (isset($company_page)): ?>
<link rel="stylesheet" href="<?= base_url().'public/company.css?v='.filemtime(base_path()."/".'public/company.css') ?>">
<script src="<?= base_url().'public/company.js?v='.filemtime(base_path()."/".'public/company.js') ?>" defer></script>
<?php endif ?>
<?php if (isset($structured_data) && !$privatePage): ?><script type="application/ld+json"><?= json_encode($structured_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif ?>
<?php if (isset($blog_page) || isset($destination_page)): ?><link rel="stylesheet" href="<?= base_url().'public/blog.css?v='.filemtime(base_path()."/".'public/blog.css') ?>"><script src="<?= base_url().'public/blog.js?v='.filemtime(base_path()."/".'public/blog.js') ?>" defer></script><?php endif ?>
<?php if (isset($destination_page)): ?><link rel="stylesheet" href="<?= base_url().'public/destination-onpage.css?v='.filemtime(base_path()."/".'public/destination-onpage.css') ?>"><link rel="stylesheet" href="<?= base_url().'public/onpage-magazine.css?v='.filemtime(base_path()."/".'public/onpage-magazine.css') ?>"><?php if ($admin_layout): ?><script src="<?= base_url().'public/destination-onpage.js?v='.filemtime(base_path()."/".'public/destination-onpage.js') ?>" defer></script><script src="<?= base_url().'public/onpage-blocks.js?v='.filemtime(base_path()."/".'public/onpage-blocks.js') ?>" defer></script><?php endif ?><?php endif ?>
<?php if (isset($blog_page) && !$admin_layout): ?><link rel="stylesheet" href="<?= base_url().'public/blog-public.css?v='.filemtime(base_path()."/".'public/blog-public.css') ?>"><script src="<?= base_url().'public/blog-public.js?v='.filemtime(base_path()."/".'public/blog-public.js') ?>" defer></script><?php endif ?>
<?php if (!empty($home_posts)): ?><link rel="stylesheet" href="<?= base_url().'public/home-blog.css?v='.filemtime(base_path()."/".'public/home-blog.css') ?>"><?php endif ?>
<?php if ($admin_layout): ?><link rel="stylesheet" href="<?= base_url().'public/admin-access.css?v='.filemtime(base_path()."/".'public/admin-access.css') ?>"><?php endif ?>
<link rel="stylesheet" href="<?= base_url().'public/material-icons.css?v='.filemtime(base_path()."/".'public/material-icons.css') ?>">
<link rel="stylesheet" href="<?= base_url().'public/card-refinements.css?v='.filemtime(base_path()."/".'public/card-refinements.css') ?>">
<?php if (isset($gallery_page)): ?><link rel="stylesheet" href="<?= base_url().'public/gallery.css?v='.filemtime(base_path()."/".'public/gallery.css') ?>"><?php if (!$admin_layout): ?><link rel="stylesheet" href="<?= base_url().'public/gallery-public.css?v='.filemtime(base_path()."/".'public/gallery-public.css') ?>"><?php endif ?><script src="<?= base_url().'public/gallery.js?v='.filemtime(base_path()."/".'public/gallery.js') ?>" defer></script><?php endif ?>
<?php if ($admin_layout): ?>
<link rel="stylesheet" href="<?= base_url().'public/admin-shell.css?v='.filemtime(base_path()."/".'public/admin-shell.css') ?>">
<script src="<?= base_url().'public/admin-shell.js?v='.filemtime(base_path()."/".'public/admin-shell.js') ?>" defer></script>
<?php endif ?>
<?php if (isset($tour_search_catalog)): ?>
<link rel="stylesheet" href="<?= base_url().'public/tour-search.css?v='.filemtime(base_path()."/".'public/tour-search.css') ?>">
<script src="<?= base_url().'public/tour-search.js?v='.filemtime(base_path()."/".'public/tour-search.js') ?>" defer></script>
<?php endif ?>
<?php if (!$admin_layout && isset($listing_path)): ?><link rel="stylesheet" href="<?= base_url().'public/tours-listing.css?v='.filemtime(base_path()."/".'public/tours-listing.css') ?>"><?php endif ?>
<?php if (app(\App\Support\SiteGate::class)->enabled()): ?><link rel="stylesheet" href="<?= base_url().'public/site-access.css?v='.filemtime(base_path()."/".'public/site-access.css') ?>"><?php endif ?>
<?php if (!$admin_layout): ?>
<link rel="stylesheet" href="<?= base_url().'public/travel-experience.css?v='.filemtime(base_path()."/".'public/travel-experience.css') ?>">
<script src="<?= base_url().'public/travel-experience.js?v='.filemtime(base_path()."/".'public/travel-experience.js') ?>" defer></script>
<link rel="stylesheet" href="<?= base_url().'public/showcase.css?v='.filemtime(base_path()."/".'public/showcase.css') ?>">
<?php if ($publicTheme): ?><?php foreach (['vendor/gsap-3.15.0.min.js','vendor/ScrollTrigger-3.15.0.min.js','vendor/lenis-1.3.26.min.js','scroll-stage.js'] as $script): ?><script src="<?= base_url().'public/'.$script.'?v='.filemtime(base_path()."/".'public/'.$script) ?>" defer></script><?php endforeach ?><?php endif ?>
<?php if ($publicTheme): ?><link rel="stylesheet" href="<?= base_url().'public/signature-motion.css?v='.filemtime(base_path()."/".'public/signature-motion.css') ?>"><script src="<?= base_url().'public/signature-motion.js?v='.filemtime(base_path()."/".'public/signature-motion.js') ?>" defer></script><?php endif ?>
<?php endif ?>
<?php if (!$admin_layout && !empty($home_channels)): ?>
<link rel="stylesheet" href="<?= base_url().'public/home-journeys.css?v='.filemtime(base_path()."/".'public/home-journeys.css') ?>">
<script src="<?= base_url().'public/home-journeys.js?v='.filemtime(base_path()."/".'public/home-journeys.js') ?>" defer></script>
<?php endif ?>
<?php if (!$admin_layout && isset($home_gallery)): ?>
<link rel="stylesheet" href="<?= base_url().'public/home-gallery.css?v='.filemtime(base_path()."/".'public/home-gallery.css') ?>">
<script src="<?= base_url().'public/home-gallery.js?v='.filemtime(base_path()."/".'public/home-gallery.js') ?>" defer></script>
<?php endif ?>
</head><body class="<?= $admin_layout ? 'admin-body' : 'travel-experience'.($publicTheme ? ' public-dark' : '') ?>">
<?php if (!$admin_layout && app(\App\Support\SiteGate::class)->enabled() && (app(\App\Support\SiteGate::class)->viewer() || app(\App\Support\SiteGate::class)->staff())): ?>
<div class="site-access-bar"><span>เว็บไซต์จำกัดการเข้าชม · <?= app(\App\Support\SiteGate::class)->staff() ? 'เข้าชมในฐานะแอดมิน' : 'เข้าชมสำหรับผู้ได้รับอนุญาต' ?></span><?php if (app(\App\Support\SiteGate::class)->viewer() && !app(\App\Support\SiteGate::class)->staff()): ?><form method="post" action="<?= base_url().'site-logout' ?>"><?= csrf_field() ?><button type="submit">ออกจากระบบเข้าชม</button></form><?php endif ?></div>
<?php endif ?>
<a class="skip" href="#main">ข้ามไปเนื้อหา</a>
<?php if (!$admin_layout && !empty($settings['demo_mode'])): ?><div class="demo-banner">เว็บไซต์สาธิต · โปรแกรม วันเดินทาง ราคา และภาพเป็นข้อมูลตัวอย่าง ยังไม่เปิดรับจองจริง</div><?php endif ?>
<?php if ($admin_layout): ?>
<?php load_view('layout/admin_sidebar'); ?>
<div class="admin-content"><header class="admin-top"><div class="admin-top-start"><button class="admin-menu-button" type="button" aria-label="เปิดเมนูแอดมิน" aria-expanded="false" aria-controls="admin-sidebar"><i class="material-icons" aria-hidden="true">menu</i></button><div class="admin-breadcrumb"><span>RoamVenture <span aria-hidden="true">/</span> ระบบจัดการ</span><strong><?= e($title) ?></strong></div></div><span class="admin-avatar" aria-label="บัญชีแอดมิน"><i class="material-icons" aria-hidden="true">person</i></span></header><main id="main" class="admin-main">
<?php else: ?>
<header class="site-header"><div class="container nav-wrap"><a href="<?= base_url() ?>" class="brand" aria-label="RoamVenture หน้าแรก"><?= brand_logo($settings,'navbar') ?></a><button class="menu-toggle" aria-label="เปิดเมนู" aria-expanded="false" aria-controls="site-nav"><i class="material-icons" aria-hidden="true">menu</i></button><nav id="site-nav" aria-label="เมนูหลัก"><a href="<?= base_url() ?>" class="<?= uri_string()==='' ? 'active' : '' ?>">หน้าแรก</a><?php $navType=$listing_type ?? (!empty($tour) ? tour_channel($tour) : ''); ?><?php foreach (program_channels() as $navKey=>$navChannel): ?><a href="<?= e(base_url().$navChannel['path']) ?>" class="<?= $navType===$navKey ? 'active' : '' ?>"><?= e($navChannel['label']) ?></a><?php endforeach ?><a href="<?= base_url().'gallery' ?>" class="<?= isset($gallery_page) ? 'active' : '' ?>">แกลเลอรี่</a><a href="<?= base_url().'blog' ?>" class="<?= isset($blog_page) ? 'active' : '' ?>">บทความ</a><a href="<?= base_url().'aboutus' ?>" class="<?= ($company_page ?? '')==='about' ? 'active' : '' ?>">เกี่ยวกับเรา</a><a href="<?= base_url().'contactus' ?>" class="<?= ($company_page ?? '')==='contact' ? 'active' : '' ?>">ติดต่อเรา</a></nav></div></header>
<main id="main">
<?php endif ?>
<?php if ($flash=session('success')): ?><div class="notice success container" role="status"><?= e($flash) ?></div><?php endif ?>
<?php if ($flash=session('error')): ?><div class="notice error container" role="alert"><?= e($flash) ?></div><?php endif ?>
