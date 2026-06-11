<?php
// Active page detection
$_activePage = basename($_SERVER['PHP_SELF'], '.php');

$_navItems = [
    ['icon' => 'fa-gauge',                   'label' => 'لوحة التحكم',      'page' => 'index',                   'href' => 'index.php'],
    ['icon' => 'fa-box-open',                'label' => 'المنتجات',          'page' => 'products',                'href' => 'products.php'],
    ['icon' => 'fa-tags',                    'label' => 'التصنيفات',         'page' => 'categories',              'href' => 'categories.php'],
    ['icon' => 'fa-shopping-bag',            'label' => 'الطلبات',           'page' => 'orders',                  'href' => 'orders.php'],
    ['icon' => 'fa-star',                    'label' => 'الطلبات المخصصة',  'page' => 'custom-requests',         'href' => 'custom-requests.php'],
    ['icon' => 'fa-images',                  'label' => 'المعرض',            'page' => 'gallery',                 'href' => 'gallery.php'],
    ['icon' => 'fa-bars',                    'label' => 'القائمة',           'page' => 'menu',                    'href' => 'menu.php'],
    ['icon' => 'fa-gear',                    'label' => 'الإعدادات',         'page' => 'settings',                'href' => 'settings.php'],
];
?>
<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-logo">
        <a href="index.php">تذكار | <span>TITHKAR</span></a>
        <div class="admin-badge">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">الإدارة</span>

        <?php foreach ($_navItems as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="<?= $_activePage === $item['page'] ? 'active' : '' ?>">
            <i class="fas <?= $item['icon'] ?>"></i>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <span class="nav-section-label">الموقع</span>
        <a href="<?= SITE_URL ?>/" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-arrow-up-right-from-square"></i>
            عرض الموقع
        </a>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:32px;height:32px;border-radius:50%;background:rgba(201,169,110,0.2);
                        display:flex;align-items:center;justify-content:center;
                        color:#C9A96E;font-size:0.85rem;flex-shrink:0;">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div style="font-size:0.85rem;color:#E8D5B0;font-weight:500;">
                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES) ?>
                </div>
                <div style="font-size:0.72rem;color:rgba(232,213,176,0.45);">مدير النظام</div>
            </div>
        </div>
        <a href="logout.php">
            <i class="fas fa-right-from-bracket"></i>
            تسجيل الخروج
        </a>
    </div>

</aside>

<!-- Mobile overlay -->
<div id="sidebarOverlay" onclick="closeSidebar()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;"></div>

<script>
function openSidebar()  {
    document.getElementById('adminSidebar').style.right = '0';
    document.getElementById('sidebarOverlay').style.display = 'block';
    document.body.classList.add('sidebar-open');
}
function closeSidebar() {
    document.getElementById('adminSidebar').style.right = '';
    document.getElementById('sidebarOverlay').style.display = 'none';
    document.body.classList.remove('sidebar-open');
}
</script>
