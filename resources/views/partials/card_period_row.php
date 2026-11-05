<?php
 $private=!empty($period['private_rates']); $rates=$private ? json_decode($period['private_rates'],true) : [];
 $start=thai_date($period['start_date']); $end=thai_date($period['end_date']);
 if ($period['start_date']===$period['end_date']) $range=$start;
 elseif (substr($period['start_date'],0,7)===substr($period['end_date'],0,7)) $range=(int)substr($period['start_date'],8).'–'.$end;
 else $range=$start.' – '.$end;
 $soldOut=(int)$period['seats_available']<1;
 $url=(base_url() . ('tours/'.rawurlencode($code))).($soldOut ? '#departure-periods' : '?departure='.(int)$period['id'].'#booking');
?>
<a class="card-period-row <?= $soldOut ? 'is-full' : '' ?>" href="<?= e($url) ?>" aria-label="<?= e($private ? $range.' แพ็คเกจส่วนตัว เลือกวันเอง' : $range.' ไซส์กรุ๊ป '.$period['capacity'].' คน '.($soldOut ? 'เต็มแล้ว' : 'ว่าง '.$period['seats_available'].' ที่นั่ง')) ?>">
 <span class="card-period-date"><?= e($range) ?></span>
 <span class="card-period-availability"><i class="material-icons" aria-hidden="true"><?= $private ? 'event_available' : ($soldOut ? 'event_busy' : 'group') ?></i><?= $private ? 'เลือกวันเอง' : ($soldOut ? 'เต็มแล้ว' : 'ว่าง '.(int)$period['seats_available'].' / '.(int)$period['capacity'].' ที่นั่ง') ?></span>
</a>
