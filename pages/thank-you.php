<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$orderNumber = sanitize($_GET['order'] ?? '');
$type        = in_array($_GET['type'] ?? '', ['custom']) ? 'custom' : 'order';
$isCustom    = ($type === 'custom');

// Load order details if regular order
$order = null;
if (!$isCustom && $orderNumber) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section style="min-height:70vh;display:flex;align-items:center;padding:60px 20px;">
<div class="container" style="text-align:center;max-width:600px;margin:auto;">

    <!-- Icon -->
    <div style="width:90px;height:90px;border-radius:50%;background:rgba(201,169,110,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.5rem;color:var(--color-gold);">
        <i class="fas fa-<?= $isCustom ? 'paper-plane' : 'circle-check' ?>"></i>
    </div>

    <h1 style="margin-bottom:12px;">
        <?= $isRTL
            ? ($isCustom ? 'تم استلام طلبك بنجاح! 🎉' : 'تم تأكيد طلبك بنجاح! 🎉')
            : ($isCustom ? 'Request Received Successfully! 🎉' : 'Order Confirmed Successfully! 🎉') ?>
    </h1>

    <?php if ($orderNumber): ?>
    <div style="display:inline-block;background:var(--color-surface);border:1px solid var(--color-border);border-radius:10px;padding:14px 28px;margin:16px 0;">
        <div style="font-size:0.8rem;color:var(--color-brown);margin-bottom:4px;"><?= $isRTL ? 'رقم الطلب' : 'Order Number' ?></div>
        <div style="font-family:var(--font-en);font-size:1.3rem;font-weight:600;color:var(--color-gold);letter-spacing:0.05em;">
            <?= htmlspecialchars($orderNumber, ENT_QUOTES) ?>
        </div>
    </div>
    <?php endif; ?>

    <p style="margin-bottom:32px;font-size:1rem;">
        <?= $isRTL
            ? 'شكراً لك! سيتواصل معك فريقنا خلال 24 ساعة لتأكيد الطلب وتحديد موعد التوصيل.'
            : 'Thank you! Our team will contact you within 24 hours to confirm your order and arrange delivery.' ?>
    </p>

    <!-- Order details summary (regular orders) -->
    <?php if ($order): ?>
    <div style="background:white;border-radius:var(--card-radius);padding:24px;text-align:<?= $isRTL ? 'right' : 'left' ?>;margin-bottom:28px;box-shadow:0 4px 20px rgba(0,0,0,0.06);">
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--color-border);font-size:0.9rem;">
            <span style="color:var(--color-brown);"><?= $isRTL ? 'الاسم' : 'Name' ?></span>
            <span><?= sanitize($order['customer_name']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--color-border);font-size:0.9rem;">
            <span style="color:var(--color-brown);"><?= $isRTL ? 'الهاتف' : 'Phone' ?></span>
            <span><?= sanitize($order['phone']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--color-border);font-size:0.9rem;">
            <span style="color:var(--color-brown);"><?= $isRTL ? 'الإجمالي' : 'Total' ?></span>
            <span style="color:var(--color-gold);font-weight:600;"><?= formatPrice((float)$order['total']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.9rem;">
            <span style="color:var(--color-brown);"><?= $isRTL ? 'الدفع' : 'Payment' ?></span>
            <span><?= match($order['payment_method']) {
                'cash_on_delivery' => $isRTL ? 'كاش عند الاستلام' : 'Cash on Delivery',
                'bank_transfer'    => $isRTL ? 'تحويل بنكي' : 'Bank Transfer',
                'instapay'         => 'InstaPay',
                default            => $order['payment_method']
            } ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="<?= getWhatsAppLink($isRTL
            ? 'مرحباً، طلبي رقم ' . htmlspecialchars($orderNumber, ENT_QUOTES) . ' — أريد متابعة حالته'
            : 'Hello, my order ' . htmlspecialchars($orderNumber, ENT_QUOTES) . ' — I want to follow up') ?>"
           class="btn-whatsapp" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-whatsapp"></i>
            <?= $isRTL ? 'متابعة الطلب' : 'Follow Up' ?>
        </a>
        <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline">
            <i class="fas fa-shopping-bag"></i>
            <?= $isRTL ? 'متابعة التسوق' : 'Continue Shopping' ?>
        </a>
        <a href="<?= SITE_URL ?>/" class="btn-primary">
            <?= $isRTL ? 'الرئيسية' : 'Home' ?>
        </a>
    </div>

</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
