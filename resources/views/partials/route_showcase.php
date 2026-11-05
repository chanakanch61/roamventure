<?php
 $routeCountries=array_column($route_cards,'country_flag','country_name');
 $routeChannel=program_channels()[$listing_type] ?? null;
 $routeRest=array_slice($route_cards,1);
 // Up to three routes share one main card and a side column; other program types fill any empty slot.
 $routeMosaic=count($route_cards)>3;
?>
<section class="section container route-showcase" aria-labelledby="route-showcase-title">
 <div class="section-head"><div><span class="eyebrow">ROUTES</span><h2 id="route-showcase-title">เลือกเส้นทางที่อยากไป</h2></div><p class="route-showcase-count"><?php if ($routeMosaic && count($routeCountries)===1): ?><?php if (reset($routeCountries)): ?><img class="country-flag" src="<?= e(base_url().reset($routeCountries)) ?>" alt="" width="24" height="18" loading="lazy"><?php endif ?><?= e(key($routeCountries)) ?> · <?php elseif ($route_extras && $routeChannel): ?><?= e($routeChannel['badge']) ?> <?php endif ?><?= count($route_cards) ?> เส้นทาง · <?= count($tours) ?> <?= $listing_type==='private' ? 'แพ็คเกจ' : 'โปรแกรม' ?></p></div>
 <div class="route-grid">
  <?php if ($routeMosaic): ?>
   <?php foreach ($route_cards as $routeIndex=>$routeCard) load_view('partials/route_card',['route_card'=>$routeCard,'route_card_size'=>$routeIndex<2 ? 'large' : 'small','route_card_badge'=>false]); ?>
  <?php else: ?>
   <?php load_view('partials/route_card',['route_card'=>$route_cards[0],'route_card_size'=>'main','route_card_badge'=>false]); ?>
   <?php if ($routeRest || $route_extras): ?>
   <div class="route-side">
    <?php if ($route_extras): ?><span class="route-side-label"><?= $routeRest ? 'เส้นทางอื่นที่เปิดขาย' : 'เส้นทางอื่นที่เปิดขาย ในการเดินทางแบบอื่น' ?></span><?php endif ?>
    <?php foreach ($routeRest as $routeCard) load_view('partials/route_card',['route_card'=>$routeCard,'route_card_size'=>'tile','route_card_badge'=>false]); ?>
    <?php foreach ($route_extras as $routeCard) load_view('partials/route_card',['route_card'=>$routeCard,'route_card_size'=>'tile','route_card_badge'=>true]); ?>
   </div>
   <?php endif ?>
  <?php endif ?>
 </div>
</section>
