<?php
 $tabFilters=array_intersect_key($filters,array_flip(['q','month','date_from','date_to','program_type']));
 $tabFilters=array_filter($tabFilters,static function($value) { return $value!=='' && $value!=='all'; });
 $tabQuery=$tabFilters ? '?'.http_build_query($tabFilters) : '';
?>
<nav class="filter-tabs destination-tabs" aria-label="<?= $node['kind']==='zone' ? 'เลือกประเทศ' : 'เลือกเส้นทาง' ?>">
 <a class="active" aria-current="page" href="<?= e(destination_url($node).$tabQuery) ?>">ทั้งหมดใน<?= e($node['name']) ?></a>
 <?php foreach ($children as $child): ?><a href="<?= e(destination_url($child).$tabQuery) ?>"><?php if (!empty($child['flag_path'])): ?><img src="<?= e(base_url().$child['flag_path']) ?>" alt="" loading="lazy"><?php endif ?><?= e($child['name']) ?></a><?php endforeach ?>
</nav>
