<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isRTL = isRTL();

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
}

$errors       = [];
$formData     = [];
$deliveryRaw  = getSetting('delivery_areas');
$deliveryAreas = $deliveryRaw ? array_map('trim', explode(',', $deliveryRaw)) : ['Cairo', 'Giza'];

// ── POST: save order ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect
    $formData = [
        'customer_name'  => sanitize($_POST['customer_name']  ?? ''),
        'phone'          => sanitize($_POST['phone']          ?? ''),
        'email'          => sanitize($_POST['email']          ?? ''),
        'city'           => sanitize($_POST['city']           ?? ''),
        'area'           => sanitize($_POST['area']           ?? ''),
        'address'        => sanitize($_POST['address']        ?? ''),
        'payment_method' => sanitize($_POST['payment_method'] ?? ''),
        'notes'          => sanitize($_POST['notes']          ?? ''),
    ];

    // Validate
    if (empty($formData['customer_name'])) { $errors['customer_name'] = $isRTL ? 'الاسم مطلوب' : 'Name is required'; }
    if (empty($formData['phone']))         { $errors['phone']         = $isRTL ? 'رقم الهاتف مطلوب' : 'Phone is required'; }
    if (empty($formData['city']))          { $errors['city']          = $isRTL ? 'المدينة مطلوبة' : 'City is required'; }
    if (empty($formData['area']))          { $errors['area']          = $isRTL ? 'المنطقة مطلوبة' : 'Area is required'; }
    if (empty($formData['address']))       { $errors['address']       = $isRTL ? 'العنوان مطلوب' : 'Address is required'; }
    $validPayment = ['cash_on_delivery', 'bank_transfer', 'instapay'];
    if (!in_array($formData['payment_method'], $validPayment)) {
        $errors['payment_method'] = $isRTL ? 'اختر طريقة دفع' : 'Select a payment method';
    }

    if (empty($errors)) {
        // Compute totals
        $subtotal = 0.0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $shipping     = 0.0;
        $total        = $subtotal + $shipping;
        $orderNumber  = generateOrderNumber();

        // Insert order
        $stmt = $pdo->prepare(
            'INSERT INTO orders (order_number, customer_name, phone, email, city, area, address,
             subtotal, shipping, total, payment_method, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $orderNumber,
            $formData['customer_name'],
            $formData['phone'],
            $formData['email'],
            $formData['city'],
            $formData['area'],
            $formData['address'],
            $subtotal,
            $shipping,
            $total,
            $formData['payment_method'],
            $formData['notes'],
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Insert order items
        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, customization_text)
             VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($_SESSION['cart'] as $pid => $item) {
            $itemStmt->execute([
                $orderId,
                (int)$pid,
                $isRTL ? $item['name_ar'] : $item['name_en'],
                (int)$item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity'],
                $item['customization_text'] ?? '',
            ]);
        }

        // Clear cart
        unset($_SESSION['cart']);

        header('Location: ' . SITE_URL . '/pages/thank-you.php?order=' . urlencode($orderNumber));
        exit;
    }
}

// ── Totals for display ─────────────────────────
$cartItems = $_SESSION['cart'];
$subtotal  = 0.0;
foreach ($cartItems as $item) { $subtotal += $item['price'] * $item['quantity']; }
$total = $subtotal;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?= $isRTL ? 'إتمام الشراء' : 'Checkout' ?></h1>
        <nav class="breadcrumb" style="justify-content:center;margin-top:12px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/cart.php"><?= $isRTL ? 'السلة' : 'Cart' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'إتمام الشراء' : 'Checkout' ?></span>
        </nav>
    </div>
</div>

<section class="section">
<div class="container">

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <i class="fas fa-circle-exclamation"></i>
    <?= $isRTL ? 'يرجى تصحيح الأخطاء أدناه.' : 'Please correct the errors below.' ?>
</div>
<?php endif; ?>

<form method="POST" action="">
<div class="grid-2" style="gap:32px;align-items:start;">

    <!-- ── Left: Customer form ── -->
    <div>

        <!-- Customer info -->
        <div style="background:white;border-radius:var(--card-radius);padding:28px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:20px;">
            <h3 style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">
                <i class="fas fa-user" style="color:var(--color-gold);margin-left:8px;"></i>
                <?= $isRTL ? 'بيانات العميل' : 'Customer Details' ?>
            </h3>
            <div class="form-row">
                <div class="form-group">
                    <label><?= $isRTL ? 'الاسم الكامل *' : 'Full Name *' ?></label>
                    <input type="text" name="customer_name" value="<?= htmlspecialchars($formData['customer_name'] ?? '', ENT_QUOTES) ?>"
                           placeholder="<?= $isRTL ? 'مثال: أحمد محمد' : 'e.g. Ahmed Mohamed' ?>">
                    <?php if (!empty($errors['customer_name'])): ?><div class="form-error"><?= $errors['customer_name'] ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label><?= $isRTL ? 'رقم الهاتف *' : 'Phone *' ?></label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? '', ENT_QUOTES) ?>"
                           placeholder="01XXXXXXXXX">
                    <?php if (!empty($errors['phone'])): ?><div class="form-error"><?= $errors['phone'] ?></div><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'البريد الإلكتروني (اختياري)' : 'Email (optional)' ?></label>
                <input type="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES) ?>"
                       placeholder="example@email.com">
            </div>
        </div>

        <!-- Delivery address -->
        <div style="background:white;border-radius:var(--card-radius);padding:28px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:20px;">
            <h3 style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">
                <i class="fas fa-location-dot" style="color:var(--color-gold);margin-left:8px;"></i>
                <?= $isRTL ? 'عنوان التوصيل' : 'Delivery Address' ?>
            </h3>
            <div class="form-row">
                <div class="form-group">
                    <label><?= $isRTL ? 'المدينة *' : 'City *' ?></label>
                    <select name="city">
                        <option value=""><?= $isRTL ? '-- اختر --' : '-- Select --' ?></option>
                        <?php
                        $cities = array_unique(array_map(function($a){ return explode(' ',$a)[0]; }, $deliveryAreas));
                        $cities = array_unique(array_merge(['Cairo','Giza','New Cairo','Heliopolis','Nasr City','Madinaty','Rehab','Shorouk'], $deliveryAreas));
                        foreach (array_unique($cities) as $city):
                        ?>
                        <option value="<?= htmlspecialchars($city, ENT_QUOTES) ?>"
                                <?= (($formData['city'] ?? '') === $city) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city, ENT_QUOTES) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['city'])): ?><div class="form-error"><?= $errors['city'] ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label><?= $isRTL ? 'المنطقة *' : 'Area *' ?></label>
                    <input type="text" name="area" value="<?= htmlspecialchars($formData['area'] ?? '', ENT_QUOTES) ?>"
                           placeholder="<?= $isRTL ? 'مثال: التجمع الخامس' : 'e.g. Fifth Settlement' ?>">
                    <?php if (!empty($errors['area'])): ?><div class="form-error"><?= $errors['area'] ?></div><?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'العنوان بالتفصيل *' : 'Full Address *' ?></label>
                <textarea name="address" rows="2" placeholder="<?= $isRTL ? 'رقم الشارع، المبنى، الدور، الشقة...' : 'Street, building, floor, apartment...' ?>"><?= htmlspecialchars($formData['address'] ?? '', ENT_QUOTES) ?></textarea>
                <?php if (!empty($errors['address'])): ?><div class="form-error"><?= $errors['address'] ?></div><?php endif; ?>
            </div>
        </div>

        <!-- Payment method -->
        <div style="background:white;border-radius:var(--card-radius);padding:28px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:20px;">
            <h3 style="margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--color-border);">
                <i class="fas fa-credit-card" style="color:var(--color-gold);margin-left:8px;"></i>
                <?= $isRTL ? 'طريقة الدفع' : 'Payment Method' ?>
            </h3>
            <?php
            $paymentMethods = [
                'cash_on_delivery' => [$isRTL ? 'كاش عند الاستلام' : 'Cash on Delivery', 'fa-money-bill-wave'],
                'bank_transfer'    => [$isRTL ? 'تحويل بنكي' : 'Bank Transfer',           'fa-building-columns'],
                'instapay'         => ['InstaPay',                                         'fa-mobile-screen'],
            ];
            foreach ($paymentMethods as $val => [$label, $icon]):
                $checked = (($formData['payment_method'] ?? '') === $val) ? 'checked' : '';
            ?>
            <label class="form-check" style="border:1px solid var(--color-border);border-radius:10px;padding:14px 16px;margin-bottom:10px;cursor:pointer;">
                <input type="radio" name="payment_method" value="<?= $val ?>" <?= $checked ?> required>
                <i class="fas <?= $icon ?>" style="color:var(--color-gold);width:20px;"></i>
                <span><?= $label ?></span>
            </label>
            <?php endforeach; ?>
            <?php if (!empty($errors['payment_method'])): ?><div class="form-error"><?= $errors['payment_method'] ?></div><?php endif; ?>
        </div>

        <!-- Notes -->
        <div class="form-group">
            <label><?= $isRTL ? 'ملاحظات إضافية (اختياري)' : 'Additional Notes (optional)' ?></label>
            <textarea name="notes" rows="3" placeholder="<?= $isRTL ? 'أي تعليمات خاصة للتوصيل أو الطلب...' : 'Any special delivery or order instructions...' ?>"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES) ?></textarea>
        </div>
    </div>

    <!-- ── Right: Order summary ── -->
    <div class="cart-summary" style="position:sticky;top:88px;">
        <h3 style="margin-bottom:20px;"><?= $isRTL ? 'ملخص الطلب' : 'Order Summary' ?></h3>

        <!-- Items list -->
        <div style="margin-bottom:16px;max-height:260px;overflow-y:auto;">
            <?php foreach ($cartItems as $item): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--color-border);gap:10px;">
                <div style="font-size:0.9rem;color:var(--color-text);flex:1;">
                    <?= sanitize($isRTL ? $item['name_ar'] : $item['name_en']) ?>
                    <span style="color:var(--color-brown);font-size:0.8rem;"> ×<?= (int)$item['quantity'] ?></span>
                </div>
                <div style="font-weight:600;color:var(--color-gold);white-space:nowrap;font-size:0.9rem;">
                    <?= formatPrice($item['price'] * $item['quantity']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary-row">
            <span><?= $isRTL ? 'المجموع' : 'Subtotal' ?></span>
            <span><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="cart-summary-row">
            <span><?= $isRTL ? 'التوصيل' : 'Shipping' ?></span>
            <span style="color:var(--color-gold);font-size:0.85rem;"><?= $isRTL ? 'يُحدد لاحقاً' : 'TBC' ?></span>
        </div>
        <div class="cart-summary-row cart-summary-total">
            <span><?= $isRTL ? 'الإجمالي' : 'Total' ?></span>
            <span><?= formatPrice($total) ?></span>
        </div>

        <button type="submit" class="btn-primary"
                style="width:100%;justify-content:center;margin-top:24px;font-size:1.05rem;padding:16px;">
            <i class="fas fa-check-circle"></i>
            <?= $isRTL ? 'تأكيد الطلب' : 'Place Order' ?>
        </button>
    </div>

</div>
</form>

</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
