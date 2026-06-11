<?php
/**
 * Shared occasion page template.
 * Expected variables set by the caller:
 *   $occasionSlug  - category slug in DB  (e.g. 'weddings')
 *   $pageIcon      - Font Awesome class    (e.g. 'fa-rings-wedding')
 *   $titleAr       - Arabic page title
 *   $titleEn       - English page title
 *   $subtitleAr    - Arabic subtitle
 *   $subtitleEn    - English subtitle
 *   $features      - array of [icon, titleAr, titleEn, descAr, descEn]
 */

// Fetch category + products
$cat = null;
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
$catStmt->execute([$occasionSlug]);
$cat = $catStmt->fetch();

$products = [];
if ($cat) {
    $prodStmt = $pdo->prepare(
        'SELECT p.*, pi.image_url AS main_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
         WHERE p.category_id = ? AND p.status = "active"
         ORDER BY p.is_featured DESC, p.created_at DESC'
    );
    $prodStmt->execute([$cat['id']]);
    $products = $prodStmt->fetchAll();
}

require_once __DIR__ . '/header.php';
?>

<!-- Banner -->
<div class="page-banner">
    <div class="container">
        <div style="font-size:2.2rem;color:var(--color-gold);margin-bottom:12px;">
            <i class="fas <?= $pageIcon ?>"></i>
        </div>
        <h1><?= $isRTL ? $titleAr : $titleEn ?></h1>
        <p style="margin-top:10px;color:var(--color-brown);"><?= $isRTL ? $subtitleAr : $subtitleEn ?></p>
        <nav class="breadcrumb" style="justify-content:center;margin-top:14px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/events.php"><?= $isRTL ? 'المناسبات' : 'Events' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? $titleAr : $titleEn ?></span>
        </nav>
    </div>
</div>

<!-- Features -->
<?php if (!empty($features)): ?>
<section class="section section-alt">
<div class="container">
    <div class="grid-3">
        <?php foreach ($features as $i => [$icon, $tAr, $tEn, $dAr, $dEn]): ?>
        <div class="animate fade-in-delay-<?= $i+1 ?>" style="text-align:center;padding:28px 20px;">
            <div style="width:60px;height:60px;border-radius:50%;background:rgba(201,169,110,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.4rem;color:var(--color-gold);">
                <i class="fas <?= $icon ?>"></i>
            </div>
            <h3 style="margin-bottom:8px;font-size:1.05rem;"><?= $isRTL ? $tAr : $tEn ?></h3>
            <p style="font-size:0.875rem;"><?= $isRTL ? $dAr : $dEn ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>
<?php endif; ?>

<!-- Products -->
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'مجموعتنا' : 'Our Collection' ?></span>
        <h2><?= $isRTL ? "منتجات $titleAr" : "$titleEn Products" ?></h2>
    </div>

    <?php if ($products): ?>
    <div class="grid-4">
        <?php foreach ($products as $i => $product): ?>
        <div class="product-card animate fade-in-delay-<?= ($i%4)+1 ?>">
            <div class="product-card-image-wrap">
                <?php if (!empty($product['main_image'])): ?>
                <img src="<?= UPLOAD_URL . sanitize($product['main_image']) ?>"
                     alt="<?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>"
                     class="product-card-image" loading="lazy">
                <?php else: ?>
                <div class="product-card-placeholder"><i class="fas fa-spray-can-sparkles"></i></div>
                <?php endif; ?>
                <?php if ($product['order_mode'] === 'request_quote'): ?>
                <span class="product-badge badge-quote"><?= $isRTL ? 'بالتفاوض' : 'Quote' ?></span>
                <?php endif; ?>
            </div>
            <div class="product-card-body">
                <h3 class="product-name">
                    <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($product['slug']) ?>">
                        <?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>
                    </a>
                </h3>
                <div class="product-price-wrap">
                    <?php if ($product['price']): ?>
                    <span class="product-price"><?= formatPrice((float)($product['sale_price'] ?: $product['price'])) ?></span>
                    <?php else: ?>
                    <span class="product-price-request"><?= $isRTL ? 'السعر بالتفاوض' : 'Price on request' ?></span>
                    <?php endif; ?>
                </div>
                <div class="product-actions">
                    <?php if (in_array($product['order_mode'], ['buy_now','both'])): ?>
                    <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <button type="submit" class="btn-primary btn-sm add-to-cart-btn">
                            <i class="fas fa-shopping-bag"></i> <?= $isRTL ? 'أضف للسلة' : 'Add to Cart' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (in_array($product['order_mode'], ['request_quote','both'])): ?>
                    <a href="<?= getWhatsAppLink(($isRTL ? 'أريد الاستفسار عن: ' : 'Inquiry: ').sanitize($isRTL ? $product['name_ar'] : $product['name_en'])) ?>"
                       class="btn-whatsapp btn-sm" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp"></i> <?= $isRTL ? 'واتساب' : 'WhatsApp' ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-spray-can-sparkles"></i>
        <h3><?= $isRTL ? 'قريباً...' : 'Coming Soon...' ?></h3>
        <p><?= $isRTL ? 'نعمل على إضافة منتجات لهذه المناسبة.' : 'We\'re adding products for this occasion soon.' ?></p>
    </div>
    <?php endif; ?>
</div>
</section>

<!-- Custom order CTA -->
<section style="background:var(--color-surface);padding:60px 20px;text-align:center;">
<div class="container">
    <div class="animate">
        <span class="section-subtitle"><?= $isRTL ? 'هدية بمواصفاتك' : 'Your Specifications' ?></span>
        <h2 style="margin-bottom:12px;">
            <?= $isRTL ? "تريد طلباً مخصصاً لـ $titleAr؟" : "Want a Custom $titleEn Order?" ?>
        </h2>
        <p style="margin-bottom:28px;max-width:500px;margin-inline:auto;">
            <?= $isRTL
                ? 'أخبرنا عن عدد الضيوف وميزانيتك وتفضيلاتك وسنصمم لك هديتك المثالية.'
                : 'Tell us your guest count, budget and preferences and we\'ll design your perfect gift.' ?>
        </p>
        <a href="<?= SITE_URL ?>/pages/custom-order.php?event=<?= urlencode($occasionSlug) ?>" class="btn-primary" style="margin-inline-end:12px;">
            <i class="fas fa-sparkles"></i>
            <?= $isRTL ? 'طلب مخصص' : 'Custom Order' ?>
        </a>
        <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
            <i class="fab fa-whatsapp"></i>
            <?= $isRTL ? 'استفسر الآن' : 'Enquire Now' ?>
        </a>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
