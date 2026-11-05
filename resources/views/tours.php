<?php
 $scopedListing=!empty($destination_page) && isset($node);
 $listingChannel=program_channels()[$listing_type] ?? null;
 $listingTitle=$listing_searching ? 'ผลการค้นหา' : ($listingChannel['list_title'] ?? ($scopedListing ? 'โปรแกรม' : 'ทัวร์')).($scopedListing ? 'ใน'.$node['name'] : 'ทั้งหมด');
 load_view('partials/page_hero',['hero_key'=>$listingChannel ? $listing_type : 'fixed','hero_destination'=>$scopedListing ? $node : null,'hero_trail'=>$trail ?? [],'hero_cover'=>$tours[0]['cover'] ?? '']);
?>
<div class="container listing-search<?= $scopedListing ? ' destination-search' : '' ?>">
 <?php load_view('partials/search',['show_search_types'=>$scopedListing]); ?>
</div>
<?php if (!empty($route_cards)) load_view('partials/route_showcase'); ?>
<section class="section container tours-listing"<?= $scopedListing ? ' id="destination-programs"' : '' ?>>
 <?php if ($scopedListing && $children) load_view('partials/destination_tabs'); ?>
 <div class="section-head"><h2 class="small-heading"><?= e($listingTitle) ?></h2><div class="listing-tools"><span class="listing-count">พบ <?= count($tours) ?> โปรแกรม</span><?php if ($listing_searching || ($scopedListing && $listing_type!=='all')): ?><a href="<?= base_url() . $listing_path ?>" class="text-link">ล้างตัวกรอง <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?></div></div>
 <div class="tour-list"><?php foreach ($tours as $item) load_view('partials/card_row',['item'=>$item]); ?></div>
 <?php if (!$tours): ?><div class="empty"><h3>ยังไม่พบโปรแกรมที่ตรงกับการค้นหา<?= $scopedListing ? 'ใน'.e($node['name']) : '' ?></h3><p>ลองเปลี่ยนคำค้น<?= $scopedListing ? ' ประเภทโปรแกรม' : ' จุดหมาย' ?> หรือช่วงเดินทาง</p><a class="btn" href="<?= base_url() . $listing_path ?>">ดูทุกโปรแกรม<?= $scopedListing ? 'ใน'.e($node['name']) : '' ?></a></div><?php endif ?>
</section>
<?php if (!$scopedListing && !$listing_searching) load_view('partials/channel_links'); ?>
