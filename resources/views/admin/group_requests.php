<?php
use App\Repositories\GroupTourRepository as Groups;
$statuses = Groups::statuses();
$pill = ['new' => 'new', 'contacted' => 'contacted', 'quoted' => 'contacted', 'confirmed' => 'confirmed', 'closed' => 'cancelled'];
$link = function (array $changes) use ($filters) {
    $q = http_build_query(array_filter(array_merge(['status' => $filters['status'], 'q' => $filters['q']], $changes), fn ($v) => $v !== '' && $v !== null));

    return base_url().'admin/group-requests'.($q !== '' ? '?'.$q : '');
};
?>
<div class="section-head"><div><span class="eyebrow">GROUP TOUR REQUESTS</span><h1>คำขอจัดกรุ๊ปเหมา</h1><p>คำขอจากแบบฟอร์มหน้า <a class="text-link" href="<?= base_url().'grouptour' ?>" target="_blank" rel="noopener">/grouptour ↗</a> ติดต่อกลับ ส่งใบเสนอราคา และบันทึกความคืบหน้า</p></div><a class="outline-link" href="<?= base_url().'admin/group-trips' ?>">ผลงานกรุ๊ปเหมา</a></div>
<div class="filter-tabs"><a href="<?= e($link(['status' => '', 'page' => ''])) ?>" class="<?= ! $filters['status'] ? 'active' : '' ?>">ทั้งหมด (<?= array_sum($counts) ?>)</a><?php foreach ($statuses as $key => $label): ?><a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e($link(['status' => $key, 'page' => ''])) ?>"><?= e($label) ?> (<?= (int) $counts[$key] ?>)</a><?php endforeach ?></div>
<form class="panel stack-form" method="get" role="search"><?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif ?><label>ค้นหา<input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="เลขที่คำขอ ชื่อ องค์กร เบอร์โทร อีเมล หรือจุดหมาย"></label><div class="actions"><button class="btn" type="submit">ค้นหา</button><?php if ($filters['q']): ?><a href="<?= e($link(['q' => '', 'page' => ''])) ?>">ล้าง</a><?php endif ?></div></form>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>คำขอ / ผู้ติดต่อ</th><th>จุดหมาย / ประเภท</th><th>ผู้เดินทาง / ช่วงเวลา</th><th>สถานะ</th><th></th></tr></thead><tbody>
<?php foreach ($requests as $r): ?><tr>
 <td><b><?= e($r['reference']) ?></b><small><?= e($r['contact_name']) ?><?= $r['organization'] ? ' · '.e($r['organization']) : '' ?></small><small><?= e($r['phone']) ?> · <?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></small></td>
 <td><?= e($r['destination']) ?><small><?= e(Groups::group_types()[$r['group_type']] ?? $r['group_type']) ?></small></td>
 <td><?= (int) $r['adults'] ?> ผู้ใหญ่<?= (int) $r['children'] ? ' · '.(int) $r['children'].' เด็ก' : '' ?><small><?= $r['date_from'] ? thai_date($r['date_from']) : e($r['date_note'] ?: 'ยังไม่ระบุวัน') ?></small></td>
 <td><span class="pill <?= e($pill[$r['status']] ?? 'new') ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td>
 <td><a class="text-link" href="<?= base_url().'admin/group-requests/'.(int) $r['id'] ?>">เปิดคำขอ ↗</a></td>
</tr><?php endforeach ?>
<?php if (! $requests): ?><tr><td colspan="5" class="empty">ยังไม่มีคำขอในรายการนี้</td></tr><?php endif ?>
</tbody></table></div></div>
<?php if ($pages > 1): ?><nav class="filter-tabs" aria-label="หน้า"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e($link(['page' => $i > 1 ? $i : ''])) ?>"><?= $i ?></a><?php endfor ?></nav><?php endif ?>
