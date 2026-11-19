<?php
use App\Repositories\GroupTourRepository as Groups;
$types = Groups::group_types();
$services = Groups::services();
$v = fn ($key) => e((string) ($old[$key] ?? ''));
$oldServices = is_array($old['services'] ?? null) ? $old['services'] : [];
$months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$monthYear = fn ($date) => $date ? $months[(int) date('n', strtotime($date))].' '.(date('Y', strtotime($date)) + 543) : '';
$typeIcons = ['corporate' => 'business_center', 'family' => 'family_restroom', 'friends' => 'diversity_3', 'school' => 'school', 'association' => 'groups', 'pilgrimage' => 'temple_buddhist', 'other' => 'tour'];
$serviceIcons = ['flight' => 'flight', 'hotel' => 'hotel', 'transport' => 'directions_bus', 'guide' => 'record_voice_over', 'meals' => 'restaurant', 'meeting' => 'meeting_room', 'activity' => 'emoji_events', 'insurance' => 'health_and_safety', 'visa' => 'description', 'photographer' => 'photo_camera'];
$usedTypes = array_values(array_unique(array_column($trips, 'group_type')));
?>
<div class="rv-company gt-page">
 <section class="rv-company-hero gt-hero">
  <img class="rv-hero-photo" src="<?= base_url() ?>public/images/story.jpg" alt="กรุ๊ปเหมาเดินทางท่ามกลางทิวทัศน์ธรรมชาติ" width="1400" height="1000" fetchpriority="high">
  <div class="rv-hero-shade"></div>
  <div class="container rv-hero-inner">
   <nav class="rv-breadcrumb" aria-label="เส้นทางหน้าเว็บ"><a href="<?= base_url() ?>">หน้าแรก</a><span aria-hidden="true">/</span><span aria-current="page">รับจัดกรุ๊ปเหมา</span></nav>
   <div class="rv-kicker"><span></span> PRIVATE GROUP TOURS · TAILOR-MADE FOR YOUR TEAM</div>
   <p class="rv-display" aria-hidden="true">YOUR GROUP.<br><span>YOUR WAY.</span></p>
   <h1>รับจัดกรุ๊ปเหมา ทัวร์ส่วนตัว<br>สำหรับองค์กร ครอบครัว และหมู่คณะ</h1>
   <p class="rv-hero-description">บอกจุดหมาย จำนวนคน และช่วงเวลา แล้วให้ <?= e($brand) ?> ออกแบบโปรแกรม<br>ที่พัก รถ ไกด์ และตั๋วเครื่องบิน ให้ตรงกับโจทย์และงบของกรุ๊ปคุณ</p>
   <div class="rv-actions"><a class="btn" href="#request">ส่งรายละเอียดกรุ๊ป <span aria-hidden="true"><i class="material-icons" aria-hidden="true">arrow_downward</i></span></a><?php if ($trips): ?><a class="rv-link" href="#past-groups">ดูผลงานกรุ๊ปที่ผ่านมา <i class="material-icons" aria-hidden="true">north_east</i></a><?php endif ?></div>
   <?php if ($stats['groups']): ?><dl class="gt-stats"><div><dt>กรุ๊ปที่ดูแล</dt><dd><?= number_format($stats['groups']) ?></dd></div><?php if ($stats['travelers']): ?><div><dt>ผู้เดินทาง</dt><dd><?= number_format($stats['travelers']) ?>+</dd></div><?php endif ?><div><dt>จุดหมาย</dt><dd><?= number_format($stats['destinations']) ?></dd></div></dl><?php endif ?>
   <div class="rv-hero-foot"><span>PLAN / QUOTE / TRAVEL TOGETHER</span><span>ใบอนุญาตนำเที่ยว <?= e($license['number']) ?></span></div>
  </div>
 </section>

 <nav class="rv-section-nav" aria-label="หัวข้อในหน้ากรุ๊ปเหมา"><div class="container"><a href="#what-is-group-tour">กรุ๊ปเหมาคืออะไร</a><a href="#services">บริการ</a><?php if ($trips): ?><a href="#past-groups">ผลงานที่ผ่านมา</a><?php endif ?><a href="#process">ขั้นตอน</a><a href="#request">ขอใบเสนอราคา</a><a href="#group-faq">คำถามที่พบบ่อย</a></div></nav>

 <section class="container rv-section gt-answer" id="what-is-group-tour" aria-labelledby="gt-answer-heading">
  <div data-rv-reveal>
   <span class="rv-kicker">01 / THE SHORT ANSWER</span>
   <h2 id="gt-answer-heading">รับจัดกรุ๊ปเหมา<br><em>คืออะไร เหมาะกับใคร</em></h2>
   <p class="rv-lead gt-direct-answer"><strong>กรุ๊ปเหมา</strong> คือทัวร์ที่จัดเฉพาะสมาชิกในกลุ่มของคุณ ไม่มีลูกทัวร์อื่นร่วมเดินทาง คุณเลือกจุดหมาย วันเดินทาง ระดับที่พัก และกิจกรรมได้เอง <?= e($brand) ?> รับออกแบบโปรแกรมและดูแลการเดินทางให้บริษัท องค์กร ครอบครัว โรงเรียน สมาคม และกลุ่มเพื่อน แล้วเสนอราคาต่อคนตามรายละเอียดจริงของกรุ๊ป</p>
   <dl class="gt-facts">
    <div><dt>เหมาะกับ</dt><dd>สัมมนาและ Incentive ของบริษัท ทริปครอบครัวใหญ่ คณะศึกษาดูงาน สมาคม ชมรม และทริปสายบุญ</dd></div>
    <div><dt>จัดให้ได้</dt><dd>ตั๋วเครื่องบิน ที่พัก รถรับส่ง ไกด์ อาหาร ห้องประชุม กิจกรรม ประกันการเดินทาง และเอกสารวีซ่า</dd></div>
    <div><dt>ราคา</dt><dd>คิดต่อคนตามจุดหมาย จำนวนวัน จำนวนผู้เดินทาง ช่วงเวลา และบริการที่เลือก</dd></div>
    <div><dt>เริ่มต้น</dt><dd>ส่งรายละเอียดกรุ๊ปผ่านแบบฟอร์มในหน้านี้ ไม่มีค่าใช้จ่ายและยังไม่ผูกมัดการจอง</dd></div>
   </dl>
  </div>
  <div data-rv-reveal>
   <h3 class="gt-table-title">กรุ๊ปเหมา ต่างจากทัวร์จอยกรุ๊ปอย่างไร</h3>
   <div class="gt-table-wrap"><table class="gt-compare">
    <caption class="gt-sr-only">เปรียบเทียบกรุ๊ปเหมากับทัวร์จอยกรุ๊ป</caption>
    <thead><tr><th scope="col">หัวข้อ</th><th scope="col">กรุ๊ปเหมา</th><th scope="col">ทัวร์จอยกรุ๊ป</th></tr></thead>
    <tbody>
     <tr><th scope="row">ผู้ร่วมเดินทาง</th><td>เฉพาะคนในกรุ๊ปของคุณ</td><td>เดินทางร่วมกับผู้จองรายอื่น</td></tr>
     <tr><th scope="row">วันเดินทาง</th><td>เลือกวันที่สะดวกได้เอง</td><td>ตามรอบที่เปิดขาย</td></tr>
     <tr><th scope="row">โปรแกรม</th><td>ปรับจุดเที่ยว เวลา และกิจกรรมได้</td><td>ตามโปรแกรมที่กำหนด</td></tr>
     <tr><th scope="row">ที่พักและอาหาร</th><td>เลือกระดับและสไตล์ได้</td><td>ตามแพ็กเกจของรอบนั้น</td></tr>
     <tr><th scope="row">กิจกรรมองค์กร</th><td>เพิ่มห้องประชุม งานเลี้ยง Team building ได้</td><td>ไม่รองรับ</td></tr>
     <tr><th scope="row">ราคา</th><td>เสนอราคาตามขนาดกรุ๊ปและบริการ</td><td>ราคาต่อคนตามรอบ</td></tr>
    </tbody>
   </table></div>
   <h3 class="gt-table-title">ราคากรุ๊ปเหมาขึ้นกับอะไร</h3>
   <ol class="gt-factors"><li>จุดหมายและจำนวนวัน</li><li>จำนวนผู้เดินทาง (ผู้ใหญ่และเด็ก)</li><li>ช่วงเวลา เช่น วันหยุดยาวหรือเทศกาล</li><li>ตั๋วเครื่องบินและระดับที่พัก</li><li>รถ ไกด์ มื้ออาหาร และกิจกรรมพิเศษ</li></ol>
  </div>
 </section>

 <section class="rv-section rv-style-section gt-services" id="services" aria-labelledby="gt-services-heading"><div class="container">
  <div class="rv-section-heading" data-rv-reveal><div><span class="rv-kicker">02 / WHO WE PLAN FOR</span><h2 id="gt-services-heading">กรุ๊ปแบบไหน<br><em>ก็จัดให้ได้</em></h2></div><p>เล่าเป้าหมายของทริปให้เราฟัง<br>แล้วเราจะจัดโปรแกรมให้เหมาะกับคนในกรุ๊ป</p></div>
  <div class="gt-type-grid">
   <?php foreach ([
    'corporate' => 'สัมมนานอกสถานที่ ทริป Incentive ให้รางวัลพนักงาน outing และดูงานต่างประเทศ พร้อมห้องประชุมและงานเลี้ยง',
    'family' => 'ทริปพาพ่อแม่ ญาติผู้ใหญ่ และเด็กเที่ยว จังหวะสบาย ไม่เร่ง พร้อมที่พักและรถที่เหมาะกับทุกวัย',
    'friends' => 'เที่ยวกับเพื่อนสนิท ทริปรวมรุ่น หรือฉลองโอกาสพิเศษ เลือกสไตล์และกิจกรรมที่กลุ่มชอบ',
    'school' => 'คณะครู นักเรียน และศึกษาดูงาน วางแผนจุดเรียนรู้ ตารางเวลา และความปลอดภัยของผู้เดินทาง',
    'association' => 'สมาคม ชมรม และหน่วยงาน จัดทริปประจำปีหรือดูงาน พร้อมใบเสนอราคาและเอกสารประกอบ',
    'pilgrimage' => 'ทริปสายบุญ ไหว้พระ และแสวงบุญ จัดลำดับวัดและเวลาให้เหมาะกับผู้สูงอายุ',
   ] as $key => $text): ?>
   <article class="gt-type" data-rv-reveal><i class="material-icons" aria-hidden="true"><?= $typeIcons[$key] ?></i><h3><?= e($types[$key]) ?></h3><p><?= e($text) ?></p></article>
   <?php endforeach ?>
  </div>
  <h3 class="gt-sub-heading" data-rv-reveal>บริการที่เลือกรวมในกรุ๊ปได้</h3>
  <ul class="gt-service-list" data-rv-reveal><?php foreach ($services as $key => $label): ?><li><i class="material-icons" aria-hidden="true"><?= $serviceIcons[$key] ?></i><?= e($label) ?></li><?php endforeach ?></ul>
 </div></section>

 <?php if ($trips): ?>
 <section class="container rv-section gt-past" id="past-groups" aria-labelledby="gt-past-heading">
  <div class="rv-section-heading" data-rv-reveal><div><span class="rv-kicker">03 / TRUSTED BY GROUPS LIKE YOURS</span><h2 id="gt-past-heading">ผลงานกรุ๊ปเหมา<br><em>ที่ผ่านมา</em></h2></div><p>ตัวอย่างกรุ๊ปที่ <?= e($brand) ?> ดูแลการเดินทาง<br>เปิดดูรายละเอียดแต่ละทริปเพื่อเป็นไอเดียให้กรุ๊ปของคุณ</p></div>
  <?php if (count($usedTypes) > 1): ?><div class="gt-filter" role="group" aria-label="กรองผลงานตามประเภทกรุ๊ป" data-gt-filter><button type="button" aria-pressed="true" data-type="">ทั้งหมด</button><?php foreach ($usedTypes as $type): ?><button type="button" aria-pressed="false" data-type="<?= e($type) ?>"><?= e(Groups::group_type_short($type)) ?></button><?php endforeach ?></div><?php endif ?>
  <div class="gt-trip-grid" data-gt-trips>
   <?php foreach ($trips as $trip): ?>
   <article class="gt-trip" data-type="<?= e($trip['group_type']) ?>">
    <a href="<?= e(base_url().'grouptour/'.rawurlencode($trip['slug'])) ?>" class="gt-trip-media"><img src="<?= e(base_url().$trip['image']) ?>" alt="<?= e($trip['title']) ?>" loading="lazy" width="640" height="480"><span class="gt-badge"><?= e(Groups::group_type_short($trip['group_type'])) ?></span></a>
    <div class="gt-trip-body">
     <ul class="gt-trip-meta"><li><i class="material-icons" aria-hidden="true">place</i><?= e($trip['destination']) ?></li><?php if ($trip['travel_date']): ?><li><i class="material-icons" aria-hidden="true">event</i><time datetime="<?= e(substr($trip['travel_date'], 0, 7)) ?>"><?= e($monthYear($trip['travel_date'])) ?></time></li><?php endif ?><?php if ($trip['travelers']): ?><li><i class="material-icons" aria-hidden="true">group</i><?= (int) $trip['travelers'] ?> ท่าน</li><?php endif ?><?php if ($trip['days']): ?><li><i class="material-icons" aria-hidden="true">schedule</i><?= (int) $trip['days'] ?> วัน</li><?php endif ?></ul>
     <h3><a href="<?= e(base_url().'grouptour/'.rawurlencode($trip['slug'])) ?>"><?= e($trip['title']) ?></a></h3>
     <?php if ($trip['summary']): ?><p><?= e($trip['summary']) ?></p><?php endif ?>
     <a class="gt-trip-more" href="<?= e(base_url().'grouptour/'.rawurlencode($trip['slug'])) ?>" aria-label="ดูรายละเอียด <?= e($trip['title']) ?>">ดูรายละเอียดทริป <i class="material-icons" aria-hidden="true">north_east</i></a>
    </div>
   </article>
   <?php endforeach ?>
  </div>
  <p class="gt-filter-empty" data-gt-empty hidden>ยังไม่มีผลงานในประเภทนี้</p>
 </section>
 <?php endif ?>

 <section class="container rv-section" id="process" aria-labelledby="gt-process-heading">
  <div class="rv-section-heading" data-rv-reveal><div><span class="rv-kicker"><?= $trips ? '04' : '03' ?> / HOW IT WORKS</span><h2 id="gt-process-heading">จัดกรุ๊ปเหมากับเรา<br><em>ใน 4 ขั้นตอน</em></h2></div><a class="rv-link" href="#request">เริ่มขั้นตอนแรก <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <ol class="rv-steps gt-steps">
   <?php foreach ([
    ['ส่งรายละเอียดกรุ๊ป', 'กรอกจุดหมาย จำนวนผู้เดินทาง ช่วงเวลา และงบประมาณผ่านแบบฟอร์ม ระบบออกเลขที่คำขอให้ทันที'],
    ['คุยโจทย์กับทีมงาน', 'ทีมงานติดต่อกลับเพื่อสอบถามเป้าหมายของทริป ความต้องการพิเศษ และข้อจำกัดของผู้เดินทาง'],
    ['รับร่างโปรแกรมและใบเสนอราคา', 'ได้แผนการเดินทางรายวัน ราคาต่อคน และรายการที่รวมและไม่รวม ปรับแก้ได้จนลงตัว'],
    ['ยืนยันและออกเดินทาง', 'ยืนยันโปรแกรมและชำระเงินตามเงื่อนไข ทีมงานเตรียมเอกสารและดูแลจนจบทริป'],
   ] as $i => $step): ?>
   <li data-rv-reveal><span class="rv-step-number">0<?= $i + 1 ?></span><h3><?= e($step[0]) ?></h3><p><?= e($step[1]) ?></p></li>
   <?php endforeach ?>
  </ol>
 </section>

 <section class="container rv-section gt-request" id="request" aria-labelledby="gt-request-heading">
  <div class="rv-planner-copy gt-request-copy" data-rv-reveal>
   <span class="rv-kicker"><?= $trips ? '05' : '04' ?> / REQUEST A QUOTE</span>
   <h2 id="gt-request-heading">ส่งรายละเอียดกรุ๊ป<br><em>รับใบเสนอราคา</em></h2>
   <p class="rv-lead">กรอกเท่าที่ทราบก่อนก็ได้ ช่องที่ยังไม่แน่ใจเว้นไว้ แล้วทีมงานจะช่วยแนะนำ</p>
   <div class="rv-planner-note gt-quick-contact"><b>อยากคุยก่อนกรอกฟอร์ม?</b><p><?php if ($contact['line_url']): ?><a href="<?= e($contact['line_url']) ?>" target="_blank" rel="noopener">LINE <?= e($contact['line']) ?></a><?php endif ?><?php if ($contact['tel']): ?><?= $contact['line_url'] ? ' · ' : '' ?><a href="tel:<?= e($contact['tel']) ?>">โทร <?= e($contact['phone']) ?></a><?php endif ?><?php if ($contact['email']): ?><?= $contact['line_url'] || $contact['tel'] ? ' · ' : '' ?><a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><?php endif ?><?php if (!$contact['line_url'] && !$contact['tel'] && !$contact['email']): ?><a href="<?= base_url() ?>contactus">ดูช่องทางติดต่อทั้งหมด</a><?php endif ?></p></div>
  </div>
  <div class="rv-planner-panel gt-form-panel" data-rv-reveal>
   <?php if ($ref_trip): ?><p class="gt-ref-note"><i class="material-icons" aria-hidden="true">auto_awesome</i> อ้างอิงจากผลงาน “<?= e($ref_trip['title']) ?>” ปรับรายละเอียดได้ตามต้องการ</p><?php endif ?>
   <form action="<?= base_url().'grouptour/request' ?>" method="post" id="gt-form" novalidate<?= $recaptcha_key ? ' data-recaptcha="'.e($recaptcha_key).'"' : '' ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="source" value="<?= $v('source') ?: 'grouptour' ?>">
    <?php if ($recaptcha_key): ?><input type="hidden" name="recaptcha_token" value=""><?php endif ?>
    <div class="gt-hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="gt-progress" hidden><p class="gt-progress-text" aria-live="polite">ขั้นตอน <b data-step-now>1</b> จาก 4</p><ol class="gt-progress-bar" aria-hidden="true"><li></li><li></li><li></li><li></li></ol></div>
    <?php $formError = session('error'); ?><p class="gt-form-error" id="gt-form-error" role="alert"<?= $formError ? '' : ' hidden' ?>><?= e((string) $formError) ?></p>

    <fieldset class="gt-fieldset" tabindex="-1"><legend><span>1</span> เกี่ยวกับกรุ๊ป</legend>
     <p class="gt-label" id="gt-type-label">ประเภทกรุ๊ป <b aria-hidden="true">*</b></p>
     <div class="rv-topic-options" role="radiogroup" aria-labelledby="gt-type-label"><?php foreach ($types as $key => $label): ?><label><input type="radio" name="group_type" value="<?= e($key) ?>" required<?= ($old['group_type'] ?? 'corporate') === $key ? ' checked' : '' ?>><span><?= e(Groups::group_type_short($key) === 'กรุ๊ปเหมา' ? 'อื่น ๆ' : Groups::group_type_short($key)) ?></span></label><?php endforeach ?></div>
     <label class="rv-field">จุดหมายที่อยากไป <b aria-hidden="true">*</b><input name="destination" required maxlength="200" value="<?= $v('destination') ?>" placeholder="เช่น ญี่ปุ่น, เวียดนาม, เชียงใหม่"></label>
     <div class="rv-form-row gt-row-3"><div class="rv-field"><label for="gt-adults">ผู้ใหญ่ <b aria-hidden="true">*</b></label><input id="gt-adults" name="adults" type="number" inputmode="numeric" min="1" max="999" required value="<?= $v('adults') ?>" placeholder="เช่น 25" data-stepper="ผู้ใหญ่"></div><div class="rv-field"><label for="gt-children">เด็ก</label><input id="gt-children" name="children" type="number" inputmode="numeric" min="0" max="999" value="<?= $v('children') ?>" placeholder="0" data-stepper="เด็ก"></div><div class="rv-field"><label for="gt-days">จำนวนวัน</label><input id="gt-days" name="days" type="number" inputmode="numeric" min="1" max="60" value="<?= $v('days') ?>" placeholder="เช่น 5" data-stepper="จำนวนวัน"></div></div>
    </fieldset>

    <fieldset class="gt-fieldset" tabindex="-1"><legend><span>2</span> ช่วงเวลาเดินทาง</legend>
     <div class="rv-form-row"><label class="rv-field">วันเริ่มเดินทาง <span>ถ้าทราบแล้ว</span><input name="date_from" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= $v('date_from') ?>"></label><label class="rv-field">วันกลับ <span>ถ้าทราบแล้ว</span><input name="date_to" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= $v('date_to') ?>"></label></div>
     <label class="rv-field">หรือระบุช่วงคร่าว ๆ <span>ไม่บังคับ</span><input name="date_note" maxlength="120" value="<?= $v('date_note') ?>" placeholder="เช่น ปลายเดือนมีนาคม หรือช่วงสงกรานต์"></label>
    </fieldset>

    <fieldset class="gt-fieldset" tabindex="-1"><legend><span>3</span> งบประมาณและบริการ</legend>
     <div class="rv-form-row"><label class="rv-field">งบประมาณต่อคน<select name="budget"><option value="">เลือกช่วงงบ</option><?php foreach (Groups::budgets() as $key => $label): ?><option value="<?= e($key) ?>"<?= ($old['budget'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label><label class="rv-field">ระดับที่พัก<select name="hotel_level"><option value="">เลือกระดับ</option><?php foreach (Groups::hotel_levels() as $key => $label): ?><option value="<?= e($key) ?>"<?= ($old['hotel_level'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select></label></div>
     <p class="gt-label" id="gt-services-label">บริการที่ต้องการ <span>เลือกได้หลายข้อ</span></p>
     <div class="gt-checks" role="group" aria-labelledby="gt-services-label"><?php foreach ($services as $key => $label): ?><label><input type="checkbox" name="services[]" value="<?= e($key) ?>"<?= in_array($key, $oldServices, true) ? ' checked' : '' ?>><span><i class="material-icons" aria-hidden="true"><?= $serviceIcons[$key] ?></i><?= e($label) ?></span></label><?php endforeach ?></div>
    </fieldset>

    <fieldset class="gt-fieldset" tabindex="-1"><legend><span>4</span> ข้อมูลติดต่อ</legend>
     <div class="rv-form-row"><label class="rv-field">ชื่อผู้ติดต่อ <b aria-hidden="true">*</b><input name="contact_name" required maxlength="120" autocomplete="name" value="<?= $v('contact_name') ?>"></label><label class="rv-field">บริษัท / องค์กร / ชื่อกลุ่ม<input name="organization" maxlength="180" autocomplete="organization" value="<?= $v('organization') ?>"></label></div>
     <div class="rv-form-row"><label class="rv-field">เบอร์โทรศัพท์ <b aria-hidden="true">*</b><input name="phone" type="tel" required maxlength="25" pattern="[0-9+() \-]{8,25}" autocomplete="tel" value="<?= $v('phone') ?>"></label><label class="rv-field">LINE ID<input name="line_id" maxlength="100" value="<?= $v('line_id') ?>"></label></div>
     <label class="rv-field">อีเมล <span>กรอกเพื่อรับอีเมลยืนยันคำขอ</span><input name="email" type="email" maxlength="190" autocomplete="email" value="<?= $v('email') ?>"></label>
     <label class="rv-field">รายละเอียดเพิ่มเติม<textarea name="message" rows="4" maxlength="3000" placeholder="เช่น วัตถุประสงค์ของทริป จุดที่อยากไป อาหารที่แพ้ ผู้สูงอายุหรือผู้ใช้วีลแชร์ ห้องประชุมกี่ที่นั่ง"><?= $v('message') ?></textarea></label>
    </fieldset>

    <div class="gt-form-end">
     <label class="gt-consent"><input type="checkbox" name="consent" value="1" required> <span>ยินยอมให้ <?= e($brand) ?> ใช้ข้อมูลนี้เพื่อติดต่อกลับและจัดทำใบเสนอราคา <a href="<?= base_url() ?>privacy" target="_blank" rel="noopener">อ่านการใช้ข้อมูล</a></span></label>
     <button type="submit" class="btn gt-submit" data-busy="กำลังส่งคำขอ…">ส่งคำขอจัดกรุ๊ป <i class="material-icons" aria-hidden="true">send</i></button>
     <p class="rv-form-help">ส่งคำขอได้ฟรี ยังไม่ใช่การจองและยังไม่มีค่าใช้จ่าย ทีมงานจะติดต่อกลับตามข้อมูลที่ให้ไว้</p>
     <?php if ($recaptcha_key): ?><p class="recaptcha-note">ป้องกันสแปมด้วย reCAPTCHA ภายใต้<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">นโยบายความเป็นส่วนตัว</a>และ<a href="https://policies.google.com/terms" target="_blank" rel="noopener">ข้อกำหนด</a>ของ Google</p><?php endif ?>
    </div>
    <div class="gt-step-nav" hidden><button type="button" class="gt-step-back" data-step-back><i class="material-icons" aria-hidden="true">arrow_back</i> ย้อนกลับ</button><button type="button" class="btn gt-step-next" data-step-next>ถัดไป <i class="material-icons" aria-hidden="true">arrow_forward</i></button></div>
   </form>
  </div>
  <aside class="gt-request-aside" data-rv-reveal>
   <ul class="rv-prep-list"><li><span>01</span> จุดหมายและช่วงเวลาที่อยากไป</li><li><span>02</span> จำนวนผู้ใหญ่และเด็ก</li><li><span>03</span> งบประมาณต่อคนโดยประมาณ</li><li><span>04</span> บริการและกิจกรรมที่ต้องการ</li></ul>
   <div class="gt-trust"><i class="material-icons" aria-hidden="true">verified_user</i><p>ดำเนินการโดยผู้ได้รับใบอนุญาตประกอบธุรกิจนำเที่ยว เลขที่ <strong><?= e($license['number']) ?></strong><br><?= e($license['holder']) ?></p></div>
  </aside>
 </section>

 <section class="rv-faq-section" id="group-faq" aria-labelledby="gt-faq-heading"><div class="container rv-faq-layout">
  <div data-rv-reveal><span class="rv-kicker">GOOD QUESTIONS. CLEAR ANSWERS.</span><h2 id="gt-faq-heading">คำถามที่พบบ่อย<br><em>เรื่องกรุ๊ปเหมา</em></h2><p>คำตอบสั้น ๆ ก่อนตัดสินใจจัดทริปให้กรุ๊ปของคุณ</p></div>
  <div class="rv-faq-list" data-rv-reveal><?php foreach ($faqs as $i => $faq): ?><details<?= $i === 0 ? ' open' : '' ?>><summary><span class="rv-faq-number"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><h3 class="gt-faq-q"><?= e($faq[0]) ?></h3><span class="rv-faq-icon" aria-hidden="true">+</span></summary><p><?= e($faq[1]) ?></p></details><?php endforeach ?></div>
 </div></section>

 <section class="container rv-final-cta" data-rv-reveal><span class="rv-kicker">READY WHEN YOUR GROUP IS</span><h2>กรุ๊ปของคุณ<br><em>อยากไปที่ไหน?</em></h2><a class="btn" href="#request">ส่งรายละเอียดกรุ๊ป <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><span class="rv-cta-orbit" aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></section>
</div>
<?php if ($recaptcha_key): ?><script src="https://www.google.com/recaptcha/api.js?render=<?= rawurlencode($recaptcha_key) ?>" async defer></script><?php endif ?>
