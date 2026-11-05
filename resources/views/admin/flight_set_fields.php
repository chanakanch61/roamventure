<?php $setKey=$set['key'] ?? (!empty($set['id']) ? 's'.$set['id'] : 'n'.$s); ?>
<div class="flight-set-item">
 <input type="hidden" name="flight_sets[<?= $s ?>][key]" value="<?= e($setKey) ?>">
 <div class="flight-set-head">
  <label>ชื่อชุดเที่ยวบิน<input name="flight_sets[<?= $s ?>][name]" maxlength="120" value="<?= e($set['name'] ?? '') ?>" placeholder="เช่น ชุดหลัก หรือ ชุดเดือนธันวาคม"></label>
  <div class="flight-set-actions"><span data-flight-set-usage></span><button type="button" class="text-button" data-flight-set-copy>คัดลอกชุดนี้</button><button type="button" class="text-button danger" data-flight-set-remove>นำชุดนี้ออก</button></div>
 </div>
 <div class="flight-rows"><?php foreach (($set['flights'] ?? []) ?: [[]] as $j=>$flight) load_view('admin/flight_fields',['s'=>$s,'j'=>$j,'flight'=>$flight]); ?></div>
 <button type="button" class="outline-link" data-flight-add>＋ เพิ่มเที่ยวบิน</button>
</div>
