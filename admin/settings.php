<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── POST: Save settings ──────────────────────────────────────────────
$flashMsg  = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: settings.php?err=csrf');
        exit;
    }

    // All plain text/textarea/select settings
    $keys = [
        // Brand identity
        'site_name_ar', 'site_name_en', 'site_tagline_ar', 'site_tagline_en',
        // Contact
        'whatsapp_number', 'phone_number', 'email',
        'address_ar', 'address_en', 'working_hours_ar', 'working_hours_en',
        // Social
        'instagram_url', 'facebook_url', 'tiktok_url', 'youtube_url', 'snapchat_url',
        // Homepage hero
        'hero_badge_ar', 'hero_badge_en',
        'hero_title_1_ar', 'hero_title_gold_ar', 'hero_title_2_ar',
        'hero_title_1_en', 'hero_title_gold_en', 'hero_title_2_en',
        'hero_desc_ar', 'hero_desc_en',
        // Announcement bar
        'announcements_ar', 'announcements_en',
        // Footer
        'footer_description_ar', 'footer_description_en',
        // Delivery & site
        'delivery_areas', 'currency', 'default_language',
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );

    foreach ($keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val]);
    }

    // Checkboxes
    $stmt->execute(['announcement_enabled', isset($_POST['announcement_enabled']) ? '1' : '0']);

    // ── Image uploads: logo & favicon ─────────────────────────────────
    $uploadErr = '';
    $imageFields = [
        'site_logo'    => ['field' => 'site_logo_file',    'allowed' => ['image/jpeg','image/png','image/webp','image/gif'],            'prefix' => 'logo_'],
        'site_favicon' => ['field' => 'site_favicon_file', 'allowed' => ['image/png','image/x-icon','image/vnd.microsoft.icon','image/webp'], 'prefix' => 'favicon_'],
        'hero_image'   => ['field' => 'hero_image_file',   'allowed' => ['image/jpeg','image/png','image/webp'],                        'prefix' => 'hero_'],
    ];

    foreach ($imageFields as $settingKey => $cfg) {
        // Remove requested?
        if (isset($_POST['remove_' . $settingKey])) {
            $stmt->execute([$settingKey, '']);
            continue;
        }
        if (empty($_FILES[$cfg['field']]['name'])) {
            continue;
        }
        $file = $_FILES[$cfg['field']];
        if (!in_array($file['type'], $cfg['allowed'])) {
            $uploadErr = 'نوع الملف غير مدعوم: ' . htmlspecialchars($file['name'], ENT_QUOTES);
            continue;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            $uploadErr = 'الحجم الأقصى للصورة 2MB';
            continue;
        }
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = uniqid($cfg['prefix'], true) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $name)) {
            $stmt->execute([$settingKey, $name]);
        } else {
            $uploadErr = 'فشل رفع الملف. تأكد من صلاحيات مجلد uploads.';
        }
    }

    header('Location: settings.php?msg=saved' . ($uploadErr ? '&uperr=' . urlencode($uploadErr) : ''));
    exit;
}

// ── Flash messages ───────────────────────────────────────────────────
if (!empty($_GET['msg']) && $_GET['msg'] === 'saved') {
    $flashMsg = 'تم حفظ الإعدادات بنجاح';
}
if (!empty($_GET['uperr'])) {
    $flashMsg  = 'تم الحفظ، لكن: ' . htmlspecialchars($_GET['uperr'], ENT_QUOTES);
    $flashType = 'error';
}
if (!empty($_GET['err']) && $_GET['err'] === 'csrf') {
    $flashMsg  = 'طلب غير صالح. يرجى إعادة المحاولة.';
    $flashType = 'error';
}

// ── Load all settings ────────────────────────────────────────────────
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$s    = [];
foreach ($rows as $r) { $s[$r['setting_key']] = $r['setting_value']; }

// Helper: safe htmlspecialchars from settings array
function sv(array $s, string $key, string $default = ''): string {
    return htmlspecialchars($s[$key] ?? $default, ENT_QUOTES);
}

$pageTitle = 'الإعدادات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الإعدادات — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.settings-layout { display: grid; grid-template-columns: 1fr 280px; gap: 24px; align-items: start; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 16px; }
@media(max-width:700px){ .form-row { grid-template-columns: 1fr; } }
.settings-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    margin-bottom: 24px;
}
.settings-card h3 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #2C1F14;
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #F0EBE3;
    display: flex;
    align-items: center;
    gap: 8px;
}
.settings-card h3 i { color: #C9A96E; }
.form-hint { font-size: 0.75rem; color: #9E8877; margin-top: 4px; }
.settings-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #6B4F3A;
    margin-bottom: 6px;
}
.settings-input, .settings-textarea, .settings-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid rgba(201,169,110,.35);
    border-radius: 8px;
    font-family: 'Tajawal', sans-serif;
    font-size: 0.9rem;
    color: #2C1F14;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
}
.settings-input:focus, .settings-textarea:focus, .settings-select:focus {
    outline: none;
    border-color: #C9A96E;
    box-shadow: 0 0 0 3px rgba(201,169,110,.12);
}
.settings-textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
.sidebar-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    position: sticky;
    top: 20px;
}
.sidebar-card h4 { font-size: 0.9rem; font-weight: 600; color: #2C1F14; margin: 0 0 16px; }
.btn-save-settings {
    width: 100%;
    padding: 13px;
    background: #C9A96E;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Tajawal', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background .2s;
}
.btn-save-settings:hover { background: #b8945a; }
.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.8rem;
    color: #6B4F3A;
    padding: 6px 0;
    border-bottom: 1px solid #FAF7F2;
}
.tip-item:last-child { border-bottom: none; }
.tip-item i { color: #C9A96E; margin-top: 2px; flex-shrink: 0; }
@media(max-width:900px){ .settings-layout { grid-template-columns: 1fr; } .sidebar-card { position: static; } }

/* ── Tabs ──────────────────────────────── */
.stabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 22px;
    background: #fff;
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.stab-btn {
    border: none;
    background: transparent;
    padding: 10px 16px;
    border-radius: 8px;
    font-family: 'Tajawal', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #6B4F3A;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all .25s ease;
    white-space: nowrap;
}
.stab-btn i { color: #C9A96E; transition: color .25s; }
.stab-btn:hover { background: #FAF7F2; }
.stab-btn.active {
    background: linear-gradient(135deg, #C9A96E, #B8925A);
    color: #fff;
    box-shadow: 0 4px 14px rgba(201,169,110,.35);
}
.stab-btn.active i { color: #fff; }
.stab-panel { display: none; }
.stab-panel.active { display: block; animation: panelIn .35s ease both; }
@keyframes panelIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

/* ── Image upload preview ─────────────── */
.img-upload-box {
    border: 2px dashed rgba(201,169,110,.4);
    border-radius: 10px;
    padding: 18px;
    text-align: center;
    background: #FCFAF6;
    transition: border-color .25s, background .25s;
}
.img-upload-box:hover { border-color: #C9A96E; background: #fff; }
.img-upload-box img.current-img {
    max-height: 70px;
    margin: 0 auto 10px;
    display: block;
    border-radius: 6px;
    background: #FAF7F2;
    padding: 6px;
}
.img-upload-box .no-img {
    font-size: 0.8rem;
    color: #9E8877;
    margin-bottom: 10px;
    font-style: italic;
}
.img-upload-box input[type=file] {
    font-size: 0.8rem;
    margin: 0 auto;
    display: block;
    max-width: 100%;
}
.remove-check {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #C0392B;
    margin-top: 10px;
    cursor: pointer;
}
.lang-pair { border-inline-start: 3px solid rgba(201,169,110,.3); padding-inline-start: 14px; margin-bottom: 18px; }
.lang-pair-title { font-size: 0.8rem; font-weight: 700; color: #2C1F14; margin-bottom: 10px; }
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
        <h1 class="admin-page-title">الإعدادات</h1>
        <div class="admin-page-subtitle">تحكم كامل في هوية الموقع، النصوص، التواصل والمزيد</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType === 'error' ? 'error' : 'success' ?>"
     style="margin-bottom:20px;">
    <i class="fas fa-<?= $flashType === 'error' ? 'circle-exclamation' : 'circle-check' ?>"></i>
    <?= $flashMsg ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

<!-- ── Tab buttons ── -->
<div class="stabs">
    <button type="button" class="stab-btn active" data-tab="identity"><i class="fas fa-palette"></i> هوية الموقع</button>
    <button type="button" class="stab-btn" data-tab="contact"><i class="fas fa-phone"></i> التواصل</button>
    <button type="button" class="stab-btn" data-tab="social"><i class="fas fa-share-nodes"></i> السوشيال</button>
    <button type="button" class="stab-btn" data-tab="home"><i class="fas fa-house"></i> الصفحة الرئيسية</button>
    <button type="button" class="stab-btn" data-tab="footer"><i class="fas fa-align-right"></i> الفوتر</button>
    <button type="button" class="stab-btn" data-tab="delivery"><i class="fas fa-truck"></i> التوصيل والعملة</button>
</div>

<div class="settings-layout">

    <!-- LEFT: Settings sections -->
    <div>

        <!-- ════ TAB 1: Brand Identity ════ -->
        <div class="stab-panel active" data-panel="identity">

            <div class="settings-card">
                <h3><i class="fas fa-image"></i> اللوجو والأيقونة</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">لوجو الموقع</label>
                        <div class="img-upload-box">
                            <?php if (!empty($s['site_logo'])): ?>
                                <img src="<?= UPLOAD_URL . sv($s, 'site_logo') ?>" alt="اللوجو الحالي" class="current-img">
                            <?php else: ?>
                                <div class="no-img">لا يوجد لوجو — يظهر اسم الموقع كنص</div>
                            <?php endif; ?>
                            <input type="file" name="site_logo_file" accept="image/png,image/jpeg,image/webp,image/gif">
                            <?php if (!empty($s['site_logo'])): ?>
                            <label class="remove-check">
                                <input type="checkbox" name="remove_site_logo"> حذف اللوجو الحالي
                            </label>
                            <?php endif; ?>
                        </div>
                        <div class="form-hint">PNG بخلفية شفافة هو الأفضل — يظهر في الهيدر أعلى الموقع</div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">أيقونة المتصفح (Favicon)</label>
                        <div class="img-upload-box">
                            <?php if (!empty($s['site_favicon'])): ?>
                                <img src="<?= UPLOAD_URL . sv($s, 'site_favicon') ?>" alt="الأيقونة الحالية" class="current-img" style="max-height:40px;">
                            <?php else: ?>
                                <div class="no-img">لا توجد أيقونة</div>
                            <?php endif; ?>
                            <input type="file" name="site_favicon_file" accept="image/png,image/x-icon,image/webp">
                            <?php if (!empty($s['site_favicon'])): ?>
                            <label class="remove-check">
                                <input type="checkbox" name="remove_site_favicon"> حذف الأيقونة الحالية
                            </label>
                            <?php endif; ?>
                        </div>
                        <div class="form-hint">صورة مربعة PNG أو ICO — تظهر في تبويب المتصفح</div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3><i class="fas fa-signature"></i> اسم الموقع</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">اسم الموقع — عربي</label>
                        <input type="text" name="site_name_ar" class="settings-input"
                               value="<?= sv($s, 'site_name_ar') ?>" placeholder="تذكار">
                    </div>
                    <div class="form-group">
                        <label class="settings-label">اسم الموقع — English</label>
                        <input type="text" name="site_name_en" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'site_name_en') ?>" placeholder="TITHKAR">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">الشعار / الوصف القصير — عربي</label>
                        <input type="text" name="site_tagline_ar" class="settings-input"
                               value="<?= sv($s, 'site_tagline_ar') ?>" placeholder="هدايا عطرية فاخرة">
                        <div class="form-hint">يظهر في عنوان المتصفح وفي نتائج البحث Google</div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">الشعار / الوصف القصير — English</label>
                        <input type="text" name="site_tagline_en" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'site_tagline_en') ?>" placeholder="Premium Perfume Gifts">
                    </div>
                </div>
            </div>

        </div>

        <!-- ════ TAB 2: Contact ════ -->
        <div class="stab-panel" data-panel="contact">

            <div class="settings-card">
                <h3><i class="fas fa-phone"></i> أرقام التواصل</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">رقم واتساب</label>
                        <input type="text" name="whatsapp_number" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'whatsapp_number') ?>"
                               placeholder="201012345678">
                        <div class="form-hint">بدون + أو مسافات — يستخدم في جميع أزرار الواتساب بالموقع</div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">رقم هاتف إضافي (اختياري)</label>
                        <input type="text" name="phone_number" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'phone_number') ?>"
                               placeholder="01012345678">
                        <div class="form-hint">يظهر في صفحة التواصل والفوتر</div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="settings-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'email') ?>"
                           placeholder="info@tithkar.com">
                </div>
            </div>

            <div class="settings-card">
                <h3><i class="fas fa-location-dot"></i> العنوان وساعات العمل</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">العنوان — عربي</label>
                        <input type="text" name="address_ar" class="settings-input"
                               value="<?= sv($s, 'address_ar') ?>" placeholder="القاهرة الجديدة، مصر">
                    </div>
                    <div class="form-group">
                        <label class="settings-label">العنوان — English</label>
                        <input type="text" name="address_en" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'address_en') ?>" placeholder="New Cairo, Egypt">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">ساعات العمل — عربي</label>
                        <input type="text" name="working_hours_ar" class="settings-input"
                               value="<?= sv($s, 'working_hours_ar') ?>" placeholder="يومياً من 10 صباحاً حتى 10 مساءً">
                    </div>
                    <div class="form-group">
                        <label class="settings-label">ساعات العمل — English</label>
                        <input type="text" name="working_hours_en" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'working_hours_en') ?>" placeholder="Daily 10 AM – 10 PM">
                    </div>
                </div>
            </div>

        </div>

        <!-- ════ TAB 3: Social ════ -->
        <div class="stab-panel" data-panel="social">

            <div class="settings-card">
                <h3><i class="fas fa-share-nodes"></i> حسابات السوشيال ميديا</h3>
                <div class="form-group">
                    <label class="settings-label"><i class="fab fa-instagram" style="color:#E4405F;"></i> Instagram</label>
                    <input type="url" name="instagram_url" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'instagram_url') ?>"
                           placeholder="https://instagram.com/tithkar">
                </div>
                <div class="form-group">
                    <label class="settings-label"><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook</label>
                    <input type="url" name="facebook_url" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'facebook_url') ?>"
                           placeholder="https://facebook.com/tithkar">
                </div>
                <div class="form-group">
                    <label class="settings-label"><i class="fab fa-tiktok" style="color:#010101;"></i> TikTok</label>
                    <input type="url" name="tiktok_url" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'tiktok_url') ?>"
                           placeholder="https://tiktok.com/@tithkar">
                </div>
                <div class="form-group">
                    <label class="settings-label"><i class="fab fa-youtube" style="color:#FF0000;"></i> YouTube</label>
                    <input type="url" name="youtube_url" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'youtube_url') ?>"
                           placeholder="https://youtube.com/@tithkar">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="settings-label"><i class="fab fa-snapchat" style="color:#F7C600;"></i> Snapchat</label>
                    <input type="url" name="snapchat_url" class="settings-input" dir="ltr"
                           value="<?= sv($s, 'snapchat_url') ?>"
                           placeholder="https://snapchat.com/add/tithkar">
                </div>
                <div class="form-hint" style="margin-top:12px;">الحسابات الفاضية ما رح تظهر بالموقع — عبّي اللي عندك بس</div>
            </div>

        </div>

        <!-- ════ TAB 4: Homepage ════ -->
        <div class="stab-panel" data-panel="home">

            <div class="settings-card">
                <h3><i class="fas fa-bullhorn"></i> الشريط المتحرك (أعلى الصفحة الرئيسية)</h3>
                <div class="form-group">
                    <label class="settings-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="announcement_enabled"
                               <?= (($s['announcement_enabled'] ?? '1') !== '0') ? 'checked' : '' ?>>
                        تفعيل الشريط المتحرك
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">العروض — عربي</label>
                        <textarea name="announcements_ar" class="settings-textarea"
                                  placeholder="كل عرض في سطر:&#10;شحن مجاني للطلبات فوق 500 جنيه&#10;تغليف فاخر مجاناً مع كل طلب"><?= sv($s, 'announcements_ar') ?></textarea>
                        <div class="form-hint">كل سطر = عرض واحد يتحرك بالشريط</div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">العروض — English</label>
                        <textarea name="announcements_en" class="settings-textarea" dir="ltr"
                                  placeholder="One offer per line:&#10;Free Delivery on orders above 500 EGP"><?= sv($s, 'announcements_en') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <h3><i class="fas fa-star"></i> البانر الرئيسي (Hero)</h3>

                <div class="form-group">
                    <label class="settings-label">صورة البانر</label>
                    <div class="img-upload-box">
                        <?php if (!empty($s['hero_image'])): ?>
                            <img src="<?= UPLOAD_URL . sv($s, 'hero_image') ?>" alt="صورة البانر الحالية" class="current-img" style="max-height:110px;">
                        <?php else: ?>
                            <div class="no-img">لا توجد صورة — يظهر التصميم الزخرفي الافتراضي</div>
                        <?php endif; ?>
                        <input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($s['hero_image'])): ?>
                        <label class="remove-check">
                            <input type="checkbox" name="remove_hero_image"> حذف الصورة الحالية
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="form-hint">صورة عمودية (مثلاً 800×1000) — تظهر في البانر بإطار قوس فاخر مع أنيميشن حركة وتقريب ناعم</div>
                </div>

                <div class="lang-pair">
                    <div class="lang-pair-title">🇪🇬 النسخة العربية</div>
                    <div class="form-group">
                        <label class="settings-label">الشارة فوق العنوان</label>
                        <input type="text" name="hero_badge_ar" class="settings-input"
                               value="<?= sv($s, 'hero_badge_ar') ?>" placeholder="العلامة الأولى للهدايا العطرية في مصر">
                    </div>
                    <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;">
                        <div class="form-group">
                            <label class="settings-label">العنوان — سطر 1</label>
                            <input type="text" name="hero_title_1_ar" class="settings-input"
                                   value="<?= sv($s, 'hero_title_1_ar') ?>" placeholder="اجعل كل">
                        </div>
                        <div class="form-group">
                            <label class="settings-label">الكلمة الذهبية ✨</label>
                            <input type="text" name="hero_title_gold_ar" class="settings-input"
                                   value="<?= sv($s, 'hero_title_gold_ar') ?>" placeholder="مناسبة">
                        </div>
                        <div class="form-group">
                            <label class="settings-label">العنوان — سطر 2</label>
                            <input type="text" name="hero_title_2_ar" class="settings-input"
                                   value="<?= sv($s, 'hero_title_2_ar') ?>" placeholder="لا تُنسى">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">الوصف</label>
                        <textarea name="hero_desc_ar" class="settings-textarea" style="min-height:70px;"
                                  placeholder="هدايا عطرية فاخرة مصنوعة بعناية..."><?= sv($s, 'hero_desc_ar') ?></textarea>
                    </div>
                </div>

                <div class="lang-pair" style="margin-bottom:0;">
                    <div class="lang-pair-title">🇬🇧 English Version</div>
                    <div class="form-group">
                        <label class="settings-label">Badge above title</label>
                        <input type="text" name="hero_badge_en" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'hero_badge_en') ?>" placeholder="Egypt's #1 Perfume Gift Brand">
                    </div>
                    <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;">
                        <div class="form-group">
                            <label class="settings-label">Title — line 1</label>
                            <input type="text" name="hero_title_1_en" class="settings-input" dir="ltr"
                                   value="<?= sv($s, 'hero_title_1_en') ?>" placeholder="Make Every">
                        </div>
                        <div class="form-group">
                            <label class="settings-label">Gold word ✨</label>
                            <input type="text" name="hero_title_gold_en" class="settings-input" dir="ltr"
                                   value="<?= sv($s, 'hero_title_gold_en') ?>" placeholder="Occasion">
                        </div>
                        <div class="form-group">
                            <label class="settings-label">Title — line 2</label>
                            <input type="text" name="hero_title_2_en" class="settings-input" dir="ltr"
                                   value="<?= sv($s, 'hero_title_2_en') ?>" placeholder="Unforgettable">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="settings-label">Description</label>
                        <textarea name="hero_desc_en" class="settings-textarea" dir="ltr" style="min-height:70px;"
                                  placeholder="Handcrafted premium perfume gifts..."><?= sv($s, 'hero_desc_en') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- ════ TAB 5: Footer ════ -->
        <div class="stab-panel" data-panel="footer">

            <div class="settings-card">
                <h3><i class="fas fa-align-right"></i> نصوص الفوتر</h3>
                <div class="form-group">
                    <label class="settings-label">وصف الفوتر — عربي</label>
                    <textarea name="footer_description_ar" class="settings-textarea"
                              placeholder="هدايا عطرية فاخرة مصنوعة بعناية لكل مناسبة..."><?= sv($s, 'footer_description_ar') ?></textarea>
                    <div class="form-hint">يظهر تحت اللوجو في أسفل كل صفحة</div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="settings-label">وصف الفوتر — English</label>
                    <textarea name="footer_description_en" class="settings-textarea"
                              placeholder="Footer text — English" dir="ltr"><?= sv($s, 'footer_description_en') ?></textarea>
                </div>
            </div>

        </div>

        <!-- ════ TAB 6: Delivery & Currency ════ -->
        <div class="stab-panel" data-panel="delivery">

            <div class="settings-card">
                <h3><i class="fas fa-truck"></i> التوصيل والعملة</h3>
                <div class="form-group">
                    <label class="settings-label">مناطق التوصيل</label>
                    <textarea name="delivery_areas" class="settings-textarea" style="min-height:120px;"
                              placeholder="افصل المناطق بفاصلة:&#10;New Cairo, Heliopolis, Nasr City"><?= sv($s, 'delivery_areas') ?></textarea>
                    <div class="form-hint">افصل بين المناطق بفاصلة ( , ) — تظهر في فورم الطلب وصفحة التواصل والفوتر</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="settings-label">رمز العملة</label>
                        <input type="text" name="currency" class="settings-input" dir="ltr"
                               value="<?= sv($s, 'currency', 'EGP') ?>"
                               placeholder="EGP">
                        <div class="form-hint">يظهر بجانب الأسعار في كل الموقع</div>
                    </div>
                    <div class="form-group">
                        <label class="settings-label">اللغة الافتراضية</label>
                        <select name="default_language" class="settings-select">
                            <option value="ar" <?= (($s['default_language'] ?? 'ar') === 'ar') ? 'selected' : '' ?>>العربية</option>
                            <option value="en" <?= (($s['default_language'] ?? 'ar') === 'en') ? 'selected' : '' ?>>English</option>
                        </select>
                        <div class="form-hint">اللغة عند فتح الموقع لأول مرة</div>
                    </div>
                </div>
            </div>

        </div>

    </div>
    <!-- /LEFT -->

    <!-- RIGHT: Sidebar -->
    <div>
        <div class="sidebar-card">
            <h4><i class="fas fa-floppy-disk" style="color:#C9A96E;margin-left:6px;"></i> حفظ الإعدادات</h4>
            <button type="submit" class="btn-save-settings">
                <i class="fas fa-floppy-disk"></i> حفظ جميع الإعدادات
            </button>
            <div class="form-hint" style="margin-top:10px;text-align:center;">الحفظ يشمل كل التبويبات مرة واحدة</div>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #F0EBE3;">
                <div style="font-size:0.8rem;font-weight:600;color:#6B4F3A;margin-bottom:12px;">
                    <i class="fas fa-circle-info" style="color:#C9A96E;"></i> تلميحات
                </div>
                <div class="tip-item">
                    <i class="fas fa-image"></i>
                    <span>ارفع لوجو PNG بخلفية شفافة ليظهر بشكل احترافي في الهيدر</span>
                </div>
                <div class="tip-item">
                    <i class="fab fa-whatsapp"></i>
                    <span>رقم الواتساب يستخدم في جميع أزرار التواصل بالموقع</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-house"></i>
                    <span>نصوص البانر الرئيسي والشريط المتحرك تتحكم بأول ما يشوفه الزائر</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-truck"></i>
                    <span>مناطق التوصيل تظهر في فورم الطلب المخصص وصفحة التواصل</span>
                </div>
            </div>
        </div>

        <!-- Current values preview -->
        <div class="sidebar-card" style="margin-top:16px;">
            <h4 style="margin-bottom:14px;"><i class="fas fa-eye" style="color:#C9A96E;margin-left:6px;"></i> الإعدادات الحالية</h4>
            <div style="font-size:0.8rem;color:#6B4F3A;line-height:1.8;">
                <?php if (!empty($s['site_logo'])): ?>
                <div>🖼️ لوجو مرفوع</div>
                <?php endif; ?>
                <?php if (!empty($s['whatsapp_number'])): ?>
                <div>📱 <?= sv($s, 'whatsapp_number') ?></div>
                <?php endif; ?>
                <?php if (!empty($s['email'])): ?>
                <div>✉️ <?= sv($s, 'email') ?></div>
                <?php endif; ?>
                <?php if (!empty($s['instagram_url'])): ?>
                <div>📸 Instagram مربوط</div>
                <?php endif; ?>
                <?php if (!empty($s['currency'])): ?>
                <div>💰 عملة: <?= sv($s, 'currency', 'EGP') ?></div>
                <?php endif; ?>
                <?php if (empty($s['whatsapp_number']) && empty($s['email'])): ?>
                <div style="color:#9E8877;font-style:italic;">لا توجد إعدادات محفوظة بعد</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- /RIGHT -->

</div>
</form>

<script>
(function () {
    'use strict';
    var btns   = document.querySelectorAll('.stab-btn');
    var panels = document.querySelectorAll('.stab-panel');

    function openTab(name) {
        btns.forEach(function (b)  { b.classList.toggle('active', b.dataset.tab === name); });
        panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === name); });
        try { localStorage.setItem('tithkar_settings_tab', name); } catch (e) {}
    }

    btns.forEach(function (b) {
        b.addEventListener('click', function () { openTab(b.dataset.tab); });
    });

    /* Restore last open tab (survives save/reload) */
    try {
        var saved = localStorage.getItem('tithkar_settings_tab');
        if (saved && document.querySelector('.stab-btn[data-tab="' + saved + '"]')) {
            openTab(saved);
        }
    } catch (e) {}
}());
</script>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
