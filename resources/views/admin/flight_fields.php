<?php
 $flightName='flight_sets['.$s.'][flights]['.$j.']';
 $flightIncludedDefault=$flight['included'] ?? (!empty($tour['flight_included']) ? 1 : 0);
 $flightMinutes=$flight['duration_minutes'] ?? null;
?>
<div class="flight-row">
 <label>ประเภทขา<select name="<?= $flightName ?>[leg_type]"><?php foreach (flight_leg_types() as $legKey=>$legLabel): ?><option value="<?= e($legKey) ?>" <?= ($flight['leg_type'] ?? 'outbound')===$legKey ? 'selected' : '' ?>><?= e($legLabel) ?></option><?php endforeach ?></select></label>
 <label>วันที่ของทริป<input type="number" min="1" max="60" name="<?= $flightName ?>[day_number]" value="<?= e($flight['day_number'] ?? '') ?>"></label>
 <label class="flight-row-airline">สายการบิน<select name="<?= $flightName ?>[airline_id]"><option value="">เลือกสายการบิน</option><?php foreach ($airline_options as $airlineOption): ?><option value="<?= (int)$airlineOption['id'] ?>" <?= (int)($flight['airline_id'] ?? 0)===(int)$airlineOption['id'] ? 'selected' : '' ?>><?= e($airlineOption['name']) ?></option><?php endforeach ?></select></label>
 <label>เที่ยวบิน<input name="<?= $flightName ?>[flight_number]" maxlength="12" placeholder="KY8370" value="<?= e($flight['flight_number'] ?? '') ?>"></label>
 <label>จาก<input name="<?= $flightName ?>[from_airport]" maxlength="40" placeholder="BKK" value="<?= e($flight['from_airport'] ?? '') ?>"></label>
 <label>ถึง<input name="<?= $flightName ?>[to_airport]" maxlength="40" placeholder="KMG" value="<?= e($flight['to_airport'] ?? '') ?>"></label>
 <label>เวลาออก<input type="time" name="<?= $flightName ?>[depart_time]" value="<?= e($flight['depart_time'] ?? '') ?>"></label>
 <label>เวลาถึง<input type="time" name="<?= $flightName ?>[arrive_time]" value="<?= e($flight['arrive_time'] ?? '') ?>"></label>
 <label>ระยะเวลาบิน<input name="<?= $flightName ?>[duration]" maxlength="5" placeholder="2:15" value="<?= $flightMinutes ? (int)floor($flightMinutes/60).':'.sprintf('%02d',$flightMinutes%60) : '' ?>"></label>
 <label>ในราคาทัวร์<select name="<?= $flightName ?>[included]" data-flight-included><option value="1" <?= $flightIncludedDefault ? 'selected' : '' ?>>รวมในราคา</option><option value="0" <?= $flightIncludedDefault ? '' : 'selected' ?>>แนะนำ · ซื้อเอง</option></select></label>
 <label class="checkbox flight-row-next"><input type="checkbox" name="<?= $flightName ?>[arrive_next_day]" value="1" <?= !empty($flight['arrive_next_day']) ? 'checked' : '' ?>> ถึงวันถัดไป</label>
 <button type="button" class="text-button danger flight-row-remove" data-flight-remove>นำเที่ยวบินออก</button>
</div>
