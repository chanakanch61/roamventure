<?php $bookingFlights=json_decode($booking['flight_snapshot'] ?? '',true) ?: []; ?>
<?php if (!empty($booking['ticket_help'])): ?><p class="notice"><?= !empty($booking_flights_admin) ? 'ลูกค้าขอให้ทีมงานช่วยจองตั๋วเครื่องบิน แจ้งราคาตั๋วจริงให้ลูกค้ายืนยันก่อนซื้อ' : 'คุณขอให้ทีมงานช่วยจองตั๋วเครื่องบิน ทีมงานจะแจ้งราคาตั๋วจริงให้ยืนยันก่อนซื้อ' ?></p><?php endif ?>
<?php if ($bookingFlights): ?>
<h3>เที่ยวบิน ณ เวลาจอง</h3>
<div class="table-scroll"><table class="booking-flights"><thead><tr><th>ขา</th><th>สายการบิน</th><th>เที่ยวบิน</th><th>เส้นทาง</th><th>เวลา</th><th>ในราคาทัวร์</th></tr></thead><tbody>
<?php foreach ($bookingFlights as $flight): ?><tr><td><?= e(flight_leg_types()[$flight['leg_type']] ?? '') ?><?= $flight['day_number'] ? ' · วันที่ '.(int)$flight['day_number'] : '' ?></td><td><?= e($flight['airline_name']) ?></td><td><?= e($flight['flight_number']) ?></td><td><?= e($flight['from_airport']) ?> → <?= e($flight['to_airport']) ?></td><td><?= e($flight['depart_time']) ?>–<?= e($flight['arrive_time']) ?><?= $flight['arrive_next_day'] ? ' (+1 วัน)' : '' ?></td><td><?= $flight['included'] ? 'รวม' : 'ไม่รวม' ?></td></tr><?php endforeach ?>
</tbody></table></div>
<?php endif ?>
