<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Fetch corporate products ───────────────────────────────────────────────
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = 'corporate' AND status = 'active' LIMIT 1");
$catStmt->execute();
$cat = $catStmt->fetch();
$products = [];
if ($cat) {
    $stmt = $pdo->prepare(
        'SELECT p.*, pi.image_url AS main_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
         WHERE p.category_id = ? AND p.status = "active"
         ORDER BY p.is_featured DESC, p.created_at DESC'
    );
    $stmt->execute([$cat['id']]);
    $products = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.occasion-hero{padding:80px 20px;text-align:center;}
.occasion-hero-icon{font-size:3rem;margin-bottom:16px;color:var(--color-gold);}
.occasion-hero h1{font-size:clamp(1.7rem,3.5vw,2.5rem);margin-bottom:16px;line-height:1.3;}
.occasion-hero p{max-width:600px;margin:0 auto 28px;color:var(--color-brown);font-size:1rem;line-height:1.8;}
.pkg-card{background:white;border-radius:var(--card-radius);padding:28px 24px;box-shadow:0 4px 20px rgba(0,0,0,.06);border-top:3px solid var(--color-gold);display:flex;flex-direction:column;gap:10px;}
.pkg-card-icon{font-size:2rem;color:var(--color-gold);}
.pkg-card h3{font-size:1.05rem;margin:0;}
.pkg-card p{font-size:.875rem;color:var(--color-brown);line-height:1.5;margin:0;}
.custom-opt{display:flex;align-items:center;gap:12px;padding:14px;background:white;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.custom-opt-icon{width:38px;height:38px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);font-size:1rem;}
.custom-opt-text{font-size:.875rem;color:var(--color-text);line-height:1.4;}
.trust-strip{display:flex;gap:0;border-radius:var(--card-radius);overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.trust-item{flex:1;padding:24px 20px;text-align:center;background:white;border-<?= $isRTL ? 'left' : 'right' ?>:1px solid var(--color-border);}
.trust-item:last-child{border:none;}
.trust-item-icon{font-size:1.5rem;color:var(--color-gold);margin-bottom:8px;}
.trust-item-label{font-size:.875rem;font-weight:700;color:var(--color-text);display:block;margin-bottom:4px;}
.trust-item-sub{font-size:.78rem;color:var(--color-brown);}
.quick-form-card{background:white;border-radius:var(--card-radius);padding:32px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.quick-form-card h3{margin-bottom:6px;font-size:1.2rem;}
.quick-form-card .sub{font-size:.875rem;color:var(--color-brown);margin-bottom:22px;}
.quick-form-card input,.quick-form-card select,.quick-form-card textarea{
    width:100%;padding:11px 14px;border:1px solid var(--color-border);border-radius:8px;
    font-family:var(--font-ar);font-size:.9rem;background:white;box-sizing:border-box;
    outline:none;transition:border-color .2s;color:var(--color-text);}
.quick-form-card input:focus,.quick-form-card select:focus{border-color:var(--color-gold);}
@media(max-width:768px){.trust-strip{flex-direction:column;}.trust-item{border-right:none;border-bottom:1px solid var(--color-border);}.trust-item:last-child{border:none;}}
</style>

<!-- Hero -->
<div class="occasion-hero" style="background:linear-gradient(135deg,#FAF7F2 0%,#F0EBE4 100%);">
    <div class="container">
        <nav class="breadcrumb" style="justify-content:center;margin-bottom:24px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/events.php"><?= $isRTL ? 'المناسبات' : 'Events' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'هدايا شركات' : 'Corporate' ?></span>
        </nav>
        <div class="occasion-hero-icon"><i class="fas fa-briefcase"></i></div>
        <h1><?= $isRTL ? 'هدايا شركات راقية تحمل هوية علامتك' : 'Premium Corporate Gifts That Carry Your Brand' ?></h1>
        <p>
            <?= $isRTL
                ? 'هدايا مؤسسية مصنوعة بعناية — تعكس احترافية شركتك وتُعمّق علاقتك بموظفيك وعملائك في كل مناسبة.'
                : 'Thoughtfully crafted corporate gifts — reflecting your company\'s professionalism and deepening relationships with employees and clients at every occasion.' ?>
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="#quick-form" class="btn-primary" style="display:inline-flex;">
                <i class="fas fa-paper-plane"></i>
                <?= $isRTL ? 'اطلب عرض سعر للشركة' : 'Request a Corporate Quote' ?>
            </a>
            <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'تحدث معنا' : 'Chat with Us' ?>
            </a>
        </div>
    </div>
</div>

<!-- Trust Strip -->
<div style="padding:0 20px;margin-top:-1px;">
<div class="container">
    <div class="trust-strip animate">
        <?php
        $trust = $isRTL ? [
            ['fa-clock',          'عرض سعر خلال 24 ساعة', 'نتواصل معك فوراً بعرض مخصص'],
            ['fa-boxes-stacked',  'تجهيز خلال 5–7 أيام',  'من تأكيد الطلب حتى الاستلام'],
            ['fa-truck',          'توصيل القاهرة الكبرى',  'بعنوان شركتك أو موظفيك'],
        ] : [
            ['fa-clock',          'Quote within 24 Hours',  'We\'ll respond with a personalised offer'],
            ['fa-boxes-stacked',  'Ready in 5–7 Business Days', 'From order confirmation to delivery'],
            ['fa-truck',          'Greater Cairo Delivery',  'To your company or employee addresses'],
        ];
        foreach ($trust as [$icon, $label, $sub]): ?>
        <div class="trust-item">
            <div class="trust-item-icon"><i class="fas <?= $icon ?>"></i></div>
            <span class="trust-item-label"><?= $label ?></span>
            <span class="trust-item-sub"><?= $sub ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>

<!-- Package Types -->
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'خدماتنا' : 'Our Services' ?></span>
        <h2><?= $isRTL ? 'باكدجات مؤسسية لكل احتياج' : 'Corporate Packages for Every Need' ?></h2>
    </div>
    <?php
    $packages = $isRTL ? [
        ['fa-people-group', 'هدايا الموظفين',    'Employee Appreciation', 'هدايا عطرية فاخرة لمكافأة موظفيك في الأعياد والمناسبات السنوية.'],
        ['fa-handshake',    'هدايا العملاء',     'Client Appreciation',   'انطباع أول لا يُنسى — هدايا تُرسّخ علاقتك بعملائك وشركائك.'],
        ['fa-calendar-star','هدايا الفعاليات',  'Event Giveaways',       'هدايا وتوزيعات مخصصة للمؤتمرات والمعارض والفعاليات الرسمية.'],
        ['fa-boxes-stacked','طلبات بالجملة',     'Bulk Orders',           'أسعار خاصة للطلبات الكبيرة من 20 قطعة فأكثر بجودة مضمونة.'],
    ] : [
        ['fa-people-group', 'Employee Gifts',         'Employee Appreciation', 'Luxury fragrance gifts to reward your team on holidays and anniversaries.'],
        ['fa-handshake',    'Client Gifts',            'Client Appreciation',   'An unforgettable first impression — gifts that cement client and partner relationships.'],
        ['fa-calendar-star','Event Giveaways',         'Event Giveaways',       'Custom gifts and favours for conferences, exhibitions, and formal events.'],
        ['fa-boxes-stacked','Bulk Orders',             'Bulk Orders',           'Special pricing for large orders of 20+ pieces with guaranteed quality.'],
    ];
    ?>
    <div class="grid-2" style="gap:24px;">
        <?php foreach ($packages as $i => [$icon, $titleAr, $titleEn, $desc]): ?>
        <div class="pkg-card animate fade-in-delay-<?= ($i % 2) + 1 ?>">
            <div class="pkg-card-icon"><i class="fas <?= $icon ?>"></i></div>
            <h3><?= $isRTL ? $titleAr : $titleEn ?></h3>
            <p><?= $desc ?></p>
            <a href="#quick-form" style="font-size:.85rem;color:var(--color-gold);font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-top:4px;">
                <?= $isRTL ? 'اطلب عرض سعر' : 'Request a Quote' ?> <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>" style="font-size:.75rem;"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- Customisation Options -->
<section class="section section-alt">
<div class="container">
    <div class="grid-2" style="gap:48px;align-items:center;">
        <div class="animate">
            <span class="section-subtitle"><?= $isRTL ? 'التخصيص الكامل' : 'Full Customisation' ?></span>
            <h2 style="margin-bottom:20px;"><?= $isRTL ? 'هديتك تحمل هويتك' : 'Your Gift Carries Your Identity' ?></h2>
            <p style="color:var(--color-brown);margin-bottom:24px;line-height:1.8;">
                <?= $isRTL
                    ? 'كل تفصيلة في الهدية تعكس علامتك التجارية — من شعار الشركة على الكارت، إلى ألوان التغليف وأنواع العطور.'
                    : 'Every detail of the gift reflects your brand — from the company logo on the card to packaging colours and fragrance selection.' ?>
            </p>
            <?php
            $opts = $isRTL ? [
                ['fa-id-card',        'كارت شخصية بشعار الشركة'],
                ['fa-palette',        'تغليف بألوان هويتك البصرية'],
                ['fa-droplet',        'عطور مختارة خصيصاً لشركتك'],
                ['fa-box-open',       'طلبات من 20 قطعة وأكثر'],
            ] : [
                ['fa-id-card',        'Personalised card with company logo'],
                ['fa-palette',        'Packaging in your brand colours'],
                ['fa-droplet',        'Fragrances specially curated for your brand'],
                ['fa-box-open',       'Orders from 20 pieces and above'],
            ];
            foreach ($opts as [$icon, $text]): ?>
            <div class="custom-opt animate">
                <div class="custom-opt-icon"><i class="fas <?= $icon ?>"></i></div>
                <span class="custom-opt-text"><?= $text ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Form -->
        <div class="animate" id="quick-form">
            <div class="quick-form-card">
                <h3><?= $isRTL ? 'اطلب عرضاً مخصصاً لشركتك' : 'Request a Custom Corporate Quote' ?></h3>
                <p class="sub"><?= $isRTL ? 'فريقنا سيتواصل معك خلال 24 ساعة بعرض سعر مفصّل.' : 'Our team will contact you within 24 hours with a detailed offer.' ?></p>
                <form method="GET" action="<?= SITE_URL ?>/pages/custom-order.php" style="display:flex;flex-direction:column;gap:12px;">
                    <input type="hidden" name="event_type" value="corporate">
                    <div class="form-row" style="gap:12px;">
                        <input type="text" name="customer_name" placeholder="<?= $isRTL ? 'اسمك *' : 'Your name *' ?>" required>
                        <input type="tel" name="phone" placeholder="<?= $isRTL ? 'رقم الهاتف *' : 'Phone *' ?>" required>
                    </div>
                    <input type="text" name="notes" placeholder="<?= $isRTL ? 'اسم الشركة *' : 'Company name *' ?>" required>
                    <div class="form-row" style="gap:12px;">
                        <input type="number" name="quantity" min="20" placeholder="<?= $isRTL ? 'الكمية (20 فأكثر)' : 'Quantity (20+)' ?>">
                        <input type="date" name="event_date">
                    </div>
                    <select name="preferred_style">
                        <option value=""><?= $isRTL ? 'ستايل التغليف' : 'Packaging style' ?></option>
                        <option value="luxury"><?= $isRTL ? 'فاخر' : 'Luxury' ?></option>
                        <option value="minimal"><?= $isRTL ? 'مينيمال' : 'Minimal' ?></option>
                        <option value="classic"><?= $isRTL ? 'كلاسيك' : 'Classic' ?></option>
                        <option value="branded"><?= $isRTL ? 'بألوان الشركة' : 'Brand Colours' ?></option>
                    </select>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:1rem;">
                        <i class="fas fa-paper-plane"></i>
                        <?= $isRTL ? 'اطلب عرض السعر' : 'Request a Quote' ?>
                    </button>
                    <p style="text-align:center;font-size:.78rem;color:var(--color-brown);margin:0;"><?= $isRTL ? 'أو تواصل مباشرة:' : 'Or contact directly:' ?>
                        <a href="<?= $waLink ?>" target="_blank" rel="noopener noreferrer" style="color:#25D366;font-weight:600;text-decoration:none;"> <i class="fab fa-whatsapp"></i> WhatsApp</a>
                    </p>
                </form>
            </div>
        </div>

    </div>
</div>
</section>

<!-- Products -->
<?php if ($products): ?>
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'منتجاتنا' : 'Our Products' ?></span>
        <h2><?= $isRTL ? 'هدايا الشركات المميزة' : 'Featured Corporate Gifts' ?></h2>
    </div>
    <div class="grid-4">
        <?php foreach ($products as $i => $product): ?>
        <div class="product-card animate fade-in-delay-<?= ($i % 4) + 1 ?>">
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
                    <a href="<?= getWhatsAppLink(($isRTL ? 'أريد الاستفسار عن: ' : 'Inquiry: ') . sanitize($isRTL ? $product['name_ar'] : $product['name_en'])) ?>"
                       class="btn-whatsapp btn-sm" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp"></i> <?= $isRTL ? 'واتساب' : 'WhatsApp' ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>
<?php endif; ?>

<!-- CTA -->
<section style="background:var(--color-text);padding:72px 20px;text-align:center;">
<div class="container">
    <div class="animate">
        <span class="section-subtitle" style="color:var(--color-gold);opacity:1;"><?= $isRTL ? 'ابدأ الآن' : 'Get Started' ?></span>
        <h2 style="color:white;margin-bottom:14px;">
            <?= $isRTL ? 'هدايا شركتك تستحق أفضل ما لدينا' : 'Your Corporate Gifts Deserve Our Very Best' ?>
        </h2>
        <p style="color:rgba(232,213,176,.8);margin-bottom:32px;max-width:520px;margin-inline:auto;">
            <?= $isRTL
                ? 'تواصل معنا اليوم لنصمم لك حلاً متكاملاً لهدايا شركتك — بجودة تعكس مستوى علامتك وميزانية تناسبك.'
                : 'Contact us today and we\'ll design a complete corporate gift solution — quality that reflects your brand and a budget that suits you.' ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php?event_type=corporate" class="btn-primary">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'اطلب عرض سعر' : 'Request a Quote' ?>
            </a>
            <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'تحدث معنا' : 'Chat with Us' ?>
            </a>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
