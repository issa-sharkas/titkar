<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── Stats ───────────────────────────────────────────────────────────
$total_products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_orders   = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$new_requests   = (int) $pdo->query("SELECT COUNT(*) FROM custom_requests WHERE status = 'new'")->fetchColumn();
$pending_orders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();

// ── Recent data ─────────────────────────────────────────────────────
$recent_orders   = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recent_requests = $pdo->query('SELECT * FROM custom_requests ORDER BY created_at DESC LIMIT 5')->fetchAll();

// ── Helpers ─────────────────────────────────────────────────────────
$orderStatusLabels = [
    'pending'   => ['badge-pending',   'معلق'],
    'confirmed' => ['badge-confirmed', 'مؤكد'],
    'preparing' => ['badge-preparing', 'جاري التجهيز'],
    'shipped'   => ['badge-shipped',   'تم الشحن'],
    'delivered' => ['badge-delivered', 'تم التسليم'],
    'cancelled' => ['badge-cancelled', 'ملغي'],
];
$requestStatusLabels = [
    'new'            => ['badge-new',            'جديد'],
    'contacted'      => ['badge-contacted',       'تم التواصل'],
    'quotation_sent' => ['badge-quotation_sent',  'تم إرسال السعر'],
    'confirmed'      => ['badge-confirmed',       'مؤكد'],
    'in_preparation' => ['badge-in_preparation',  'جاري التجهيز'],
    'delivered'      => ['badge-delivered',       'تم التسليم'],
    'cancelled'      => ['badge-cancelled',       'ملغي'],
];
$eventLabels = [
    'wedding'    => 'أفراح',    'engagement'  => 'خطوبات',
    'birthday'   => 'أعياد ميلاد', 'corporate' => 'هدايا شركات',
    'graduation' => 'تخرج',    'baby_shower' => 'استقبال مولود',
    'other'      => 'أخرى',
];
function adminBadge(string $status, array $map): string {
    [$cls, $label] = $map[$status] ?? ['badge-inactive', htmlspecialchars($status, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

$pageTitle = 'لوحة التحكم';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.stat-card.info::before    { background:#1565C0; }
.stat-card.info .stat-number { color:#1565C0; }
.stat-card.warning::before  { background:#E65100; }
.stat-card.warning .stat-number { color:#E65100; }
.dash-tables { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media(max-width:1100px){ .dash-tables{ grid-template-columns:1fr; } }
</style>
</head>
<body class="admin-body">
<div class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>

<!-- Stats -->
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box-open"></i></div>
        <div class="stat-number"><?= $total_products ?></div>
        <div class="stat-label">إجمالي المنتجات</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-number"><?= $total_orders ?></div>
        <div class="stat-label">إجمالي الطلبات</div>
    </div>

    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-number"><?= $new_requests ?></div>
        <div class="stat-label">طلبات مخصصة جديدة</div>
    </div>

    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-number"><?= $pending_orders ?></div>
        <div class="stat-label">طلبات معلقة</div>
    </div>

</div>

<!-- Recent tables -->
<div class="dash-tables">

    <!-- Recent Orders -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="font-size:1rem;font-weight:600;color:#2C1F14;margin:0;">
                <i class="fas fa-shopping-bag" style="color:#C9A96E;margin-left:8px;"></i>آخر الطلبات
            </h3>
            <a href="orders.php" style="font-size:0.8rem;color:#C9A96E;text-decoration:none;">
                عرض الكل <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <div class="admin-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>المجموع</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#9E8877;padding:24px;">لا توجد طلبات بعد</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $ord): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:0.85rem;font-weight:500;">
                            <?= htmlspecialchars($ord['order_number'], ENT_QUOTES) ?>
                        </td>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($ord['customer_name'], ENT_QUOTES) ?></div>
                            <div style="font-size:0.78rem;color:#9E8877;"><?= htmlspecialchars($ord['phone'], ENT_QUOTES) ?></div>
                        </td>
                        <td style="color:#C9A96E;font-weight:600;">
                            <?= number_format((float)$ord['total'], 0) ?> EGP
                        </td>
                        <td><?= adminBadge($ord['order_status'], $orderStatusLabels) ?></td>
                        <td>
                            <a href="order-details.php?id=<?= (int)$ord['id'] ?>"
                               class="btn-icon btn-icon-view" title="عرض">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Custom Requests -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="font-size:1rem;font-weight:600;color:#2C1F14;margin:0;">
                <i class="fas fa-star" style="color:#C9A96E;margin-left:8px;"></i>آخر الطلبات المخصصة
            </h3>
            <a href="custom-requests.php" style="font-size:0.8rem;color:#C9A96E;text-decoration:none;">
                عرض الكل <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <div class="admin-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>المناسبة</th>
                        <th>الكمية</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent_requests)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#9E8877;padding:24px;">لا توجد طلبات مخصصة بعد</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_requests as $req): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($req['customer_name'], ENT_QUOTES) ?></div>
                            <div style="font-size:0.78rem;color:#9E8877;"><?= htmlspecialchars($req['phone'], ENT_QUOTES) ?></div>
                        </td>
                        <td><?= $eventLabels[$req['event_type']] ?? htmlspecialchars($req['event_type'], ENT_QUOTES) ?></td>
                        <td><?= $req['quantity'] ? (int)$req['quantity'] : '—' ?></td>
                        <td><?= adminBadge($req['status'], $requestStatusLabels) ?></td>
                        <td>
                            <a href="custom-request-details.php?id=<?= (int)$req['id'] ?>"
                               class="btn-icon btn-icon-view" title="عرض">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /dash-tables -->

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
