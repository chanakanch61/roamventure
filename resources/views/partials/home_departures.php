<?php if (!empty($departures)): $months=['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC']; ?>
<section class="section container rv-board" id="departures" aria-labelledby="departures-title">
 <div class="section-head"><div><span class="eyebrow">NOW BOARDING / DEPARTURES</span><h2 id="departures-title">ออกเดินทางเร็ว ๆ นี้</h2><p>รอบที่ใกล้ที่สุดซึ่งยังมีที่นั่งว่าง เลือกแล้วไปที่ฟอร์มจองของรอบนั้นได้ทันที</p></div><a href="<?= base_url().'tours' ?>" class="outline-link">ดูทุกรอบ <span><i class="material-icons" aria-hidden="true">north_east</i></span></a></div>
 <div class="rv-board-panel">
  <div class="rv-board-labels" aria-hidden="true"><span>DATE</span><span>CODE</span><span>JOURNEY</span><span>SEATS</span><span>STATUS</span><span>FROM</span></div>
  <ol class="rv-board-list">
  <?php foreach ($departures as $row): $tour=$row['tour']; $period=$row['period']; $time=strtotime($period['start_date']); $seats=(int)$period['seats_available']; $capacity=max(1,(int)$period['capacity']); $last=$seats<=max(2,ceil($capacity*0.3)); $place=$tour['countries'][0]['name'] ?? $tour['destination']; ?>
   <li><a class="rv-board-row" href="<?= e(base_url().'tours/'.rawurlencode($tour['code']).'?departure='.(int)$period['id'].'#booking') ?>" aria-label="<?= e(thai_date($period['start_date']).' · '.$tour['title'].' · '.(int)$tour['days'].' วัน '.(int)$tour['nights'].' คืน · ว่าง '.$seats.' จาก '.$capacity.' ที่นั่ง · ราคาเริ่มต้น ฿'.money($period['price_per_person']).' ต่อคน') ?>">
    <span class="rv-board-date" aria-hidden="true"><b data-flap><?= date('d',$time) ?></b><small><?= $months[(int)date('n',$time)].' '.substr((string)(date('Y',$time)+543),-2) ?></small></span>
    <span class="rv-board-code" data-flap aria-hidden="true"><?= e($tour['code']) ?></span>
    <span class="rv-board-trip" aria-hidden="true"><strong><?= e($tour['title']) ?></strong><small><i class="material-icons">place</i><?= e($place) ?> · <?= (int)$tour['days'] ?> วัน <?= (int)$tour['nights'] ?> คืน</small></span>
    <span class="rv-board-seats" aria-hidden="true"><i style="--fill:<?= round(1-$seats/$capacity,3) ?>"></i><span>ว่าง <?= $seats ?>/<?= $capacity ?></span></span>
    <span class="rv-board-status<?= $last ? ' is-last' : '' ?>" aria-hidden="true"><?= $last ? 'ใกล้เต็ม' : 'เปิดจอง' ?></span>
    <span class="rv-board-price" aria-hidden="true">฿<?= money($period['price_per_person']) ?></span>
    <span class="rv-board-go" aria-hidden="true"><i class="material-icons">north_east</i></span>
   </a></li>
  <?php endforeach ?>
  </ol>
 </div>
</section>
<?php endif ?>
