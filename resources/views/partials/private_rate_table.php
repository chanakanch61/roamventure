<?php
 $rateLevels=[];
 $ratePeriod=$item['card_periods'][0] ?? null;
 foreach (json_decode($ratePeriod['private_rates'] ?? '[]',true) ?: [] as $rate) $rateLevels[$rate['level']][]=$rate;
 foreach ($rateLevels as &$levelRates) usort($levelRates,static function($a,$b) { return $a['min'] <=> $b['min']; });
 unset($levelRates);
 // Lead with the cheapest service level, matching the "from" price on the card.
 uasort($rateLevels,static function($a,$b) { return min(array_column($a,'price')) <=> min(array_column($b,'price')); });
?>
<?php if ($rateLevels): ?>
<div class="rate-table" data-rate-levels>
 <div class="rate-levels" role="group" aria-label="เลือกระดับบริการ"><?php $rateIndex=0; foreach (array_keys($rateLevels) as $levelName): ?><button type="button" aria-pressed="<?= $rateIndex===0 ? 'true' : 'false' ?>" data-rate-level="<?= $rateIndex++ ?>"><?= e($levelName) ?></button><?php endforeach ?></div>
 <?php $rateIndex=0; foreach ($rateLevels as $levelRates): ?><div class="rate-grid" data-rate-panel="<?= $rateIndex ?>"<?= $rateIndex++ ? ' hidden' : '' ?>><?php foreach ($levelRates as $rate): ?><div><span><?= people_range($rate['min'],$rate['max']) ?> คน</span><b>฿<?= money($rate['price']) ?></b></div><?php endforeach ?></div><?php endforeach ?>
 <p class="rate-note">ราคาต่อคน ตามจำนวนผู้เดินทางและระดับบริการ<?= count($item['card_periods'])>1 ? ' · ราคาของช่วง '.e(thai_date($ratePeriod['start_date'])).' – '.e(thai_date($ratePeriod['end_date'])).' ช่วงอื่นดูในหน้าโปรแกรม' : '' ?></p>
</div>
<?php endif ?>
