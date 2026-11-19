<?php
// Payment Summary column on booking/detail. The whole payment flow (choose amount → transfer → slip)
// runs inside this card; BookingController::payment() re-renders this partial for script requests.
$s=$billing; $token=$booking['access_token']; $repo=app(\App\Repositories\BillingRepository::class); $now=date('Y-m-d H:i:s');
$p=$s['active']; if (!$p) foreach ($s['payments'] as $candidate) if ($candidate['status']==='awaiting_upload') { $p=$candidate; break; }
$payable=$booking['status']==='confirmed' && $s['invoice'] && $s['remaining']>0;
$overdue=$payable && $s['due']<$now;
$step=!$p ? 'choose' : ($p['status']==='pending' ? 'done' : 'transfer');
$flowStep=($step==='transfer' && ($pay_step ?? null)==='slip') ? 'slip' : $step;
$latest=$s['payments'][0] ?? null; $rejected=$latest && $latest['status']==='rejected' ? $latest : null;
$payUrl=base_url().'booking/payment/'.$token;
$label=!$p ? ($s['paid']>0 ? 'ชำระยอดคงเหลือ' : 'ชำระเงิน / แจ้งโอน') : ($p['status']==='pending' ? 'ดูสลิปที่ส่ง' : 'ทำรายการต่อ · โอนเงิน / แจ้งสลิป');
?>
<section class="panel payment-summary"><span class="eyebrow">PAYMENT SUMMARY</span><h2><?= $s['remaining']===0 ? 'ชำระครบแล้ว' : ($p && $p['status']==='pending' ? 'รอตรวจสอบสลิป' : ($s['paid']>0 ? 'ชำระบางส่วนแล้ว' : 'ยังไม่ชำระเงิน')) ?></h2><div class="booking-total"><div><span>ยอดจองทั้งหมด</span><b>฿<?= money($s['invoice'] ? $s['invoice']['total_amount'] : $repo->bill_total($booking) / 100) ?></b></div><div><span>ชำระแล้ว (ตรวจสอบแล้ว)</span><b>฿<?= money($s['paid']/100) ?></b></div><div class="grand-total"><span>ยอดคงเหลือ</span><strong>฿<?= money($s['remaining']/100) ?></strong></div></div>
<?php if ($booking['status']==='cancelled'): ?><p class="notice">การจองถูกยกเลิกแล้ว<?php if ($s['paid']>0): ?> · มียอดรับเงิน กรุณาติดต่อทีมงานเพื่อตรวจสอบการคืนเงิน<?php endif ?></p>
<?php elseif ($payable): ?>
<p>กำหนดชำระ: <b><?= e($s['due']) ?></b></p><?php if ($overdue): ?><p class="notice">พ้นกำหนดชำระ กรุณาติดต่อทีมงาน<?= $p && $p['status']==='awaiting_upload' ? ' หากโอนแล้วสามารถส่งสลิปของรายการเดิมให้ตรวจสอบได้' : '' ?></p><?php endif ?>
<?php if ($p || !$overdue): ?>
<details class="pay-flow" id="pay-flow" data-step="<?= e($flowStep) ?>"<?= !empty($pay_open) || !empty($pay_error) ? ' open' : '' ?>>
 <summary class="btn full"><span class="pay-flow-closed"><?= e($label) ?> <i class="material-icons" aria-hidden="true">north_east</i></span><span class="pay-flow-opened">ซ่อนขั้นตอนชำระเงิน <i class="material-icons" aria-hidden="true">expand_less</i></span></summary>
 <div class="pay-flow-body">
 <?php if ($step!=='done'): ?><ol class="pay-steps" aria-label="ขั้นตอนชำระเงิน"><li data-step="choose">1. เลือกยอด</li><li data-step="transfer">2. โอนเงิน</li><li data-step="slip">3. แจ้งสลิป</li></ol><?php endif ?>
 <?php if (!empty($pay_error)): ?><p class="notice error" role="alert"><?= e($pay_error) ?></p><?php endif ?>

 <?php if ($step==='choose'): ?>
  <?php if ($rejected): ?><p class="notice">สลิปของรายการ <?= e($rejected['reference']) ?> ไม่ผ่านการตรวจสอบ<?= $rejected['review_note'] ? ': '.e($rejected['review_note']) : '' ?> · กรุณาเริ่มรายการใหม่</p><?php endif ?>
  <form method="post" action="<?= e($payUrl) ?>" class="pay-choices" data-pay-form><?= csrf_field() ?><input type="hidden" name="action" value="start">
   <?php if ($s['deposit_allowed']): ?><label class="pay-choice"><input type="radio" name="kind" value="deposit" checked><span><b>ชำระมัดจำ</b><strong>฿<?= money($s['invoice']['deposit_amount']) ?></strong><small>ส่วนที่เหลือ ฿<?= money(($s['remaining']-$repo->cents($s['invoice']['deposit_amount']))/100) ?> รวมค่าพักเดี่ยว ชำระภายใน <?= e($s['invoice']['balance_due_at']) ?></small></span></label><?php endif ?>
   <label class="pay-choice"><input type="radio" name="kind" value="full"<?= !$s['deposit_allowed'] ? ' checked' : '' ?>><span><b>ชำระเต็มยอดคงเหลือ</b><strong>฿<?= money($s['remaining']/100) ?></strong><small>ชำระครั้งเดียวครบยอด</small></span></label>
   <button class="btn full" type="submit">ดูช่องทางชำระเงิน <i class="material-icons" aria-hidden="true">north_east</i></button>
  </form>

 <?php elseif ($step==='transfer'): $a=json_decode($p['account_snapshot'],true) ?: []; ?>
  <div class="pay-transfer">
   <div class="pay-amount"><div><span class="eyebrow"><?= $p['kind']==='deposit' ? 'DEPOSIT / มัดจำ' : 'BALANCE / ยอดคงเหลือ' ?></span><strong>฿<?= money($p['expected_amount']) ?></strong><small><?= e($p['reference']) ?></small></div><span class="pay-deadline">โอนภายใน <?= e($p['expires_at']) ?></span></div>
   <?php if ($p['expires_at']<$now): ?><p class="notice">รายการหมดเวลาแล้ว อย่าโอนเพิ่ม หากโอนภายในเวลาที่กำหนดไว้ ส่งสลิปให้ทีมงานตรวจสอบได้</p><?php endif ?>
   <div class="pay-account">
    <dl>
     <div><dt>ชื่อผู้รับเงิน</dt><dd><?= e($a['bank_holder'] ?? '') ?></dd></div>
     <?php if (!empty($a['bank_account'])): ?><div><dt><?= e($a['bank_name'] ?? 'บัญชีธนาคาร') ?></dt><dd><span class="account-number"><?= e($a['bank_account']) ?></span><button type="button" class="pay-copy" data-copy="<?= e(preg_replace('/\D+/','',$a['bank_account'])) ?>">คัดลอก</button></dd></div><?php endif ?>
     <?php if (!empty($a['promptpay_number'])): ?><div><dt>PromptPay</dt><dd><span class="account-number"><?= e($a['promptpay_number']) ?></span><button type="button" class="pay-copy" data-copy="<?= e(preg_replace('/\D+/','',$a['promptpay_number'])) ?>">คัดลอก</button></dd></div><?php endif ?>
    </dl>
    <?php if (!empty($a['payment_qr'])): ?><img class="pay-qr" src="<?= e(base_url().$a['payment_qr']) ?>" alt="QR สำหรับชำระเงิน"><?php endif ?>
   </div>
   <p class="fineprint">ตรวจชื่อผู้รับและกรอกยอด ฿<?= money($p['expected_amount']) ?> ในแอปธนาคารก่อนยืนยันโอน</p>
   <a class="btn full" href="#pay-slip" data-pay-goto="slip">โอนแล้ว · แจ้งสลิป <i class="material-icons" aria-hidden="true">upload</i></a>
   <form method="post" action="<?= e($payUrl) ?>" class="pay-cancel" data-pay-form data-confirm="ยกเลิกรายการ <?= e($p['reference']) ?>? ถ้าโอนเงินแล้ว อย่ายกเลิก ให้แจ้งสลิปแทน"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>"><button class="text-button" type="submit">ยังไม่ได้โอน · ยกเลิกรายการนี้เพื่อเลือกยอดใหม่</button></form>
  </div>
  <form method="post" action="<?= e($payUrl) ?>" enctype="multipart/form-data" class="pay-slip stack-form" id="pay-slip" data-pay-form><?= csrf_field() ?><input type="hidden" name="action" value="slip"><input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
   <label>ยอดที่โอนจริง (บาท)<input type="number" name="reported_amount" min="0.01" max="<?= e($repo->decimal($s['remaining'])) ?>" step="0.01" inputmode="decimal" value="<?= e($p['expected_amount']) ?>" required></label>
   <label class="pay-drop"><span class="pay-drop-preview" data-pay-preview><i class="material-icons" aria-hidden="true">upload_file</i></span><span><b data-pay-file-label>แนบสลิป · แตะเพื่อเลือกรูป</b><small>JPG หรือ PNG ไม่เกิน 5 MB</small></span><input type="file" name="slip" accept="image/jpeg,image/png" required></label>
   <button class="btn full" type="submit">ส่งสลิปให้ทีมงานตรวจสอบ</button>
   <button type="button" class="text-button pay-back" data-pay-goto="transfer"><i class="material-icons" aria-hidden="true">arrow_back</i> กลับไปดูช่องทางโอน</button>
   <p class="fineprint">ระบบรับยอดชำระเมื่อทีมงานตรวจสลิปและยืนยันแล้วเท่านั้น</p>
  </form>

 <?php else: $slipUrl=base_url().'booking/slip/'.$token.'/'.(int)$p['id']; ?>
  <div class="pay-done">
   <div class="pay-done-head"><span class="pay-check"><i class="material-icons" aria-hidden="true">check</i></span><div><b>ได้รับสลิปแล้ว รอตรวจสอบ</b><p>ยอดแจ้งโอน ฿<?= money($p['reported_amount']) ?> · <?= e($p['reference']) ?></p></div></div>
   <a class="pay-slip-thumb" href="<?= e($slipUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($slipUrl) ?>" alt="สลิปที่ส่ง" loading="lazy"><span>ดูสลิปที่ส่ง <i class="material-icons" aria-hidden="true">north_east</i></span></a>
   <p>เมื่อทีมงานยืนยัน ใบเสร็จจะขึ้นในส่วนเอกสารการจองของหน้านี้ ไม่ต้องส่งสลิปซ้ำครับ</p>
  </div>
 <?php endif ?>
 </div>
</details>
<?php endif ?>
<?php elseif ($booking['status']==='confirmed' && !$s['invoice']): ?><p>ยืนยันการจองแล้ว ทีมงานกำลังเตรียม Invoice เพื่อเปิดรับชำระเงิน</p>
<?php elseif ($booking['status']!=='confirmed'): ?><p>ทีมงานกำลังตรวจสอบรายละเอียด ยังไม่ยืนยันบริการและยังไม่เปิดชำระเงิน</p><?php endif ?>
<p class="fineprint">เก็บลิงก์หน้านี้ไว้เพื่อติดตามการจอง ลิงก์นี้ใช้เข้าถึงข้อมูลและเอกสารของคุณ</p>
<?php if (!empty($settings['line_id'])): ?><a class="outline-link" target="_blank" rel="noopener noreferrer" href="https://line.me/R/oaMessage/<?= rawurlencode($settings['line_id']) ?>/?<?= rawurlencode('ติดตามการจอง '.$booking['reference']) ?>">ติดต่อทีมงานผ่าน LINE <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?></section>
<?php if ($s['payments']): ?><section class="panel"><h3>รายการชำระเงิน</h3><?php foreach ($s['payments'] as $hp): ?><div class="payment-history"><b><?= e($hp['reference']) ?></b><p><?= e(payment_status_label($hp['status'])) ?> · ฿<?= money($hp['reported_amount'] ?? $hp['expected_amount']) ?></p><?php if ($hp['review_note']): ?><p><?= e($hp['review_note']) ?></p><?php endif ?></div><?php endforeach ?></section><?php endif ?>
