<div class="day-photo-editor">
 <b>ภาพประจำวัน · สูงสุด 6 รูป</b><small>JPG, PNG, WebP ไม่เกิน 5 MB ต่อรูป และ 8000 × 8000 px · รูปและลำดับจะเปลี่ยนเมื่อบันทึกโปรแกรม</small>
 <div class="day-picture-list"><?php foreach (day_photos($day) as $j=>$photo) load_view('admin/day_picture',['i'=>$i,'j'=>$j,'photo'=>$photo]); ?></div>
 <template class="day-picture-template"><?php load_view('admin/day_picture',['i'=>$i,'j'=>'__PHOTO__','photo'=>[]]); ?></template>
 <button type="button" class="outline-link" data-picture-add>＋ เพิ่มรูปภาพ</button><p class="fineprint" data-day-photo-status aria-live="polite"></p>
</div>