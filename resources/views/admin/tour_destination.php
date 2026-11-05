<?php $routeRows=$tour['destinations'] ?? []; if (!$routeRows) $routeRows=[['zone_id'=>0,'country_id'=>0,'route_id'=>0]]; ?>
<div id="tour-destination-picker"><div class="section-head"><div><h3>จุดหมายของทัวร์</h3><p>เพิ่มได้หลายประเทศและหลายโซน · เรียงตามลำดับเส้นทาง · ไม่เลือก Route ซ้ำ</p></div><button type="button" class="outline-link" data-place-add>＋ เพิ่มจุดหมาย</button></div><div id="tour-destination-rows">
<?php foreach ($routeRows as $i=>$row) load_view('admin/tour_destination_row',['i'=>$i,'row'=>$row,'destination_nodes'=>$destination_nodes]); ?>
</div></div><p class="fineprint"><a href="<?= base_url() . 'admin/destinations/zone' ?>" target="_blank" rel="noopener">จัดการ Zone / Country / Route ↗</a> · เพิ่มจุดหมายแล้วโหลดหน้านี้ใหม่ก่อนเลือก</p>
<script type="application/json" id="destination-options"><?= json_encode($destination_nodes,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<template id="tour-destination-template"><?php load_view('admin/tour_destination_row',['i'=>'__INDEX__','row'=>['zone_id'=>0,'country_id'=>0,'route_id'=>0],'destination_nodes'=>$destination_nodes]); ?></template>
