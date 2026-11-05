<div class="section-head"><h1><?= e($title) ?></h1><a class="outline-link" href="<?= base_url().'admin/vehicle-templates' ?>">กลับรายการ</a></div>
<?php if ($errors): ?><div class="notice error" role="alert"><ul><?php foreach (array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" class="panel stack-form" id="vehicle-template-form"><?= csrf_field() ?>
 <label>ชื่อ template *<input name="name" required maxlength="120" value="<?= e($template['name']) ?>" placeholder="เช่น Land A"><small>แนะนำให้ตั้งตามชื่อ land หรือเส้นทาง เพื่อเลือกได้ง่ายตอนลงโปรแกรม</small></label>
 <?php load_view('admin/vehicle_options',['vehicleRows'=>\App\Support\VehicleOptions::decode($template['vehicle_rows']),'uploadUrl'=>base_url().'admin/vehicle-templates/photo','uploadFolder'=>'','vehicleTemplates'=>null]); ?>
 <button type="submit" class="btn">บันทึก template</button>
</form>
