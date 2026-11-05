<?php
 $searchPrivate = ($listing_type ?? '') === 'private';
 $searchScoped = !empty($search_scope);
 $searchMode = $listing_type ?? 'fixed';
 $searchAction = base_url().($listing_path ?? 'tours');
 $searchMonth = substr($filters['date_from'] ?? '', 0, 7);
 $searchMonths = [];
 $monthNames = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
 $firstMonth = new DateTimeImmutable('first day of this month');
 for ($i = 0; $i < 24; $i++) {
  $monthDate = $firstMonth->modify('+'.$i.' months');
  $searchMonths[$monthDate->format('Y-m')] = $monthNames[(int)$monthDate->format('n')].' '.((int)$monthDate->format('Y') + 543);
 }
 if (preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $searchMonth) && !isset($searchMonths[$searchMonth])) {
  $searchMonths[$searchMonth] = $monthNames[(int)substr($searchMonth, 5, 2)].' '.((int)substr($searchMonth, 0, 4) + 543);
  ksort($searchMonths);
 }
?>
<form class="search-form" action="<?= e($searchAction) ?>" method="get" role="search" aria-label="<?= e($searchScoped ? 'ค้นหาโปรแกรมใน'.$search_scope : ($searchPrivate ? 'ค้นหาแพ็คเกจส่วนตัว' : 'ค้นหาทัวร์')) ?>" data-tour-search data-search-mode="<?= e($searchMode) ?>" data-search-scope="<?= e($search_scope ?? '') ?>">
 <?php if ($searchScoped): ?><input type="hidden" name="program_type" value="<?= e($searchMode) ?>"><?php endif ?>
 <?php if (!empty($show_search_types)): ?>
 <div class="search-types" role="group" aria-label="ประเภทการเดินทาง" data-search-types>
  <?php if ($searchScoped): ?><button type="button" class="search-type" aria-pressed="<?= $searchMode==='all' ? 'true' : 'false' ?>" data-search-path="<?= e($searchAction) ?>" data-search-kind="all">ทั้งหมด</button><?php else: ?><button type="button" class="search-type" aria-pressed="<?= $searchMode==='fixed' ? 'true' : 'false' ?>" data-search-path="<?= e(base_url().'tours') ?>" data-search-kind="tour">ทัวร์ทั้งหมด</button><?php endif ?>
  <?php foreach (program_channels() as $channelKey=>$searchChannel): ?><button type="button" class="search-type" aria-pressed="<?= $searchMode===$channelKey ? 'true' : 'false' ?>" data-search-path="<?= e($searchScoped ? $searchAction : base_url().$searchChannel['path']) ?>" data-search-kind="<?= e($channelKey) ?>"><?= e($searchChannel['label']) ?></button><?php endforeach ?>
 </div>
 <?php endif ?>
 <label class="search-field search-keyword">
  <i class="material-icons search-icon" aria-hidden="true">search</i>
  <span class="search-field-content"><span class="search-label"><?= e($searchScoped ? 'ค้นหาใน'.$search_scope : ($searchPrivate ? 'ค้นหาแพ็คเกจที่ใช่' : 'ค้นหาทัวร์ที่ใช่')) ?></span><input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= $searchPrivate ? 'ชื่อแพ็คเกจ ประเทศ เส้นทาง หรือรหัสโปรแกรม' : 'ชื่อทัวร์ ประเทศ เส้นทาง หรือรหัสโปรแกรม' ?>" maxlength="100" data-tour-search-input></span>
 </label>
 <label class="search-field search-month"<?= $searchPrivate ? ' hidden' : '' ?>>
  <i class="material-icons search-icon" aria-hidden="true">calendar_month</i>
  <span class="search-field-content"><span class="search-label">ช่วงเวลาเดินทาง</span><select name="month"<?= $searchPrivate ? ' disabled' : '' ?>>
   <option value="">เลือกเดือนเดินทาง (ทุกเดือน)</option>
   <?php foreach ($searchMonths as $value => $label): ?><option value="<?= e($value) ?>"<?= $searchMonth === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
  </select></span>
 </label>
 <div class="search-period" data-date-range data-today="<?= date('Y-m-d') ?>"<?= $searchPrivate ? '' : ' hidden' ?>>
 <button class="search-field date-range-trigger" type="button" aria-haspopup="dialog" aria-expanded="false">
  <i class="material-icons search-icon" aria-hidden="true">calendar_month</i>
  <span class="search-field-content"><span class="search-label">ช่วงเวลาเดินทาง</span><span class="date-range-value">วันเริ่มต้น <span aria-hidden="true">–</span> วันสิ้นสุด</span></span>
  <span class="date-range-chevron" aria-hidden="true"><i class="material-icons" aria-hidden="true">expand_more</i></span>
 </button>
 <input type="hidden" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"<?= $searchPrivate ? '' : ' disabled' ?>>
 <input type="hidden" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"<?= $searchPrivate ? '' : ' disabled' ?>>
 <dialog class="date-range-dialog" aria-label="เลือกช่วงเวลาเดินทาง">
  <div class="date-range-heading"><div><strong>เลือกช่วงเวลาเดินทาง</strong><p><?= ($listing_type ?? '')==='private' ? 'ค้นหาแพ็คเกจที่เริ่มเดินทางได้ในช่วงวันที่เลือก' : 'ค้นหารอบทัวร์ที่เริ่มเดินทางในช่วงวันที่เลือก' ?></p></div><button type="button" class="date-range-close" aria-label="ปิดปฏิทิน"><i class="material-icons" aria-hidden="true">close</i></button></div>
  <div class="date-range-months"></div>
  <div class="date-range-footer"><span class="date-range-summary" role="status" aria-live="polite">เลือกวันเริ่มต้น</span><div><button type="button" class="date-range-clear">ล้างวันที่</button><button type="button" class="date-range-apply" disabled>ยืนยัน</button></div></div>
 </dialog>
 </div>
 <button class="btn" type="submit">ค้นหา <span aria-hidden="true"><i class="material-icons" aria-hidden="true">north_east</i></span></button>
</form>
<script src="<?= base_url().'public/date-range.js?v='.filemtime(base_path()."/".'public/date-range.js') ?>" defer></script>
