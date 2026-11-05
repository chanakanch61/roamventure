<div class="private-rate form-grid">
 <label>ระดับ<select required name="departures[<?= $i ?>][rates][<?= $j ?>][level_id]"><option value="">เลือกระดับบริการ</option><?php foreach (app(\App\Repositories\PackageLevelRepository::class)->all() as $level): ?><option value="<?= (int)$level['id'] ?>" <?= (!empty($rate['level_id']) ? (int)$rate['level_id']===(int)$level['id'] : ($rate['level'] ?? '')===$level['name']) ? 'selected' : '' ?>><?= e($level['name']) ?></option><?php endforeach ?></select><small><a href="<?= base_url().'admin/package-levels' ?>" target="_blank" rel="noopener">จัดการระดับบริการ ↗</a> · เพิ่มแล้วโหลดหน้านี้ใหม่</small></label>
 <label>ขั้นต่ำ (คน)<input type="number" min="1" max="500" required name="departures[<?= $i ?>][rates][<?= $j ?>][min]" value="<?= e($rate['min'] ?? 2) ?>"></label>
 <label>สูงสุด (คน)<input type="number" min="1" max="500" required name="departures[<?= $i ?>][rates][<?= $j ?>][max]" value="<?= e($rate['max'] ?? 3) ?>"></label>
 <label>ราคาต่อคน (บาท)<input type="number" min="0.01" max="99999999.99" step="0.01" required name="departures[<?= $i ?>][rates][<?= $j ?>][price]" value="<?= e($rate['price'] ?? '') ?>"></label>
 <label>บริการที่ได้รับ<textarea maxlength="1000" name="departures[<?= $i ?>][rates][<?= $j ?>][details]" placeholder="โรงแรม รถ อาหาร และบริการของเรตนี้"><?= e($rate['details'] ?? '') ?></textarea></label>
 <button type="button" class="text-button danger" data-rate-remove>ลบราคาแถวนี้</button>
</div>
