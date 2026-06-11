<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

ensureMenuTable();

// ── POST actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: menu.php?err=csrf');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'add') {
        $labelAr = trim($_POST['label_ar'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url     = trim($_POST['url'] ?? '');
        if ($labelAr !== '' && $labelEn !== '') {
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM menu_items')->fetchColumn();
            $pdo->prepare('INSERT INTO menu_items (label_ar, label_en, url, sort_order) VALUES (?,?,?,?)')
                ->execute([$labelAr, $labelEn, $url, $maxSort + 1]);
            header('Location: menu.php?msg=added');
        } else {
            header('Location: menu.php?err=required');
        }
        exit;
    }

    if ($action === 'update' && $id) {
        $labelAr = trim($_POST['label_ar'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url     = trim($_POST['url'] ?? '');
        if ($labelAr !== '' && $labelEn !== '') {
            $pdo->prepare('UPDATE menu_items SET label_ar = ?, label_en = ?, url = ? WHERE id = ?')
                ->execute([$labelAr, $labelEn, $url, $id]);
            header('Location: menu.php?msg=saved');
        } else {
            header('Location: menu.php?err=required');
        }
        exit;
    }

    if ($action === 'toggle' && $id) {
        $pdo->prepare("UPDATE menu_items
                       SET status = IF(status = 'active', 'inactive', 'active')
                       WHERE id = ?")->execute([$id]);
        header('Location: menu.php?msg=saved');
        exit;
    }

    if ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
        header('Location: menu.php?msg=deleted');
        exit;
    }

    if ($action === 'move' && $id) {
        $dir = ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
        $cur = $pdo->prepare('SELECT id, sort_order FROM menu_items WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if ($row) {
            $op  = $dir === 'up' ? '<' : '>';
            $ord = $dir === 'up' ? 'DESC' : 'ASC';
            $nb  = $pdo->prepare("SELECT id, sort_order FROM menu_items
                                  WHERE sort_order $op ? ORDER BY sort_order $ord LIMIT 1");
            $nb->execute([$row['sort_order']]);
            $neighbor = $nb->fetch();
            if ($neighbor) {
                $upd = $pdo->prepare('UPDATE menu_items SET sort_order = ? WHERE id = ?');
                $upd->execute([$neighbor['sort_order'], $row['id']]);
                $upd->execute([$row['sort_order'], $neighbor['id']]);
            }
        }
        header('Location: menu.php');
        exit;
    }

    header('Location: menu.php');
    exit;
}

// ── Flash ────────────────────────────────────────────────────────────
$flashMsg  = '';
$flashType = 'success';
$msgs = ['added' => 'تمت إضافة الرابط بنجاح', 'saved' => 'تم الحفظ بنجاح', 'deleted' => 'تم الحذف بنجاح'];
if (!empty($_GET['msg']) && isset($msgs[$_GET['msg']])) {
    $flashMsg = $msgs[$_GET['msg']];
}
if (!empty($_GET['err'])) {
    $flashType = 'error';
    $flashMsg  = $_GET['err'] === 'csrf' ? 'طلب غير صالح. يرجى إعادة المحاولة.' : 'الاسم العربي والإنجليزي مطلوبان.';
}

// ── Load all items ───────────────────────────────────────────────────
$items = getMenuItems(false);
$total = count($items);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إدارة القائمة — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.menu-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    margin-bottom: 24px;
}
.menu-card h3 {
    font-size: .95rem; font-weight: 600; color: #2C1F14;
    margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #F0EBE3;
    display: flex; align-items: center; gap: 8px;
}
.menu-card h3 i { color: #C9A96E; }
.m-input {
    width: 100%; padding: 9px 12px;
    border: 1px solid rgba(201,169,110,.35); border-radius: 8px;
    font-family: 'Tajawal', sans-serif; font-size: .85rem; color: #2C1F14;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box; background:#fff;
}
.m-input:focus { outline: none; border-color: #C9A96E; box-shadow: 0 0 0 3px rgba(201,169,110,.12); }
.m-label { display:block; font-size:.78rem; font-weight:600; color:#6B4F3A; margin-bottom:5px; }
.m-hint  { font-size:.72rem; color:#9E8877; margin-top:4px; }

.menu-row {
    display: grid;
    grid-template-columns: 70px 1fr 1fr 1.2fr auto;
    gap: 10px; align-items: end;
    padding: 14px; border: 1px solid #F0EBE3; border-radius: 10px;
    margin-bottom: 10px; background: #FCFAF6;
    transition: box-shadow .25s ease, transform .25s ease;
    animation: rowIn .4s ease both;
}
@keyframes rowIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
.menu-row:hover { box-shadow: 0 6px 18px rgba(107,79,58,.08); }
.menu-row.row-hidden { opacity: .55; background: #F5F2EC; }

.sort-btns { display: flex; flex-direction: column; gap: 4px; align-items: center; }
.sort-btn {
    width: 26px; height: 22px; border: 1px solid rgba(201,169,110,.4);
    background: #fff; color: #C9A96E; border-radius: 6px; cursor: pointer;
    font-size: .7rem; display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.sort-btn:hover { background: #C9A96E; color: #fff; }
.sort-btn:disabled { opacity: .3; cursor: default; }
.sort-btn:disabled:hover { background: #fff; color: #C9A96E; }

.row-actions { display: flex; gap: 6px; align-items: center; }
.mbtn {
    border: none; border-radius: 8px; cursor: pointer;
    font-family: 'Tajawal', sans-serif; font-size: .78rem; font-weight: 600;
    padding: 9px 14px; display: inline-flex; align-items: center; gap: 5px;
    transition: all .2s;
}
.mbtn-save   { background: #C9A96E; color: #fff; }
.mbtn-save:hover { background: #b8945a; }
.mbtn-toggle { background: rgba(107,79,58,.08); color: #6B4F3A; }
.mbtn-toggle:hover { background: rgba(107,79,58,.16); }
.mbtn-del    { background: rgba(192,57,43,.08); color: #C0392B; }
.mbtn-del:hover { background: rgba(192,57,43,.18); }
.mbtn-add    { background: #C9A96E; color: #fff; padding: 11px 26px; font-size: .85rem; }
.mbtn-add:hover { background: #b8945a; }

.status-pill {
    font-size: .68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    display: inline-block; margin-bottom: 6px;
}
.pill-on  { background: rgba(46,125,50,.1); color: #2E7D32; }
.pill-off { background: rgba(158,136,119,.15); color: #9E8877; }

.add-grid { display: grid; grid-template-columns: 1fr 1fr 1.2fr auto; gap: 12px; align-items: end; }

@media (max-width: 900px) {
    .menu-row, .add-grid { grid-template-columns: 1fr; }
    .sort-btns { flex-direction: row; }
}
</style>
</head>
<body class="admin-body">
<div class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">إدارة القائمة</h1>
        <div class="admin-page-subtitle">تحكم في روابط القائمة العلوية والفوتر — إضافة، تعديل، إخفاء، ترتيب</div>
    </div>
</div>

<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:20px;">
    <i class="fas fa-<?= $flashType === 'error' ? 'circle-exclamation' : 'circle-check' ?>"></i>
    <?= htmlspecialchars($flashMsg, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<!-- ── Add new link ── -->
<div class="menu-card">
    <h3><i class="fas fa-plus"></i> إضافة رابط جديد</h3>
    <form method="POST">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="add">
        <div class="add-grid">
            <div>
                <label class="m-label">الاسم — عربي *</label>
                <input type="text" name="label_ar" class="m-input" required placeholder="مثال: عروض خاصة">
            </div>
            <div>
                <label class="m-label">الاسم — English *</label>
                <input type="text" name="label_en" class="m-input" dir="ltr" required placeholder="e.g. Special Offers">
            </div>
            <div>
                <label class="m-label">الرابط</label>
                <input type="text" name="url" class="m-input" dir="ltr" placeholder="pages/shop.php">
                <div class="m-hint">فاضي = الرئيسية · مسار داخلي مثل pages/shop.php · أو رابط كامل https://...</div>
            </div>
            <button type="submit" class="mbtn mbtn-add"><i class="fas fa-plus"></i> إضافة</button>
        </div>
    </form>
</div>

<!-- ── Existing links ── -->
<div class="menu-card">
    <h3><i class="fas fa-bars"></i> روابط القائمة الحالية (<?= $total ?>)</h3>

    <?php if (!$items): ?>
    <p style="color:#9E8877;font-style:italic;font-size:.85rem;">لا توجد روابط — أضف أول رابط من الأعلى.</p>
    <?php endif; ?>

    <?php foreach ($items as $i => $item): ?>
    <div class="menu-row <?= $item['status'] === 'inactive' ? 'row-hidden' : '' ?>" style="animation-delay:<?= $i * 0.05 ?>s;">

        <!-- Reorder -->
        <div class="sort-btns">
            <form method="POST" style="display:contents;">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" name="dir" value="up" class="sort-btn" <?= $i === 0 ? 'disabled' : '' ?> title="تحريك لأعلى">
                    <i class="fas fa-chevron-up"></i>
                </button>
                <button type="submit" name="dir" value="down" class="sort-btn" <?= $i === $total - 1 ? 'disabled' : '' ?> title="تحريك لأسفل">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </form>
        </div>

        <!-- Edit form fields (one form per row) -->
        <form method="POST" style="display:contents;" id="edit-<?= (int) $item['id'] ?>">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <div>
                <span class="status-pill <?= $item['status'] === 'active' ? 'pill-on' : 'pill-off' ?>">
                    <?= $item['status'] === 'active' ? '✓ ظاهر' : '✕ مخفي' ?>
                </span>
                <label class="m-label">الاسم — عربي</label>
                <input type="text" name="label_ar" class="m-input" required
                       value="<?= htmlspecialchars($item['label_ar'], ENT_QUOTES) ?>">
            </div>
            <div>
                <label class="m-label">الاسم — English</label>
                <input type="text" name="label_en" class="m-input" dir="ltr" required
                       value="<?= htmlspecialchars($item['label_en'], ENT_QUOTES) ?>">
            </div>
            <div>
                <label class="m-label">الرابط</label>
                <input type="text" name="url" class="m-input" dir="ltr"
                       value="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>" placeholder="(فاضي = الرئيسية)">
            </div>
        </form>

        <!-- Row actions -->
        <div class="row-actions">
            <button type="submit" form="edit-<?= (int) $item['id'] ?>" class="mbtn mbtn-save" title="حفظ التعديل">
                <i class="fas fa-floppy-disk"></i>
            </button>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="mbtn mbtn-toggle" title="<?= $item['status'] === 'active' ? 'إخفاء من الموقع' : 'إظهار في الموقع' ?>">
                    <i class="fas fa-eye<?= $item['status'] === 'active' ? '-slash' : '' ?>"></i>
                </button>
            </form>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('متأكد من حذف هذا الرابط نهائياً؟');">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="mbtn mbtn-del" title="حذف">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>

    </div>
    <?php endforeach; ?>

    <div class="m-hint" style="margin-top:14px;">
        <i class="fas fa-circle-info" style="color:#C9A96E;"></i>
        التغييرات تنعكس فوراً على القائمة العلوية وروابط الفوتر في الموقع. الروابط المخفية تبقى محفوظة ويمكن إظهارها لاحقاً.
    </div>
</div>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
