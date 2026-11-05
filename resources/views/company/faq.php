<section class="rv-faq-section" id="company-faq"><div class="container rv-faq-layout">
 <div data-rv-reveal><span class="rv-kicker">GOOD QUESTIONS. CLEAR ANSWERS.</span><h2>อยากรู้ก่อน<br><em>ออกเดินทาง</em></h2><p>คำตอบสั้น ๆ เพื่อช่วยให้คุณวางแผนได้ง่ายขึ้น</p></div>
 <div class="rv-faq-list" data-rv-reveal><?php foreach ($faqs as $i=>$faq): ?><details<?= $i===0 ? ' open' : '' ?>><summary><span class="rv-faq-number">0<?= $i+1 ?></span><span><?= e($faq[0]) ?></span><span class="rv-faq-icon" aria-hidden="true">+</span></summary><p><?= e($faq[1]) ?></p></details><?php endforeach ?></div>
</div></section>
