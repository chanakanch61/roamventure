<div class="day-hotel-editor" data-day-hotels>
 <b>โรงแรมที่พักคืนนี้</b><small>เรียงตามลำดับ โรงแรมที่ 1 คือโรงแรมหลัก โรงแรมถัดไปคือตัวสำรองเมื่อโรงแรมก่อนหน้าไม่ได้ · รูป JPG, PNG, WebP ไม่เกิน 5 MB ต่อรูป เลือกได้ทีละหลายรูป</small>
 <input type="hidden" name="itinerary[<?= $i ?>][hotels_json]" value="<?= e(json_encode(day_hotels($day),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>" data-hotels-field>
 <div class="day-hotel-list" data-hotel-list></div>
 <div class="day-hotel-actions"><button type="button" class="outline-link" data-hotel-add>＋ เพิ่มโรงแรม</button><button type="button" class="text-button" data-hotel-copy>คัดลอกจากวันก่อนหน้า</button></div>
 <p class="fineprint" data-hotel-status aria-live="polite"></p>
</div>
