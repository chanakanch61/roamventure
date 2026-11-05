<div class="vehicle-edit" data-vehicle-next="<?= count($vehicleRows) ?>" data-upload-url="<?= e($uploadUrl) ?>" data-upload-folder="<?= e($uploadFolder) ?>" data-asset-base="<?= e(base_url()) ?>" data-template-save-url="<?= e(base_url().'admin/vehicle-templates/from-program') ?>">
 <div class="section-head"><div><h3><?= $vehicleTemplates===null ? 'ตารางรถ' : 'รถตามจำนวนผู้เดินทาง' ?></h3><p><?= $vehicleTemplates===null ? '' : 'แสดงบนหน้าโปรแกรมพร้อมรูปรถ เว้นว่างไว้ถ้าไม่ต้องการแสดง ควรตรงกับรายละเอียด “รถและการรับส่ง” ด้านบน ' ?>รูปรถเป็น JPG, PNG หรือ WebP ไม่เกิน 5 MB แนะนำแนวนอน 16:9 แถวที่ไม่มีรูปจะใช้ภาพวาดแทน</p></div><div class="actions"><button type="button" class="outline-link" data-vehicle-sample data-rows="<?= e(json_encode(\App\Support\VehicleOptions::sample())) ?>"<?= $vehicleRows ? ' hidden' : '' ?>>ใส่ตารางตัวอย่าง</button><button type="button" class="outline-link" data-vehicle-add>＋ เพิ่มรถ</button></div></div>
 <?php if ($vehicleTemplates!==null): ?><div class="vehicle-template-bar">
  <label>ดึงจาก template<select data-vehicle-template-select><option value="">เลือก template</option><?php foreach ($vehicleTemplates as $vehicleTemplate): ?><option value="<?= (int)$vehicleTemplate['id'] ?>" data-rows="<?= e($vehicleTemplate['vehicle_rows']) ?>"><?= e($vehicleTemplate['name']) ?></option><?php endforeach ?></select></label>
  <button type="button" class="outline-link" data-vehicle-template-apply>ใช้ template นี้</button>
  <button type="button" class="text-button" data-vehicle-template-save>บันทึกตารางนี้เป็น template</button>
  <a class="text-link" href="<?= base_url().'admin/vehicle-templates' ?>" target="_blank" rel="noopener">จัดการ template ↗</a>
  <small data-vehicle-template-status aria-live="polite"></small>
 </div><?php endif ?>
 <div id="vehicle-list"><?php foreach ($vehicleRows as $i=>$row) load_view('admin/vehicle_row',['i'=>$i,'row'=>$row]); ?></div>
 <template id="vehicle-template"><?php load_view('admin/vehicle_row',['i'=>'__INDEX__','row'=>[]]); ?></template>
</div>
