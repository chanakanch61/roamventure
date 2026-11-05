<div class="rv-company rv-about">
 <section class="rv-company-hero">
  <img class="rv-hero-photo" src="<?= base_url() ?>public/images/story.jpg" alt="ทิวทัศน์ธรรมชาติและภูเขาระหว่างการเดินทาง" width="1400" height="1000" fetchpriority="high">
  <div class="rv-hero-shade"></div>
  <div class="container rv-hero-inner">
   <nav class="rv-breadcrumb" aria-label="เส้นทางหน้าเว็บ"><a href="<?= base_url() ?>">หน้าแรก</a><span aria-hidden="true">/</span><span aria-current="page">เกี่ยวกับเรา</span></nav>
   <div class="rv-kicker"><span></span> THE PEOPLE. THE PLACES. THE POSSIBILITIES.</div>
   <p class="rv-display" aria-hidden="true">GO FURTHER.<br><span>FEEL CLOSER.</span></p>
   <h1>เกี่ยวกับ RoamVenture<br>ให้ทุกการเดินทาง มีความหมายในแบบคุณ</h1>
   <p class="rv-hero-description">บางความทรงจำ เริ่มจากการก้าวออกไป<br>เราชวนคุณค้นหาทริปที่ใช่ แล้วออกไปพบเรื่องราวใหม่ด้วยกัน</p>
   <div class="rv-actions"><a class="btn" href="#our-story">รู้จักเราให้มากขึ้น <span aria-hidden="true"><i class="material-icons" aria-hidden="true">arrow_downward</i></span></a><a class="rv-link" href="<?= base_url() ?>contactus">คุยเรื่องทริปของคุณ <i class="material-icons" aria-hidden="true">north_east</i></a></div>
   <div class="rv-hero-foot"><span>ROAM / CONNECT / REMEMBER</span><span>01 — OUR STORY</span></div>
  </div>
  <div class="rv-compass" aria-hidden="true"><span>N</span><b><i class="material-icons" aria-hidden="true">north_east</i></b><small>KEEP EXPLORING</small></div>
 </section>
 <nav class="rv-section-nav" aria-label="หัวข้อเกี่ยวกับเรา"><div class="container"><a href="#our-story">เรื่องราวของเรา</a><?php if ($style_cards): ?><a href="#travel-style">สไตล์การเดินทาง</a><?php endif ?><a href="#how-it-works">ก่อนออกเดินทาง</a><a href="#company-faq">คำถามที่พบบ่อย</a></div></nav>
 <section class="container rv-section rv-story" id="our-story">
  <div data-rv-reveal><span class="rv-kicker">01 / A LITTLE ABOUT US</span><h2>ไม่ใช่แค่จุดหมาย<br><em>แต่คือเรื่องราวระหว่างทาง</em></h2><p class="rv-lead">RoamVenture ให้บริการทัวร์และแพ็คเกจส่วนตัว ตั้งแต่เลือกโปรแกรม ดูราคา และส่งคำขอจอง ไปจนถึงให้ทีมงานตรวจสอบและยืนยันการเดินทาง</p><p>เริ่มจากสถานที่ที่อยากไป เลือกช่วงเวลาที่ลงตัว แล้วดูบริการและราคาของแต่ละโปรแกรม เรารวมข้อมูลวันเดินทาง จำนวนผู้เดินทาง ที่พัก การเดินทาง และบริการตามโปรแกรม เพื่อช่วยให้คุณวางแผนทริปได้ชัดเจนขึ้น</p><div class="rv-actions"><a class="rv-link" href="<?= base_url() ?>tours">หาจุดหมายถัดไปของคุณ <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><?php if ($has_gallery): ?><a class="rv-link" href="<?= base_url() ?>gallery">ชมแกลเลอรี่ <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><?php endif ?><?php if ($has_blog): ?><a class="rv-link" href="<?= base_url() ?>blog">อ่านบทความ <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><?php endif ?></div></div>
  <figure class="rv-story-image" data-rv-reveal><img src="<?= base_url() ?>public/images/krabi.jpg" alt="ทิวทัศน์ทะเลและภูเขาหินปูนที่กระบี่" width="800" height="900" loading="lazy"><figcaption><span>A CHANGE OF SCENERY.</span><b>A NEW PERSPECTIVE. <i class="material-icons" aria-hidden="true">north_east</i></b></figcaption><span class="rv-image-label">THE WORLD IS WAITING</span></figure>
 </section>
 <?php if ($style_cards): ?>
 <section class="rv-section rv-style-section" id="travel-style"><div class="container">
  <div class="rv-section-heading" data-rv-reveal><div><span class="rv-kicker">02 / FIND YOUR KIND OF JOURNEY</span><h2>ไปแบบไหน<br><em>ให้เป็นตัวคุณ</em></h2></div><p>เลือกทัวร์ตามรอบเดินทาง หรือแพ็คเกจสำหรับกลุ่มของคุณ<br>แล้วสำรวจรายละเอียดที่ตรงใจ</p></div>
  <div class="rv-style-grid rv-style-grid-<?= count($style_cards) ?>">
  <?php foreach ($style_cards as $i=>$card): ?>
   <a class="rv-style-card" href="<?= e(base_url().$card['path']) ?>" data-rv-reveal><img src="<?= base_url() ?>public/images/<?= e($card['image']) ?>" width="800" height="700" loading="lazy" alt="<?= e($card['alt']) ?>"><div><span class="rv-kicker">0<?= $i+1 ?> / <?= e($card['eyebrow']) ?></span><h3><?= e($card['label']) ?></h3><p><?= e($card['lines'][0]) ?><br><?= e($card['lines'][1]) ?></p><span class="rv-card-cta"><?= e($card['cta']) ?> <b aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></b></span></div></a>
  <?php endforeach ?>
  </div>
 </div></section>
 <?php endif ?>
 <section class="container rv-section" id="how-it-works">
  <div class="rv-section-heading" data-rv-reveal><div><span class="rv-kicker">03 / FROM A WISH TO A JOURNEY</span><h2>จาก “อยากไป”<br><em>สู่การวางแผนที่ชัดเจน</em></h2></div><a class="rv-link" href="<?= base_url() ?>contactus#trip-planner">เริ่มวางแผนทริป <i class="material-icons" aria-hidden="true">north_east</i></a></div>
  <div class="rv-steps">
  <?php foreach ([['ค้นหาทริปที่ใช่','เลือกจุดหมายและรูปแบบการเดินทาง เปิดดูแผนเที่ยวและภาพประกอบของโปรแกรม'],['เช็กรายละเอียดให้ครบ','เลือกวันและจำนวนผู้เดินทาง ดูราคาได้ก่อนกรอกข้อมูลติดต่อ พร้อมตรวจสอบหัวข้อราคารวม ราคาไม่รวม และเงื่อนไขของทริป'],['ส่งคำขอหรือสอบถาม','ส่งคำขอจองจากหน้าโปรแกรม หรือคัดลอกสรุปทริปไปถามทีมงานในแชท ทีมงานจะตรวจสอบคำขอและรายละเอียดก่อนยืนยัน']] as $i=>$step): ?>
   <article data-rv-reveal><span class="rv-step-number">0<?= $i+1 ?></span><h3><?= e($step[0]) ?></h3><p><?= e($step[1]) ?></p></article>
  <?php endforeach ?>
  </div>
 </section>
 <?php load_view('company/faq',['faqs'=>$faqs]); ?>
 <section class="container rv-final-cta" data-rv-reveal><span class="rv-kicker">YOUR NEXT CHAPTER STARTS HERE</span><h2>ทริปต่อไปของคุณ<br><em>เริ่มจากบทสนทนาดี ๆ</em></h2><a class="btn" href="<?= base_url() ?>contactus">มาวางแผนด้วยกัน <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></a><span class="rv-cta-orbit" aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></section>
</div>
