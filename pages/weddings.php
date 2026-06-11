<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Fetch wedding products ─────────────────────────────────────────────────
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = 'weddings' AND status = 'active' LIMIT 1");
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
.occasion-hero p{max-width:580px;margin:0 auto 28px;color:var(--color-brown);font-size:1rem;line-height:1.8;}
.offer-item{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--color-border);}
.offer-item:last-child{border-bottom:none;}
.offer-emoji{font-size:1.3rem;flex-shrink:0;line-height:1.4;}
.offer-text{font-size:.9rem;color:var(--color-brown);line-height:1.5;padding-top:2px;}
.quick-form-card{background:white;border-radius:var(--card-radius);padding:32px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.quick-form-card h3{margin-bottom:6px;font-size:1.2rem;}
.quick-form-card .sub{font-size:.875rem;color:var(--color-brown);margin-bottom:22px;}
.quick-form-card input,.quick-form-card select,.quick-form-card textarea{
    width:100%;padding:11px 14px;border:1px solid var(--color-border);border-radius:8px;
    font-family:var(--font-ar);font-size:.9rem;background:white;box-sizing:border-box;
    outline:none;transition:border-color .2s;color:var(--color-text);}
.quick-form-card input:focus,.quick-form-card select:focus{border-color:var(--color-gold);}
.insp-card{background:var(--color-surface);border-radius:var(--card-radius);aspect-ratio:4/3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--color-gold);font-size:2.4rem;}
.insp-caption{font-size:.8rem;color:var(--color-brown);}
</style>

<!-- Hero -->
<div class="occasion-hero" style="background:linear-gradient(135deg,#FDF8F4 0%,#F9EFE8 100%);">
    <div class="container">
        <nav class="breadcrumb" style="justify-content:center;margin-bottom:24px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/events.php"><?= $isRTL ? 'المناسبات' : 'Events' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'أفراح' : 'Weddings' ?></span>
        </nav>
        <div class="occasion-hero-icon"><i class="fas fa-rings-wedding"></i></div>
        <h1><?= $isRTL ? 'توزيعات عطرية لأجمل يوم في حياتكم' : 'Fragrance Favours for the Most Beautiful Day of Your Lives' ?></h1>
        <p>
            <?= $isRTL
                ? 'صمّموا هدايا صغيرة لضيوفكم تحمل أسماءكم وتاريخ يومكم، وتترك ذكرى عطرية لا تُنسى في كل بيت.'
                : 'Design personalised favours for your guests, carrying your names and wedding date — leaving a lasting fragrant memory in every home.' ?>
        </p>
        <a href="#quick-form" class="btn-primary" style="display:inline-flex;">
            <i class="fas fa-paper-plane"></i>
            <?= $isRTL ? 'اطلب عرض سعر لتوزيعات فرحك' : 'Request a Quote for Your Wedding Favours' ?>
        </a>
    </div>
</div>

<!-- What We Offer + Quick Form -->
<section class="section">
<div class="container">
    <div class="grid-2" style="gap:48px;align-items:start;">

        <!-- Offer list -->
        <div class="animate">
            <span class="section-subtitle"><?= $isRTL ? 'ما نقدمه لك' : 'What We Offer' ?></span>
            <h2 style="margin-bottom:20px;"><?= $isRTL ? 'كل ما تحتاجه لتوزيعات مثالية' : 'Everything You Need for Perfect Favours' ?></h2>
            <?php
            $offers = $isRTL ? [
                ['💐','زجاجات عطر صغيرة أنيقة للضيوف'],
                ['💌','كروت مخصصة بأسماء العريس والعروسة'],
                ['🎀','تغليف فاخر (ريبون، بوكس، كيس)'],
                ['🗓️','تخصيص بتاريخ الزفاف'],
                ['🌹','خيار ورد طبيعي مع التوزيعات'],
                ['📦','طلبات بالجملة (10 قطعة وأكثر)'],
                ['🚚','توصيل لجميع مناطق القاهرة الكبرى'],
            ] : [
                ['💐','Elegant mini perfume bottles for guests'],
                ['💌','Custom cards with the couple\'s names'],
                ['🎀','Luxury packaging (ribbon, box, bag)'],
                ['🗓️','Personalised with your wedding date'],
                ['🌹','Optional fresh flowers with favours'],
                ['📦','Bulk orders (10 pieces and above)'],
                ['🚚','Delivery across Greater Cairo'],
            ];
            foreach ($offers as [$emoji, $text]): ?>
            <div class="offer-item">
                <span class="offer-emoji"><?= $emoji ?></span>
                <span class="offer-text"><?= $text ?></span>
            </div>
            <?php endforeach; ?>

            <!-- Trust snippet -->
            <div style="margin-top:24px;padding:16px;background:rgba(201,169,110,.08);border-radius:10px;border-<?= $isRTL ? 'right' : 'left' ?>:3px solid var(--color-gold);">
                <strong style="font-size:.875rem;display:block;margin-bottom:4px;"><?= $isRTL ? 'ضمان الجودة' : 'Quality Guarantee' ?></strong>
                <span style="font-size:.825rem;color:var(--color-brown);"><?= $isRTL ? 'عرض سعر خلال 24 ساعة · تجهيز خلال 3-5 أيام · توصيل مضمون' : 'Quote within 24h · Ready in 3-5 days · Guaranteed delivery' ?></span>
            </div>
        </div>

        <!-- Quick Form -->
        <div class="animate" id="quick-form">
            <div class="quick-form-card">
                <h3><?= $isRTL ? 'ابدأ بطلب عرض سعر سريع' : 'Get a Quick Quote' ?></h3>
                <p class="sub"><?= $isRTL ? 'أرسل بياناتك وسنتواصل معك خلال 24 ساعة.' : 'Send your details and we\'ll contact you within 24 hours.' ?></p>
                <form method="GET" action="<?= SITE_URL ?>/pages/custom-order.php" style="display:flex;flex-direction:column;gap:12px;">
                    <input type="hidden" name="event_type" value="wedding">
                    <div class="form-row" style="gap:12px;">
                        <input type="text" name="customer_name" placeholder="<?= $isRTL ? 'اسمك *' : 'Your name *' ?>" required>
                        <input type="tel" name="phone" placeholder="<?= $isRTL ? 'رقم الهاتف *' : 'Phone *' ?>" required>
                    </div>
                    <div class="form-row" style="gap:12px;">
                        <input type="date" name="event_date">
                        <input type="number" name="quantity" min="10" placeholder="<?= $isRTL ? 'عدد الضيوف (تقريبي)' : 'Guest count (approx.)' ?>">
                    </div>
                    <select name="preferred_style">
                        <option value=""><?= $isRTL ? 'الستايل المفضل' : 'Preferred style' ?></option>
                        <option value="luxury"><?= $isRTL ? 'فاخر' : 'Luxury' ?></option>
                        <option value="romantic"><?= $isRTL ? 'رومانسي' : 'Romantic' ?></option>
                        <option value="classic"><?= $isRTL ? 'كلاسيك' : 'Classic' ?></option>
                        <option value="arabic"><?= $isRTL ? 'عربي أصيل' : 'Arabic Style' ?></option>
                        <option value="minimal"><?= $isRTL ? 'مينيمال' : 'Minimal' ?></option>
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
<section class="section section-alt">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'منتجاتنا' : 'Our Products' ?></span>
        <h2><?= $isRTL ? 'توزيعات وهدايا أفراح' : 'Wedding Favours & Gifts' ?></h2>
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

<!-- Inspiration Gallery (placeholders) -->
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'إلهام' : 'Inspiration' ?></span>
        <h2><?= $isRTL ? 'أفكار لتوزيعات فرحك' : 'Wedding Favour Ideas' ?></h2>
    </div>
    <?php
    $gallery = $isRTL ? [
        ['fa-spray-can-sparkles','توزيعات عطر مينيمال'],
        ['fa-ribbon',            'بوكسات بريبون ذهبي'],
        ['fa-heart',             'زجاجات قلب مخصصة'],
        ['fa-envelope-open-text','كروت باسم العروسين'],
        ['fa-gift',              'باكدجات فاخرة متكاملة'],
        ['fa-star',              'توزيعات ورد وعطر'],
    ] : [
        ['fa-spray-can-sparkles','Minimal Fragrance Favours'],
        ['fa-ribbon',            'Gold Ribbon Boxes'],
        ['fa-heart',             'Custom Heart Bottles'],
        ['fa-envelope-open-text','Named Guest Cards'],
        ['fa-gift',              'Full Luxury Packages'],
        ['fa-star',              'Rose & Fragrance Sets'],
    ];
    ?>
    <div class="grid-3">
        <?php foreach ($gallery as $i => [$icon, $caption]): ?>
        <div class="insp-card animate fade-in-delay-<?= ($i % 3) + 1 ?>">
            <i class="fas <?= $icon ?>"></i>
            <span class="insp-caption"><?= $caption ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- CTA -->
<section style="background:var(--color-text);padding:72px 20px;text-align:center;">
<div class="container">
    <div class="animate">
        <span class="section-subtitle" style="color:var(--color-gold);opacity:1;"><?= $isRTL ? 'ابدأ رحلتك' : 'Begin Your Journey' ?></span>
        <h2 style="color:white;margin-bottom:14px;">
            <?= $isRTL ? 'اجعلوا يومكم لا يُنسى' : 'Make Your Day Unforgettable' ?>
        </h2>
        <p style="color:rgba(232,213,176,.8);margin-bottom:32px;max-width:500px;margin-inline:auto;">
            <?= $isRTL
                ? 'تواصلوا معنا اليوم ونبدأ تصميم توزيعات فرحكم المثالية.'
                : 'Contact us today and we\'ll start designing your perfect wedding favours.' ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php?event_type=wedding" class="btn-primary">
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
