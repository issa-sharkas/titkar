<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Fetch birthday products ────────────────────────────────────────────────
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = 'birthdays' AND status = 'active' LIMIT 1");
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
.path-card{border-radius:var(--card-radius);padding:36px 28px;text-align:center;}
.insp-card{background:var(--color-surface);border-radius:var(--card-radius);aspect-ratio:4/3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--color-gold);font-size:2.4rem;}
.insp-caption{font-size:.8rem;color:var(--color-brown);}
</style>

<!-- Hero -->
<div class="occasion-hero" style="background:linear-gradient(135deg,#FDF8F4 0%,#FBF0E8 100%);">
    <div class="container">
        <nav class="breadcrumb" style="justify-content:center;margin-bottom:24px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <a href="<?= SITE_URL ?>/pages/events.php"><?= $isRTL ? 'المناسبات' : 'Events' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'أعياد ميلاد' : 'Birthdays' ?></span>
        </nav>
        <div class="occasion-hero-icon"><i class="fas fa-cake-candles"></i></div>
        <h1><?= $isRTL ? 'هدايا عيد ميلاد مختلفة ومميزة' : 'Birthday Gifts That Stand Apart' ?></h1>
        <p>
            <?= $isRTL
                ? 'عيد ميلاد يستحق هدية استثنائية — عطر مخصص باسمه وستايله، في تغليف يجعل لحظة فتح الهدية لا تُنسى.'
                : 'A birthday deserves an exceptional gift — a fragrance personalised to their name and style, in packaging that makes the unboxing unforgettable.' ?>
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-primary">
                <i class="fas fa-shopping-bag"></i>
                <?= $isRTL ? 'تسوق الآن' : 'Shop Now' ?>
            </a>
            <a href="#quick-form" class="btn-outline" style="display:inline-flex;">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'اطلب هدية مخصصة' : 'Order Custom Gift' ?>
            </a>
        </div>
    </div>
</div>

<!-- What We Offer + Quick Form -->
<section class="section">
<div class="container">
    <div class="grid-2" style="gap:48px;align-items:start;">

        <!-- Offer list -->
        <div class="animate">
            <span class="section-subtitle"><?= $isRTL ? 'ما نقدمه لك' : 'What We Offer' ?></span>
            <h2 style="margin-bottom:20px;"><?= $isRTL ? 'هدية تُبهج وتدوم في الذاكرة' : 'A Gift That Delights and Stays in Memory' ?></h2>
            <?php
            $offers = $isRTL ? [
                ['🎁','بوكسات هدايا عيد ميلاد فاخرة'],
                ['🎉','توزيعات للضيوف في الحفلات'],
                ['💌','كروت شخصية باسم المحتفل وعمره'],
                ['🌸','تغليف ملوّن بكل الأحجام والأذواق'],
                ['🌹','باكدج ورد مع عطر لهدية رومانسية'],
                ['🧴','تشكيلة واسعة من العطور لكل شخصية'],
            ] : [
                ['🎁','Luxury birthday gift boxes'],
                ['🎉','Party favours for guests'],
                ['💌','Personal cards with name and age'],
                ['🌸','Colourful packaging in all sizes'],
                ['🌹','Rose & fragrance combo packages'],
                ['🧴','Wide range of scents for every personality'],
            ];
            foreach ($offers as [$emoji, $text]): ?>
            <div class="offer-item">
                <span class="offer-emoji"><?= $emoji ?></span>
                <span class="offer-text"><?= $text ?></span>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:24px;padding:16px;background:rgba(201,169,110,.08);border-radius:10px;border-<?= $isRTL ? 'right' : 'left' ?>:3px solid var(--color-gold);">
                <strong style="font-size:.875rem;display:block;margin-bottom:4px;"><?= $isRTL ? 'ضمان الجودة' : 'Quality Guarantee' ?></strong>
                <span style="font-size:.825rem;color:var(--color-brown);"><?= $isRTL ? 'عرض سعر خلال 24 ساعة · تجهيز خلال 3-5 أيام · توصيل مضمون' : 'Quote within 24h · Ready in 3-5 days · Guaranteed delivery' ?></span>
            </div>
        </div>

        <!-- Quick Form -->
        <div class="animate" id="quick-form">
            <div class="quick-form-card">
                <h3><?= $isRTL ? 'اطلب هديتك المميزة' : 'Order Your Special Gift' ?></h3>
                <p class="sub"><?= $isRTL ? 'أرسل بياناتك وسنتواصل معك خلال 24 ساعة.' : 'Send your details and we\'ll contact you within 24 hours.' ?></p>
                <form method="GET" action="<?= SITE_URL ?>/pages/custom-order.php" style="display:flex;flex-direction:column;gap:12px;">
                    <input type="hidden" name="event_type" value="birthday">
                    <div class="form-row" style="gap:12px;">
                        <input type="text" name="customer_name" placeholder="<?= $isRTL ? 'اسمك *' : 'Your name *' ?>" required>
                        <input type="tel" name="phone" placeholder="<?= $isRTL ? 'رقم الهاتف *' : 'Phone *' ?>" required>
                    </div>
                    <div class="form-row" style="gap:12px;">
                        <input type="date" name="event_date">
                        <input type="number" name="quantity" min="1" placeholder="<?= $isRTL ? 'الكمية المطلوبة' : 'Quantity needed' ?>">
                    </div>
                    <select name="preferred_style">
                        <option value=""><?= $isRTL ? 'الستايل المفضل' : 'Preferred style' ?></option>
                        <option value="fun"><?= $isRTL ? 'مرح وملوّن' : 'Fun & Colourful' ?></option>
                        <option value="luxury"><?= $isRTL ? 'فاخر' : 'Luxury' ?></option>
                        <option value="romantic"><?= $isRTL ? 'رومانسي' : 'Romantic' ?></option>
                        <option value="minimal"><?= $isRTL ? 'مينيمال' : 'Minimal' ?></option>
                        <option value="arabic"><?= $isRTL ? 'عربي أصيل' : 'Arabic Style' ?></option>
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

<!-- Two Paths -->
<section class="section section-alt">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'ما الذي تبحث عنه؟' : 'What Are You Looking For?' ?></span>
        <h2><?= $isRTL ? 'نخدم كل الاحتياجات' : 'We Cover Every Need' ?></h2>
    </div>
    <div class="grid-2" style="gap:24px;">
        <div class="path-card animate" style="background:var(--color-gold);">
            <div style="font-size:2.8rem;margin-bottom:16px;">🎁</div>
            <h3 style="color:white;font-size:1.3rem;margin-bottom:10px;"><?= $isRTL ? 'هدية لشخص واحد' : 'Gift for One Person' ?></h3>
            <p style="color:rgba(255,255,255,.85);font-size:.9rem;margin-bottom:20px;line-height:1.6;">
                <?= $isRTL
                    ? 'اختر من متجرنا هدية جاهزة أو خصصها بالكامل — باسم وذوق وستايل الشخص المميز في حياتك.'
                    : 'Choose a ready gift from our shop or fully personalise it — to match the name, taste, and style of someone special.' ?>
            </p>
            <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline" style="background:white;border-color:white;color:var(--color-gold);display:inline-flex;">
                <i class="fas fa-shopping-bag"></i>
                <?= $isRTL ? 'تسوق الآن' : 'Shop Now' ?>
            </a>
        </div>
        <div class="path-card animate" style="background:var(--color-text);">
            <div style="font-size:2.8rem;margin-bottom:16px;">🎉</div>
            <h3 style="color:white;font-size:1.3rem;margin-bottom:10px;"><?= $isRTL ? 'توزيعات للحفلة' : 'Party Favours' ?></h3>
            <p style="color:rgba(232,213,176,.8);font-size:.9rem;margin-bottom:20px;line-height:1.6;">
                <?= $isRTL
                    ? 'احتفال كبير؟ نجهّز لك توزيعات عطرية للضيوف بأسعار الجملة وتصميم موحد ومميز.'
                    : 'Big celebration? We prepare fragrance favours for all your guests with bulk pricing and a unified, beautiful design.' ?>
            </p>
            <a href="<?= SITE_URL ?>/pages/custom-order.php?event_type=birthday" class="btn-primary" style="display:inline-flex;">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'اطلب توزيعات' : 'Order Favours' ?>
            </a>
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
        <h2><?= $isRTL ? 'هدايا أعياد الميلاد' : 'Birthday Gift Collection' ?></h2>
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

<!-- Inspiration Gallery -->
<section class="section<?= $products ? ' section-alt' : '' ?>">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'إلهام' : 'Inspiration' ?></span>
        <h2><?= $isRTL ? 'أفكار لهدايا عيد الميلاد' : 'Birthday Gift Ideas' ?></h2>
    </div>
    <?php
    $gallery = $isRTL ? [
        ['fa-cake-candles',      'باكدج عيد ميلاد متكامل'],
        ['fa-ribbon',            'تغليف احتفالي ملوّن'],
        ['fa-heart',             'زجاجة بريميوم مخصصة'],
        ['fa-envelope-open-text','كارت باسم المحتفل'],
        ['fa-gift',              'هدايا عيد ميلاد فاخرة'],
        ['fa-star',              'توزيعات حفلة مميزة'],
    ] : [
        ['fa-cake-candles',      'Complete Birthday Package'],
        ['fa-ribbon',            'Festive Colourful Wrapping'],
        ['fa-heart',             'Custom Premium Bottle'],
        ['fa-envelope-open-text','Personalised Birthday Card'],
        ['fa-gift',              'Luxury Birthday Gifts'],
        ['fa-star',              'Special Party Favours'],
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
        <span class="section-subtitle" style="color:var(--color-gold);opacity:1;"><?= $isRTL ? 'ابدأ الآن' : 'Get Started' ?></span>
        <h2 style="color:white;margin-bottom:14px;">
            <?= $isRTL ? 'اجعل عيد ميلاده لا يُنسى' : 'Make Their Birthday Unforgettable' ?>
        </h2>
        <p style="color:rgba(232,213,176,.8);margin-bottom:32px;max-width:500px;margin-inline:auto;">
            <?= $isRTL
                ? 'تواصل معنا ونصمم لك الهدية المثالية التي تعبّر عن مشاعرك بأجمل صورة.'
                : 'Contact us and we\'ll design the perfect gift that expresses your feelings in the most beautiful way.' ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php?event_type=birthday" class="btn-primary">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'اطلب هدية مخصصة' : 'Order a Custom Gift' ?>
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
