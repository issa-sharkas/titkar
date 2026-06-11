<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$flashMsg  = '';
$flashType = '';
$formErrors = [];

// ── POST: Add / Delete / Toggle ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // ── Add category ────────────────────────────────────────────────
    if ($action === 'add') {
        $name_ar   = trim($_POST['name_ar']   ?? '');
        $name_en   = trim($_POST['name_en']   ?? '');
        $slug      = trim(strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $_POST['slug'] ?? ''))));
        $type      = in_array($_POST['type'] ?? '', ['shop','occasion','style','product_type']) ? $_POST['type'] : 'occasion';
        $parent_id = ($_POST['parent_id'] ?? '') !== '' ? (int)$_POST['parent_id'] : null;

        // Auto-generate slug from name_en if empty
        if (!$slug && $name_en) {
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $name_en)));
        }

        if (!$name_ar) { $formErrors[] = 'الاسم بالعربية مطلوب'; }
        if (!$slug)    { $formErrors[] = 'الـ Slug مطلوب'; }

        if (empty($formErrors)) {
            // Check slug uniqueness
            $check = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
            $check->execute([$slug]);
            if ($check->fetch()) {
                $formErrors[] = 'الـ Slug مستخدم بالفعل';
            } else {
                $pdo->prepare(
                    'INSERT INTO categories (name_ar, name_en, slug, type, parent_id) VALUES (?,?,?,?,?)'
                )->execute([$name_ar, $name_en ?: $name_ar, $slug, $type, $parent_id]);
                header('Location: categories.php?msg=' . urlencode('تمت إضافة التصنيف بنجاح') . '&type=success');
                exit;
            }
        }
    }

    // ── Delete category ─────────────────────────────────────────────
    elseif ($action === 'delete') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        if ($cat_id) {
            // Check for products using this category
            $prodCheck = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $prodCheck->execute([$cat_id]);
            $prodCount = (int) $prodCheck->fetchColumn();

            if ($prodCount > 0) {
                header('Location: categories.php?msg=' . urlencode('لا يمكن الحذف — يوجد ' . $prodCount . ' منتج في هذا التصنيف') . '&type=error');
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$cat_id]);
                header('Location: categories.php?msg=' . urlencode('تم حذف التصنيف') . '&type=success');
            }
        }
        exit;
    }

    // ── Toggle status (AJAX) ─────────────────────────────────────────
    elseif ($action === 'toggle_status') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        if ($cat_id) {
            $pdo->prepare(
                "UPDATE categories SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?"
            )->execute([$cat_id]);
            if ($isAjax) {
                $row = $pdo->prepare('SELECT status FROM categories WHERE id = ?');
                $row->execute([$cat_id]);
                $result = $row->fetch();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => $result['status']]);
                exit;
            }
        }
        header('Location: categories.php');
        exit;
    }
}

// Flash from redirect
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg'], ENT_QUOTES);
    $flashType = in_array($_GET['type'] ?? '', ['success','error']) ? $_GET['type'] : 'info';
}

// ── Fetch all categories with product count ──────────────────────────
$categories = $pdo->query(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.type, c.name_ar"
)->fetchAll();

// Parent categories for dropdown (only top-level, no parent themselves)
$parentOptions = array_filter($categories, fn($c) => $c['parent_id'] === null);

// Type labels
$typeLabels = [
    'shop'         => 'متجر',
    'occasion'     => 'مناسبة',
    'style'        => 'ستايل',
    'product_type' => 'نوع المنتج',
];

// Keep POST data after validation error
$formData = [
    'name_ar'   => $_POST['name_ar']   ?? '',
    'name_en'   => $_POST['name_en']   ?? '',
    'slug'      => $_POST['slug']      ?? '',
    'type'      => $_POST['type']      ?? 'occasion',
    'parent_id' => $_POST['parent_id'] ?? '',
];

$pageTitle = 'التصنيفات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>التصنيفات — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.cats-layout{display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;}
.add-form-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);position:sticky;top:20px;}
.add-form-card h3{font-size:1rem;font-weight:600;color:#2C1F14;margin:0 0 20px;
                  padding-bottom:12px;border-bottom:1px solid #F0EBE3;}
.status-toggle-btn{background:none;border:none;cursor:pointer;padding:0;}
.type-pill{display:inline-block;padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:500;}
.type-shop{background:#E3F2FD;color:#1565C0;}
.type-occasion{background:rgba(201,169,110,.15);color:#8B6914;}
.type-style{background:#F3E5F5;color:#6A1B9A;}
.type-product_type{background:#E8F5E9;color:#2E7D32;}
@media(max-width:900px){.cats-layout{grid-template-columns:1fr;} .add-form-card{position:static;}}
</style>
</head>
<body class="admin-body">
<div class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>

<!-- Page header -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">التصنيفات</h1>
        <div class="admin-page-subtitle"><?= count($categories) ?> تصنيف</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType ?>" style="margin-bottom:20px;">
    <i class="fas fa-<?= $flashType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
    <?= $flashMsg ?>
</div>
<?php endif; ?>

<div class="cats-layout">

    <!-- LEFT: Add form -->
    <div>
        <div class="add-form-card">
            <h3><i class="fas fa-plus" style="color:#C9A96E;margin-left:8px;"></i>إضافة تصنيف جديد</h3>

            <!-- Validation errors -->
            <?php if (!empty($formErrors)): ?>
            <div class="admin-alert admin-alert-error" style="margin-bottom:16px;flex-direction:column;align-items:flex-start;gap:4px;">
                <?php foreach ($formErrors as $err): ?>
                <div>• <?= htmlspecialchars($err, ENT_QUOTES) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>الاسم بالعربية <span style="color:#C62828;">*</span></label>
                    <input type="text" name="name_ar"
                           value="<?= htmlspecialchars($formData['name_ar'], ENT_QUOTES) ?>"
                           placeholder="مثال: أفراح" required>
                </div>

                <div class="form-group">
                    <label>الاسم بالإنجليزية</label>
                    <input type="text" name="name_en" id="catNameEn"
                           value="<?= htmlspecialchars($formData['name_en'], ENT_QUOTES) ?>"
                           placeholder="e.g. Weddings">
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="catSlug" dir="ltr"
                           value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>"
                           placeholder="weddings" pattern="[a-z0-9-]+">
                    <div class="form-hint">يُولَّد تلقائياً من الاسم الإنجليزي</div>
                </div>

                <div class="form-group">
                    <label>النوع</label>
                    <select name="type">
                        <option value="occasion"     <?= $formData['type'] === 'occasion'     ? 'selected' : '' ?>>مناسبة</option>
                        <option value="shop"         <?= $formData['type'] === 'shop'         ? 'selected' : '' ?>>متجر</option>
                        <option value="product_type" <?= $formData['type'] === 'product_type' ? 'selected' : '' ?>>نوع المنتج</option>
                        <option value="style"        <?= $formData['type'] === 'style'        ? 'selected' : '' ?>>ستايل</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>التصنيف الأب (اختياري)</label>
                    <select name="parent_id">
                        <option value="">— بدون أب —</option>
                        <?php foreach ($parentOptions as $pc): ?>
                        <option value="<?= (int)$pc['id'] ?>"
                                <?= (string)$formData['parent_id'] === (string)$pc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pc['name_ar'], ENT_QUOTES) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit"
                        style="width:100%;padding:12px;background:#C9A96E;color:#fff;border:none;
                               border-radius:8px;font-family:inherit;font-size:0.95rem;font-weight:700;
                               cursor:pointer;transition:background .2s;"
                        onmouseover="this.style.background='#b8945a'"
                        onmouseout="this.style.background='#C9A96E'">
                    <i class="fas fa-plus"></i> إضافة التصنيف
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT: Categories table -->
    <div>
        <div class="admin-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>النوع</th>
                        <th>Slug</th>
                        <th style="text-align:center;">المنتجات</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#9E8877;padding:40px;">
                        لا توجد تصنيفات بعد
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($cat['name_ar'], ENT_QUOTES) ?></div>
                            <div style="font-size:0.78rem;color:#9E8877;"><?= htmlspecialchars($cat['name_en'], ENT_QUOTES) ?></div>
                        </td>
                        <td>
                            <span class="type-pill type-<?= htmlspecialchars($cat['type'], ENT_QUOTES) ?>">
                                <?= $typeLabels[$cat['type']] ?? htmlspecialchars($cat['type'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td style="font-size:0.8rem;color:#9E8877;font-family:monospace;direction:ltr;text-align:right;">
                            <?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>
                        </td>
                        <td style="text-align:center;font-weight:600;color:<?= (int)$cat['product_count'] > 0 ? '#C9A96E' : '#ccc' ?>;">
                            <?= (int)$cat['product_count'] ?>
                        </td>
                        <td>
                            <button class="status-toggle-btn"
                                    data-cat-id="<?= (int)$cat['id'] ?>"
                                    data-status="<?= htmlspecialchars($cat['status'], ENT_QUOTES) ?>">
                                <span class="badge <?= $cat['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $cat['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                                </span>
                            </button>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('هل تريد حذف تصنيف \'<?= htmlspecialchars(addslashes($cat['name_ar']), ENT_QUOTES) ?>\'؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="cat_id" value="<?= (int)$cat['id'] ?>">
                                <button type="submit" class="btn-icon btn-icon-delete" title="حذف">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /cats-layout -->

</div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script>
// ── Slug auto-generation ─────────────────────────────────────────────
var catNameEn = document.getElementById('catNameEn');
var catSlug   = document.getElementById('catSlug');
if (catNameEn && catSlug) {
    catNameEn.addEventListener('input', function() {
        if (!catSlug.dataset.manual) {
            catSlug.value = this.value.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
    catSlug.addEventListener('input', function() { this.dataset.manual = '1'; });
}

// ── AJAX: Status toggle ──────────────────────────────────────────────
document.querySelectorAll('.status-toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var catId = this.dataset.catId;
        var fd    = new FormData();
        fd.append('action', 'toggle_status');
        fd.append('cat_id', catId);
        fetch('categories.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var badge    = btn.querySelector('.badge');
                var isActive = data.status === 'active';
                badge.className   = 'badge ' + (isActive ? 'badge-active' : 'badge-inactive');
                badge.textContent = isActive ? 'نشط' : 'غير نشط';
            }
        }.bind(this))
        .catch(function() {});
    });
});
</script>
</body>
</html>
