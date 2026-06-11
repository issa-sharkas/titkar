<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── POST action handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $isAjax     = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                  && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($action === 'delete' && $product_id) {
        // Delete image files from filesystem first
        $imgStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = ?');
        $imgStmt->execute([$product_id]);
        foreach ($imgStmt->fetchAll() as $img) {
            $filePath = UPLOAD_PATH . $img['image_url'];
            if ($img['image_url'] && file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        // Delete product (DB cascades to product_images, product_tags)
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$product_id]);
        header('Location: products.php?msg=' . urlencode('تم حذف المنتج بنجاح') . '&type=success');
        exit;
    }

    if ($action === 'toggle_status' && $product_id) {
        $pdo->prepare(
            "UPDATE products SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?"
        )->execute([$product_id]);
        if ($isAjax) {
            $stmt = $pdo->prepare('SELECT status FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => $row['status']]);
            exit;
        }
        header('Location: products.php');
        exit;
    }

    if ($action === 'toggle_featured' && $product_id) {
        $pdo->prepare('UPDATE products SET is_featured = 1 - is_featured WHERE id = ?')
            ->execute([$product_id]);
        if ($isAjax) {
            $stmt = $pdo->prepare('SELECT is_featured FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_featured' => (bool)$row['is_featured']]);
            exit;
        }
        header('Location: products.php');
        exit;
    }

    header('Location: products.php');
    exit;
}

// ── Flash message from redirect ─────────────────────────────────────
$flashMsg  = '';
$flashType = '';
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg'], ENT_QUOTES);
    $flashType = in_array($_GET['type'] ?? '', ['success', 'error', 'info']) ? $_GET['type'] : 'info';
}

// ── Search + pagination ─────────────────────────────────────────────
$search  = sanitize($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($search) {
    $where  = 'WHERE (p.name_ar LIKE ? OR p.name_en LIKE ?)';
    $like   = '%' . $search . '%';
    $params = [$like, $like];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);

$listParams   = array_merge($params, [$perPage, $offset]);
$productsStmt = $pdo->prepare(
    "SELECT p.*, c.name_ar AS category_name,
            pi.image_url AS main_image
     FROM products p
     LEFT JOIN categories c  ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
     $where
     ORDER BY p.created_at DESC
     LIMIT ? OFFSET ?"
);
$productsStmt->execute($listParams);
$products = $productsStmt->fetchAll();

// ── Label helpers ────────────────────────────────────────────────────
$typeLabels = [
    'ready_gift'     => ['badge-active',   'هدية جاهزة'],
    'event_giveaway' => ['badge-new',      'مناسبات'],
    'both'           => ['badge-featured', 'كلاهما'],
];
$modeLabels = [
    'buy_now'       => ['badge-confirmed', 'شراء مباشر'],
    'request_quote' => ['badge-pending',   'طلب سعر'],
    'both'          => ['badge-featured',  'كلاهما'],
];

function pBadge(string $val, array $map): string {
    [$cls, $label] = $map[$val] ?? ['badge-inactive', htmlspecialchars($val, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

$pageTitle = 'إدارة المنتجات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>المنتجات — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.thumb-img{width:48px;height:48px;object-fit:cover;border-radius:6px;background:#F0EBE3;display:flex;align-items:center;justify-content:center;}
.thumb-placeholder{width:48px;height:48px;border-radius:6px;background:#F0EBE3;display:flex;align-items:center;justify-content:center;color:#C9A96E;font-size:1.1rem;}
.featured-star{background:none;border:none;font-size:1.1rem;cursor:pointer;padding:2px 6px;transition:transform .2s;}
.featured-star:hover{transform:scale(1.2);}
.status-toggle-btn{background:none;border:none;cursor:pointer;padding:0;}
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
        <h1 class="admin-page-title">إدارة المنتجات</h1>
        <div class="admin-page-subtitle"><?= $total ?> منتج</div>
    </div>
    <a href="product-form.php"
       style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
              background:#C9A96E;color:white;border-radius:8px;text-decoration:none;
              font-size:0.9rem;font-weight:600;transition:background .2s;"
       onmouseover="this.style.background='#b8945a'" onmouseout="this.style.background='#C9A96E'">
        <i class="fas fa-plus"></i> إضافة منتج جديد
    </a>
</div>

<!-- Flash message -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType ?>" style="margin-bottom:20px;">
    <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <?= $flashMsg ?>
</div>
<?php endif; ?>

<!-- Filter bar -->
<form method="GET" class="admin-filter-bar">
    <input type="search" name="search" placeholder="ابحث عن منتج..." value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <button type="submit"
            style="padding:9px 18px;background:#C9A96E;color:white;border:none;
                   border-radius:8px;font-family:inherit;font-size:0.9rem;cursor:pointer;">
        <i class="fas fa-magnifying-glass"></i> بحث
    </button>
    <?php if ($search): ?>
    <a href="products.php" style="color:#9E8877;font-size:0.875rem;text-decoration:none;">
        <i class="fas fa-xmark"></i> مسح
    </a>
    <?php endif; ?>
</form>

<!-- Products Table -->
<div class="admin-table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:60px;">الصورة</th>
                <th>المنتج</th>
                <th>التصنيف</th>
                <th>السعر</th>
                <th>النوع</th>
                <th>وضع الطلب</th>
                <th>الحالة</th>
                <th style="width:40px;">⭐</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="9" style="text-align:center;color:#9E8877;padding:40px;">
                    <?= $search ? 'لا توجد نتائج للبحث عن "' . htmlspecialchars($search, ENT_QUOTES) . '"' : 'لا توجد منتجات بعد' ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
            <tr>
                <!-- Thumbnail -->
                <td>
                    <?php if ($p['main_image']): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($p['main_image'], ENT_QUOTES) ?>"
                         alt="" class="thumb-img">
                    <?php else: ?>
                    <div class="thumb-placeholder"><i class="fas fa-spray-can-sparkles"></i></div>
                    <?php endif; ?>
                </td>

                <!-- Name -->
                <td>
                    <div style="font-weight:500;max-width:200px;">
                        <?= htmlspecialchars($p['name_ar'], ENT_QUOTES) ?>
                    </div>
                    <div style="font-size:0.78rem;color:#9E8877;">
                        <?= htmlspecialchars($p['name_en'], ENT_QUOTES) ?>
                    </div>
                </td>

                <!-- Category -->
                <td style="font-size:0.875rem;color:#6B4F3A;">
                    <?= $p['category_name'] ? htmlspecialchars($p['category_name'], ENT_QUOTES) : '<span style="color:#ccc;">—</span>' ?>
                </td>

                <!-- Price -->
                <td style="white-space:nowrap;">
                    <?php if ($p['sale_price']): ?>
                        <span style="text-decoration:line-through;color:#aaa;font-size:0.8rem;">
                            <?= number_format((float)$p['price'], 0) ?>
                        </span>
                        <span style="color:#C9A96E;font-weight:600;">
                            <?= number_format((float)$p['sale_price'], 0) ?> EGP
                        </span>
                    <?php elseif ($p['price']): ?>
                        <span style="color:#C9A96E;font-weight:600;">
                            <?= number_format((float)$p['price'], 0) ?> EGP
                        </span>
                    <?php else: ?>
                        <span style="color:#9E8877;font-size:0.85rem;font-style:italic;">حسب الطلب</span>
                    <?php endif; ?>
                </td>

                <!-- Product type -->
                <td><?= pBadge($p['product_type'], $typeLabels) ?></td>

                <!-- Order mode -->
                <td><?= pBadge($p['order_mode'], $modeLabels) ?></td>

                <!-- Status toggle (AJAX) -->
                <td>
                    <button class="status-toggle-btn"
                            data-pid="<?= (int)$p['id'] ?>"
                            data-status="<?= htmlspecialchars($p['status'], ENT_QUOTES) ?>">
                        <span class="badge <?= $p['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $p['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                        </span>
                    </button>
                </td>

                <!-- Featured star (AJAX) -->
                <td>
                    <button class="featured-star" data-pid="<?= (int)$p['id'] ?>"
                            data-featured="<?= (int)$p['is_featured'] ?>"
                            title="<?= $p['is_featured'] ? 'إزالة من المميزة' : 'إضافة للمميزة' ?>">
                        <?= $p['is_featured'] ? '⭐' : '☆' ?>
                    </button>
                </td>

                <!-- Actions -->
                <td>
                    <div class="table-actions">
                        <a href="product-form.php?id=<?= (int)$p['id'] ?>"
                           class="btn-icon btn-icon-edit" title="تعديل">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('هل تريد حذف هذا المنتج نهائياً؟')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn-icon btn-icon-delete" title="حذف">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </form>
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>"
                           target="_blank" class="btn-icon btn-icon-view" title="عرض">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="admin-pagination">
    <?php if ($page > 1): ?>
    <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
    <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
    <?php else: ?>
        <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
    <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
    <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script>
// ── AJAX: Status toggle ──────────────────────────────────────────────
document.querySelectorAll('.status-toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var pid  = this.dataset.pid;
        var fd   = new FormData();
        fd.append('action', 'toggle_status');
        fd.append('product_id', pid);
        fetch('products.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var badge = btn.querySelector('.badge');
                var isActive = data.status === 'active';
                badge.className = 'badge ' + (isActive ? 'badge-active' : 'badge-inactive');
                badge.textContent = isActive ? 'نشط' : 'غير نشط';
                btn.dataset.status = data.status;
            }
        }.bind(this))
        .catch(function() {});
    });
});

// ── AJAX: Featured toggle ────────────────────────────────────────────
document.querySelectorAll('.featured-star').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var pid = this.dataset.pid;
        var fd  = new FormData();
        fd.append('action', 'toggle_featured');
        fd.append('product_id', pid);
        fetch('products.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                this.dataset.featured = data.is_featured ? '1' : '0';
                this.textContent      = data.is_featured ? '⭐' : '☆';
                this.title            = data.is_featured ? 'إزالة من المميزة' : 'إضافة للمميزة';
            }
        }.bind(this))
        .catch(function() {});
    });
});
</script>
</body>
</html>
