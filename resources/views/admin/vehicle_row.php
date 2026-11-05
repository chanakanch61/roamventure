<?php
 $vehicleTypeList=\App\Support\VehicleOptions::types();
 $rowType=$row['type'] ?? array_key_first($vehicleTypeList);
 $rowImage=$row['image'] ?? '';
?>
<div class="vehicle-row">
 <label>จำนวนคนขั้นต่ำ<input type="number" min="1" max="99" name="vehicle_options[<?= $i ?>][min]" value="<?= e($row['min'] ?? '') ?>"></label>
 <label>จำนวนคนสูงสุด<input type="number" min="1" max="99" name="vehicle_options[<?= $i ?>][max]" value="<?= e($row['max'] ?? '') ?>"></label>
 <label>ประเภทรถ<select name="vehicle_options[<?= $i ?>][type]" data-vehicle-type><?php foreach ($vehicleTypeList as $typeKey=>$typeInfo): ?><option value="<?= e($typeKey) ?>" data-image="<?= e(base_url().$typeInfo['image']) ?>" <?= $rowType===$typeKey ? 'selected' : '' ?>><?= e($typeInfo['label']) ?></option><?php endforeach ?></select></label>
 <label>จำนวนที่นั่งของรถ<input type="number" min="1" max="99" name="vehicle_options[<?= $i ?>][seats]" value="<?= e($row['seats'] ?? '') ?>"></label>
 <button type="button" class="text-button danger" data-vehicle-remove>ลบแถวนี้</button>
 <div class="vehicle-row-photo">
  <input type="hidden" name="vehicle_options[<?= $i ?>][image]" value="<?= e($rowImage) ?>" data-vehicle-image>
  <img src="<?= e(base_url().($rowImage ?: $vehicleTypeList[$rowType]['image'])) ?>" alt="" width="128" height="72" data-vehicle-preview<?= $rowImage ? ' class="is-photo"' : '' ?>>
  <div>
   <label class="outline-link vehicle-pick"><span data-vehicle-pick-label><?= $rowImage ? 'เปลี่ยนรูปรถ' : 'อัปโหลดรูปรถจริง' ?></span><input type="file" accept="image/jpeg,image/png,image/webp" data-vehicle-file></label>
   <button type="button" class="text-button" data-vehicle-photo-remove<?= $rowImage ? '' : ' hidden' ?>>เอารูปออก ใช้ภาพวาดแทน</button>
   <small data-vehicle-status aria-live="polite"><?= $rowImage ? '' : 'ยังไม่มีรูปจริง จะแสดงภาพวาดตามประเภทรถ' ?></small>
  </div>
 </div>
</div>
