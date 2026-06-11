<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── GET: cart item count (AJAX badge refresh) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'count') {
    header('Content-Type: application/json');
    $count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
    echo json_encode(['count' => $count]);
    exit;
}

// ── Cart action handler ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $isAjax     = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                  && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

    if ($action === 'add' && $product_id > 0) {
        $stmt = $pdo->prepare(
            'SELECT p.*, pi.image_url
             FROM products p
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
             WHERE p.id = ? AND p.status = "active" LIMIT 1'
        );
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product && in_array($product['order_mode'], ['buy_now', 'both'])) {
            $qty        = max(1, (int)($_POST['quantity'] ?? 1));
            $customText = sanitize($_POST['customization_text'] ?? '');
            $unitPrice  = (float)($product['sale_price'] ?: $product['price']);

            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $qty;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'product_id'         => $product_id,
                    'name_ar'            => $product['name_ar'],
                    'name_en'            => $product['name_en'],
                    'slug'               => $product['slug'],
                    'price'              => $unitPrice,
                    'image'              => $product['image_url'] ?? '',
                    'quantity'           => $qty,
                    'customization_text' => $customText,
                ];
            }
        }

    } elseif ($action === 'update' && $product_id > 0) {
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } elseif (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $qty;
        }

    } elseif ($action === 'remove' && $product_id > 0) {
        unset($_SESSION['cart'][$product_id]);

    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    $cartTotal    = array_sum(array_column($_SESSION['cart'], 'quantity'));
    $cartSubtotal = 0.0;
    foreach ($_SESSION['cart'] as $it) { $cartSubtotal += $it['price'] * $it['quantity']; }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'cartCount' => $cartTotal,
            'subtotal'  => $cartSubtotal,
        ]);
        exit;
    }
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
}

// ── Totals ─────────────────────────────────────
$cartItems = $_SESSION['cart'] ?? [];
$subtotal  = 0.0;
foreach ($cartItems as $item) { $subtotal += $item['price'] * $item['quantity']; }
$total = $subtotal; // shipping determined at fulfilment

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?= $isRTL ? 'سلة التسوق' : 'Shopping Cart' ?></h1>
        <nav class="breadcrumb" style="justify-content:center;margin-top:12px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'السلة' : 'Cart' ?></span>
        </nav>
    </div>
</div>

<section class="section">
<div class="container">

<?php if (empty($cartItems)): ?>

<!-- Empty cart -->
<div class="empty-state">
    <i class="fas fa-shopping-bag"></i>
    <h3><?= $isRTL ? 'سلتك فارغة' : 'Your cart is empty' ?></h3>
    <p><?= $isRTL ? 'لم تضف أي منتجات بعد.' : "You haven't added any products yet." ?></p>
    <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-primary" style="margin-top:20px;">
        <i class="fas fa-shopping-bag"></i>
        <?= $isRTL ? 'تسوق الآن' : 'Shop Now' ?>
    </a>
</div>

<?php else: ?>

<div class="grid-2" style="align-items:start;gap:32px;">

    <!-- Items table -->
    <div>
        <div style="background:white;border-radius:var(--card-radius);overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);">
            <table class="cart-table" style="width:100%;">
                <thead>
                    <tr>
                        <th><?= $isRTL ? 'المنتج' : 'Product' ?></th>
                        <th><?= $isRTL ? 'السعر' : 'Price' ?></th>
                        <th><?= $isRTL ? 'الكمية' : 'Qty' ?></th>
                        <th><?= $isRTL ? 'الإجمالي' : 'Total' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cartItems as $pid => $item): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:14px;">
                            <?php if ($item['image']): ?>
                            <img src="<?= UPLOAD_URL . sanitize($item['image']) ?>" alt=""
                                 style="width:64px;height:64px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                            <?php else: ?>
                            <div style="width:64px;height:64px;background:var(--color-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold-light);font-size:1.4rem;">
                                <i class="fas fa-spray-can-sparkles"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($item['slug']) ?>"
                                   style="font-weight:500;color:var(--color-text);">
                                    <?= sanitize($isRTL ? $item['name_ar'] : $item['name_en']) ?>
                                </a>
                                <?php if (!empty($item['customization_text'])): ?>
                                <div style="font-size:0.8rem;color:var(--color-brown);margin-top:4px;">
                                    <?= sanitize($item['customization_text']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--color-gold);font-weight:500;white-space:nowrap;">
                        <?= formatPrice($item['price']) ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="qty-form"
                              data-price="<?= $item['price'] ?>" data-pid="<?= (int)$pid ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
                            <div style="display:flex;align-items:center;gap:4px;">
                                <button type="button" class="qty-btn qty-minus" style="width:28px;height:28px;border:1px solid var(--color-border);border-radius:6px;background:white;cursor:pointer;font-size:1rem;color:var(--color-brown);">−</button>
                                <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="1" max="99"
                                       class="cart-qty-input qty-input">
                                <button type="button" class="qty-btn qty-plus" style="width:28px;height:28px;border:1px solid var(--color-border);border-radius:6px;background:white;cursor:pointer;font-size:1rem;color:var(--color-brown);">+</button>
                            </div>
                        </form>
                    </td>
                    <td class="row-total" data-pid="<?= (int)$pid ?>" style="font-weight:600;white-space:nowrap;"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                    <td>
                        <button type="button" data-remove-id="<?= (int)$pid ?>"
                                style="background:none;border:none;color:#ccc;cursor:pointer;font-size:1rem;transition:color 0.2s;"
                                onmouseover="this.style.color='#C62828'" onmouseout="this.style.color='#ccc'">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:10px;">
            <a href="<?= SITE_URL ?>/pages/shop.php" style="color:var(--color-gold);font-size:0.9rem;">
                <i class="fas fa-arrow-<?= $isRTL ? 'right' : 'left' ?>"></i>
                <?= $isRTL ? 'متابعة التسوق' : 'Continue Shopping' ?>
            </a>
            <form method="POST" action="<?= SITE_URL ?>/pages/cart.php"
                  onsubmit="return confirm('<?= $isRTL ? 'هل تريد تفريغ السلة؟' : 'Clear your cart?' ?>')">
                <input type="hidden" name="action" value="clear">
                <button type="submit" style="background:none;border:none;color:#aaa;font-size:0.85rem;cursor:pointer;">
                    <i class="fas fa-trash"></i> <?= $isRTL ? 'تفريغ السلة' : 'Clear Cart' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Summary -->
    <div class="cart-summary">
        <h3 style="margin-bottom:20px;"><?= $isRTL ? 'ملخص الطلب' : 'Order Summary' ?></h3>
        <div class="cart-summary-row">
            <span><?= $isRTL ? 'المجموع الفرعي' : 'Subtotal' ?></span>
            <span class="cart-subtotal-val"><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="cart-summary-row">
            <span><?= $isRTL ? 'التوصيل' : 'Shipping' ?></span>
            <span style="color:var(--color-gold);font-size:0.85rem;"><?= $isRTL ? 'يُحدد عند التأكيد' : 'At checkout' ?></span>
        </div>
        <div class="cart-summary-row cart-summary-total">
            <span><?= $isRTL ? 'الإجمالي' : 'Total' ?></span>
            <span><?= formatPrice($total) ?></span>
        </div>
        <a href="<?= SITE_URL ?>/pages/checkout.php" class="btn-primary"
           style="width:100%;justify-content:center;margin-top:24px;font-size:1rem;padding:15px;">
            <i class="fas fa-lock"></i>
            <?= $isRTL ? 'إتمام الشراء' : 'Proceed to Checkout' ?>
        </a>
        <a href="<?= $waLink ?>" class="btn-whatsapp"
           style="width:100%;justify-content:center;margin-top:10px;" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-whatsapp"></i>
            <?= $isRTL ? 'طلب عبر واتساب' : 'Order via WhatsApp' ?>
        </a>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--color-border);text-align:center;">
            <div style="font-size:0.78rem;color:var(--color-brown);margin-bottom:8px;"><?= $isRTL ? 'طرق الدفع' : 'Payment Methods' ?></div>
            <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap;font-size:0.8rem;color:var(--color-brown);">
                <span><i class="fas fa-money-bill-wave" style="color:var(--color-gold);"></i> <?= $isRTL ? 'كاش' : 'Cash' ?></span>
                <span><i class="fas fa-building-columns" style="color:var(--color-gold);"></i> <?= $isRTL ? 'تحويل بنكي' : 'Bank Transfer' ?></span>
                <span><i class="fas fa-mobile-screen" style="color:var(--color-gold);"></i> InstaPay</span>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
