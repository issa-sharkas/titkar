<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// ── Fetch order ──────────────────────────────────────────────────────
$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) { header('Location: orders.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) { header('Location: orders.php'); exit; }

// ── POST: Update status / notes ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['order_status'] ?? $order['order_status'];
    $notes     = trim($_POST['notes']   ?? '');
    $validStatuses = ['pending','confirmed','preparing','shipped','delivered','cancelled'];
    if (!in_array($newStatus, $validStatuses)) { $newStatus = $order['order_status']; }

    $pdo->prepare("UPDATE orders SET order_status = ?, notes = ? WHERE id = ?")
        ->execute([$newStatus, $notes ?: null, $order_id]);
    header('Location: order-details.php?id=' . $order_id . '&msg=saved');
    exit;
}

$flashMsg = isset($_GET['msg']) && $_GET['msg'] === 'saved' ? 'تم حفظ التغييرات بنجاح' : '';

// ── Fetch order items ────────────────────────────────────────────────
$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll();

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
function odBadge(string $s, array $map): string {
    [$cls, $lbl] = $map[$s] ?? ['badge-inactive', htmlspecialchars($s, ENT_QUOTES)];
    return '<span class="badge ' . $cls . '">' . $lbl . '</span>';
}

// WhatsApp link to customer
$customerPhone = preg_replace('/\D/', '', $order['phone']);
if (substr($customerPhone, 0, 1) === '0') { $customerPhone = '20' . substr($customerPhone, 1); }
$waMsg     = 'مرحبًا ' . $order['customer_name'] . '، بخصوص طلبك رقم ' . $order['order_number'] . ' — نتواصل معك لمزيد من التفاصيل.';
$waCustomer = 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode($waMsg);

$pageTitle = 'تفاصيل الطلب';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>طلب <?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?> — تذكار Admin</title>
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
.info-label{width:140px;flex-shrink:0;color:#6B4F3A;font-weight:500;}
.info-value{color:#2C1F14;flex:1;}
.action-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.05);position:sticky;top:20px;}
.action-card h3{font-size:0.95rem;font-weight:600;color:#2C1F14;margin:0 0 18px;}
.action-card select,.action-card textarea{
    width:100%;padding:10px 14px;border:1px solid rgba(201,169,110,.35);
    border-radius:8px;font-family:'Tajawal',sans-serif;font-size:0.9rem;
    color:#2C1F14;margin-bottom:14px;background:#fff;}
.action-card select:focus,.action-card textarea:focus{
    outline:none;border-color:#C9A96E;box-shadow:0 0 0 3px rgba(201,169,110,.12);}
.action-card textarea{resize:vertical;min-height:90px;}
.btn-full{width:100%;padding:12px;border:none;border-radius:8px;
          font-family:'Tajawal',sans-serif;font-size:0.95rem;font-weight:700;cursor:pointer;
          display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;}
.btn-save-action{background:#C9A96E;color:#fff;transition:background .2s;}
.btn-save-action:hover{background:#b8945a;}
.btn-call{background:#E8F5E9;color:#2E7D32;margin-top:10px;border:1px solid #C8E6C9;}
.btn-wa{background:#25D366;color:#fff;margin-top:8px;}
.summary-row{display:flex;justify-content:space-between;padding:8px 0;font-size:0.9rem;}
.summary-row.total{font-weight:700;font-size:1rem;border-top:1px solid #F0EBE3;margin-top:4px;padding-top:12px;}
@media(max-width:900px){.details-layout{grid-template-columns:1fr;}}
</style>
</head>
<body class="admin-body">
<div class="admin-layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>

<!-- Header -->
<div class="admin-page-header">
    <div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <h1 class="admin-page-title"><?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?></h1>
            <?= odBadge($order['order_status'], $statusLabels) ?>
        </div>
        <div class="admin-page-subtitle">
            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
        </div>
    </div>
    <a href="orders.php" style="color:#6B4F3A;text-decoration:none;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
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

    <!-- LEFT -->
    <div>

        <!-- Customer Info -->
        <div class="info-card">
            <h3><i class="fas fa-user"></i> بيانات العميل</h3>
            <div class="info-row">
                <span class="info-label">الاسم</span>
                <span class="info-value"><?= htmlspecialchars($order['customer_name'], ENT_QUOTES) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الهاتف</span>
                <span class="info-value">
                    <a href="tel:<?= htmlspecialchars($order['phone'], ENT_QUOTES) ?>"
                       style="color:#C9A96E;text-decoration:none;">
                        <?= htmlspecialchars($order['phone'], ENT_QUOTES) ?>
                    </a>
                </span>
            </div>
            <?php if ($order['email']): ?>
            <div class="info-row">
                <span class="info-label">البريد</span>
                <span class="info-value"><?= htmlspecialchars($order['email'], ENT_QUOTES) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">المدينة / المنطقة</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['city'], ENT_QUOTES) ?>
                    <?php if ($order['area']): ?>— <?= htmlspecialchars($order['area'], ENT_QUOTES) ?><?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">العنوان</span>
                <span class="info-value"><?= htmlspecialchars($order['address'], ENT_QUOTES) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">طريقة الدفع</span>
                <span class="info-value">
                    <?= $paymentLabels[$order['payment_method']] ?? htmlspecialchars($order['payment_method'], ENT_QUOTES) ?>
                </span>
            </div>
            <?php if ($order['notes']): ?>
            <div class="info-row">
                <span class="info-label">ملاحظات</span>
                <span class="info-value" style="font-style:italic;color:#6B4F3A;">
                    <?= htmlspecialchars($order['notes'], ENT_QUOTES) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div class="info-card">
            <h3><i class="fas fa-box-open"></i> المنتجات المطلوبة</h3>
            <div class="admin-table-wrap" style="box-shadow:none;border-radius:8px;overflow:hidden;">
                <table>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style="text-align:center;">الكمية</th>
                            <th>السعر</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;">
                                <?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?>
                            </div>
                            <?php if ($item['customization_text']): ?>
                            <div style="font-size:0.78rem;color:#9E8877;margin-top:3px;">
                                <?= htmlspecialchars($item['customization_text'], ENT_QUOTES) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;"><?= (int)$item['quantity'] ?></td>
                        <td><?= number_format((float)$item['unit_price'], 0) ?> EGP</td>
                        <td style="font-weight:600;"><?= number_format((float)$item['total_price'], 0) ?> EGP</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Price summary -->
            <div style="max-width:300px;margin-top:16px;margin-right:auto;">
                <div class="summary-row">
                    <span style="color:#6B4F3A;">المجموع الجزئي</span>
                    <span><?= number_format((float)$order['subtotal'], 0) ?> EGP</span>
                </div>
                <div class="summary-row">
                    <span style="color:#6B4F3A;">الشحن</span>
                    <span><?= $order['shipping'] > 0 ? number_format((float)$order['shipping'], 0) . ' EGP' : 'مجاني' ?></span>
                </div>
                <div class="summary-row total">
                    <span>الإجمالي</span>
                    <span style="color:#C9A96E;"><?= number_format((float)$order['total'], 0) ?> EGP</span>
                </div>
            </div>
        </div>

    </div>
    <!-- /LEFT -->

    <!-- RIGHT: Actions -->
    <div>
        <div class="action-card">
            <h3>تحديث الطلب</h3>
            <form method="POST">
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.8rem;color:#6B4F3A;margin-bottom:6px;font-weight:500;">حالة الطلب</label>
                    <select name="order_status">
                        <option value="pending"   <?= $order['order_status'] === 'pending'   ? 'selected' : '' ?>>معلق</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>مؤكد</option>
                        <option value="preparing" <?= $order['order_status'] === 'preparing' ? 'selected' : '' ?>>قيد التجهيز</option>
                        <option value="shipped"   <?= $order['order_status'] === 'shipped'   ? 'selected' : '' ?>>مشحون</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>تم التسليم</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                    </select>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.8rem;color:#6B4F3A;margin-bottom:6px;font-weight:500;">ملاحظات</label>
                    <textarea name="notes" placeholder="ملاحظات على الطلب..."><?= htmlspecialchars($order['notes'] ?? '', ENT_QUOTES) ?></textarea>
                </div>
                <button type="submit" class="btn-full btn-save-action">
                    <i class="fas fa-floppy-disk"></i> حفظ التغييرات
                </button>
            </form>

            <!-- Contact buttons -->
            <a href="tel:<?= htmlspecialchars($order['phone'], ENT_QUOTES) ?>"
               class="btn-full btn-call">
                <i class="fas fa-phone"></i> اتصال بالعميل
            </a>
            <a href="<?= $waCustomer ?>" class="btn-full btn-wa" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-whatsapp"></i> واتساب
            </a>
        </div>
    </div>
    <!-- /RIGHT -->

</div><!-- /details-layout -->

</div><!-- /admin-content -->
</div><!-- /admin-layout -->
</body>
</html>
