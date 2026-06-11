<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Load product ───────────────────────────────────────────────────────────
$slug    = sanitize($_GET['slug'] ?? '');
$product = $slug ? getProductBySlug($slug) : false;

if (!$product) {
    header('Location: ' . SITE_URL . '/pages/shop.php');
    exit;
}

// ── Related products ───────────────────────────────────────────────────────
$related = [];
if ($product['category_id']) {
    $stmt = $pdo->prepare(
        'SELECT p.*, pi.image_url AS main_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
         WHERE p.category_id = ? AND p.id != ? AND p.status = "active"
         ORDER BY p.is_featured DESC LIMIT 4'
    );
    $stmt->execute([$product['category_id'], $product['id']]);
    $related = $stmt->fetchAll();
}

// ── Category name ──────────────────────────────────────────────────────────
$catName = '';
if ($product['category_id']) {
    $catStmt = $pdo->prepare('SELECT name_ar, name_en FROM categories WHERE id = ? LIMIT 1');
    $catStmt->execute([$product['category_id']]);
    $cat = $catStmt->fetch();
    if ($cat) { $catName = $isRTL ? $cat['name_ar'] : $cat['name_en']; }
}

// ── Delivery areas for shipping tab ───────────────────────────────────────
$deliveryAreas = getSetting('delivery_areas');

$images      = is_array($product['images']) ? $product['images'] : [];
$mainImage   = $images[0] ?? '';
$displayName = sanitize($isRTL ? $product['name_ar'] : $product['name_en']);
$displayPrice = $product['sale_price'] ? (float)$product['sale_price'] : (float)$product['price'];

// ── Product type badge data ────────────────────────────────────────────────
$typeBadges = [
    'ready_gift'     => ['icon' => '🎁', 'ar' => 'هدية جاهزة',   'en' => 'Ready Gift'],
    'event_giveaway' => ['icon' => '✨', 'ar' => 'هدية مناسبة', 'en' => 'Event Giveaway'],
    'both'           => ['icon' => '🎀', 'ar' => 'متعدد الاستخدام','en' => 'Versatile'],
];
$typeBadge = $typeBadges[$product['product_type']] ?? null;

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.product-type-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:600;background:rgba(201,169,110,.12);color:#8B6914;margin-bottom:14px;}
.discount-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;background:#FFEBEE;color:#C62828;margin-<?= $isRTL ? 'right' : 'left' ?>:8px;vertical-align:middle;}
.stock-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:600;}
.stock-in{background:#E8F5E9;color:#2E7D32;}
.stock-out{background:#FFEBEE;color:#C62828;}
.product-tab-btn{padding:12px 24px;border:none;background:none;cursor:pointer;font-family:var(--font-ar);font-size:.95rem;color:var(--color-brown);border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;white-space:nowrap;}
.product-tab-btn.active{color:var(--color-gold);border-bottom-color:var(--color-gold);}
.product-tab-btn:hover:not(.active){color:var(--color-text);}
.product-tab-panel{display:none;line-height:1.9;color:var(--color-brown);padding:24px 0;}
.product-tab-panel.active{display:block;}
.qty-control-wrap{display:flex;align-items:center;border:1px solid var(--color-border);border-radius:10px;overflow:hidden;width:fit-content;}
.qty-control-btn{width:38px;height:38px;border:none;background:var(--color-surface);cursor:pointer;font-size:1.1rem;color:var(--color-brown);transition:background .15s;flex-shrink:0;}
.qty-control-btn:hover{background:rgba(201,169,110,.15);}
.qty-control-input{width:56px;height:38px;text-align:center;border:none;border-radius:0;font-family:inherit;font-size:1rem;background:white;}
.action-stack{display:flex;flex-direction:column;gap:10px;}
</style>

<!-- Breadcrumb -->
<div class="page-banner" style="padding:32px 20px;">
    <div class="container">
        <nav class="breadcrumb" style="justify-content:center;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/shop.php"><?= $isRTL ? 'المتجر' : 'Shop' ?></a>
            <?php if ($catName): ?>
            <span class="breadcrumb-sep">/</span>
            <span><?= sanitize($catName) ?></span>
            <?php endif; ?>
            <span class="breadcrumb-sep">/</span>
            <span><?= $displayName ?></span>
        </nav>
    </div>
</div>

<section class="section" style="padding-top:32px;">
<div class="container">

    <!-- ═══ PRODUCT MAIN ═══ -->
    <div class="grid-2" style="gap:52px;align-items:start;">

        <!-- ── LEFT: Images ── -->
        <div>
            <div style="background:var(--color-surface);border-radius:var(--card-radius);overflow:hidden;aspect-ratio:1/1;margin-bottom:12px;position:relative;">
                <?php if ($mainImage): ?>
                <img src="<?= UPLOAD_URL . sanitize($mainImage) ?>" alt="<?= $displayName ?>"
                     id="mainProductImage"
                     style="width:100%;height:100%;object-fit:cover;transition:opacity .25s;">
                <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;color:var(--color-gold-light);">
                    <i class="fas fa-spray-can-sparkles"></i>
                </div>
                <?php endif; ?>

                <?php if ($product['sale_price']): ?>
                <div style="position:absolute;top:12px;<?= $isRTL ? 'right' : 'left' ?>:12px;background:#C62828;color:white;font-size:.75rem;font-weight:700;padding:4px 10px;border-radius:20px;">
                    <?= $isRTL ? 'خصم!' : 'SALE!' ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Thumbnails -->
            <?php if (count($images) > 1): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach ($images as $idx => $img): ?>
                <div class="thumb-item <?= $idx === 0 ? 'active' : '' ?>"
                     onclick="switchImage('<?= UPLOAD_URL . sanitize($img) ?>', this)"
                     style="width:72px;height:72px;border-radius:8px;overflow:hidden;cursor:pointer;flex-shrink:0;border:2px solid <?= $idx === 0 ? 'var(--color-gold)' : 'var(--color-border)' ?>;transition:border-color .2s;">
                    <img src="<?= UPLOAD_URL . sanitize($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── RIGHT: Info ── -->
        <div>

            <!-- Product type badge -->
            <?php if ($typeBadge): ?>
            <div class="product-type-badge">
                <?= $typeBadge['icon'] ?> <?= $isRTL ? $typeBadge['ar'] : $typeBadge['en'] ?>
            </div>
            <?php endif; ?>

            <h1 style="font-size:1.9rem;margin-bottom:10px;line-height:1.3;"><?= $displayName ?></h1>

            <?php
            $shortDesc = sanitize($isRTL ? $product['short_description_ar'] : $product['short_description_en']);
            if ($shortDesc): ?>
            <p style="margin-bottom:20px;font-size:1rem;color:var(--color-brown);line-height:1.7;"><?= $shortDesc ?></p>
            <?php endif; ?>

            <!-- Price block -->
            <div style="margin-bottom:20px;">
                <?php if ($product['sale_price']): ?>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:1.9rem;font-weight:700;color:var(--color-gold);"><?= formatPrice((float)$product['sale_price']) ?></span>
                    <span style="text-decoration:line-through;color:#bbb;font-size:1.1rem;"><?= formatPrice((float)$product['price']) ?></span>
                    <?php
                    $discountPct = round((1 - $product['sale_price'] / $product['price']) * 100);
                    ?>
                    <span class="discount-badge"><?= $isRTL ? "خصم $discountPct%" : "$discountPct% OFF" ?></span>
                </div>
                <?php elseif ($product['price']): ?>
                <span style="font-size:1.9rem;font-weight:700;color:var(--color-gold);"><?= formatPrice((float)$product['price']) ?></span>
                <?php else: ?>
                <span style="font-size:1rem;color:var(--color-brown);font-style:italic;"><?= $isRTL ? 'السعر حسب الطلب — تواصل معنا' : 'Price on request — contact us' ?></span>
                <?php endif; ?>
            </div>

            <!-- Stock status -->
            <div style="margin-bottom:20px;">
                <span class="stock-badge <?= $product['stock_status'] === 'in_stock' ? 'stock-in' : 'stock-out' ?>">
                    <?php if ($product['stock_status'] === 'in_stock'): ?>
                    <i class="fas fa-circle-check" style="font-size:.75rem;"></i>
                    <?= $isRTL ? 'متوفر' : 'In Stock' ?>
                    <?php else: ?>
                    <i class="fas fa-circle-xmark" style="font-size:.75rem;"></i>
                    <?= $isRTL ? 'غير متوفر حالياً' : 'Out of Stock' ?>
                    <?php endif; ?>
                </span>
            </div>

            <!-- Action buttons -->
            <div class="action-stack">

                <!-- Add to cart (buy_now / both + in_stock) -->
                <?php if (in_array($product['order_mode'], ['buy_now', 'both']) && $product['stock_status'] === 'in_stock'): ?>
                <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <!-- Qty selector -->
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                        <label style="font-size:.875rem;color:var(--color-brown);white-space:nowrap;"><?= $isRTL ? 'الكمية:' : 'Qty:' ?></label>
                        <div class="qty-control-wrap">
                            <button type="button" class="qty-control-btn qty-btn" data-dir="down">−</button>
                            <input type="number" name="quantity" value="1" min="1"
                                   max="<?= ($product['stock_quantity'] > 0) ? (int)$product['stock_quantity'] : 99 ?>"
                                   class="qty-control-input qty-input">
                            <button type="button" class="qty-control-btn qty-btn" data-dir="up">+</button>
                        </div>
                    </div>

                    <!-- Customisation note -->
                    <?php if ($product['customization_options']): ?>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:.875rem;"><?= $isRTL ? 'ملاحظة التخصيص (اختياري):' : 'Customisation note (optional):' ?></label>
                        <textarea name="customization_text" rows="2"
                                  placeholder="<?= sanitize($product['customization_options']) ?>"
                                  style="font-size:.875rem;"></textarea>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary add-to-cart-btn"
                            style="width:100%;justify-content:center;font-size:1rem;padding:15px;">
                        <i class="fas fa-shopping-bag"></i>
                        <?= $isRTL ? 'أضف إلى السلة' : 'Add to Cart' ?>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Request a quote -->
                <?php if (in_array($product['order_mode'], ['request_quote', 'both'])): ?>
                <a href="<?= SITE_URL ?>/pages/custom-order.php?product=<?= (int)$product['id'] ?>"
                   class="btn-outline" style="justify-content:center;padding:14px;">
                    📋 <?= $isRTL ? 'اطلب عرض سعر' : 'Request a Quote' ?>
                </a>
                <?php endif; ?>

                <!-- WhatsApp (always shown) -->
                <a href="<?= getWhatsAppLink(($isRTL ? 'مرحباً، أريد الاستفسار عن: ' : 'Hello, I want to inquire about: ') . $displayName) ?>"
                   class="btn-whatsapp" target="_blank" rel="noopener noreferrer"
                   style="justify-content:center;padding:14px;font-size:1rem;">
                    <i class="fab fa-whatsapp"></i>
                    <?= $isRTL ? 'اطلب عبر واتساب' : 'Order via WhatsApp' ?>
                </a>
            </div>

            <!-- Meta info strip -->
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--color-border);display:flex;flex-direction:column;gap:9px;">
                <?php if ($product['preparation_time']): ?>
                <div style="display:flex;gap:10px;align-items:center;font-size:.85rem;color:var(--color-brown);">
                    <i class="fas fa-clock" style="color:var(--color-gold);width:16px;"></i>
                    <span><?= $isRTL ? 'وقت التحضير: ' : 'Preparation: ' ?><?= sanitize($product['preparation_time']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;gap:10px;align-items:center;font-size:.85rem;color:var(--color-brown);">
                    <i class="fas fa-shield-check" style="color:var(--color-gold);width:16px;"></i>
                    <span><?= $isRTL ? 'جودة مضمونة وتغليف فاخر' : 'Quality guaranteed & luxury packaging' ?></span>
                </div>
                <div style="display:flex;gap:10px;align-items:center;font-size:.85rem;color:var(--color-brown);">
                    <i class="fas fa-truck" style="color:var(--color-gold);width:16px;"></i>
                    <span><?= $isRTL ? 'توصيل لجميع المحافظات' : 'Delivery across all governorates' ?></span>
                </div>
            </div>
        </div>
    </div><!-- /grid-2 -->

    <!-- ═══ TABS ═══ -->
    <?php
    // Build tabs array
    $tabs = [];
    if ($product['full_description_ar'] || $product['full_description_en']) {
        $tabs[] = [
            'id'       => 'desc',
            'label_ar' => 'الوصف الكامل',
            'label_en' => 'Full Description',
            'html'     => nl2br(sanitize($isRTL ? $product['full_description_ar'] : $product['full_description_en'])),
        ];
    }
    if ($product['package_contents']) {
        $tabs[] = [
            'id'       => 'contents',
            'label_ar' => 'محتويات الباقة',
            'label_en' => 'Package Contents',
            'html'     => nl2br(sanitize($product['package_contents'])),
        ];
    }
    // Shipping tab always present
    $tabs[] = [
        'id'       => 'shipping',
        'label_ar' => 'الشحن والتسليم',
        'label_en' => 'Shipping & Delivery',
        'html'     => null, // rendered separately
    ];
    if ($product['customization_options']) {
        $tabs[] = [
            'id'       => 'custom',
            'label_ar' => 'خيارات التخصيص',
            'label_en' => 'Customisation Options',
            'html'     => nl2br(sanitize($product['customization_options'])),
        ];
    }
    ?>

    <div style="margin-top:52px;">
        <!-- Tab buttons -->
        <div style="display:flex;gap:2px;border-bottom:2px solid var(--color-border);margin-bottom:0;overflow-x:auto;">
            <?php foreach ($tabs as $i => $tab): ?>
            <button class="product-tab-btn <?= $i === 0 ? 'active' : '' ?>"
                    id="ptab-btn-<?= $tab['id'] ?>"
                    onclick="switchProductTab('<?= $tab['id'] ?>')">
                <?= $isRTL ? $tab['label_ar'] : $tab['label_en'] ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab panels -->
        <?php foreach ($tabs as $i => $tab): ?>
        <div class="product-tab-panel <?= $i === 0 ? 'active' : '' ?>" id="ptab-<?= $tab['id'] ?>">
            <?php if ($tab['id'] === 'shipping'): ?>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div>
                            <strong style="display:block;margin-bottom:4px;"><?= $isRTL ? 'مدة التوصيل' : 'Delivery Time' ?></strong>
                            <span><?= $isRTL ? '2–5 أيام عمل من تأكيد الطلب.' : '2–5 business days from order confirmation.' ?></span>
                        </div>
                    </div>
                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <strong style="display:block;margin-bottom:4px;"><?= $isRTL ? 'مناطق التوصيل' : 'Delivery Areas' ?></strong>
                            <span><?= $deliveryAreas ? sanitize($deliveryAreas) : ($isRTL ? 'جميع محافظات مصر.' : 'All Egyptian governorates.') ?></span>
                        </div>
                    </div>
                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            <strong style="display:block;margin-bottom:4px;"><?= $isRTL ? 'التغليف' : 'Packaging' ?></strong>
                            <span><?= $isRTL ? 'كل طلب يُغلّف بعناية في صناديق فاخرة تليق بالمناسبة.' : 'Every order is carefully wrapped in luxury boxes befitting the occasion.' ?></span>
                        </div>
                    </div>
                    <div style="display:flex;gap:14px;align-items:flex-start;">
                        <div style="width:40px;height:40px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <strong style="display:block;margin-bottom:4px;"><?= $isRTL ? 'الشحن المجاني' : 'Free Shipping' ?></strong>
                            <span><?= $isRTL ? 'توصيل مجاني على الطلبات فوق 500 ج.م.' : 'Free delivery on orders above 500 EGP.' ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?= $tab['html'] ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ RELATED PRODUCTS ═══ -->
    <?php if ($related): ?>
    <div style="margin-top:72px;">
        <div class="section-title animate" style="margin-bottom:28px;">
            <span class="section-subtitle"><?= $isRTL ? 'قد يعجبك أيضاً' : 'You Might Also Like' ?></span>
            <h2><?= $isRTL ? 'منتجات مشابهة' : 'Related Products' ?></h2>
        </div>
        <div class="grid-4">
            <?php foreach ($related as $i => $rel): ?>
            <div class="product-card animate fade-in-delay-<?= ($i % 4) + 1 ?>">
                <div class="product-card-image-wrap">
                    <?php if (!empty($rel['main_image'])): ?>
                    <img src="<?= UPLOAD_URL . sanitize($rel['main_image']) ?>"
                         alt="<?= sanitize($isRTL ? $rel['name_ar'] : $rel['name_en']) ?>"
                         class="product-card-image" loading="lazy">
                    <?php else: ?>
                    <div class="product-card-placeholder"><i class="fas fa-spray-can-sparkles"></i></div>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <h3 class="product-name">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($rel['slug']) ?>">
                            <?= sanitize($isRTL ? $rel['name_ar'] : $rel['name_en']) ?>
                        </a>
                    </h3>
                    <div class="product-price-wrap">
                        <?php if ($rel['price']): ?>
                        <span class="product-price"><?= formatPrice((float)($rel['sale_price'] ?: $rel['price'])) ?></span>
                        <?php else: ?>
                        <span class="product-price-request"><?= $isRTL ? 'بالتفاوض' : 'On request' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions" style="margin-top:10px;">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($rel['slug']) ?>"
                           class="btn-outline btn-sm" style="flex:1;justify-content:center;">
                            <?= $isRTL ? 'عرض المنتج' : 'View Product' ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</section>

<script>
function switchProductTab(id) {
    document.querySelectorAll('.product-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.product-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.getElementById('ptab-' + id).classList.add('active');
    document.getElementById('ptab-btn-' + id).classList.add('active');
}

// Thumbnail switcher
function switchImage(src, thumbEl) {
    var main = document.getElementById('mainProductImage');
    if (!main) return;
    main.style.opacity = '0';
    setTimeout(function() { main.src = src; main.style.opacity = '1'; }, 200);
    document.querySelectorAll('.thumb-item').forEach(function(t) {
        t.style.borderColor = 'var(--color-border)';
    });
    if (thumbEl) thumbEl.style.borderColor = 'var(--color-gold)';
}

// Qty stepper (product page)
(function(){
    var minus = document.querySelector('.qty-control-btn[data-dir="down"]');
    var plus  = document.querySelector('.qty-control-btn[data-dir="up"]');
    var input = document.querySelector('.qty-control-input');
    if (!minus || !plus || !input) return;
    minus.addEventListener('click', function() {
        var v = parseInt(input.value, 10);
        var min = parseInt(input.min, 10) || 1;
        if (v > min) input.value = v - 1;
    });
    plus.addEventListener('click', function() {
        var v   = parseInt(input.value, 10);
        var max = parseInt(input.max, 10) || 99;
        if (v < max) input.value = v + 1;
    });
}());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
