<section id="departure-periods"><h2>วันเดินทาง ไซส์กรุ๊ป และที่นั่งว่าง</h2><p>จองได้ตั้งแต่ 1 คน ไม่จำเป็นต้องจองเต็มกรุ๊ป ที่นั่งว่างคำนวณจากรายการที่ยืนยันแล้ว</p>
<div class="table-scroll"><table class="departure-table"><thead><tr><th>วันเดินทาง</th><th>ไซส์กรุ๊ป</th><th>ที่นั่งว่าง</th><th>ราคาต่อคน</th></tr></thead><tbody>
<?php foreach ($available as $dep): ?><tr data-departure="<?= (int)$dep['id'] ?>"><td><?= thai_date($dep['start_date']) ?><small>ถึง <?= thai_date($dep['end_date']) ?></small></td><td><?= (int)$dep['capacity'] ?> คน</td><td><span class="seat-badge <?= $dep['seats_available']<1 ? 'sold-out' : '' ?>"><?= $dep['seats_available']>0 ? 'ว่าง '.(int)$dep['seats_available'].' ที่นั่ง' : 'เต็มแล้ว' ?></span></td><td>฿<?= money($dep['price_per_person']) ?></td></tr><?php endforeach ?>
<?php if (!$available): ?><tr><td colspan="4">ยังไม่มีรอบเปิดรับคำขอ กรุณาสอบถามทีมงาน</td></tr><?php endif ?>
</tbody></table></div></section>
