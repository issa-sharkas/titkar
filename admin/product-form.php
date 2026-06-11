<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── Edit mode detection ──────────────────────────────────────────────
$product_id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$isEdit     = $product_id > 0;
$product    = null;
$images     = [];
$errors     = [];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        header('Location: products.php');
        exit;
    }
    $imgStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $imgStmt->execute([$product_id]);
    $images = $imgStmt->fetchAll();
}

// ── Handle: delete single image ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_image') {
    $imgId  = (int)($_POST['image_id']   ?? 0);
    $pid    = (int)($_POST['product_id'] ?? 0);
    if ($imgId && $pid) {
        $imgStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE id = ? AND product_id = ?');
        $imgStmt->execute([$imgId, $pid]);
        $imgRow = $imgStmt->fetch();
        if ($imgRow) {
            $filePath = UPLOAD_PATH . $imgRow['image_url'];
            if ($imgRow['image_url'] && file_exists($filePath)) { @unlink($filePath); }
            $pdo->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imgId]);
        }
    }
    header('Location: product-form.php?id=' . $pid . '&msg=image_deleted');
    exit;
}

// ── Handle: set main image ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_main') {
    $imgId = (int)($_POST['image_id']   ?? 0);
    $pid   = (int)($_POST['product_id'] ?? 0);
    if ($imgId && $pid) {
        $pdo->prepare('UPDATE product_images SET is_main = 0 WHERE product_id = ?')->execute([$pid]);
        $pdo->prepare('UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?')->execute([$imgId, $pid]);
    }
    header('Location: product-form.php?id=' . $pid);
    exit;
}

// ── Handle: save product (add / edit) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($_POST['action'] ?? '', ['delete_image', 'set_main'])) {

    if (!csrf_verify()) {
        header('Location: product-form.php' . ($product_id ? '?id=' . $product_id . '&err=csrf' : '?err=csrf'));
        exit;
    }

    // Collect & sanitize
    $name_ar              = trim($_POST['name_ar']              ?? '');
    $name_en              = trim($_POST['name_en']              ?? '');
    $slug                 = trim(strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $_POST['slug'] ?? ''))));
    $category_id          = ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null;
    $status               = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $is_featured          = isset($_POST['is_featured']) ? 1 : 0;
    $short_description_ar = trim($_POST['short_description_ar'] ?? '');
    $short_description_en = trim($_POST['short_description_en'] ?? '');
    $full_description_ar  = trim($_POST['full_description_ar']  ?? '');
    $full_description_en  = trim($_POST['full_description_en']  ?? '');
    $price                = ($_POST['price'] ?? '') !== '' ? (float)$_POST['price'] : null;
    $sale_price           = ($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null;
    $product_type         = in_array($_POST['product_type'] ?? '', ['ready_gift', 'event_giveaway', 'both']) ? $_POST['product_type'] : 'ready_gift';
    $order_mode           = in_array($_POST['order_mode'] ?? '', ['buy_now', 'request_quote', 'both']) ? $_POST['order_mode'] : 'buy_now';
    $stock_quantity       = (int)($_POST['stock_quantity'] ?? 0);
    $stock_status         = in_array($_POST['stock_status'] ?? '', ['in_stock', 'out_of_stock']) ? $_POST['stock_status'] : 'in_stock';
    $preparation_time     = trim($_POST['preparation_time']     ?? '');
    $package_contents     = trim($_POST['package_contents']     ?? '');
    $customization_options = trim($_POST['customization_options'] ?? '');

    // Validate
    if (!$name_ar) { $errors[] = 'اسم المنتج بالعربية مطلوب'; }
    if (!$name_en) { $errors[] = 'اسم المنتج بالإنجليزية مطلوب'; }
    if (!$slug)    { $errors[] = 'حقل الـ Slug مطلوب'; }

    // Check slug uniqueness
    if ($slug) {
        $slugCheck = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
        $slugCheck->execute([$slug, $product_id ?: 0]);
        if ($slugCheck->fetch()) { $errors[] = 'الـ Slug مستخدم بالفعل — اختر آخر'; }
    }

    if (empty($errors)) {
        if ($isEdit) {
            // UPDATE
            $pdo->prepare(
                "UPDATE products SET
                    name_ar=?, name_en=?, slug=?, category_id=?, status=?, is_featured=?,
                    short_description_ar=?, short_description_en=?,
                    full_description_ar=?, full_description_en=?,
                    price=?, sale_price=?, product_type=?, order_mode=?,
                    stock_quantity=?, stock_status=?,
                    preparation_time=?, package_contents=?, customization_options=?
                 WHERE id=?"
            )->execute([
                $name_ar, $name_en, $slug, $category_id, $status, $is_featured,
                $short_description_ar, $short_description_en,
                $full_description_ar, $full_description_en,
                $price, $sale_price, $product_type, $order_mode,
                $stock_quantity, $stock_status,
                $preparation_time ?: null,
                $package_contents ?: null,
                $customization_options ?: null,
                $product_id,
            ]);
            $savedId = $product_id;

        } else {
            // INSERT
            $pdo->prepare(
                "INSERT INTO products
                    (name_ar, name_en, slug, category_id, status, is_featured,
                     short_description_ar, short_description_en,
                     full_description_ar, full_description_en,
                     price, sale_price, product_type, order_mode,
                     stock_quantity, stock_status,
                     preparation_time, package_contents, customization_options)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $name_ar, $name_en, $slug, $category_id, $status, $is_featured,
                $short_description_ar, $short_description_en,
                $full_description_ar, $full_description_en,
                $price, $sale_price, $product_type, $order_mode,
                $stock_quantity, $stock_status,
                $preparation_time ?: null,
                $package_contents ?: null,
                $customization_options ?: null,
            ]);
            $savedId = (int) $pdo->lastInsertId();
        }

        // ── Image uploads ────────────────────────────────────────────
        if (!empty($_FILES['images']['name'][0])) {
            $allowed    = ['image/jpeg', 'image/png', 'image/webp'];
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            // Current sort order start
            $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?');
            $sortStmt->execute([$savedId]);
            $sortOrder = (int) $sortStmt->fetchColumn() + 1;

            // Is there already a main image?
            $mainStmt = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_main = 1');
            $mainStmt->execute([$savedId]);
            $hasMain = (bool) $mainStmt->fetchColumn();

            if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0755, true); }

            foreach ($_FILES['images']['name'] as $i => $origName) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) { continue; }
                $tmpPath = $_FILES['images']['tmp_name'][$i];

                // Validate via getimagesize (safe for basic XAMPP setups)
                $imgInfo = @getimagesize($tmpPath);
                if (!$imgInfo) { continue; }
                $realMime = $imgInfo['mime'];
                if (!in_array($realMime, $allowed)) { continue; }

                $ext      = $extensions[$realMime];
                $filename = 'product_' . $savedId . '_' . uniqid() . '.' . $ext;
                $destPath = UPLOAD_PATH . $filename;

                if (move_uploaded_file($tmpPath, $destPath)) {
                    $isMain = (!$hasMain) ? 1 : 0;
                    $hasMain = true;
                    $pdo->prepare(
                        'INSERT INTO product_images (product_id, image_url, sort_order, is_main) VALUES (?,?,?,?)'
                    )->execute([$savedId, $filename, $sortOrder++, $isMain]);
                }
            }
        }

        header('Location: products.php?msg=' . urlencode($isEdit ? 'تم تحديث المنتج بنجاح' : 'تم إضافة المنتج بنجاح') . '&type=success');
        exit;
    }
    // If errors: fall through to render form with POST data
}

// ── Form data defaults ───────────────────────────────────────────────
// Priority: POST (on validation error) → DB (edit mode) → empty (new)
$f = [
    'name_ar'               => $_POST['name_ar']               ?? $product['name_ar']               ?? '',
    'name_en'               => $_POST['name_en']               ?? $product['name_en']               ?? '',
    'slug'                  => $_POST['slug']                  ?? $product['slug']                  ?? '',
    'category_id'           => $_POST['category_id']           ?? $product['category_id']           ?? '',
    'status'                => $_POST['status']                ?? $product['status']                ?? 'active',
    'is_featured'           => $_POST['is_featured']           ?? $product['is_featured']           ?? 0,
    'short_description_ar'  => $_POST['short_description_ar']  ?? $product['short_description_ar']  ?? '',
    'short_description_en'  => $_POST['short_description_en']  ?? $product['short_description_en']  ?? '',
    'full_description_ar'   => $_POST['full_description_ar']   ?? $product['full_description_ar']   ?? '',
    'full_description_en'   => $_POST['full_description_en']   ?? $product['full_description_en']   ?? '',
    'price'                 => $_POST['price']                 ?? $product['price']                 ?? '',
    'sale_price'            => $_POST['sale_price']            ?? $product['sale_price']            ?? '',
    'product_type'          => $_POST['product_type']          ?? $product['product_type']          ?? 'ready_gift',
    'order_mode'            => $_POST['order_mode']            ?? $product['order_mode']            ?? 'buy_now',
    'stock_quantity'        => $_POST['stock_quantity']        ?? $product['stock_quantity']        ?? 0,
    'stock_status'          => $_POST['stock_status']          ?? $product['stock_status']          ?? 'in_stock',
    'preparation_time'      => $_POST['preparation_time']      ?? $product['preparation_time']      ?? '',
    'package_contents'      => $_POST['package_contents']      ?? $product['package_contents']      ?? '',
    'customization_options' => $_POST['customization_options'] ?? $product['customization_options'] ?? '',
];

function fv(array $f, string $key): string {
    return htmlspecialchars($f[$key] ?? '', ENT_QUOTES);
}
function sel(array $f, string $key, string $val): string {
    return ($f[$key] ?? '') == $val ? 'selected' : '';
}

// Fetch categories for dropdown
$allCats = $pdo->query("SELECT id, name_ar, type FROM categories WHERE status = 'active' ORDER BY type, name_ar")->fetchAll();

$pageTitle = $isEdit ? 'تعديل منتج' : 'إضافة منتج جديد';

// Flash message (e.g. after image delete)
$flashMsg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'image_deleted') { $flashMsg = 'تم حذف الصورة'; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.form-layout      { display:grid; grid-template-columns:1fr 280px; gap:24px; align-items:start; }
.form-section     { background:#fff; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,.05); margin-bottom:20px; }
.form-section h4  { font-size:1rem; font-weight:600; color:#2C1F14; margin:0 0 20px; padding-bottom:14px; border-bottom:1px solid #F0EBE3; display:flex; align-items:center; gap:8px; }
.form-section h4 i { color:#C9A96E; }
.sidebar-card     { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.05); margin-bottom:16px; }
.sidebar-card h4  { font-size:0.9rem; font-weight:600; color:#2C1F14; margin:0 0 16px; }
.img-grid         { display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:10px; margin-top:14px; }
.img-item         { position:relative; aspect-ratio:1; border-radius:8px; overflow:hidden; border:2px solid #F0EBE3; }
.img-item.is-main { border-color:#C9A96E; }
.img-item img     { width:100%;height:100%;object-fit:cover; }
.img-delete       { position:absolute;top:3px;right:3px;width:20px;height:20px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;font-size:.65rem;cursor:pointer;display:flex;align-items:center;justify-content:center; }
.img-main-label   { position:absolute;bottom:3px;left:3px;background:rgba(201,169,110,.9);color:#fff;font-size:.6rem;padding:2px 6px;border-radius:4px; }
.img-set-main     { position:absolute;bottom:3px;left:3px;background:rgba(0,0,0,.5);color:#fff;font-size:.6rem;padding:2px 6px;border-radius:4px;border:none;cursor:pointer;font-family:inherit; }
.btn-save         { width:100%;padding:13px;background:#C9A96E;color:#fff;border:none;border-radius:8px;font-family:inherit;font-size:0.975rem;font-weight:700;cursor:pointer;transition:background .2s; }
.btn-save:hover   { background:#b8945a; }
@media(max-width:900px){ .form-layout{ grid-template-columns:1fr; } }
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
        <h1 class="admin-page-title"><?= $isEdit ? 'تعديل منتج' : 'إضافة منتج جديد' ?></h1>
        <div class="admin-page-subtitle">
            <?= $isEdit ? htmlspecialchars($product['name_ar'], ENT_QUOTES) : 'ملء البيانات وحفظ المنتج' ?>
        </div>
    </div>
    <a href="products.php"
       style="color:#6B4F3A;text-decoration:none;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
        <i class="fas fa-arrow-right"></i> العودة للمنتجات
    </a>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-success" style="margin-bottom:20px;">
    <i class="fas fa-circle-check"></i> <?= htmlspecialchars($flashMsg, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
<div class="admin-alert admin-alert-error" style="margin-bottom:20px;flex-direction:column;align-items:flex-start;gap:6px;">
    <div><i class="fas fa-circle-exclamation"></i> <strong>يوجد أخطاء:</strong></div>
    <?php foreach ($errors as $err): ?>
    <div style="margin-right:22px;">• <?= htmlspecialchars($err, ENT_QUOTES) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── FORM ── -->
<form method="POST" enctype="multipart/form-data"
      action="product-form.php<?= $isEdit ? '?id=' . $product_id : '' ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="product_id" value="<?= $product_id ?>">
    <?php endif; ?>

<div class="form-layout">

    <!-- LEFT: Main form sections -->
    <div>

        <!-- Basic Info -->
        <div class="form-section">
            <h4><i class="fas fa-pen-to-square"></i> المعلومات الأساسية</h4>

            <div class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>الاسم بالعربية <span style="color:#C62828;">*</span></label>
                        <input type="text" name="name_ar" value="<?= fv($f,'name_ar') ?>" required placeholder="مثال: بوكس القلب الرومانسي">
                    </div>
                    <div class="form-group">
                        <label>الاسم بالإنجليزية <span style="color:#C62828;">*</span></label>
                        <input type="text" name="name_en" id="name_en" value="<?= fv($f,'name_en') ?>" required placeholder="e.g. Romantic Heart Box">
                    </div>
                </div>

                <div class="form-group">
                    <label>Slug (رابط المنتج) <span style="color:#C62828;">*</span></label>
                    <input type="text" name="slug" id="slug" value="<?= fv($f,'slug') ?>" dir="ltr"
                           placeholder="romantic-heart-box" pattern="[a-z0-9-]+" required>
                    <div class="form-hint">أحرف إنجليزية صغيرة وأرقام وشرطات فقط — يُستخدم في رابط الصفحة</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>التصنيف</label>
                        <select name="category_id">
                            <option value="">— بدون تصنيف —</option>
                            <?php foreach ($allCats as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= sel($f,'category_id',(string)$cat['id']) ?>>
                                <?= htmlspecialchars($cat['name_ar'], ENT_QUOTES) ?>
                                (<?= htmlspecialchars($cat['type'], ENT_QUOTES) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status">
                            <option value="active"   <?= sel($f,'status','active') ?>>نشط</option>
                            <option value="inactive" <?= sel($f,'status','inactive') ?>>غير نشط</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descriptions -->
        <div class="form-section">
            <h4><i class="fas fa-align-right"></i> الأوصاف</h4>
            <div class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>وصف قصير — عربي</label>
                        <textarea name="short_description_ar" rows="3" placeholder="وصف مختصر يظهر في قائمة المنتجات"><?= fv($f,'short_description_ar') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>وصف قصير — English</label>
                        <textarea name="short_description_en" rows="3" dir="ltr" placeholder="Short description shown in listing"><?= fv($f,'short_description_en') ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>وصف تفصيلي — عربي</label>
                        <textarea name="full_description_ar" rows="6" placeholder="الوصف الكامل للمنتج"><?= fv($f,'full_description_ar') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>وصف تفصيلي — English</label>
                        <textarea name="full_description_en" rows="6" dir="ltr" placeholder="Full product description"><?= fv($f,'full_description_en') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="form-section">
            <h4><i class="fas fa-tag"></i> السعر</h4>
            <div class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>السعر الأصلي (EGP)</label>
                        <input type="number" name="price" step="0.01" min="0"
                               value="<?= fv($f,'price') ?>" placeholder="اتركه فارغاً لـ &quot;حسب الطلب&quot;">
                    </div>
                    <div class="form-group">
                        <label>سعر الخصم (EGP) — اختياري</label>
                        <input type="number" name="sale_price" step="0.01" min="0"
                               value="<?= fv($f,'sale_price') ?>" placeholder="يظهر الأصلي مشطوباً">
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Settings -->
        <div class="form-section">
            <h4><i class="fas fa-sliders"></i> إعدادات المنتج</h4>
            <div class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>نوع المنتج</label>
                        <select name="product_type">
                            <option value="ready_gift"     <?= sel($f,'product_type','ready_gift') ?>>هدية جاهزة</option>
                            <option value="event_giveaway" <?= sel($f,'product_type','event_giveaway') ?>>هدية مناسبات</option>
                            <option value="both"           <?= sel($f,'product_type','both') ?>>كلاهما</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>وضع الطلب</label>
                        <select name="order_mode">
                            <option value="buy_now"       <?= sel($f,'order_mode','buy_now') ?>>شراء مباشر</option>
                            <option value="request_quote" <?= sel($f,'order_mode','request_quote') ?>>طلب سعر</option>
                            <option value="both"          <?= sel($f,'order_mode','both') ?>>كلاهما</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>الكمية في المخزن</label>
                        <input type="number" name="stock_quantity" min="0" value="<?= fv($f,'stock_quantity') ?>">
                    </div>
                    <div class="form-group">
                        <label>حالة المخزن</label>
                        <select name="stock_status">
                            <option value="in_stock"      <?= sel($f,'stock_status','in_stock') ?>>متوفر</option>
                            <option value="out_of_stock"  <?= sel($f,'stock_status','out_of_stock') ?>>غير متوفر</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>وقت التجهيز</label>
                    <input type="text" name="preparation_time" value="<?= fv($f,'preparation_time') ?>"
                           placeholder="مثال: 2–3 أيام عمل">
                </div>
            </div>
        </div>

        <!-- Extra Info -->
        <div class="form-section">
            <h4><i class="fas fa-list-check"></i> معلومات إضافية</h4>
            <div class="admin-form">
                <div class="form-group">
                    <label>محتويات الباقة</label>
                    <textarea name="package_contents" rows="4" placeholder="مثال: زجاجة عطر 30ml + كرت مخصص + علبة هدية"><?= fv($f,'package_contents') ?></textarea>
                </div>
                <div class="form-group">
                    <label>خيارات التخصيص</label>
                    <textarea name="customization_options" rows="4" placeholder="مثال: يمكن تخصيص اسم العروسين والتاريخ"><?= fv($f,'customization_options') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="form-section">
            <h4><i class="fas fa-images"></i> الصور</h4>

            <?php if ($isEdit && !empty($images)): ?>
            <!-- Existing images -->
            <div style="margin-bottom:20px;">
                <div style="font-size:0.85rem;color:#6B4F3A;margin-bottom:10px;">
                    الصور الحالية — الصورة الرئيسية محاطة بإطار ذهبي
                </div>
                <div class="img-grid">
                    <?php foreach ($images as $img): ?>
                    <div class="img-item <?= $img['is_main'] ? 'is-main' : '' ?>">
                        <img src="<?= UPLOAD_URL . htmlspecialchars($img['image_url'], ENT_QUOTES) ?>"
                             alt="">
                        <?php if ($img['is_main']): ?>
                        <span class="img-main-label">رئيسية</span>
                        <?php else: ?>
                        <form method="POST" style="display:contents;">
                            <input type="hidden" name="action"     value="set_main">
                            <input type="hidden" name="image_id"   value="<?= (int)$img['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <button type="submit" class="img-set-main" title="تعيين كرئيسية">رئيسي</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:contents;"
                              onsubmit="return confirm('حذف هذه الصورة؟')">
                            <input type="hidden" name="action"     value="delete_image">
                            <input type="hidden" name="image_id"   value="<?= (int)$img['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <button type="submit" class="img-delete" title="حذف">×</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upload new images -->
            <div class="image-upload-area" onclick="document.getElementById('imgInput').click();">
                <i class="fas fa-cloud-arrow-up"></i>
                <div style="margin-top:8px;font-size:0.9rem;">انقر لرفع صور جديدة (حتى 5)</div>
                <div style="font-size:0.75rem;color:#9E8877;margin-top:4px;">JPG / PNG / WebP — حتى 5MB للصورة</div>
            </div>
            <input type="file" name="images[]" id="imgInput" multiple
                   accept="image/jpeg,image/png,image/webp"
                   style="display:none;" onchange="previewImages(this)">

            <!-- New image previews (JS) -->
            <div id="newImgPreview" class="img-grid" style="margin-top:12px;"></div>
        </div>

    </div>
    <!-- /LEFT -->

    <!-- RIGHT: Sidebar -->
    <div>

        <!-- Publish card -->
        <div class="sidebar-card">
            <h4>النشر</h4>
            <div style="margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:0.9rem;color:#2C1F14;">
                    <input type="checkbox" name="is_featured" value="1"
                           <?= ($f['is_featured'] ?? 0) ? 'checked' : '' ?>
                           style="width:16px;height:16px;accent-color:#C9A96E;">
                    <span>منتج مميز ⭐</span>
                </label>
                <div class="form-hint" style="margin-top:4px;margin-right:26px;">يظهر في قسم المنتجات المميزة في الرئيسية</div>
            </div>
            <button type="submit" class="btn-save">
                <i class="fas fa-floppy-disk"></i>
                <?= $isEdit ? 'حفظ التعديلات' : 'حفظ المنتج' ?>
            </button>
        </div>

        <!-- Quick info -->
        <?php if ($isEdit && $product): ?>
        <div class="sidebar-card" style="font-size:0.82rem;color:#6B4F3A;line-height:2;">
            <h4>معلومات</h4>
            <div>🆔 ID: <strong><?= $product_id ?></strong></div>
            <div>📅 أُضيف: <strong><?= date('d/m/Y', strtotime($product['created_at'])) ?></strong></div>
            <div>🔄 آخر تعديل: <strong><?= date('d/m/Y', strtotime($product['updated_at'])) ?></strong></div>
            <div style="margin-top:10px;">
                <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($product['slug']) ?>"
                   target="_blank" rel="noopener"
                   style="color:#C9A96E;font-size:0.82rem;">
                    <i class="fas fa-arrow-up-right-from-square"></i> عرض الصفحة
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Danger zone (edit only) -->
        <?php if ($isEdit): ?>
        <div class="sidebar-card" style="border:1px solid #FFEBEE;">
            <h4 style="color:#C62828;">خطر</h4>
            <form method="POST" action="products.php"
                  onsubmit="return confirm('هل تريد حذف هذا المنتج نهائياً؟')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                <button type="submit"
                        style="width:100%;padding:10px;background:#FFEBEE;color:#C62828;
                               border:1px solid #FFCDD2;border-radius:8px;font-family:inherit;
                               font-size:0.875rem;font-weight:600;cursor:pointer;transition:background .2s;"
                        onmouseover="this.style.background='#C62828';this.style.color='#fff';"
                        onmouseout="this.style.background='#FFEBEE';this.style.color='#C62828';">
                    <i class="fas fa-trash-can"></i> حذف المنتج
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>
    <!-- /RIGHT -->

</div><!-- /form-layout -->
</form>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script>
// ── Slug auto-generation from English name ───────────────────────────
var nameEn = document.getElementById('name_en');
var slugEl = document.getElementById('slug');
if (nameEn && slugEl) {
    nameEn.addEventListener('input', function() {
        if (!slugEl.dataset.manual) {
            slugEl.value = this.value
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
    slugEl.addEventListener('input', function() {
        this.dataset.manual = '1';
    });
}

// ── New image preview ────────────────────────────────────────────────
function previewImages(input) {
    var container = document.getElementById('newImgPreview');
    container.innerHTML = '';
    var files = Array.from(input.files).slice(0, 5);
    files.forEach(function(file) {
        if (!file.type.startsWith('image/')) { return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'img-item';
            var img = document.createElement('img');
            img.src = e.target.result;
            div.appendChild(img);
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>
