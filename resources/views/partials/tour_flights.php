<?php
 $flightsIncluded=!empty($tour['flight_included']);
 $flightSets=$isPrivate ? [] : ($tour['flight_sets'] ?? []);
 $flightDefault=null;
 foreach ($available as $flightDeparture) {
  if (!empty($flightDeparture['flight_set_id'])) { $flightDefault=$flightDeparture['flight_set_id']; break; }
 }
 $flightDefault=$flightDefault ?: ($flightSets[0]['id'] ?? null);
?>
<?php if ($flightSets): ?>
<section id="flights" class="trip-flights">
 <div class="trip-flights-head"><div><span class="eyebrow"><?= $flightsIncluded ? 'YOUR FLIGHTS' : 'SUGGESTED FLIGHTS' ?></span><h2><?= $flightsIncluded ? 'เที่ยวบินของทัวร์นี้' : 'เที่ยวบินแนะนำ' ?></h2></div><?php if ($flightsIncluded): ?><span class="flight-badge is-included">รวมในราคาทัวร์แล้ว</span><?php endif ?></div>
 <p class="trip-flights-round" data-flight-round<?= count($flightSets)>1 ? '' : ' hidden' ?>><?= count($flightSets)>1 ? 'เที่ยวบินอาจต่างกันตามรอบ เลือกวันเดินทางเพื่อดูเที่ยวบินของรอบนั้น' : '' ?></p>
 <?php foreach ($flightSets as $flightSet): ?><div class="flight-set" data-flight-set="<?= (int)$flightSet['id'] ?>"<?= (string)$flightSet['id']===(string)$flightDefault ? '' : ' hidden' ?>><?php foreach ($flightSet['flights'] as $leg): ?>
  <article class="flight-leg">
   <div class="flight-leg-type"><b><?= e(flight_leg_types()[$leg['leg_type']] ?? '') ?></b><?php if ($leg['day_number']): ?><small>วันที่ <?= (int)$leg['day_number'] ?></small><?php endif ?></div>
   <div class="flight-leg-airline"><?= airline_logo($leg['airline_logo'],$leg['airline_name'],$leg['airline_code'],'large') ?><span><b><?= e($leg['airline_name']) ?></b><small>เที่ยวบิน <?= e($leg['flight_number']) ?></small></span></div>
   <div class="flight-leg-time"><span><b><?= e($leg['depart_time']) ?></b><small><?= e($leg['from_airport']) ?></small></span><span class="flight-leg-line"><small><?= e(flight_duration($leg['duration_minutes'])) ?></small><i aria-hidden="true"></i></span><span><b><?= e($leg['arrive_time']) ?></b><small><?= e($leg['to_airport']) ?></small></span><?php if ($leg['arrive_next_day']): ?><span class="flight-next-day">+1 วัน</span><?php endif ?></div>
   <?php if (!$flightsIncluded): ?><span class="flight-badge<?= $leg['included'] ? ' is-included' : '' ?>"><?= $leg['included'] ? 'รวมในราคาทัวร์' : 'แนะนำ · ซื้อเอง' ?></span><?php elseif (!$leg['included']): ?><span class="flight-badge">ไม่รวมในราคา</span><?php endif ?>
  </article>
 <?php endforeach ?></div><?php endforeach ?>
 <p class="trip-flights-none" data-flight-none hidden>รอบนี้ยังไม่ได้ระบุเที่ยวบิน ทีมงานจะแจ้งให้ทราบ</p>
 <p class="fineprint">เวลาอาจเปลี่ยนแปลงตามสายการบิน</p>
</section>
<?php endif ?>
<?php if (!$flightsIncluded): ?>
<section<?= $flightSets ? '' : ' id="flights"' ?> class="ticket-help-card">
 <div><h3><?= $isPrivate ? 'ตั๋วเครื่องบิน' : 'ควรซื้อตั๋วหลังทีมงานยืนยันการเดินทางแล้ว' ?></h3><p><?= $isPrivate ? 'ไม่รวมในแพ็คเกจ เพราะคุณเลือกวันเดินทางเองได้ เลือกเที่ยวบินตามวันเดินทางของกลุ่ม หรือให้ทีมงานช่วยดูตั๋วให้ ทีมงานจะแจ้งราคาจริงให้ยืนยันก่อนซื้อ' : 'ไม่อยากจองเอง ให้ทีมงานช่วยดูตั๋วได้ ทีมงานจะแจ้งราคาตั๋วจริงให้ยืนยันก่อนซื้อ' ?></p></div>
 <button type="button" class="btn" data-ticket-help>ให้ทีมงานช่วยจองตั๋ว</button>
</section>
<?php endif ?>
