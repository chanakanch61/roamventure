<div class="repeater-item departure-item" data-index="<?= $i ?>">
 <input type="hidden" name="departures[<?= $i ?>][id]" value="<?= (int)($dep['id'] ?? 0) ?>">
 <div class="repeater-head"><h3>รอบเดินทาง</h3><button type="button" class="text-button danger" data-remove>นำรอบออก</button></div>
 <?php $depAnytime=\App\Support\PrivatePackage::anytime($dep); $depMonths=(int)($dep['advance_months'] ?? 0) ?: 12; ?>
 <fieldset class="departure-mode" data-private-only hidden><legend>ช่วงวันที่ลูกค้าเลือกเดินทางได้</legend>
  <label><input type="radio" name="departures[<?= $i ?>][anytime]" value="1" data-anytime-toggle <?= $depAnytime ? 'checked' : '' ?>><span><strong>เดินทางได้ตลอด</strong><small>ไม่ต้องกำหนดวันเริ่ม–วันสิ้นสุด ลูกค้าเลือกวันเองได้ทุกวัน</small></span></label>
  <label><input type="radio" name="departures[<?= $i ?>][anytime]" value="0" data-anytime-toggle <?= $depAnytime ? '' : 'checked' ?>><span><strong>กำหนดช่วงวัน</strong><small>ระบุวันเริ่มและวันสิ้นสุดของพีเรียด</small></span></label>
 </fieldset>
 <div class="form-grid" data-range-fields><label><span data-start-label>วันเริ่มเดินทาง</span><input type="date" required name="departures[<?= $i ?>][start_date]" value="<?= $depAnytime ? '' : e($dep['start_date'] ?? '') ?>"></label><label><span data-end-label>วันกลับ</span><input type="date" required name="departures[<?= $i ?>][end_date]" value="<?= $depAnytime ? '' : e($dep['end_date'] ?? '') ?>"></label></div>
 <div data-private-only hidden><div class="form-grid" data-anytime-fields hidden>
  <label>จองล่วงหน้าได้สูงสุด<select name="departures[<?= $i ?>][advance_months]"><?php foreach (\App\Support\PrivatePackage::ADVANCE_MONTHS as $months): ?><option value="<?= $months ?>" <?= $months===$depMonths ? 'selected' : '' ?>><?= $months ?> เดือน</option><?php endforeach ?></select><small>ลูกค้าเลือกวันได้ตั้งแต่วันตัดรอบการจองถัดไป จนถึงวันนี้ + จำนวนเดือนนี้ (เลื่อนไปทุกวัน)</small></label>
  <p class="fineprint">มีได้ 1 รอบต่อโปรแกรม · ถ้ามีรอบแบบกำหนดช่วงวันด้วย ทริปที่อยู่ในช่วงนั้นจะใช้ราคาของช่วงนั้นแทน เช่น ช่วงปีใหม่</p>
 </div></div>
 <label class="checkbox"><input type="checkbox" name="departures[<?= $i ?>][active]" value="1" <?= !isset($dep['active']) || $dep['active'] ? 'checked' : '' ?>> เปิดรับคำขอสำหรับรอบนี้</label>
 <div class="form-grid" data-fixed-only><label>ไซส์กรุ๊ป / ที่นั่งทั้งหมด<input type="number" name="departures[<?= $i ?>][capacity]" required min="1" max="500" value="<?= e($dep['capacity'] ?? 6) ?>"><small>จำนวนสูงสุดของรอบ ไม่ใช่จำนวนที่ลูกค้าต้องจอง</small></label><label>ราคาต่อคน (บาท)<input type="number" name="departures[<?= $i ?>][price_per_person]" required min="0.01" max="99999999.99" step="0.01" value="<?= e($dep['price_per_person'] ?? '') ?>"></label></div>
 <div data-private-only hidden>
 <p data-range-fields>ทั้งวันไปและวันกลับต้องอยู่ในช่วงนี้ · แต่ละ booking เป็นกรุ๊ปส่วนตัว · ช่วงวันแต่ละพีเรียดต้องไม่ซ้อนกัน (ซ้อนกับรอบเดินทางได้ตลอดได้)</p>
 <label>วันงดเดินทาง<textarea name="departures[<?= $i ?>][blackout_dates]" placeholder="YYYY-MM-DD หนึ่งวันต่อบรรทัด"><?= e(implode("\n",json_decode($dep['blackout_dates'] ?? '[]',true) ?: [])) ?></textarea><small>ทริปต้องไม่คร่อมวันงดเดินทาง</small></label>
 <h4>ราคาต่อคนตามระดับและขนาดกรุ๊ป</h4><div class="private-rates"><?php $rates=json_decode($dep['private_rates'] ?? '[]',true) ?: [[]]; foreach ($rates as $j=>$rate) load_view('admin/private_rate',['i'=>$i,'j'=>$j,'rate'=>$rate]); ?></div>
 <button type="button" class="outline-link" data-rate-add>＋ เพิ่มระดับ / ช่วงจำนวนคน</button>
 </div>
 <div class="departure-discount">
  <div class="form-grid"><label data-fixed-only>ส่วนลด (%)<input type="number" name="departures[<?= $i ?>][discount_percent]" min="0" max="<?= \App\Support\DepartureDiscount::MAX_PERCENT ?>" step="1" inputmode="numeric" placeholder="0" value="<?= !empty($dep['discount_percent']) ? (int)$dep['discount_percent'] : '' ?>" data-discount-input><small>0–<?= \App\Support\DepartureDiscount::MAX_PERCENT ?> · เว้นว่างหรือ 0 = ไม่มีส่วนลด · ไม่ลดค่าพักเดี่ยว</small></label>
  <label>ส่วนลดถึงวันที่ (ไม่บังคับ)<input type="date" name="departures[<?= $i ?>][discount_until]" value="<?= e($dep['discount_until'] ?? '') ?>"><small>หลังวันนี้กลับไปใช้ราคาปกติเอง · เว้นว่าง = ลดจนกว่าจะแก้<span data-private-only hidden> · ใช้กับส่วนลดทุกแถวในตารางราคาด้านบน</span></small></label></div>
  <p class="fineprint" data-discount-preview data-fixed-only aria-live="polite"></p>
 </div>
 <?php $depSetKey=$dep['flight_set_key'] ?? (!empty($dep['flight_set_id']) ? 's'.$dep['flight_set_id'] : ''); ?>
 <label data-fixed-only>ชุดเที่ยวบินของรอบนี้<select name="departures[<?= $i ?>][flight_set]" data-flight-set-select><option value="">ยังไม่เลือกชุด</option><?php foreach ($tour['flight_sets'] ?? [] as $s=>$depSet): $optionKey=$depSet['key'] ?? (!empty($depSet['id']) ? 's'.$depSet['id'] : 'n'.$s); ?><option value="<?= e($optionKey) ?>" <?= $optionKey===$depSetKey ? 'selected' : '' ?>><?= e($depSet['name']) ?></option><?php endforeach ?></select><small>สร้างชุดในส่วนเที่ยวบินด้านบน รอบที่บินเหมือนกันเลือกชุดเดียวกันได้</small></label>
 <label class="single-supplement-field">ค่าพักเดี่ยวเพิ่ม (บาท / คน / ตลอดทริป)<input type="number" name="departures[<?= $i ?>][single_supplement]" min="0" max="99999999.99" step="0.01" value="<?= e($dep['single_supplement'] ?? '') ?>" placeholder="ยังไม่กำหนด"><small>เพิ่มจากราคาปกติสำหรับผู้ที่เลือกพักเดี่ยว · 0 = ไม่คิดเพิ่ม · เว้นว่าง = ให้สอบถามทีมงานก่อน</small></label>
 <label class="checkbox"><input type="checkbox" name="departures[<?= $i ?>][deposit_enabled]" value="1" <?= !empty($dep['deposit_enabled']) ? 'checked' : '' ?>> เปิดรับมัดจำในพีเรียดนี้</label>
 <div class="form-grid"><label>มัดจำได้เมื่อเหลืออย่างน้อย (วันก่อนเดินทาง)<input type="number" name="departures[<?= $i ?>][deposit_cutoff_days]" min="0" max="3650" value="<?= e($dep['deposit_cutoff_days'] ?? 30) ?>" required></label><label>ชำระส่วนที่เหลือก่อนเดินทาง (วัน)<input type="number" name="departures[<?= $i ?>][balance_due_days]" min="0" max="3650" value="<?= e($dep['balance_due_days'] ?? 14) ?>" required></label></div>
 <div class="seat-summary" data-fixed-only><div><span>ยืนยันแล้ว</span><strong><?= (int)($dep['booked_seats'] ?? 0) ?> คน</strong></div><div><span>ที่นั่งว่างที่บันทึกอยู่</span><strong><?= (int)($dep['seats_available'] ?? $dep['capacity'] ?? 6) ?> ที่นั่ง</strong></div></div>
 <p class="fineprint">คำขอใหม่และติดต่อแล้วยังไม่กันที่นั่ง · รอบที่มีคำขอแล้วให้ปิดรับแทนการลบหรือเปลี่ยนวันที่</p>
</div>
