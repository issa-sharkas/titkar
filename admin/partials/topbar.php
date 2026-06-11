<?php
// $pageTitle should be set by the including page before including this partial
$pageTitle = $pageTitle ?? 'لوحة التحكم';
?>
<div class="admin-topbar">

    <div style="display:flex;align-items:center;gap:14px;">
        <!-- Mobile sidebar toggle -->
        <button onclick="openSidebar()"
                style="display:none;background:none;border:none;cursor:pointer;
                       color:#6B4F3A;font-size:1.1rem;padding:4px;"
                class="sidebar-toggle-btn" aria-label="القائمة">
            <i class="fas fa-bars"></i>
        </button>
        <div class="admin-topbar-title">
            <?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>
        </div>
    </div>

    <div class="admin-user-info">
        <a href="<?= SITE_URL ?>/" target="_blank" rel="noopener noreferrer"
           style="color:#C9A96E;font-size:0.8rem;text-decoration:none;
                  display:flex;align-items:center;gap:5px;margin-left:6px;">
            <i class="fas fa-arrow-up-right-from-square" style="font-size:0.75rem;"></i>
            الموقع
        </a>
        <span style="color:#D5C8B8;"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES) ?></span>
        <div class="admin-user-avatar">
            <i class="fas fa-user"></i>
        </div>
        <a href="logout.php" title="تسجيل الخروج"
           style="color:#C62828;font-size:0.9rem;text-decoration:none;padding:4px;">
            <i class="fas fa-right-from-bracket"></i>
        </a>
    </div>

</div>

<style>
@media (max-width: 768px) {
    .sidebar-toggle-btn { display:flex !important; }
}
</style>
