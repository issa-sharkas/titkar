<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── POST: Quick status update ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId     = (int)($_POST['request_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['new','contacted','quotation_sent','confirmed','in_preparation','delivered','cancelled'];
    if ($reqId && in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE custom_requests SET status = ? WHERE id = ?")
            ->execute([$newStatus, $reqId]);
    }
    header('Location: custom-requests.php?msg=' . urlencode('تم تحديث الحالة'));
    exit;
}

// ── Counts per status ────────────────────────────────────────────────
$countRows = $pdo->query("SELECT status, COUNT(*) AS c FROM custom_requests GROUP BY status")->fetchAll();
$counts    = [];
foreach ($countRows as $r) { $counts[$r['status']] = (int)$r['c']; }
$totalCount = array_sum($counts);

// ── Filters ──────────────────────────────────────────────────────────
$validStatuses = ['new','contacted','quotation_sent','confirmed','in_preparation','delivered','cancelled'];
$statusFilter  = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : '';
$search        = sanitize($_GET['search'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 20;

$where  = [];
$params = [];
if ($statusFilter) { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($search) {
    $where[] = '(customer_name LIKE ? OR phone LIKE ?)';
    $like = '%' . $search . '%'; $params[] = $like; $params[] = $like;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM custom_requests $whereSql");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$reqStmt    = $pdo->prepare("SELECT * FROM custom_requests $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$reqStmt->execute($listParams);
$requests   = $reqStmt->fetchAll();

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
$statusTabs = [
    ''               => ['الكل',               $totalCount],
    'new'            => ['جديد',               $counts['new']            ?? 0],
    'contacted'      => ['تم التواصل',         $counts['contacted']      ?? 0],
    'quotation_sent' => ['تم إرسال السعر',     $counts['quotation_sent'] ?? 0],
    'confirmed'      => ['مؤكد',               $counts['confirmed']      ?? 0],
    'in_preparation' => ['قيد التجهيز',        $counts['in_preparation'] ?? 0],
    'delivered'      => ['تم التسليم',         $counts['delivered']      ?? 0],
    'cancelled'      => ['ملغي',               $counts['cancelled']      ?? 0],
];
function crBadge(string $s, array $map): string {
    [$cls, $lbl] = $map[$s] ?? ['badge-inactive', htmlspecialchars($s, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $lbl . '</span>';
}

$flashMsg = '';
if (!empty($_GET['msg'])) { $flashMsg = htmlspecialchars($_GET['msg'], ENT_QUOTES); }

$pageTitle = 'الطلبات المخصصة';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الطلبات المخصصة — تذكار Admin</title>
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
.tab-count{font-size:0.75rem;padding:1px 6px;border-radius:10px;margin-right:4px;
           background:rgba(0,0,0,.07);}
.status-tab.active .tab-count{background:rgba(255,255,255,.25);}
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
        <h1 class="admin-page-title">الطلبات المخصصة</h1>
        <div class="admin-page-subtitle"><?= $total ?> طلب</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flashMsg): ?>
<div class="admin-alert admin-alert-success" style="margin-bottom:20px;">
    <i class="fas fa-circle-check"></i> <?= $flashMsg ?>
</div>
<?php endif; ?>

<!-- Status tabs -->
<div class="status-tabs">
    <?php foreach ($statusTabs as $val => [$label, $cnt]):
        $href = 'custom-requests.php?status=' . $val . ($search ? '&search=' . urlencode($search) : '');
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
    <input type="search" name="search" placeholder="اسم العميل أو الهاتف..."
           value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <button type="submit"
            style="padding:9px 18px;background:#C9A96E;color:#fff;border:none;
                   border-radius:8px;font-family:inherit;font-size:0.9rem;cursor:pointer;">
        <i class="fas fa-magnifying-glass"></i> بحث
    </button>
    <?php if ($search): ?>
    <a href="custom-requests.php<?= $statusFilter ? '?status=' . $statusFilter : '' ?>"
       style="color:#9E8877;font-size:0.875rem;text-decoration:none;">
        <i class="fas fa-xmark"></i> مسح
    </a>
    <?php endif; ?>
</form>

<!-- Requests table -->
<div class="admin-table-wrap">
    <table>
        <thead>
            <tr>
                <th>الاسم</th>
                <th>الهاتف</th>
                <th>المناسبة</th>
                <th>الكمية</th>
                <th>المدينة</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requests)): ?>
            <tr><td colspan="8" style="text-align:center;color:#9E8877;padding:40px;">
                لا توجد طلبات
            </td></tr>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td>
                    <div style="font-weight:500;"><?= htmlspecialchars($req['customer_name'], ENT_QUOTES) ?></div>
                </td>
                <td>
                    <a href="tel:<?= htmlspecialchars($req['phone'], ENT_QUOTES) ?>"
                       style="color:#2C1F14;text-decoration:none;font-size:0.875rem;">
                        <?= htmlspecialchars($req['phone'], ENT_QUOTES) ?>
                    </a>
                </td>
                <td><?= $eventLabels[$req['event_type']] ?? htmlspecialchars($req['event_type'], ENT_QUOTES) ?></td>
                <td><?= $req['quantity'] ? (int)$req['quantity'] : '—' ?></td>
                <td style="font-size:0.875rem;color:#6B4F3A;">
                    <?= $req['city'] ? htmlspecialchars($req['city'], ENT_QUOTES) : '—' ?>
                </td>
                <td><?= crBadge($req['status'], $statusLabels) ?></td>
                <td style="font-size:0.82rem;color:#9E8877;white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($req['created_at'])) ?>
                </td>
                <td>
                    <a href="custom-request-details.php?id=<?= (int)$req['id'] ?>"
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
