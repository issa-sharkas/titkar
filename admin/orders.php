<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── POST: Status update ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)($_POST['order_id']     ?? 0);
    $newStatus = $_POST['order_status']        ?? '';
    $validStatuses = ['pending','confirmed','preparing','shipped','delivered','cancelled'];
    if ($orderId && in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")
            ->execute([$newStatus, $orderId]);
    }
    header('Location: orders.php?msg=' . urlencode('تم تحديث حالة الطلب') . '&type=success'
           . ($newStatus ? '&status=' . $newStatus : ''));
    exit;
}

// ── Counts per status (for tab badges) ──────────────────────────────
$countRows = $pdo->query("SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status")->fetchAll();
$counts    = [];
foreach ($countRows as $r) { $counts[$r['order_status']] = (int)$r['c']; }
$totalCount = array_sum($counts);

// ── Filters ──────────────────────────────────────────────────────────
$validStatuses  = ['pending','confirmed','preparing','shipped','delivered','cancelled'];
$statusFilter   = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';
$search         = sanitize($_GET['search'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$where  = [];
$params = [];
if ($statusFilter) { $where[] = 'order_status = ?';              $params[] = $statusFilter; }
if ($search)       { $where[] = '(order_number LIKE ? OR customer_name LIKE ?)';
                     $like = '%' . $search . '%'; $params[] = $like; $params[] = $like; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders $whereSql");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$ordersStmt = $pdo->prepare("SELECT * FROM orders $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$ordersStmt->execute($listParams);
$orders = $ordersStmt->fetchAll();

// ── Helpers ──────────────────────────────────────────────────────────
$statusLabels = [
    'pending'   => ['badge-pending',   'معلق'],
    'confirmed' => ['badge-confirmed', 'مؤكد'],
    'preparing' => ['badge-preparing', 'قيد التجهيز'],
    'shipped'   => ['badge-shipped',   'مشحون'],
    'delivered' => ['badge-delivered', 'تم التسليم'],
    'cancelled' => ['badge-cancelled', 'ملغي'],
];
$paymentLabels = [
    'cash_on_delivery' => 'كاش عند الاستلام',
    'bank_transfer'    => 'تحويل بنكي',
    'instapay'         => 'InstaPay',
];
$statusTabs = [
    ''          => ['الكل',           $totalCount],
    'pending'   => ['معلق',           $counts['pending']   ?? 0],
    'confirmed' => ['مؤكد',           $counts['confirmed'] ?? 0],
    'preparing' => ['قيد التجهيز',   $counts['preparing'] ?? 0],
    'shipped'   => ['مشحون',          $counts['shipped']   ?? 0],
    'delivered' => ['تم التسليم',     $counts['delivered'] ?? 0],
    'cancelled' => ['ملغي',           $counts['cancelled'] ?? 0],
];
function osBadge(string $s, array $map): string {
    [$cls, $lbl] = $map[$s] ?? ['badge-inactive', htmlspecialchars($s, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $lbl . '</span>';
}

// Flash
$flashMsg  = '';
$flashType = '';
if (!empty($_GET['msg'])) {
    $flashMsg  = htmlspecialchars($_GET['msg'], ENT_QUOTES);
    $flashType = in_array($_GET['type'] ?? '', ['success','error']) ? $_GET['type'] : 'info';
}

$pageTitle = 'الطلبات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الطلبات — تذكار Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
<style>
.status-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;}
.status-tab{padding:7px 14px;border-radius:8px;text-decoration:none;font-size:0.85rem;color:#6B4F3A;
            background:#fff;border:1px solid #F0EBE3;transition:all .2s;white-space:nowrap;}
.status-tab:hover{border-color:#C9A96E;color:#C9A96E;}
.status-tab.active{background:#C9A96E;color:#fff;border-color:#C9A96E;}
.tab-count{font-size:0.75rem;opacity:.8;background:rgba(255,255,255,.25);
           padding:1px 6px;border-radius:10px;margin-right:4px;}
.status-tab:not(.active) .tab-count{background:rgba(0,0,0,.07);color:#6B4F3A;}
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
        <h1 class="admin-page-title">الطلبات</h1>
        <div class="admin-page-subtitle"><?= $total ?> طلب</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-<?= $flashType ?>" style="margin-bottom:20px;">
    <i class="fas fa-circle-check"></i> <?= $flashMsg ?>
</div>
<?php endif; ?>

<!-- Status tabs -->
<div class="status-tabs">
    <?php foreach ($statusTabs as $val => [$label, $cnt]):
        $href = 'orders.php?status=' . $val . ($search ? '&search=' . urlencode($search) : '');
    ?>
    <a href="<?= $href ?>" class="status-tab <?= $statusFilter === $val ? 'active' : '' ?>">
        <?= $label ?>
        <span class="tab-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search -->
<form method="GET" class="admin-filter-bar">
    <?php if ($statusFilter): ?>
    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter, ENT_QUOTES) ?>">
    <?php endif; ?>
    <input type="search" name="search" placeholder="رقم الطلب أو اسم العميل..."
           value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <button type="submit"
            style="padding:9px 18px;background:#C9A96E;color:#fff;border:none;
                   border-radius:8px;font-family:inherit;font-size:0.9rem;cursor:pointer;">
        <i class="fas fa-magnifying-glass"></i> بحث
    </button>
    <?php if ($search): ?>
    <a href="orders.php<?= $statusFilter ? '?status=' . $statusFilter : '' ?>"
       style="color:#9E8877;font-size:0.875rem;text-decoration:none;">
        <i class="fas fa-xmark"></i> مسح
    </a>
    <?php endif; ?>
</form>

<!-- Orders table -->
<div class="admin-table-wrap">
    <table>
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>المجموع</th>
                <th>الدفع</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="text-align:center;color:#9E8877;padding:40px;">
                لا توجد طلبات
            </td></tr>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
            <tr>
                <td>
                    <a href="order-details.php?id=<?= (int)$ord['id'] ?>"
                       style="font-family:monospace;font-size:0.85rem;font-weight:600;
                              color:#C9A96E;text-decoration:none;">
                        <?= htmlspecialchars($ord['order_number'], ENT_QUOTES) ?>
                    </a>
                </td>
                <td style="font-weight:500;"><?= htmlspecialchars($ord['customer_name'], ENT_QUOTES) ?></td>
                <td>
                    <a href="tel:<?= htmlspecialchars($ord['phone'], ENT_QUOTES) ?>"
                       style="color:#2C1F14;text-decoration:none;font-size:0.875rem;">
                        <?= htmlspecialchars($ord['phone'], ENT_QUOTES) ?>
                    </a>
                </td>
                <td style="color:#C9A96E;font-weight:600;white-space:nowrap;">
                    <?= number_format((float)$ord['total'], 0) ?> EGP
                </td>
                <td style="font-size:0.82rem;color:#6B4F3A;">
                    <?= $paymentLabels[$ord['payment_method']] ?? htmlspecialchars($ord['payment_method'], ENT_QUOTES) ?>
                </td>
                <td><?= osBadge($ord['order_status'], $statusLabels) ?></td>
                <td style="font-size:0.82rem;color:#9E8877;white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($ord['created_at'])) ?>
                </td>
                <td>
                    <a href="order-details.php?id=<?= (int)$ord['id'] ?>"
                       class="btn-icon btn-icon-view" title="عرض التفاصيل">
                        <i class="fas fa-eye"></i>
                    </a>
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
    <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
        <?php else: ?>
        <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
