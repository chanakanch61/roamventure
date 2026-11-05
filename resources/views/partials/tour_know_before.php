<?php $knowBefore=text_lines($tour['know_before_go'] ?? ''); ?>
<?php if ($knowBefore): ?>
<section id="know-before"><span class="eyebrow">BEFORE YOU GO</span><h2>ควรรู้ก่อนเดินทาง</h2><div class="highlight-box"><ul class="trip-list"><?php foreach ($knowBefore as $knowLine): ?><li><i class="material-icons" aria-hidden="true">info</i><span><?= e($knowLine) ?></span></li><?php endforeach ?></ul></div></section>
<?php endif ?>
