<?php
use App\Repositories\GroupTourRepository as Groups;
$r = $request;
$statuses = Groups::statuses();
$pill = ['new' => 'new', 'contacted' => 'contacted', 'quoted' => 'contacted', 'confirmed' => 'confirmed', 'closed' => 'cancelled'];
$dates = $r['date_from'] ? thai_date($r['date_from']).($r['date_to'] ? ' – '.thai_date($r['date_to']) : '') : '';
$services = Groups::service_labels($r['services']);
$lineChat = $r['line_id'] !== '' ? 'https://line.me/R/ti/p/'.rawurlencode($r['line_id']) : '';
?>
<div class="section-head"><div><span class="eyebrow">GROUP TOUR REQUEST</span><h1><?= e($r['reference']) ?></h1><p>รับคำขอเมื่อ <?= e($r['created_at']) ?> · อัปเดตล่าสุด <?= e($r['updated_at']) ?></p></div><a class="outline-link" href="<?= base_url().'admin/group-requests' ?>">กลับรายการ</a></div>
<div class="admin-two-col">
 <section class="panel">
  <span class="pill <?= e($pill[$r['status']] ?? 'new') ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span>
  <h2><?= e($r['destination']) ?></h2>
  <p><?= e(Groups::group_types()[$r['group_type']] ?? $r['group_type']) ?></p>
  <h3>รายละเอียดกรุ๊ป</h3>
  <dl class="info-list">
   <dt>ผู้เดินทาง</dt><dd><?= (int) $r['adults'] ?> ผู้ใหญ่<?= (int) $r['children'] ? ' · '.(int) $r['children'].' เด็ก' : '' ?> (รวม <?= (int) $r['adults'] + (int) $r['children'] ?> ท่าน)</dd>
   <dt>วันเดินทาง</dt><dd><?= e($dates ?: '—') ?></dd>
   <dt>ช่วงเวลาคร่าว ๆ</dt><dd><?= e($r['date_note'] ?: '—') ?></dd>
   <dt>จำนวนวัน</dt><dd><?= $r['days'] ? (int) $r['days'].' วัน' : '—' ?></dd>
   <dt>งบประมาณต่อคน</dt><dd><?= e(Groups::budgets()[$r['budget']] ?? '—') ?></dd>
   <dt>ระดับที่พัก</dt><dd><?= e(Groups::hotel_levels()[$r['hotel_level']] ?? '—') ?></dd>
   <dt>บริการที่ต้องการ</dt><dd><?= e($services ? implode(', ', $services) : '—') ?></dd>
   <dt>รายละเอียดเพิ่มเติม</dt><dd class="preline"><?= e($r['message'] ?: '—') ?></dd>
   <dt>ส่งจากหน้า</dt><dd><?= e($r['source'] ?: 'grouptour') ?></dd>
  </dl>
  <h3>ข้อมูลผู้ติดต่อ</h3>
  <dl class="info-list">
   <dt>ชื่อ</dt><dd><?= e($r['contact_name']) ?></dd>
   <dt>องค์กร / กลุ่ม</dt><dd><?= e($r['organization'] ?: '—') ?></dd>
   <dt>โทรศัพท์</dt><dd><a class="text-link" href="tel:<?= e(preg_replace('/[^+0-9]/', '', $r['phone'])) ?>"><?= e($r['phone']) ?></a></dd>
   <dt>อีเมล</dt><dd><?= $r['email'] ? '<a class="text-link" href="mailto:'.e($r['email']).'">'.e($r['email']).'</a>' : '—' ?></dd>
   <dt>LINE ID</dt><dd><?= $lineChat ? '<a class="text-link" href="'.e($lineChat).'" target="_blank" rel="noopener">'.e($r['line_id']).' ↗</a>' : '—' ?></dd>
  </dl>
 </section>
 <section class="panel">
  <h2>ติดตามคำขอ</h2>
  <form method="post" class="stack-form"><?= csrf_field() ?>
   <label>สถานะ<select name="status"><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>"<?= $r['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label>
   <fieldset class="gr-dates"><legend>วันเดินทาง<?php if (! $r['date_from']): ?> <span class="pill new">ยังไม่ระบุวันที่</span><?php endif ?></legend>
    <div class="form-grid"><label>วันเริ่มเดินทาง<input type="date" name="date_from" value="<?= e((string) $r['date_from']) ?>"></label><label>วันกลับ<input type="date" name="date_to" value="<?= e((string) $r['date_to']) ?>"></label></div>
    <div class="form-grid"><label>จำนวนวัน<input type="number" name="days" min="1" max="60" value="<?= e((string) $r['days']) ?>" placeholder="คำนวณจากวันที่ให้"></label><label>ช่วงเวลาคร่าว ๆ<input name="date_note" maxlength="120" value="<?= e($r['date_note']) ?>" placeholder="เช่น ช่วงสงกรานต์"></label></div>
    <small>ลูกค้าระบุมาคร่าว ๆ ได้ ยืนยันวันที่กับลูกค้าแล้วกรอกที่นี่ ใบเสนอราคาและผลงานที่สร้างหลังจากนี้จะใช้วันที่ใหม่ (ใบเสนอราคาเดิมไม่เปลี่ยน)</small>
   </fieldset>
   <label>บันทึกภายใน<textarea name="admin_note" rows="8" maxlength="5000" placeholder="เช่น โทรคุยแล้ว ส่งใบเสนอราคาวันที่… ลูกค้าขอปรับโรงแรม…"><?= e($r['admin_note'] ?? '') ?></textarea></label>
   <button class="btn" type="submit">บันทึก</button>
  </form>
  <p class="fineprint">บันทึกภายในเห็นเฉพาะทีมงาน ลูกค้าไม่เห็น</p>
  <h3>ใบเสนอราคา</h3>
  <?php $quoteStatuses = App\Repositories\GroupQuoteRepository::statuses(); ?>
  <?php if ($quotes): ?><ul class="gq-quotes"><?php foreach ($quotes as $quote): ?><li><span><a class="text-link" href="<?= base_url().'admin/group-quotes/'.(int) $quote['id'] ?>"><b><?= e($quote['number']) ?></b></a><small><?= e(thai_date($quote['issue_date'])) ?> · <?= e($quoteStatuses[$quote['status']] ?? $quote['status']) ?></small></span><span>฿<?= money($quote['total']) ?> · <a class="text-link" href="<?= base_url().'admin/group-quotes/'.(int) $quote['id'].'/print' ?>" target="_blank" rel="noopener">พิมพ์ ↗</a></span></li><?php endforeach ?></ul><?php else: ?><p class="fineprint">ยังไม่มีใบเสนอราคาสำหรับคำขอนี้</p><?php endif ?>
  <a class="btn" href="<?= base_url().'admin/group-quotes/new?request='.(int) $r['id'] ?>">＋ สร้างใบเสนอราคา</a>
  <p class="fineprint">ระบบเติมชื่อลูกค้า เบอร์โทร จุดหมาย วันเดินทาง และจำนวนผู้ใหญ่/เด็กจากคำขอนี้ให้</p>
  <h3>ผลงานบนหน้าเว็บ</h3>
  <?php if ($linked_trip): ?><p>คำขอนี้ถูกใช้สร้างผลงาน <a class="text-link" href="<?= base_url().'admin/group-trips/'.(int) $linked_trip['id'] ?>"><?= e($linked_trip['title']) ?> ↗</a></p>
  <?php else: ?><p class="fineprint">เมื่อเดินทางเสร็จแล้ว สร้างผลงานเพื่อแสดงในหัวข้อ “ผลงานกรุ๊ปเหมาที่ผ่านมา” ได้ ระบบจะเติมจุดหมาย ประเภทกรุ๊ป วันเดินทาง และจำนวนผู้เดินทางให้ ควรขออนุญาตลูกค้าก่อนแสดงชื่อองค์กรหรือรูปภาพ</p><a class="outline-link" href="<?= base_url().'admin/group-trips/new?from_request='.(int) $r['id'] ?>">สร้างผลงานจากคำขอนี้</a><?php endif ?>
 </section>
</div>
