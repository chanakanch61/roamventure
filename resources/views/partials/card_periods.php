<?php $periods=$item['card_periods'] ?? []; ?>
<div class="card-periods card-period-bubbles">
 <div class="card-periods-heading"><span><i class="material-icons" aria-hidden="true">calendar_month</i> <?= ($item['program_type'] ?? '')==='private' ? 'ช่วงวันเดินทาง' : 'พีเรียดเดินทาง' ?></span><span><?= count($periods) ?> <?= ($item['program_type'] ?? '')==='private' ? 'ช่วง' : 'รอบ' ?></span></div>
 <?php if (!$periods): ?><p class="card-periods-empty">ยังไม่มีรอบเปิดรับจอง</p><?php else: ?>
 <div class="card-period-chips"><?php foreach ($periods as $period) load_view('partials/card_period_row',['period'=>$period,'code'=>$item['code']]); ?></div>
 <p class="card-period-hint">เลือก<?= ($item['program_type'] ?? '')==='private' ? 'ช่วงวันที่เพื่อดูแพ็คเกจ' : 'รอบเพื่อดูราคาและจอง' ?> <i class="material-icons" aria-hidden="true">arrow_forward</i></p>
 <?php endif ?>
</div>
