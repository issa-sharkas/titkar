<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

define('GALLERY_PATH', __DIR__ . '/../assets/images/gallery/');
define('GALLERY_URL',  SITE_URL . '/assets/images/gallery/');

// Ensure directory exists
if (!is_dir(GALLERY_PATH)) { @mkdir(GALLERY_PATH, 0755, true); }

// ── POST: Upload ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $errors = [];

    if (empty($_FILES['gallery_image']['tmp_name'])) {
        $errors[] = 'يرجى اختيار صورة';
    } else {
        $allowed    = ['image/jpeg', 'image/png', 'image/webp'];
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $maxSize    = 5 * 1024 * 1024; // 5 MB

        $tmpPath  = $_FILES['gallery_image']['tmp_name'];
        $fileSize = $_FILES['gallery_image']['size'];

        $imgInfo  = @getimagesize($tmpPath);
        $realMime = $imgInfo['mime'] ?? '';

        if (!$imgInfo || !in_array($realMime, $allowed)) {
            $errors[] = 'نوع الملف غير مدعوم — JPG / PNG / WebP فقط';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'حجم الصورة يتجاوز 5MB';
        } else {
            $ext       = $extensions[$realMime];
            $filename  = 'gallery_' . uniqid() . '.' . $ext;
            $destPath  = GALLERY_PATH . $filename;

            if (move_uploaded_file($tmpPath, $destPath)) {
                $title       = sanitize($_POST['title']   ?? '');
                $category    = sanitize($_POST['category'] ?? 'general');
                $showOnHome  = isset($_POST['show_on_home']) ? 1 : 0;

                // Get next sort_order
                $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM gallery')->fetchColumn();

                $pdo->prepare(
                    'INSERT INTO gallery (title, image_url, category, show_on_home, sort_order, status)
                     VALUES (?, ?, ?, ?, ?, "active")'
                )->execute([$title ?: null, $filename, $category, $showOnHome, $maxSort + 1]);

                header('Location: gallery.php?msg=' . urlencode('تم رفع الصورة بنجاح') . '&type=success');
                exit;
            } else {
                $errors[] = 'فشل في حفظ الملف — تأكد من صلاحيات المجلد';
            }
        }
    }
    // If errors, fall through and show them
}

// ── POST: Delete ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $imgId = (int)($_POST['img_id'] ?? 0);
    if ($imgId) {
        $row = $pdo->prepare('SELECT image_url FROM gallery WHERE id = ?');
        $row->execute([$imgId]);
        $img = $row->fetch();
        if ($img) {
            $filePath = GALLERY_PATH . $img['image_url'];
            if ($img['image_url'] && file_exists($filePath)) { @unlink($filePath); }
            $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$imgId]);
        }
    }
    header('Location: gallery.php?msg=' . urlencode('تم حذف الصورة') . '&type=success');
    exit;
}

// ── POST: Toggle show_on_home (AJAX) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_home') {
    $imgId  = (int)($_POST['img_id'] ?? 0);
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if ($imgId) {
        $pdo->prepare('UPDATE gallery SET show_on_home = 1 - show_on_home WHERE id = ?')->execute([$imgId]);
        if ($isAjax) {
            $r = $pdo->prepare('SELECT show_on_home FROM gallery WHERE id = ?');
            $r->execute([$imgId]);
            $result = $r->fetch();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'show_on_home' => (bool)$result['show_on_home']]);
            exit;
        }
    }
    header('Location: gallery.php');
    exit;
}

// ── POST: Update sort_order (AJAX) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sort') {
    $imgId     = (int)($_POST['img_id']     ?? 0);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isAjax    = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if ($imgId) {
        $pdo->prepare('UPDATE gallery SET sort_order = ? WHERE id = ?')->execute([$sortOrder, $imgId]);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
    header('Location: gallery.php');
    exit;
}

// ── Fetch images ──────────────────────────────────────────────────────
$images = $pdo->query('SELECT * FROM gallery ORDER BY sort_order ASC, id DESC')->fetchAll();

// Flash / upload errors
$flashMsg  = '';
$flashType = '';
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg'], ENT_QUOTES);
    $flashType = in_array($_GET['type'] ?? '', ['success','error']) ? $_GET['type'] : 'info';
}
$uploadErrors = $errors ?? [];

$categoryLabels = [
    'wedding'    => 'أفراح',
    'engagement' => 'خطوبات',
    'birthday'   => 'أعياد ميلاد',
    'corporate'  => 'شركات',
    'product'    => 'منتجات',
    'general'    => 'عام',
];

$pageTitle = 'المعرض';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>المعرض — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.gallery-layout{display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;}
.upload-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);position:sticky;top:20px;}
.upload-card h3{font-size:1rem;font-weight:600;color:#2C1F14;margin:0 0 20px;
                padding-bottom:12px;border-bottom:1px solid #F0EBE3;}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;}
.gallery-card{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:box-shadow .2s;}
.gallery-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.1);}
.gallery-card-img{aspect-ratio:4/3;overflow:hidden;background:#F0EBE3;position:relative;}
.gallery-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.gallery-card:hover .gallery-card-img img{transform:scale(1.04);}
.gallery-card-body{padding:12px;}
.gallery-card-title{font-size:0.82rem;font-weight:500;color:#2C1F14;margin-bottom:8px;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.gallery-card-meta{display:flex;align-items:center;justify-content:space-between;gap:6px;}
.home-toggle{background:none;border:none;cursor:pointer;font-size:1.1rem;padding:2px;transition:transform .2s;}
.home-toggle:hover{transform:scale(1.15);}
.sort-input{width:50px;padding:4px 6px;border:1px solid #E8DDD0;border-radius:6px;
            font-size:0.8rem;text-align:center;font-family:inherit;color:#2C1F14;}
.sort-input:focus{outline:none;border-color:#C9A96E;}
.del-btn{background:none;border:none;color:#ddd;cursor:pointer;font-size:0.9rem;padding:4px;transition:color .2s;}
.del-btn:hover{color:#C62828;}
.cat-badge{font-size:0.68rem;padding:2px 8px;border-radius:10px;background:rgba(201,169,110,.12);color:#8B6914;}
.home-on{color:#25D366;}
.home-off{color:#ccc;}
.upload-zone-input{width:100%;padding:28px;border:2px dashed rgba(201,169,110,.4);border-radius:10px;
                   text-align:center;cursor:pointer;transition:all .2s;margin-bottom:14px;background:#FDFAF7;}
.upload-zone-input:hover{border-color:#C9A96E;background:rgba(201,169,110,.04);}
@media(max-width:900px){.gallery-layout{grid-template-columns:1fr;}.upload-card{position:static;}}
</style>
</head>
<body class="admin-body">
<div class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">المعرض</h1>
        <div class="admin-page-subtitle"><?= count($images) ?> صورة</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType ?>" style="margin-bottom:20px;">
    <i class="fas fa-circle-check"></i> <?= $flashMsg ?>
</div>
<?php endif; ?>

<div class="gallery-layout">

    <!-- LEFT: Upload form -->
    <div>
        <div class="upload-card">
            <h3><i class="fas fa-cloud-arrow-up" style="color:#C9A96E;margin-left:8px;"></i>رفع صورة</h3>

            <?php if (!empty($uploadErrors)): ?>
            <div class="admin-alert admin-alert-error" style="margin-bottom:16px;flex-direction:column;gap:4px;">
                <?php foreach ($uploadErrors as $e): ?>
                <div>• <?= htmlspecialchars($e, ENT_QUOTES) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="upload">

                <!-- File drop zone -->
                <label for="galleryFile">
                    <div class="upload-zone-input" id="dropZone">
                        <i class="fas fa-image" style="font-size:2rem;color:#C9A96E;display:block;margin-bottom:8px;"></i>
                        <div style="font-size:0.9rem;color:#6B4F3A;">انقر لاختيار صورة</div>
                        <div style="font-size:0.75rem;color:#9E8877;margin-top:4px;">JPG / PNG / WebP — حتى 5MB</div>
                        <div id="fileName" style="font-size:0.8rem;color:#C9A96E;margin-top:8px;display:none;"></div>
                    </div>
                </label>
                <input type="file" name="gallery_image" id="galleryFile" accept="image/jpeg,image/png,image/webp"
                       required style="display:none;" onchange="showFileName(this)">

                <div class="form-group">
                    <label>العنوان (اختياري)</label>
                    <input type="text" name="title" placeholder="مثال: بوكس أفراح فاخر">
                </div>

                <div class="form-group">
                    <label>التصنيف</label>
                    <select name="category">
                        <option value="general">عام</option>
                        <option value="wedding">أفراح</option>
                        <option value="engagement">خطوبات</option>
                        <option value="birthday">أعياد ميلاد</option>
                        <option value="corporate">شركات</option>
                        <option value="product">منتجات</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:0.875rem;">
                        <input type="checkbox" name="show_on_home" value="1" checked
                               style="width:16px;height:16px;accent-color:#C9A96E;">
                        <span>عرض على الرئيسية</span>
                    </label>
                </div>

                <button type="submit"
                        style="width:100%;padding:12px;background:#C9A96E;color:#fff;border:none;
                               border-radius:8px;font-family:inherit;font-size:0.95rem;font-weight:700;
                               cursor:pointer;transition:background .2s;"
                        onmouseover="this.style.background='#b8945a'"
                        onmouseout="this.style.background='#C9A96E'">
                    <i class="fas fa-cloud-arrow-up"></i> رفع الصورة
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT: Gallery grid -->
    <div>
        <?php if (empty($images)): ?>
        <div style="background:#fff;border-radius:12px;padding:60px;text-align:center;
                    box-shadow:0 2px 12px rgba(0,0,0,.05);color:#9E8877;">
            <i class="fas fa-images" style="font-size:3rem;display:block;margin-bottom:16px;color:#E8DDD0;"></i>
            لا توجد صور في المعرض بعد — ارفع أول صورة من النموذج
        </div>
        <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($images as $img): ?>
            <div class="gallery-card" id="gcard-<?= $img['id'] ?>">
                <div class="gallery-card-img">
                    <img src="<?= GALLERY_URL . htmlspecialchars($img['image_url'], ENT_QUOTES) ?>"
                         alt="<?= htmlspecialchars($img['title'] ?? '', ENT_QUOTES) ?>"
                         loading="lazy">
                </div>
                <div class="gallery-card-body">
                    <div class="gallery-card-title">
                        <?= $img['title'] ? htmlspecialchars($img['title'], ENT_QUOTES) : '<span style="color:#ccc;font-style:italic;">بدون عنوان</span>' ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                        <span class="cat-badge">
                            <?= $categoryLabels[$img['category']] ?? htmlspecialchars($img['category'] ?? 'عام', ENT_QUOTES) ?>
                        </span>
                    </div>
                    <div class="gallery-card-meta">
                        <!-- Show on home toggle -->
                        <button class="home-toggle <?= $img['show_on_home'] ? 'home-on' : 'home-off' ?>"
                                data-img-id="<?= (int)$img['id'] ?>"
                                title="<?= $img['show_on_home'] ? 'يظهر على الرئيسية' : 'لا يظهر على الرئيسية' ?>">
                            <?= $img['show_on_home'] ? '🏠' : '👁' ?>
                        </button>

                        <!-- Sort order -->
                        <input type="number" class="sort-input" value="<?= (int)$img['sort_order'] ?>"
                               data-img-id="<?= (int)$img['id'] ?>" title="ترتيب العرض" min="0">

                        <!-- Delete -->
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('حذف هذه الصورة نهائياً؟')">
                            <input type="hidden" name="action"  value="delete">
                            <input type="hidden" name="img_id" value="<?= (int)$img['id'] ?>">
                            <button type="submit" class="del-btn" title="حذف">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /gallery-layout -->

</div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script>
// ── File name preview ────────────────────────────────────────────────
function showFileName(input) {
    var el = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        el.textContent = input.files[0].name;
        el.style.display = 'block';
    }
}

// ── AJAX: Show on home toggle ────────────────────────────────────────
document.querySelectorAll('.home-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var imgId = this.dataset.imgId;
        var fd    = new FormData();
        fd.append('action', 'toggle_home');
        fd.append('img_id', imgId);
        fetch('gallery.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                btn.classList.toggle('home-on', data.show_on_home);
                btn.classList.toggle('home-off', !data.show_on_home);
                btn.textContent  = data.show_on_home ? '🏠' : '👁';
                btn.title        = data.show_on_home ? 'يظهر على الرئيسية' : 'لا يظهر على الرئيسية';
            }
        }.bind(this))
        .catch(function() {});
    });
});

// ── AJAX: Sort order on blur ─────────────────────────────────────────
document.querySelectorAll('.sort-input').forEach(function(input) {
    input.addEventListener('change', function() {
        var imgId     = this.dataset.imgId;
        var sortOrder = this.value;
        var fd        = new FormData();
        fd.append('action',     'sort');
        fd.append('img_id',     imgId);
        fd.append('sort_order', sortOrder);
        fetch('gallery.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function(r) { return r.json(); })
        .catch(function() {});
    });
});
</script>
</body>
</html>
