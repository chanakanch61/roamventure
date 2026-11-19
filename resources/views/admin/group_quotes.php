<?php
use App\Repositories\GroupQuoteRepository as Quotes;
$statuses = Quotes::statuses();
$pill = ['draft' => 'new', 'sent' => 'contacted', 'accepted' => 'confirmed', 'rejected' => 'cancelled'];
$link = function (array $changes) use ($filters) {
    $q = http_build_query(array_filter(array_merge(['status' => $filters['status'], 'q' => $filters['q']], $changes), fn ($v) => $v !== '' && $v !== null));

    return base_url().'admin/group-quotes'.($q !== '' ? '?'.$q : '');
};
?>
<div class="section-head"><div><span class="eyebrow">GROUP QUOTATIONS</span><h1>ใบเสนอราคากรุ๊ป</h1><p>สร้างใบเสนอราคาจากคำขอจัดกรุ๊ป แล้วพิมพ์หรือบันทึกเป็น PDF ส่งให้ลูกค้า</p></div><div class="actions"><a class="outline-link" href="<?= base_url().'admin/group-requests' ?>">คำขอจัดกรุ๊ป</a><a class="btn" href="<?= base_url().'admin/group-quotes/new' ?>">＋ สร้างใบเสนอราคา</a></div></div>
<div class="filter-tabs"><a href="<?= e($link(['status' => '', 'page' => ''])) ?>" class="<?= ! $filters['status'] ? 'active' : '' ?>">ทั้งหมด</a><?php foreach ($statuses as $key => $label): ?><a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e($link(['status' => $key, 'page' => ''])) ?>"><?= e($label) ?></a><?php endforeach ?></div>
<form class="panel stack-form" method="get" role="search"><?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif ?><label>ค้นหา<input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="เลขที่ ชื่อลูกค้า บริษัท เบอร์โทร หรือชื่อทริป"></label><div class="actions"><button class="btn" type="submit">ค้นหา</button><?php if ($filters['q']): ?><a href="<?= e($link(['q' => '', 'page' => ''])) ?>">ล้าง</a><?php endif ?></div></form>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>เลขที่ / ลูกค้า</th><th>รายการ</th><th>ยอดรวม</th><th>สถานะ</th><th></th></tr></thead><tbody>
<?php foreach ($quotes as $q): ?><tr>
 <td><b><?= e($q['number']) ?></b><small><?= e($q['customer_name']) ?><?= $q['customer_company'] ? ' · '.e($q['customer_company']) : '' ?></small><small><?= e(thai_date($q['issue_date'])) ?></small></td>
 <td><?= e($q['title']) ?><?php if ($q['request_id']): ?><small><a class="text-link" href="<?= base_url().'admin/group-requests/'.(int) $q['request_id'] ?>">ดูคำขอ</a></small><?php endif ?></td>
 <td>฿<?= money($q['total']) ?><small><?= e(Quotes::vat_modes()[$q['vat_mode']] ?? '') ?></small></td>
 <td><span class="pill <?= e($pill[$q['status']] ?? 'new') ?>"><?= e($statuses[$q['status']] ?? $q['status']) ?></span></td>
 <td><a class="text-link" href="<?= base_url().'admin/group-quotes/'.(int) $q['id'] ?>">แก้ไข</a> · <a class="text-link" href="<?= base_url().'admin/group-quotes/'.(int) $q['id'].'/print' ?>" target="_blank" rel="noopener">พิมพ์ ↗</a></td>
</tr><?php endforeach ?>
<?php if (! $quotes): ?><tr><td colspan="5" class="empty">ยังไม่มีใบเสนอราคา เปิดคำขอจัดกรุ๊ปแล้วกด “สร้างใบเสนอราคา” เพื่อเติมข้อมูลลูกค้าให้อัตโนมัติ</td></tr><?php endif ?>
</tbody></table></div></div>
<?php if ($pages > 1): ?><nav class="filter-tabs" aria-label="หน้า"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e($link(['page' => $i > 1 ? $i : ''])) ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
