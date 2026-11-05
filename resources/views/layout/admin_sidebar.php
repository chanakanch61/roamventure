<?php
$adminMenu = app(\App\Repositories\AdminAccessRepository::class)->menu();
$adminGroups = [
 ['ภาพรวม', ['admin']],
 ['ทัวร์และการจอง', ['admin/bookings', 'admin/tours', 'admin/destinations', 'admin/package-levels', 'admin/airlines']],
 ['เนื้อหาเว็บไซต์', ['admin/home-destinations', 'admin/banners', 'admin/seo', 'admin/gallery', 'admin/blog']],
 ['ตั้งค่าและสิทธิ์', ['admin/settings', 'admin/site-access', 'admin/payment-settings', 'admin/admins', 'admin/roles']],
];
$adminIcons = ['admin'=>'space_dashboard', 'admin/bookings'=>'event_note', 'admin/tours'=>'luggage', 'admin/destinations'=>'public', 'admin/package-levels'=>'hotel', 'admin/airlines'=>'flight', 'admin/home-destinations'=>'explore', 'admin/banners'=>'view_carousel', 'admin/seo'=>'travel_explore', 'admin/gallery'=>'photo_library', 'admin/blog'=>'edit_note', 'admin/settings'=>'settings', 'admin/payment-settings'=>'account_balance_wallet', 'admin/admins'=>'manage_accounts', 'admin/roles'=>'admin_panel_settings'];
$adminLabels = ['admin/destinations'=>'โซน ประเทศ และเส้นทาง', 'admin/home-destinations'=>'จุดหมายแนะนำ', 'admin/gallery'=>'แกลเลอรี', 'admin/blog'=>'บทความ'];
$adminIcons['admin/site-access']='lock';
$adminPath = trim(uri_string(), '/');
$adminActive = null;
foreach ($adminMenu as $url=>$item) {
 if ($adminPath === $url || ($url !== 'admin' && strpos($adminPath, $url.'/') === 0)) $adminActive = $url;
}
if (strpos($adminPath, 'admin/payments/') === 0) $adminActive = 'admin/bookings';
if (strpos($adminPath, 'admin/preview/') === 0) $adminActive = 'admin/tours';
?>
<aside class="sidebar" id="admin-sidebar" aria-label="เมนูแอดมิน" tabindex="-1">
 <div class="sidebar-heading"><a href="<?= base_url().'admin' ?>" class="brand" aria-label="RoamVenture ภาพรวม"><?= brand_logo($settings,'navbar') ?></a><button class="admin-sidebar-close" type="button" aria-label="ปิดเมนูแอดมิน"><i class="material-icons" aria-hidden="true">close</i></button></div>
 <div class="sidebar-workspace"><span class="sidebar-workspace-dot" aria-hidden="true"></span>ระบบจัดการเว็บไซต์<span>ADMIN</span></div>
 <nav aria-label="เมนูหลังบ้าน">
 <?php foreach ($adminGroups as $groupIndex=>$group):
  $visibleItems = [];
  foreach ($group[1] as $url) {
   if (!isset($adminMenu[$url])) continue;
   $item = $adminMenu[$url];
   if (!$item[2] || app(\App\Repositories\AdminAccessRepository::class)->can($item[2])) $visibleItems[$url] = $item;
  }
  if (!$visibleItems) continue;
 ?>
 <section class="sidebar-group" aria-labelledby="admin-nav-group-<?= $groupIndex ?>"><h2 id="admin-nav-group-<?= $groupIndex ?>" class="sidebar-label"><?= e($group[0]) ?></h2>
 <?php foreach ($visibleItems as $url=>$item): $isActive = $adminActive === $url; ?>
 <a class="<?= $isActive ? 'active' : '' ?>" href="<?= base_url().$url ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><i class="material-icons" aria-hidden="true"><?= $adminIcons[$url] ?></i><span class="sidebar-link-label"><?= e($adminLabels[$url] ?? $item[1]) ?></span><?php if ($isActive): ?><i class="material-icons sidebar-active-arrow" aria-hidden="true">chevron_right</i><?php endif ?></a>
 <?php endforeach ?></section>
 <?php endforeach ?>
 </nav>
 <div class="sidebar-bottom"><a class="sidebar-website" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="material-icons" aria-hidden="true">open_in_new</i>ดูหน้าเว็บไซต์<i class="material-icons" aria-hidden="true">north_east</i></a><div class="sidebar-account"><span class="sidebar-account-icon"><i class="material-icons" aria-hidden="true">person</i></span><div><p><?= e(session('admin_name')) ?></p><span>บัญชีผู้ดูแลระบบ</span></div><form action="<?= base_url().'admin/logout' ?>" method="post"><?= csrf_field() ?><button class="text-button" type="submit" aria-label="ออกจากระบบ" title="ออกจากระบบ"><i class="material-icons" aria-hidden="true">logout</i></button></form></div></div>
</aside>
<button class="admin-sidebar-backdrop" type="button" aria-label="ปิดเมนูแอดมิน" tabindex="-1" hidden></button>
