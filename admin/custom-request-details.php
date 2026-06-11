<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── Fetch request ────────────────────────────────────────────────────
$req_id = (int)($_GET['id'] ?? 0);
if (!$req_id) { header('Location: custom-requests.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM custom_requests WHERE id = ? LIMIT 1');
$stmt->execute([$req_id]);
$request = $stmt->fetch();
if (!$request) { header('Location: custom-requests.php'); exit; }

// ── POST: Update status + admin notes ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validStatuses = ['new','contacted','quotation_sent','confirmed','in_preparation','delivered','cancelled'];
    $newStatus     = in_array($_POST['status'] ?? '', $validStatuses) ? $_POST['status'] : $request['status'];
    $adminNotes    = trim($_POST['admin_notes'] ?? '');

    $pdo->prepare("UPDATE custom_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$newStatus, $adminNotes ?: null, $req_id]);
    header('Location: custom-request-details.php?id=' . $req_id . '&msg=saved');
    exit;
}

$flashMsg = isset($_GET['msg']) && $_GET['msg'] === 'saved' ? 'تم حفظ التغييرات بنجاح' : '';

// ── Helpers ──────────────────────────────────────────────────────────
$statusLabels = [
    'new'            => ['badge-new',            'جديد'],
    'contacted'      => ['badge-contacted',       'تم التواصل'],
    'quotation_sent' => ['badge-quotation_sent',  'تم إرسال السعر'],
    'confirmed'      => ['badge-confirmed',       'مؤكد'],
    'in_preparation' => ['badge-in_preparation',  'قيد التجهيز'],
    'delivered'      => ['badge-delivered',       'تم التسليم'],
    'cancelled'      => ['badge-cancelled',       'ملغي'],
];
$eventLabels = [
    'wedding'    => 'أفراح',
    'engagement' => 'خطوبات',
    'birthday'   => 'أعياد ميلاد',
    'corporate'  => 'هدايا شركات',
    'graduation' => 'تخرج',
    'baby_shower'=> 'استقبال مولود',
    'other'      => 'أخرى',
];
$styleLabels = [
    'luxury'    => 'فاخر',
    'romantic'  => 'رومانسي',
    'minimal'   => 'مينيمال',
    'classic'   => 'كلاسيك',
    'colorful'  => 'ملون',
    'arabic'    => 'عربي',
];
function crdBadge(string $s, array $map): string {
    [$cls, $lbl] = $map[$s] ?? ['badge-inactive', htmlspecialchars($s, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $lbl . '</span>';
}

// ── WhatsApp link to customer ────────────────────────────────────────
$customerPhone = preg_replace('/\D/', '', $request['phone']);
if (substr($customerPhone, 0, 1) === '0') { $customerPhone = '20' . substr($customerPhone, 1); }
$eventLabel = $eventLabels[$request['event_type']] ?? $request['event_type'];
$waMsg  = 'مرحبًا ' . $request['customer_name'] . '، بخصوص طلبك المخصص لـ' . $eventLabel;
if ($request['event_date'])  { $waMsg .= ' بتاريخ ' . $request['event_date']; }
if ($request['quantity'])    { $waMsg .= '، الكمية: ' . $request['quantity']; }
$waMsg .= '. نتواصل معك لمزيد من التفاصيل والتسعير.';
$waLink = 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode($waMsg);

$pageTitle = 'طلب مخصص #' . $req_id;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>طلب مخصص #<?= $req_id ?> — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.details-layout{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;}
.info-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:20px;}
.info-card h3{font-size:0.95rem;font-weight:600;color:#2C1F14;margin:0 0 16px;
              padding-bottom:12px;border-bottom:1px solid #F0EBE3;display:flex;align-items:center;gap:8px;}
.info-card h3 i{color:#C9A96E;}
.info-row{display:flex;padding:8px 0;border-bottom:1px solid #FAF7F2;font-size:0.875rem;}
.info-row:last-child{border-bottom:none;}
.info-label{width:160px;flex-shrink:0;color:#6B4F3A;font-weight:500;}
.info-value{color:#2C1F14;flex:1;}
.action-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);position:sticky;top:20px;}
.action-card h3{font-size:0.95rem;font-weight:600;color:#2C1F14;margin:0 0 18px;}
.action-card select,.action-card textarea{
    width:100%;padding:10px 14px;border:1px solid rgba(201,169,110,.35);
    border-radius:8px;font-family:'Tajawal',sans-serif;font-size:0.9rem;
    color:#2C1F14;margin-bottom:14px;background:#fff;}
.action-card select:focus,.action-card textarea:focus{
    outline:none;border-color:#C9A96E;box-shadow:0 0 0 3px rgba(201,169,110,.12);}
.action-card textarea{resize:vertical;min-height:100px;}
.btn-full{width:100%;padding:12px;border:none;border-radius:8px;font-family:'Tajawal',sans-serif;
          font-size:0.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;
          justify-content:center;gap:8px;text-decoration:none;margin-bottom:8px;}
.btn-save-action{background:#C9A96E;color:#fff;transition:background .2s;}
.btn-save-action:hover{background:#b8945a;}
.btn-call{background:#E8F5E9;color:#2E7D32;border:1px solid #C8E6C9;}
.btn-wa{background:#25D366;color:#fff;}
.ref-img{max-width:100%;border-radius:8px;border:1px solid #F0EBE3;margin-top:10px;}
@media(max-width:900px){.details-layout{grid-template-columns:1fr;}}
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
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <h1 class="admin-page-title">طلب مخصص #<?= $req_id ?></h1>
            <?= crdBadge($request['status'], $statusLabels) ?>
        </div>
        <div class="admin-page-subtitle">
            <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
            — <?= $eventLabel ?>
        </div>
    </div>
    <a href="custom-requests.php" style="color:#6B4F3A;text-decoration:none;font-size:0.9rem;
                                          display:flex;align-items:center;gap:6px;">
        <i class="fas fa-arrow-right"></i> العودة للطلبات
    </a>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-success" style="margin-bottom:20px;">
    <i class="fas fa-circle-check"></i> <?= htmlspecialchars($flashMsg, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<div class="details-layout">

    <!-- LEFT: Full request details -->
    <div>

        <!-- Customer info -->
        <div class="info-card">
            <h3><i class="fas fa-user"></i> بيانات العميل</h3>
            <div class="info-row"><span class="info-label">الاسم</span>
                <span class="info-value"><?= htmlspecialchars($request['customer_name'], ENT_QUOTES) ?></span></div>
            <div class="info-row"><span class="info-label">الهاتف</span>
                <span class="info-value">
                    <a href="tel:<?= htmlspecialchars($request['phone'], ENT_QUOTES) ?>"
                       style="color:#C9A96E;text-decoration:none;">
                        <?= htmlspecialchars($request['phone'], ENT_QUOTES) ?>
                    </a>
                </span></div>
            <?php if ($request['email']): ?>
            <div class="info-row"><span class="info-label">البريد</span>
                <span class="info-value"><?= htmlspecialchars($request['email'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['city']): ?>
            <div class="info-row"><span class="info-label">المدينة / المنطقة</span>
                <span class="info-value">
                    <?= htmlspecialchars($request['city'], ENT_QUOTES) ?>
                    <?php if ($request['area']): ?>— <?= htmlspecialchars($request['area'], ENT_QUOTES) ?><?php endif; ?>
                </span></div>
            <?php endif; ?>
        </div>

        <!-- Event details -->
        <div class="info-card">
            <h3><i class="fas fa-calendar-star"></i> تفاصيل المناسبة</h3>
            <div class="info-row"><span class="info-label">نوع المناسبة</span>
                <span class="info-value"><?= $eventLabel ?></span></div>
            <?php if ($request['event_date']): ?>
            <div class="info-row"><span class="info-label">تاريخ المناسبة</span>
                <span class="info-value"><?= htmlspecialchars($request['event_date'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['quantity']): ?>
            <div class="info-row"><span class="info-label">الكمية</span>
                <span class="info-value"><?= (int)$request['quantity'] ?> قطعة</span></div>
            <?php endif; ?>
            <?php if ($request['approximate_budget']): ?>
            <div class="info-row"><span class="info-label">الميزانية التقريبية</span>
                <span class="info-value"><?= htmlspecialchars($request['approximate_budget'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Design preferences -->
        <div class="info-card">
            <h3><i class="fas fa-palette"></i> تفاصيل التصميم</h3>
            <?php if ($request['preferred_style']): ?>
            <div class="info-row"><span class="info-label">الستايل المفضل</span>
                <span class="info-value"><?= $styleLabels[$request['preferred_style']] ?? htmlspecialchars($request['preferred_style'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['perfume_preference']): ?>
            <div class="info-row"><span class="info-label">تفضيل العطر</span>
                <span class="info-value"><?= htmlspecialchars($request['perfume_preference'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['bottle_preference']): ?>
            <div class="info-row"><span class="info-label">تفضيل الزجاجة</span>
                <span class="info-value"><?= htmlspecialchars($request['bottle_preference'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['packaging_preference']): ?>
            <div class="info-row"><span class="info-label">التغليف</span>
                <span class="info-value"><?= htmlspecialchars($request['packaging_preference'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">كرت مخصص</span>
                <span class="info-value"><?= $request['needs_custom_card'] ? '✅ نعم' : '—' ?></span></div>
            <?php if ($request['needs_custom_card'] && $request['card_names']): ?>
            <div class="info-row"><span class="info-label">الأسماء على الكرت</span>
                <span class="info-value"><?= htmlspecialchars($request['card_names'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($request['card_date']): ?>
            <div class="info-row"><span class="info-label">التاريخ على الكرت</span>
                <span class="info-value"><?= htmlspecialchars($request['card_date'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">تغليف خاص</span>
                <span class="info-value"><?= $request['needs_packaging'] ? '✅ نعم' : '—' ?></span></div>
        </div>

        <!-- Notes -->
        <?php if ($request['notes'] || $request['admin_notes']): ?>
        <div class="info-card">
            <h3><i class="fas fa-note-sticky"></i> الملاحظات</h3>
            <?php if ($request['notes']): ?>
            <div class="info-row"><span class="info-label">ملاحظات العميل</span>
                <span class="info-value" style="font-style:italic;">
                    <?= htmlspecialchars($request['notes'], ENT_QUOTES) ?>
                </span></div>
            <?php endif; ?>
            <?php if ($request['admin_notes']): ?>
            <div class="info-row"><span class="info-label">ملاحظات الإدارة</span>
                <span class="info-value"><?= htmlspecialchars($request['admin_notes'], ENT_QUOTES) ?></span></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Reference image -->
        <?php if ($request['uploaded_reference_image']): ?>
        <div class="info-card">
            <h3><i class="fas fa-image"></i> الصورة المرجعية</h3>
            <img src="<?= UPLOAD_URL . htmlspecialchars($request['uploaded_reference_image'], ENT_QUOTES) ?>"
                 alt="صورة مرجعية" class="ref-img">
        </div>
        <?php endif; ?>

    </div>
    <!-- /LEFT -->

    <!-- RIGHT: Actions -->
    <div>
        <div class="action-card">
            <h3>تحديث الطلب</h3>
            <form method="POST">
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.8rem;color:#6B4F3A;margin-bottom:6px;font-weight:500;">الحالة</label>
                    <select name="status">
                        <option value="new"            <?= $request['status'] === 'new'            ? 'selected' : '' ?>>جديد</option>
                        <option value="contacted"      <?= $request['status'] === 'contacted'      ? 'selected' : '' ?>>تم التواصل</option>
                        <option value="quotation_sent" <?= $request['status'] === 'quotation_sent' ? 'selected' : '' ?>>تم إرسال السعر</option>
                        <option value="confirmed"      <?= $request['status'] === 'confirmed'      ? 'selected' : '' ?>>مؤكد</option>
                        <option value="in_preparation" <?= $request['status'] === 'in_preparation' ? 'selected' : '' ?>>قيد التجهيز</option>
                        <option value="delivered"      <?= $request['status'] === 'delivered'      ? 'selected' : '' ?>>تم التسليم</option>
                        <option value="cancelled"      <?= $request['status'] === 'cancelled'      ? 'selected' : '' ?>>ملغي</option>
                    </select>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.8rem;color:#6B4F3A;margin-bottom:6px;font-weight:500;">ملاحظات الإدارة</label>
                    <textarea name="admin_notes" placeholder="ملاحظات داخلية، تسعير، تفاصيل التنفيذ..."><?= htmlspecialchars($request['admin_notes'] ?? '', ENT_QUOTES) ?></textarea>
                </div>
                <button type="submit" class="btn-full btn-save-action">
                    <i class="fas fa-floppy-disk"></i> حفظ التغييرات
                </button>
            </form>

            <a href="tel:<?= htmlspecialchars($request['phone'], ENT_QUOTES) ?>"
               class="btn-full btn-call">
                <i class="fas fa-phone"></i> اتصال بالعميل
            </a>
            <a href="<?= $waLink ?>" class="btn-full btn-wa" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-whatsapp"></i> تواصل عبر واتساب
            </a>
        </div>
    </div>
    <!-- /RIGHT -->

</div><!-- /details-layout -->

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
