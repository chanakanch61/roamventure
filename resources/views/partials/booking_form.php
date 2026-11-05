<?php
$adminBooking=!empty($admin_booking);
if ($isPrivate) {
 $bookingToday=date('Y-m-d'); $firstStarts=[]; $lastStarts=[];
 foreach ($available as $period) {
  $first=\App\Support\PrivatePackage::first_date($period);
  if ($first===null) continue;
  $firstStarts[]=$first;
  $lastStarts[]=date('Y-m-d',strtotime($period['end_date'].' -'.((int)$period['days']-1).' days'));
 }
 $firstStart=$firstStarts ? min($firstStarts) : null;
 $lastStart=$lastStarts ? max($lastStarts) : null;
 $dateHint=$firstStart ? 'เริ่มเดินทางได้ตั้งแต่ '.thai_date($firstStart).' · ตามระยะจองล่วงหน้าและวันเปิดขายของแต่ละช่วง' : 'ยังไม่มีวันที่เปิดรับจองในช่วงนี้';
}
?>
<form action="<?= base_url() . ($adminBooking ? 'admin/bookings/new' : 'booking') ?>" method="post" class="stack-form" id="booking-form"<?= $adminBooking ? '' : ' data-quick-quote' ?>>
 <?= csrf_field() ?>
 <input type="hidden" name="request_token" value="<?= e($adminBooking ? $request_token : session('request_token')) ?>">
 <input type="hidden" name="quote" id="departure-quote" value="">
 <?php if ($adminBooking): ?><input type="hidden" name="tour_id" value="<?= (int)$booking_tour_id ?>"><?php else: ?><div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><?php endif ?>
 <?php $privateDateVars=$isPrivate ? ['bookingToday'=>$bookingToday,'dateHint'=>$dateHint,'firstStart'=>$firstStart,'lastStart'=>$lastStart] : []; ?>
 <?php if (!$adminBooking): ?><div class="booking-choose" id="booking-choose"><div class="booking-options"><?php endif ?>
 <?php if ($isPrivate): ?><?php if ($adminBooking) load_view('partials/booking_private_date',$privateDateVars); ?><label>ระดับบริการ<select name="package_level" id="private-level" required><option value=""><?= $adminBooking ? 'เลือกวันเดินทางก่อน' : 'เลือกระดับบริการ' ?></option></select></label><?php if ($adminBooking): ?><p id="private-details" class="preline"></p><?php else: ?><details class="booking-expand" id="level-details" name="booking-option" hidden><summary><span>รายละเอียดบริการ</span><b>สิ่งที่รวมในระดับนี้</b><i class="material-icons" aria-hidden="true">expand_more</i></summary><p id="private-details" class="preline fineprint"></p></details><?php endif ?><?php endif ?>
 <label <?= $isPrivate ? 'hidden' : '' ?>>วันเดินทาง<select id="departure-select" name="departure_id" required><option value="">เลือกรอบเดินทาง</option>
 <?php foreach ($available as $dep): ?><option value="<?= (int)$dep['id'] ?>" <?= $dep['seats_available']<1 ? 'disabled' : '' ?>><?= thai_date($dep['start_date']) ?> – <?= thai_date($dep['end_date']) ?> · <?= $dep['seats_available']>0 ? 'ว่าง '.(int)$dep['seats_available'].' ที่' : 'เต็มแล้ว' ?></option><?php endforeach ?>
 </select></label>
 <div class="seat-summary" <?= $isPrivate ? 'hidden' : '' ?> aria-live="polite"><div><span>ไซส์กรุ๊ป</span><strong id="group-capacity">—</strong></div><div><span>ที่นั่งว่าง</span><strong id="seats-available">—</strong></div></div>
 <div class="traveler-field"><label for="traveler-count">จำนวนผู้เดินทาง *</label><div class="quantity-control"><button type="button" id="traveler-minus" aria-label="ลดจำนวนผู้เดินทาง" aria-controls="traveler-count" disabled><i class="material-icons" aria-hidden="true">remove</i></button><div><input type="number" name="traveler_count" id="traveler-count" min="1" max="500" step="1" value="1" required disabled aria-describedby="traveler-help"><span>คน</span></div><button type="button" id="traveler-plus" aria-label="เพิ่มจำนวนผู้เดินทาง" aria-controls="traveler-count" disabled><i class="material-icons" aria-hidden="true">add</i></button></div><small id="traveler-help">เลือกวันเดินทางก่อน · จองได้ตั้งแต่ 1 คนตามที่นั่งว่าง</small></div>
 <?php if ($isPrivate && !$adminBooking): ?><details class="booking-expand" id="date-details" name="booking-option"><summary><span>วันเดินทาง</span><b id="date-summary">เลือกวันเริ่มเดินทาง</b><i class="material-icons" aria-hidden="true">expand_more</i></summary><?php load_view('partials/booking_private_date',$privateDateVars+['calendar'=>true]); ?></details><?php endif ?>
 <?php if (!$adminBooking): ?><details class="booking-expand room-details" id="room-details" name="booking-option"><summary><span>ห้องพัก</span><b id="room-summary">เลือกวันเดินทางก่อน</b><i class="material-icons" aria-hidden="true">expand_more</i></summary><?php endif ?>
 <fieldset class="room-picker"><legend>ประเภทห้องพัก *</legend>
 <?php foreach (['twin'=>['ห้องคู่ · 2 เตียงเดี่ยว',2],'double'=>['ห้องคู่ · 1 เตียงใหญ่',2],'triple'=>['ห้องพัก 3 คน',3],'single'=>['ห้องเดี่ยว',1]] as $type=>$room): ?>
 <div class="room-row" data-room="<?= $type ?>"><label for="room-<?= $type ?>-count"><?= $room[0] ?><small><?= $room[1] ?> คน / ห้อง</small><small <?= $type==='single' ? 'id="single-room-price"' : '' ?>><?= $type==='single' ? 'เลือกวันเดินทางเพื่อดูราคาเพิ่ม' : 'รวมในราคาทัวร์' ?></small></label>
 <div class="quantity-control"><button type="button" id="room-<?= $type ?>-minus" aria-label="ลดจำนวน<?= $room[0] ?>" aria-controls="room-<?= $type ?>-count" disabled><i class="material-icons" aria-hidden="true">remove</i></button><div><input type="number" name="<?= $type ?>_rooms" id="room-<?= $type ?>-count" min="0" max="500" step="1" value="0" required disabled aria-label="จำนวน<?= $room[0] ?>" aria-describedby="room-help"><span>ห้อง</span></div><button type="button" id="room-<?= $type ?>-plus" aria-label="เพิ่มจำนวน<?= $room[0] ?>" aria-controls="room-<?= $type ?>-count" disabled><i class="material-icons" aria-hidden="true">add</i></button></div></div>
 <?php endforeach ?>
 <small id="room-help" aria-live="polite">เลือกวันเดินทางก่อน แล้วระบุจำนวนห้องพัก</small></fieldset>
 <?php if (!$adminBooking): ?></details></div><?php endif ?>
 <div class="booking-total" aria-live="polite"><div><span>ราคาทัวร์ต่อคน</span><strong id="price-person">—</strong></div><div><span>ค่าทัวร์ <span id="base-count"></span></span><b id="base-total">—</b></div><div><span>ค่าพักเดี่ยวเพิ่ม <span id="supplement-count"></span></span><b id="supplement-total">—</b></div><div class="grand-total"><span>ยอดรวมทั้งหมด</span><strong id="price-total">—</strong></div><small><?= $isPrivate ? 'กรุ๊ปส่วนตัว · ทีมงานตรวจสอบรถ โรงแรม และบริการก่อนยืนยัน' : 'คำขอยังไม่กันที่นั่ง ตัดที่นั่งเมื่อทีมงานยืนยันแล้ว' ?></small></div>
 <?php if (!$adminBooking): ?><button type="button" class="btn full" id="booking-next" disabled>ถัดไป</button></div>
 <div class="booking-contact" id="booking-contact" tabindex="-1" hidden<?= !empty($old['customer_name']) ? ' data-open' : '' ?>>
 <?php load_view('partials/trip_summary',['isPrivate'=>$isPrivate]); ?>
 <div class="booking-contact-fields"><?php endif ?>
 <?php if (!$adminBooking): ?><div class="form-grid"><?php endif ?><label><span>ชื่อผู้ติดต่อ *</span><input<?= $adminBooking ? '' : ' placeholder="ชื่อผู้ติดต่อ *"' ?> name="customer_name" required maxlength="120" autocomplete="name" value="<?= e($old['customer_name'] ?? '') ?>"></label>
 <label><span>เบอร์โทรศัพท์ *</span><input<?= $adminBooking ? '' : ' placeholder="เบอร์โทรศัพท์ *"' ?> type="tel" name="phone" required maxlength="25" pattern="[0-9+() \-]{8,25}" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"></label><?php if (!$adminBooking): ?></div><?php endif ?>
 <div class="form-grid"><label><span>LINE ID</span><input<?= $adminBooking ? '' : ' placeholder="LINE ID"' ?> name="line_id" maxlength="100" value="<?= e($old['line_id'] ?? '') ?>"></label><label><span>อีเมล</span><input<?= $adminBooking ? '' : ' placeholder="อีเมล"' ?> type="email" name="email" maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label></div>
 <?php $ticketTour=$tour ?? ($selected_tour ?? []); if (empty($ticketTour['flight_included'])): ?><label class="checkbox ticket-help-option"><input type="checkbox" name="ticket_help" id="ticket-help" value="1" <?= !empty($old['ticket_help']) ? 'checked' : '' ?>><span>ให้ทีมงานช่วยจองตั๋วเครื่องบิน<small>ไม่รวมในยอดคำขอจอง ทีมงานแจ้งราคาตั๋วจริงให้ยืนยันก่อนซื้อ</small></span></label><?php endif ?>
 <label><span><?= $adminBooking ? 'ข้อมูลเพิ่มเติม' : 'หมายเหตุเพิ่มเติม' ?></span><textarea name="note" rows="<?= $adminBooking ? 3 : 2 ?>" maxlength="2000" placeholder="<?= $adminBooking ? '' : 'หมายเหตุเพิ่มเติม ' ?>เช่น มีเด็กหรือผู้สูงอายุร่วมเดินทาง"><?= e($old['note'] ?? '') ?></textarea></label>
 <?php if ($adminBooking): ?>
 <div class="form-grid"><label>สถานะเริ่มต้น<select name="status"><?php foreach (['new','contacted','confirmed'] as $s): ?><option value="<?= $s ?>" <?= ($old['status'] ?? 'new')===$s ? 'selected' : '' ?>><?= status_label($s) ?></option><?php endforeach ?></select></label><label>เวลาชำระหลังยืนยัน (ชั่วโมง)<input type="number" name="payment_hours" min="1" max="720" value="<?= e($old['payment_hours'] ?? 24) ?>" required></label></div>
 <label>บันทึกภายใน<textarea name="admin_note" rows="3" maxlength="5000" placeholder="ช่องทางที่ลูกค้าติดต่อเข้ามา และรายละเอียดที่ตกลงกัน"><?= e($old['admin_note'] ?? '') ?></textarea></label>
 <label class="checkbox"><input type="checkbox" name="consent" value="1" required><span>ลูกค้ารับทราบ <a href="<?= base_url() . 'privacy' ?>" target="_blank" rel="noopener">การใช้ข้อมูลส่วนบุคคล</a> แล้ว</span></label>
 <button type="submit" class="btn full" data-busy="กำลังสร้างการจอง…" disabled>สร้างการจอง</button><p class="fineprint">ใช้ราคาและที่นั่งว่างเดียวกับหน้าเว็บ · เลือก “ยืนยันแล้ว” เพื่อตัดที่นั่งและออก Invoice ทันที · ไม่ส่งข้อความหาลูกค้าอัตโนมัติ</p>
 <?php else: ?>
 <label class="checkbox"><input type="checkbox" name="consent" value="1" required><span>รับทราบ <a href="<?= base_url() . 'privacy' ?>" target="_blank" rel="noopener">การใช้ข้อมูลส่วนบุคคล</a> เพื่อดำเนินการคำขอและติดต่อกลับ</span></label>
 <div class="booking-actions"><button type="button" class="booking-back" id="booking-back"><i class="material-icons" aria-hidden="true">arrow_back</i> ย้อนกลับ</button><button type="submit" class="btn" disabled>ส่งคำขอจอง <span><i class="material-icons" aria-hidden="true">north_east</i></span></button></div><p class="fineprint">ยังไม่ใช่การยืนยันการจอง และยังไม่มีการเรียกเก็บเงิน</p></div></div>
 <?php endif ?>
</form>
<script type="application/json" id="departure-data"><?= json_encode($available,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
