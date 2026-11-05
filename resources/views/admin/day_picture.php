<div class="day-picture">
 <img class="day-picture-preview" <?= empty($photo['path']) ? 'hidden' : 'src="'.e(tour_image($photo['path'])).'"' ?> alt="ตัวอย่างภาพ" data-original-src="<?= empty($photo['path']) ? '' : e(tour_image($photo['path'])) ?>">
 <input type="hidden" data-picture-field="path" name="itinerary[<?= $i ?>][pictures][<?= $j ?>][path]" value="<?= e($photo['path'] ?? '') ?>">
 <input type="hidden" data-picture-field="has_upload" name="itinerary[<?= $i ?>][pictures][<?= $j ?>][has_upload]" value="0">
 <label>ไฟล์ภาพ<input type="file" data-picture-field="file" name="itinerary[<?= $i ?>][pictures][<?= $j ?>][file]" accept="image/jpeg,image/png,image/webp" data-picture-upload></label>
 <label>คำบรรยายภาพ<input data-picture-field="caption" name="itinerary[<?= $i ?>][pictures][<?= $j ?>][caption]" maxlength="200" value="<?= e($photo['caption'] ?? '') ?>" placeholder="สถานที่หรือกิจกรรมในภาพ"></label>
 <div class="day-picture-actions"><button type="button" class="icon-btn" data-picture-move="up" aria-label="เลื่อนภาพขึ้น">↑</button><button type="button" class="icon-btn" data-picture-move="down" aria-label="เลื่อนภาพลง">↓</button><button type="button" class="text-button" data-picture-clear hidden>ยกเลิกไฟล์ใหม่</button><button type="button" class="text-button danger" data-picture-remove>นำภาพออก</button></div>
</div>
