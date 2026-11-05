<?php
 $airlinePrivate=($item['program_type'] ?? '')==='private';
 $airlineIncluded=!empty($item['flight_included']);
 $airlineSummary=$item['flight_summary'] ?? null;
 $airlineLead=$airlineSummary['lead'] ?? null;
 $airlineOthers=$airlineSummary ? count($airlineSummary['airlines'])-1 : 0;
?>
<?php if ($airlineIncluded && !$airlinePrivate && $airlineLead): ?>
<div class="card-airline"><?= airline_logo($airlineLead['airline_logo'],$airlineLead['airline_name'],$airlineLead['airline_code']) ?><span class="card-airline-text"><?= $airlineSummary['same_flights'] ? e($airlineLead['flight_number']).' · ออก '.e($airlineLead['depart_time']).' น.' : 'เที่ยวบินตามรอบเดินทาง' ?><?= $airlineOthers>0 ? ' · และอีก '.$airlineOthers.' สาย' : '' ?></span></div>
<?php else: ?>
<?php
 if ($airlinePrivate) $airlineText='ราคาตามจำนวนคน · '.($airlineIncluded ? 'รวมตั๋ว' : 'ไม่รวมตั๋ว');
 elseif ($airlineIncluded) $airlineText=flight_note($item).(($item['airline'] ?? '')!=='' ? ' · '.$item['airline'] : '');
 // No logo on a land tour: beside the price it would read as if the ticket were included.
 else $airlineText=flight_note($item).(!empty($airlineSummary['suggested_airline']) ? ' · แนะนำ '.$airlineSummary['suggested_airline'] : '');
?>
<div class="card-airline is-plain"><i class="material-icons" aria-hidden="true">flight</i><span class="card-airline-text"><?= e($airlineText) ?></span></div>
<?php endif ?>
