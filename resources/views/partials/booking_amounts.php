<?php load_view('partials/package_snapshot',['booking'=>$booking]); ?>
<div class="booking-total">
 <div><span>ผู้เดินทาง <?= (int)$booking['traveler_count'] ?> คน</span><b><?= e(booking_rooms_label($booking)) ?></b></div>
 <div><span>ค่าทัวร์ <?= (int)$booking['traveler_count'] ?> × ฿<?= money($booking['price_per_person']) ?></span><b>฿<?= money($booking['price_per_person']*$booking['traveler_count']) ?></b></div>
 <?php $singleRooms=$booking['single_rooms'] ?? ($booking['room_type']==='single' ? $booking['traveler_count'] : 0); ?>
 <div><span>ค่าพักเดี่ยวเพิ่ม<?= $singleRooms>0 ? ' '.(int)$singleRooms.' ห้อง × ฿'.money($booking['single_supplement']) : '' ?></span><b>฿<?= money($booking['supplement_total']) ?></b></div>
 <div class="grand-total"><span>ยอดรวมตามคำขอ</span><strong>฿<?= money($booking['total_price']) ?></strong></div>
</div>
