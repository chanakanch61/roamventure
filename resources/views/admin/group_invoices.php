<?php
use App\Repositories\GroupInvoiceRepository as Invoices;
$statuses = Invoices::statuses();
$pill = ['unpaid' => 'contacted', 'paid' => 'confirmed', 'cancelled' => 'cancelled'];
$kinds = Invoices::kinds();
$link = function (array $changes) use ($filters) {
    $q = http_build_query(array_filter(array_merge(['status' => $filters['status'], 'kind' => $filters['kind'], 'q' => $filters['q']], $changes), fn ($v) => $v !== '' && $v !== null));

    return base_url().'admin/group-invoices'.($q !== '' ? '?'.$q : '');
};
?>
<div class="section-head"><div><span class="eyebrow">INVOICES</span><h1>ใบแจ้งหนี้</h1><p>งวดตามใบเสนอราคากรุ๊ป และค่าใช้จ่ายอื่น เช่น ค่าวีซ่า ค่าจิปาถะ เมื่อบันทึกรับชำระระบบออกใบเสร็จรับเงิน / ใบกำกับภาษีให้</p></div><div class="actions"><a class="btn" href="<?= base_url().'admin/group-invoices/new?kind=charges' ?>">＋ ใบแจ้งหนี้ค่าใช้จ่ายอื่น</a><a class="outline-link" href="<?= base_url().'admin/group-invoices/templates' ?>">Template รายการ</a><a class="outline-link" href="<?= base_url().'admin/group-quotes' ?>">ใบเสนอราคากรุ๊ป</a></div></div>
<div class="filter-tabs"><a href="<?= e($link(['status' => '', 'page' => ''])) ?>" class="<?= ! $filters['status'] ? 'active' : '' ?>">ทั้งหมด</a><?php foreach ($statuses as $key => $label): ?><a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e($link(['status' => $key, 'page' => ''])) ?>"><?= e($label) ?></a><?php endforeach ?></div>
<div class="filter-tabs"><a href="<?= e($link(['kind' => '', 'page' => ''])) ?>" class="<?= ! $filters['kind'] ? 'active' : '' ?>">ทุกประเภท</a><?php foreach ($kinds as $key => $label): ?><a class="<?= $filters['kind'] === $key ? 'active' : '' ?>" href="<?= e($link(['kind' => $key, 'page' => ''])) ?>"><?= e($label) ?></a><?php endforeach ?></div>
<form class="panel stack-form" method="get" role="search"><?php foreach (['status', 'kind'] as $keep): if ($filters[$keep]): ?><input type="hidden" name="<?= $keep ?>" value="<?= e($filters[$keep]) ?>"><?php endif; endforeach ?><label>ค้นหา<input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="เลขที่ใบแจ้งหนี้ / ใบเสร็จ / ใบเสนอราคา / การจอง ชื่อลูกค้า หรือชื่อทริป"></label><div class="actions"><button class="btn" type="submit">ค้นหา</button><?php if ($filters['q']): ?><a href="<?= e($link(['q' => '', 'page' => ''])) ?>">ล้าง</a><?php endif ?></div></form>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>เลขที่ / ลูกค้า</th><th>รายการ</th><th>ยอด</th><th>สถานะ</th><th></th></tr></thead><tbody>
<?php foreach ($invoices as $inv): $overdue = $inv['status'] === 'unpaid' && $inv['due_date'] && $inv['due_date'] < date('Y-m-d'); ?><tr>
 <td><b><?= e($inv['number']) ?></b><small><?= e($inv['customer_company'] ?: $inv['customer_name']) ?></small><?php $ref = trim($inv['quote_number'].' '.$inv['booking_reference']); if ($ref !== ''): ?><small>อ้างอิง <?= e($ref) ?></small><?php endif ?></td>
 <td><?= e($inv['label']) ?><small><?= e($kinds[$inv['kind']] ?? '') ?><?= $inv['title'] !== '' ? ' · '.e($inv['title']) : '' ?></small></td>
 <td>฿<?= money($inv['total']) ?><?php if ((float) $inv['wht_amount'] > 0): ?><small>สุทธิ ฿<?= money($inv['net_payable']) ?></small><?php endif ?></td>
 <td><span class="pill <?= e($pill[$inv['status']] ?? 'new') ?>"><?= e($statuses[$inv['status']] ?? $inv['status']) ?></span><?php if ($overdue): ?><small><b>เลยกำหนด <?= e(thai_date($inv['due_date'])) ?></b></small><?php elseif ($inv['due_date'] && $inv['status'] === 'unpaid'): ?><small>ครบกำหนด <?= e(thai_date($inv['due_date'])) ?></small><?php endif ?><?php if ($inv['receipt_number']): ?><small>ใบเสร็จ <?= e($inv['receipt_number']) ?></small><?php endif ?></td>
 <td><a class="text-link" href="<?= base_url().'admin/group-invoices/'.(int) $inv['id'] ?>">เปิด</a> · <a class="text-link" href="<?= base_url().'admin/group-invoices/'.(int) $inv['id'].'/print' ?>" target="_blank" rel="noopener">พิมพ์ ↗</a></td>
</tr><?php endforeach ?>
<?php if (! $invoices): ?><tr><td colspan="5" class="empty">ยังไม่มีใบแจ้งหนี้ เปิดใบเสนอราคาแล้วกด “สร้างใบแจ้งหนี้” หรือกด “ใบแจ้งหนี้ค่าใช้จ่ายอื่น”</td></tr><?php endif ?>
</tbody></table></div></div>
<?php if ($pages > 1): ?><nav class="filter-tabs" aria-label="หน้า"><?php for ($p = 1; $p <= $pages; $p++): ?><a class="<?= $p === $page ? 'active' : '' ?>" href="<?= e($link(['page' => $p > 1 ? $p : ''])) ?>"><?= $p ?></a><?php endfor ?></nav><?php endif ?>
