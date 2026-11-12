<?php $periods=$item['card_periods'] ?? []; $itemPrivate=($item['program_type'] ?? '')==='private'; $mix=private_period_mix($periods); ?>
<div class="card-periods card-period-bubbles">
 <div class="card-periods-heading"><span><i class="material-icons" aria-hidden="true">calendar_month</i> <?= $itemPrivate ? 'ช่วงวันเดินทาง' : 'พีเรียดเดินทาง' ?></span><span><?= $mix['anytime'] ? 'ทุกวัน'.($mix['dated'] ? ' + '.$mix['dated'].' ช่วงพิเศษ' : '') : count($periods).' '.($itemPrivate ? 'ช่วง' : 'รอบ') ?></span></div>
 <?php if (!$periods): ?><p class="card-periods-empty">ยังไม่มีรอบเปิดรับจอง</p><?php else: ?>
 <div class="card-period-chips"><?php foreach ($periods as $period) load_view('partials/card_period_row',['period'=>$period,'code'=>$item['code'],'days'=>$item['days']]); ?></div>
 <p class="card-period-hint"><?= $mix['anytime'] && !$mix['dated'] ? 'เลือกวันเดินทางในหน้าโปรแกรม' : ($mix['anytime'] ? 'ช่วงพิเศษใช้ราคาของช่วงนั้นแทน' : 'เลือก'.($itemPrivate ? 'ช่วงวันที่เพื่อดูแพ็คเกจ' : 'รอบเพื่อดูราคาและจอง')) ?> <i class="material-icons" aria-hidden="true">arrow_forward</i></p>
 <?php endif ?>
</div>
