<?php use App\Repositories\GroupTourRepository as Groups; ?>
<div class="section-head"><div><span class="eyebrow">PAST GROUPS</span><h1>ผลงานกรุ๊ปเหมา</h1><p>กรุ๊ปที่เคยดูแล แสดงในหัวข้อ “ผลงานกรุ๊ปเหมาที่ผ่านมา” ของหน้า <a class="text-link" href="<?= base_url().'grouptour#past-groups' ?>" target="_blank" rel="noopener">/grouptour ↗</a> และมีหน้ารายละเอียดของแต่ละกรุ๊ป</p></div><a class="btn" href="<?= base_url().'admin/group-trips/new' ?>">＋ เพิ่มผลงาน</a></div>
<p class="notice">เขียนสรุปและเรื่องราวจากทริปจริง ระบุจุดหมาย ช่วงเวลา จำนวนผู้เดินทาง และสิ่งที่จัดให้กรุ๊ปให้ชัดเจน หน้าละเอียดแบบนี้ช่วยให้ Google และ AI search เข้าใจและอ้างอิงผลงานได้ ควรได้รับอนุญาตจากลูกค้าก่อนแสดงชื่อองค์กร รูปภาพ หรือคำรีวิว</p>
<div class="panel table-scroll"><table><thead><tr><th>รูป</th><th>ผลงาน</th><th>จุดหมาย / ช่วงเวลา</th><th>สถานะ</th><th>ลำดับ</th><th>จัดการ</th></tr></thead><tbody>
<?php foreach ($trips as $t): ?><tr>
 <td><img class="hotel-library-thumb" src="<?= e(base_url().$t['image']) ?>" alt="" loading="lazy"></td>
 <td><b><?= e($t['title']) ?></b><small><?= e(Groups::group_type_short($t['group_type'])) ?><?= $t['travelers'] ? ' · '.(int) $t['travelers'].' ท่าน' : '' ?></small></td>
 <td><?= e($t['destination']) ?><small><?= $t['travel_date'] ? thai_date($t['travel_date']) : 'ไม่ระบุวัน' ?></small></td>
 <td><span class="pill <?= $t['status'] === 'published' ? 'confirmed' : 'new' ?>"><?= $t['status'] === 'published' ? 'เผยแพร่' : 'ฉบับร่าง' ?></span></td>
 <td><?= (int) $t['sort_order'] ?></td>
 <td><a class="text-link" href="<?= base_url().'admin/group-trips/'.(int) $t['id'] ?>">แก้ไข</a><?php if ($t['status'] === 'published'): ?> · <a class="text-link" href="<?= e(base_url().'grouptour/'.rawurlencode($t['slug'])) ?>" target="_blank" rel="noopener">ดูหน้าเว็บ ↗</a><?php endif ?></td>
</tr><?php endforeach ?>
<?php if (! $trips): ?><tr><td colspan="6">ยังไม่มีผลงาน กด “เพิ่มผลงาน” หรือเปิดคำขอที่เดินทางแล้วแล้วกด “สร้างผลงานจากคำขอนี้”</td></tr><?php endif ?>
</tbody></table></div>
