<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$featured    = getFeaturedProducts(8);
$occasions   = getCategories('occasion');
$homeGallery = getHomeGallery(8);
$heroImage   = getSetting('hero_image');

$occasionMeta = [
    'weddings'        => ['icon' => 'fa-rings-wedding', 'color' => '#C9A96E', 'bg' => '#2C1F14'],
    'engagements'     => ['icon' => 'fa-gem',           'color' => '#E8C4B8', 'bg' => '#5C1A2A'],
    'birthdays'       => ['icon' => 'fa-cake-candles',  'color' => '#C9A96E', 'bg' => '#3A2A1A'],
    'corporate-gifts' => ['icon' => 'fa-briefcase',     'color' => '#C9A96E', 'bg' => '#1A2A3A'],
    'valentine'       => ['icon' => 'fa-heart',         'color' => '#E8C4B8', 'bg' => '#5C1A2A'],
    'default'         => ['icon' => 'fa-gift',          'color' => '#C9A96E', 'bg' => '#2C1F14'],
];

// ── Admin-controlled homepage content (settings → fallback defaults) ──
$annEnabled = getSetting('announcement_enabled') !== '0';
$annRaw     = isRTL() ? getSetting('announcements_ar') : getSetting('announcements_en');
$annItems   = $annRaw
    ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $annRaw))))
    : (isRTL()
        ? ['شحن مجاني للطلبات فوق 500 جنيه', 'تغليف فاخر مجاناً مع كل طلب', 'تخصيص مجاني — اكتب اسمك']
        : ['Free Delivery on orders above 500 EGP', 'Free Luxury Packaging with every order', 'Free Personalisation — write your name']);

$heroBadge     = (isRTL() ? getSetting('hero_badge_ar')      : getSetting('hero_badge_en'))      ?: (isRTL() ? 'العلامة الأولى للهدايا العطرية في مصر' : 'Egypt\'s #1 Perfume Gift Brand');
$heroTitle1    = (isRTL() ? getSetting('hero_title_1_ar')    : getSetting('hero_title_1_en'))    ?: (isRTL() ? 'اجعل كل' : 'Make Every');
$heroTitleGold = (isRTL() ? getSetting('hero_title_gold_ar') : getSetting('hero_title_gold_en')) ?: (isRTL() ? 'مناسبة' : 'Occasion');
$heroTitle2    = (isRTL() ? getSetting('hero_title_2_ar')    : getSetting('hero_title_2_en'))    ?: (isRTL() ? 'لا تُنسى' : 'Unforgettable');
$heroDesc      = (isRTL() ? getSetting('hero_desc_ar')       : getSetting('hero_desc_en'))       ?: (isRTL()
    ? 'هدايا عطرية فاخرة مصنوعة بعناية لأفراحك وخطوباتك وأعياد ميلادك. كل هدية قصة تُروى.'
    : 'Handcrafted premium perfume gifts for weddings, engagements & birthdays. Every gift tells a story.');

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ════════════════════════════════════════
     1) ANNOUNCEMENT BAR (admin-controlled)
════════════════════════════════════════ -->
<?php if ($annEnabled && $annItems): ?>
<div class="announcement-bar">
    <div class="announcement-track">
        <?php /* duplicated once so the marquee loops seamlessly */ ?>
        <?php for ($loop = 0; $loop < 2; $loop++): ?>
            <?php foreach ($annItems as $ann): ?>
            <span>✦ <?= sanitize($ann) ?></span>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════
     2) HERO
════════════════════════════════════════ -->
<section class="hero-v2">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>

    <div class="container hero-v2-inner">

        <!-- Content -->
        <div class="hero-v2-content">
            <div class="hero-badge hero-anim-1">
                <i class="fas fa-star"></i>
                <?= sanitize($heroBadge) ?>
            </div>

            <h1 class="hero-v2-title hero-anim-2">
                <span class="hero-line-1"><?= sanitize($heroTitle1) ?></span>
                <span class="hero-line-gold"><?= sanitize($heroTitleGold) ?></span>
                <span class="hero-line-1"><?= sanitize($heroTitle2) ?></span>
            </h1>

            <p class="hero-v2-desc hero-anim-3">
                <?= sanitize($heroDesc) ?>
            </p>

            <div class="hero-v2-actions hero-anim-4">
                <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-primary btn-hero">
                    <i class="fas fa-shopping-bag"></i>
                    <?= $isRTL ? 'تسوق الآن' : 'Shop Now' ?>
                </a>
                <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-hero-outline">
                    <i class="fas fa-pen-nib"></i>
                    <?= $isRTL ? 'طلب مخصص' : 'Custom Order' ?>
                </a>
            </div>

            <div class="hero-trust hero-anim-5">
                <div class="hero-trust-item">
                    <i class="fas fa-shield-halved"></i>
                    <?= $isRTL ? 'جودة مضمونة' : 'Quality Guaranteed' ?>
                </div>
                <div class="hero-trust-item">
                    <i class="fas fa-truck-fast"></i>
                    <?= $isRTL ? 'توصيل سريع' : 'Fast Delivery' ?>
                </div>
                <div class="hero-trust-item">
                    <i class="fas fa-gift"></i>
                    <?= $isRTL ? 'تغليف فاخر' : 'Luxury Packing' ?>
                </div>
            </div>
        </div>

        <!-- Visual: uploaded image or decorative -->
        <div class="hero-v2-visual hero-anim-2<?= $heroImage ? ' has-image' : '' ?>">
            <?php if ($heroImage): ?>
            <div class="hero-image-frame">
                <img src="<?= UPLOAD_URL . sanitize($heroImage) ?>"
                     alt="<?= $isRTL ? 'هدايا تذكار' : 'TITHKAR gifts' ?>">
                <div class="hero-image-shine"></div>

                <div class="hero-float-badge hfb-1">
                    <i class="fas fa-star" style="color:#C9A96E;"></i>
                    <?= $isRTL ? '١٠٠٠+ عميل' : '1000+ Clients' ?>
                </div>
                <div class="hero-float-badge hfb-2">
                    <i class="fas fa-gift" style="color:#8B1A2E;"></i>
                    <?= $isRTL ? 'تخصيص مجاني' : 'Free Custom' ?>
                </div>
            </div>
            <?php else: ?>
            <div class="hero-visual-card">
                <div class="hero-visual-inner">
                    <i class="fas fa-spray-can-sparkles hero-visual-icon"></i>
                    <div class="hero-visual-rings">
                        <div class="ring ring-1"></div>
                        <div class="ring ring-2"></div>
                        <div class="ring ring-3"></div>
                    </div>
                </div>
                <div class="hero-float-badge hfb-1">
                    <i class="fas fa-star" style="color:#C9A96E;"></i>
                    <?= $isRTL ? '١٠٠٠+ عميل' : '1000+ Clients' ?>
                </div>
                <div class="hero-float-badge hfb-2">
                    <i class="fas fa-gift" style="color:#8B1A2E;"></i>
                    <?= $isRTL ? 'تخصيص مجاني' : 'Free Custom' ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Scroll indicator -->
    <div class="hero-scroll-indicator">
        <div class="scroll-mouse"><div class="scroll-wheel"></div></div>
    </div>
</section>

<!-- ════════════════════════════════════════
     3) STATS STRIP (light, elegant)
════════════════════════════════════════ -->
<section class="stats-strip">
    <div class="container">
        <div class="stats-grid">
            <?php
            $stats = $isRTL
                ? [
                    ['fa-users','1000','+','عميل سعيد'],
                    ['fa-box-open','50','+','منتج فاخر'],
                    ['fa-calendar-check','5','','سنوات خبرة'],
                    ['fa-star','4.9','/5','تقييم العملاء'],
                  ]
                : [
                    ['fa-users','1000','+','Happy Clients'],
                    ['fa-box-open','50','+','Premium Products'],
                    ['fa-calendar-check','5','','Years Experience'],
                    ['fa-star','4.9','/5','Customer Rating'],
                  ];
            foreach ($stats as $i => [$icon, $num, $suffix, $label]):
            ?>
            <div class="stat-item reveal reveal-delay-<?= $i + 1 ?>">
                <div class="stat-icon"><i class="fas <?= $icon ?>"></i></div>
                <div class="stat-number" data-target="<?= $num ?>">
                    <span class="stat-count">0</span><?= $suffix ?>
                </div>
                <div class="stat-label"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     4) SHOP BY OCCASION
════════════════════════════════════════ -->
<?php if ($occasions): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-title reveal">
            <span class="section-subtitle"><?= $isRTL ? 'لكل مناسبة هديتها' : 'For Every Occasion' ?></span>
            <h2><?= $isRTL ? 'تسوق حسب المناسبة' : 'Shop by Occasion' ?></h2>
            <p><?= $isRTL ? 'هدايا مصممة خصيصاً لكل لحظة مميزة في حياتك' : 'Gifts designed especially for every special moment in your life' ?></p>
        </div>
        <div class="occasions-grid-v2">
            <?php
            $occasionLinks = [
                'weddings'        => SITE_URL . '/pages/weddings.php',
                'engagements'     => SITE_URL . '/pages/engagements.php',
                'birthdays'       => SITE_URL . '/pages/birthdays.php',
                'corporate-gifts' => SITE_URL . '/pages/corporate.php',
                'valentine'       => SITE_URL . '/pages/events.php',
            ];
            foreach ($occasions as $i => $occ):
                $meta = $occasionMeta[$occ['slug']] ?? $occasionMeta['default'];
                $link = $occasionLinks[$occ['slug']] ?? SITE_URL . '/pages/events.php';
            ?>
            <a href="<?= $link ?>" class="occasion-card-v2 reveal reveal-delay-<?= (($i % 3) + 1) ?>">
                <div class="occ-icon-wrap">
                    <i class="fas <?= $meta['icon'] ?>"></i>
                </div>
                <div class="occ-content">
                    <h3><?= sanitize($isRTL ? $occ['name_ar'] : $occ['name_en']) ?></h3>
                    <span><?= $isRTL ? 'اكتشف الهدايا' : 'Explore Gifts' ?></span>
                </div>
                <div class="occ-arrow">
                    <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════
     5) FEATURED PRODUCTS
════════════════════════════════════════ -->
<section class="section products-section">
    <div class="container">
        <div class="section-title reveal">
            <span class="section-subtitle"><?= $isRTL ? 'الأكثر طلباً' : 'Best Sellers' ?></span>
            <h2><?= $isRTL ? 'منتجاتنا المميزة' : 'Featured Products' ?></h2>
            <p><?= $isRTL ? 'اختر هديتك المثالية من مجموعتنا الفاخرة' : 'Choose your perfect gift from our premium collection' ?></p>
        </div>

        <?php if ($featured): ?>
        <div class="products-grid">
            <?php foreach ($featured as $i => $product): ?>
            <div class="product-card-v2 reveal reveal-delay-<?= (($i % 4) + 1) ?>">

                <div class="product-card-image-wrap">
                    <?php if (!empty($product['main_image'])): ?>
                    <img src="<?= UPLOAD_URL . sanitize($product['main_image']) ?>"
                         alt="<?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>"
                         class="product-card-image" loading="lazy">
                    <?php else: ?>
                    <div class="product-card-placeholder">
                        <i class="fas fa-spray-can-sparkles"></i>
                    </div>
                    <?php endif; ?>

                    <div class="product-badges">
                        <?php if ($product['sale_price']): ?>
                        <span class="pbadge pbadge-sale"><?= $isRTL ? 'خصم' : 'Sale' ?></span>
                        <?php endif; ?>
                        <?php if ($product['stock_status'] === 'in_stock'): ?>
                        <span class="pbadge pbadge-stock"><?= $isRTL ? 'متوفر' : 'In Stock' ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-overlay">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($product['slug']) ?>"
                           class="overlay-btn">
                            <i class="fas fa-eye"></i>
                            <?= $isRTL ? 'عرض التفاصيل' : 'View Details' ?>
                        </a>
                    </div>
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
                            <?php if ($product['sale_price']): ?>
                            <span class="product-sale-price"><?= formatPrice((float)$product['price']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="product-price-request">
                                <i class="fas fa-tag"></i>
                                <?= $isRTL ? 'السعر بالتفاوض' : 'Price on request' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="product-actions-v2">
                        <?php if (in_array($product['order_mode'], ['buy_now', 'both'])): ?>
                        <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="cart-form" style="flex:1;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <button type="submit" class="btn-primary btn-sm add-to-cart-btn" style="width:100%;justify-content:center;">
                                <i class="fas fa-shopping-bag"></i>
                                <?= $isRTL ? 'أضف للسلة' : 'Add to Cart' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if (in_array($product['order_mode'], ['request_quote', 'both'])): ?>
                        <a href="<?= getWhatsAppLink(($isRTL ? 'أريد الاستفسار عن: ' : 'Inquiry about: ').sanitize($isRTL ? $product['name_ar'] : $product['name_en'])) ?>"
                           class="btn-wa-sm" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="empty-state reveal">
            <i class="fas fa-spray-can-sparkles"></i>
            <h3><?= $isRTL ? 'قريباً...' : 'Coming Soon...' ?></h3>
            <p><?= $isRTL ? 'نحضّر لكم مجموعة رائعة' : 'We\'re preparing an amazing collection' ?></p>
        </div>
        <?php endif; ?>

        <div class="text-center mt-40 reveal">
            <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-primary" style="padding:16px 48px;font-size:1.05rem;">
                <i class="fas fa-store"></i>
                <?= $isRTL ? 'عرض كل المنتجات' : 'View All Products' ?>
            </a>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     6) CATALOG GALLERY (admin-managed)
════════════════════════════════════════ -->
<?php if ($homeGallery): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-title reveal">
            <span class="section-subtitle"><?= $isRTL ? 'من أعمالنا' : 'Our Work' ?></span>
            <h2><?= $isRTL ? 'معرض الكتالوج' : 'Catalog Gallery' ?></h2>
            <p><?= $isRTL ? 'لمحة من هدايانا الحقيقية التي صنعناها لعملائنا' : 'A glimpse of the real gifts we crafted for our clients' ?></p>
        </div>

        <div class="home-gallery-grid">
            <?php foreach ($homeGallery as $i => $g): ?>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="hg-item reveal reveal-delay-<?= ($i % 4) + 1 ?>">
                <img src="<?= SITE_URL ?>/assets/images/gallery/<?= sanitize($g['image_url']) ?>"
                     alt="<?= sanitize($g['title'] ?: 'TITHKAR') ?>" loading="lazy">
                <div class="hg-overlay">
                    <?php if (!empty($g['title'])): ?>
                    <span class="hg-title"><?= sanitize($g['title']) ?></span>
                    <?php endif; ?>
                    <span class="hg-zoom"><i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-40 reveal">
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn-outline" style="padding:14px 44px;font-size:1rem;">
                <i class="fas fa-images"></i>
                <?= $isRTL ? 'تصفح الكتالوج كاملاً' : 'Browse Full Catalog' ?>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════
     7) PROMO BANNER (dark accent)
════════════════════════════════════════ -->
<section class="promo-banner reveal">
    <div class="promo-banner-inner">
        <div class="promo-content">
            <span class="promo-tag">
                <i class="fas fa-bolt"></i>
                <?= $isRTL ? 'عرض حصري' : 'Exclusive Offer' ?>
            </span>
            <h2><?= $isRTL ? 'اطلب هديتك المخصصة اليوم' : 'Order Your Custom Gift Today' ?></h2>
            <p><?= $isRTL ? 'تخصيص مجاني + تغليف فاخر + توصيل للمنزل' : 'Free customisation + luxury packaging + home delivery' ?></p>
            <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-promo">
                <?= $isRTL ? 'ابدأ الآن' : 'Start Now' ?>
                <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>"></i>
            </a>
        </div>
        <div class="promo-decoration">
            <div class="promo-circle c1"><i class="fas fa-gift"></i></div>
            <div class="promo-circle c2"><i class="fas fa-spray-can-sparkles"></i></div>
            <div class="promo-circle c3"><i class="fas fa-heart"></i></div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     8) HOW IT WORKS
════════════════════════════════════════ -->
<section class="section how-section">
    <div class="container">
        <div class="section-title reveal">
            <span class="section-subtitle"><?= $isRTL ? 'بكل بساطة' : 'Simple Steps' ?></span>
            <h2><?= $isRTL ? 'كيف يعمل تذكار؟' : 'How TITHKAR Works' ?></h2>
        </div>
        <div class="how-grid">
            <?php
            $steps = $isRTL ? [
                ['fa-magnifying-glass','01','اختر هديتك','تصفح مجموعتنا الفاخرة واختر المنتج المناسب'],
                ['fa-pen-fancy','02','خصّص طلبك','أضف اسمك وتاريخك لمسة شخصية مميزة'],
                ['fa-credit-card','03','ادفع بأمان','ادفع بطريقتك المفضلة بأمان تام'],
                ['fa-gift','04','استلم بالأناقة','نوصل هديتك في تغليف فاخر في الموعد'],
            ] : [
                ['fa-magnifying-glass','01','Choose Your Gift','Browse our collection and pick the perfect product'],
                ['fa-pen-fancy','02','Personalise It','Add names and dates for a unique touch'],
                ['fa-credit-card','03','Pay Securely','Pay with your preferred method safely'],
                ['fa-gift','04','Receive in Style','Your gift arrives in luxury packaging, on time'],
            ];
            foreach ($steps as $i => [$icon, $num, $title, $desc]):
            ?>
            <div class="how-step reveal reveal-delay-<?= $i + 1 ?>">
                <div class="how-step-icon">
                    <i class="fas <?= $icon ?>"></i>
                    <span class="how-step-num"><?= $num ?></span>
                </div>
                <h3><?= $title ?></h3>
                <p><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     9) TESTIMONIALS
════════════════════════════════════════ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-title reveal">
            <span class="section-subtitle"><?= $isRTL ? 'آراء عملائنا' : 'Customer Reviews' ?></span>
            <h2><?= $isRTL ? 'ماذا يقول عملاؤنا؟' : 'What Our Clients Say' ?></h2>
        </div>
        <div class="testimonials-grid">
            <?php
            $testimonials = $isRTL ? [
                ['سارة م.','القاهرة','هدية زفافي كانت خيالية! التغليف فاخر جداً والعطر كان بالضبط اللي طلبته. شكراً تذكار!','fa-rings-wedding'],
                ['أحمد ر.','الجيزة','اشتريت هدايا شركة لفريقي. الجودة ممتازة والتوصيل في الموعد. هنتعامل معاهم دايماً.','fa-briefcase'],
                ['نور ع.','القاهرة الجديدة','أحسن هدية خطوبة! كل الضيوف سألوا عنها. الاهتمام بالتفاصيل رائع.','fa-gem'],
            ] : [
                ['Sarah M.','Cairo','My wedding gift was absolutely magical! The packaging is so luxurious and the scent was exactly what I asked for. Thank you TITHKAR!','fa-rings-wedding'],
                ['Ahmed R.','Giza','I ordered corporate gifts for my team. Excellent quality and on-time delivery. We will always work with them.','fa-briefcase'],
                ['Nour A.','New Cairo','Best engagement gift I ever received! All guests asked about it. The attention to detail is amazing.','fa-gem'],
            ];
            foreach ($testimonials as $i => [$name, $city, $review, $icon]):
            ?>
            <div class="testimonial-card reveal reveal-delay-<?= $i + 1 ?>">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">"<?= $review ?>"</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div>
                        <strong><?= $name ?></strong>
                        <span><?= $city ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     10) WHATSAPP CTA (dark, final)
════════════════════════════════════════ -->
<section class="wa-cta-section">
    <div class="wa-cta-bg-shapes">
        <div class="wa-shape wa-shape-1"></div>
        <div class="wa-shape wa-shape-2"></div>
    </div>
    <div class="container">
        <div class="wa-cta-inner reveal">
            <div class="wa-cta-icon-wrap">
                <div class="wa-cta-icon-ring"></div>
                <i class="fab fa-whatsapp wa-cta-icon"></i>
            </div>
            <div class="wa-cta-content">
                <span class="section-subtitle" style="color:var(--color-gold);">
                    <?= $isRTL ? 'نحن هنا من أجلك' : 'We\'re Here For You' ?>
                </span>
                <h2><?= $isRTL ? 'تكلم معنا على واتساب الآن' : 'Chat With Us on WhatsApp Now' ?></h2>
                <p><?= $isRTL
                    ? 'فريقنا متاح على مدار الساعة للإجابة على أسئلتك ومساعدتك في اختيار الهدية المثالية.'
                    : 'Our team is available around the clock to answer your questions and help you find the perfect gift.' ?>
                </p>
                <div class="wa-cta-actions">
                    <a href="<?= $waLink ?>" class="btn-wa-large" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp"></i>
                        <?= $isRTL ? 'ابدأ المحادثة الآن' : 'Start Conversation Now' ?>
                    </a>
                    <a href="<?= SITE_URL ?>/pages/contact.php" class="btn-wa-outline">
                        <?= $isRTL ? 'تواصل معنا' : 'Contact Us' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════
     HOME PAGE STYLES
════════════════════════════════════════ -->
<style>
/* ── 1) Announcement Bar ──────────────── */
.announcement-bar{background:var(--color-text);color:var(--color-gold-light);padding:10px 0;overflow:hidden;white-space:nowrap;}
.announcement-track{display:inline-block;animation:ticker 30s linear infinite;font-size:.85rem;letter-spacing:.03em;}
.announcement-track span{padding:0 40px;}
@keyframes ticker{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* ── 2) Hero ──────────────────────────── */
.hero-v2{min-height:88vh;background:linear-gradient(135deg,var(--color-bg) 0%,var(--color-surface) 60%,#EDE4D9 100%);position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;}
.hero-orb{position:absolute;border-radius:50%;pointer-events:none;}
.hero-orb-1{width:600px;height:600px;background:radial-gradient(circle,rgba(201,169,110,.12) 0%,transparent 70%);top:-150px;inset-inline-end:-100px;animation:floatOrb 8s ease-in-out infinite;}
.hero-orb-2{width:400px;height:400px;background:radial-gradient(circle,rgba(139,26,46,.07) 0%,transparent 70%);bottom:-80px;inset-inline-start:5%;animation:floatOrb 10s ease-in-out infinite reverse;}
@keyframes floatOrb{0%,100%{transform:translate(0,0)}33%{transform:translate(20px,-20px)}66%{transform:translate(-15px,15px)}}

.hero-v2-inner{display:grid;grid-template-columns:1.1fr .9fr;gap:50px;align-items:center;padding:70px 20px;position:relative;z-index:1;}

.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(201,169,110,.15);border:1px solid rgba(201,169,110,.35);color:var(--color-gold);padding:8px 18px;border-radius:30px;font-size:.85rem;font-weight:500;margin-bottom:24px;}
.hero-badge i{font-size:.75rem;}

.hero-v2-title{font-size:clamp(2.4rem,5vw,3.8rem);font-weight:300;line-height:1.15;margin-bottom:20px;display:flex;flex-direction:column;gap:4px;}
.hero-line-1{color:var(--color-text);}
.hero-line-gold{color:var(--color-gold);font-style:italic;font-weight:600;}

.hero-v2-desc{font-size:1.1rem;color:var(--color-brown);margin-bottom:32px;max-width:480px;line-height:1.8;}

.hero-v2-actions{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:32px;}
.btn-hero{padding:16px 36px;font-size:1.05rem;border-radius:50px;}
.btn-hero-outline{display:inline-flex;align-items:center;gap:8px;padding:15px 34px;border:2px solid var(--color-gold);color:var(--color-gold);border-radius:50px;font-size:1.05rem;font-weight:500;transition:all .3s ease;background:transparent;font-family:var(--font-ar);}
.btn-hero-outline:hover{background:var(--color-gold);color:#fff;transform:translateY(-2px);}

.hero-trust{display:flex;gap:24px;flex-wrap:wrap;padding-top:24px;border-top:1px solid rgba(201,169,110,.25);}
.hero-trust-item{display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--color-brown);}
.hero-trust-item i{color:var(--color-gold);font-size:.9rem;}

/* Hero visual — decorative */
.hero-v2-visual{display:flex;justify-content:center;align-items:center;position:relative;}
.hero-visual-card{width:340px;height:340px;background:linear-gradient(135deg,rgba(201,169,110,.2) 0%,rgba(232,213,176,.15) 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;position:relative;border:1px solid rgba(201,169,110,.25);}
.hero-visual-inner{position:relative;display:flex;align-items:center;justify-content:center;}
.hero-visual-icon{font-size:5rem;color:var(--color-gold);animation:float 4s ease-in-out infinite;filter:drop-shadow(0 8px 24px rgba(201,169,110,.4));}
.hero-visual-rings{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;}
.ring{position:absolute;border-radius:50%;border:1px solid rgba(201,169,110,.25);}
.ring-1{width:120px;height:120px;animation:ringPulse 3s ease-in-out infinite;}
.ring-2{width:180px;height:180px;animation:ringPulse 3s ease-in-out infinite .5s;}
.ring-3{width:240px;height:240px;animation:ringPulse 3s ease-in-out infinite 1s;}
@keyframes ringPulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.05);opacity:1}}

/* Hero visual — uploaded image (arch frame + ken-burns + float + shine) */
.hero-image-frame{
    position:relative;width:360px;height:450px;
    border-radius:180px 180px 22px 22px;
    animation:heroImgFloat 6s ease-in-out infinite;
}
.hero-image-frame::before{
    content:'';position:absolute;inset:-14px;
    border:1.5px solid rgba(201,169,110,.45);
    border-radius:194px 194px 30px 30px;
    animation:ringPulse 3.5s ease-in-out infinite;pointer-events:none;
}
.hero-image-frame img{
    width:100%;height:100%;object-fit:cover;
    border-radius:180px 180px 22px 22px;
    box-shadow:0 30px 80px rgba(107,79,58,.3);
    animation:kenBurns 16s ease-in-out infinite alternate;
}
.hero-image-shine{position:absolute;inset:0;border-radius:180px 180px 22px 22px;overflow:hidden;pointer-events:none;}
.hero-image-shine::after{
    content:'';position:absolute;top:0;left:-80%;width:50%;height:100%;
    background:linear-gradient(115deg,transparent 0%,rgba(255,255,255,.35) 50%,transparent 100%);
    transform:skewX(-15deg);animation:heroShine 5s ease-in-out infinite;
}
@keyframes kenBurns{from{transform:scale(1)}to{transform:scale(1.12)}}
@keyframes heroImgFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
@keyframes heroShine{0%,60%{left:-80%}90%,100%{left:130%}}

.hero-float-badge{position:absolute;background:white;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:8px;font-size:.8rem;font-weight:600;color:var(--color-text);box-shadow:0 8px 30px rgba(0,0,0,.12);white-space:nowrap;z-index:2;}
.hfb-1{top:-10px;inset-inline-end:0;animation:floatBadge 4s ease-in-out infinite;}
.hfb-2{bottom:30px;inset-inline-start:-20px;animation:floatBadge 4s ease-in-out infinite 1.5s;}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}

/* Scroll indicator */
.hero-scroll-indicator{position:absolute;bottom:28px;left:50%;transform:translateX(-50%);animation:fadeInUp 1s 1.5s both;}
.scroll-mouse{width:26px;height:42px;border:2px solid rgba(201,169,110,.5);border-radius:13px;display:flex;justify-content:center;padding-top:6px;}
.scroll-wheel{width:4px;height:8px;background:var(--color-gold);border-radius:2px;animation:scrollWheel 1.8s ease-in-out infinite;}
@keyframes scrollWheel{0%{transform:translateY(0);opacity:1}100%{transform:translateY(14px);opacity:0}}

/* Hero entrance animations */
.hero-anim-1{animation:fadeInUp .8s .1s both;}
.hero-anim-2{animation:fadeInUp .8s .25s both;}
.hero-anim-3{animation:fadeInUp .8s .4s both;}
.hero-anim-4{animation:fadeInUp .8s .55s both;}
.hero-anim-5{animation:fadeInUp .8s .7s both;}

/* ── 3) Stats Strip (light & elegant) ─── */
.stats-strip{background:#fff;padding:44px 20px;border-bottom:1px solid var(--color-border);}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);max-width:var(--container-width);margin:0 auto;}
.stat-item{text-align:center;padding:10px 20px;position:relative;}
.stat-item:not(:last-child)::after{
    content:'';position:absolute;top:50%;inset-inline-end:0;transform:translateY(-50%);
    width:1px;height:54px;background:var(--color-border);
}
.stat-icon{font-size:1.3rem;color:var(--color-gold);margin-bottom:10px;opacity:.85;}
.stat-number{font-family:var(--font-en);font-size:2.4rem;font-weight:700;color:var(--color-text);line-height:1;}
.stat-number .stat-count{color:var(--color-gold);}
.stat-label{color:var(--color-brown);font-size:.88rem;margin-top:8px;}

/* ── 4) Occasions ─────────────────────── */
.occasions-grid-v2{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.occasion-card-v2{display:flex;align-items:center;gap:20px;padding:26px 24px;background:white;border-radius:16px;border:1px solid var(--color-border);position:relative;overflow:hidden;transition:all .4s cubic-bezier(.22,1,.36,1);}
.occasion-card-v2:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.1);border-color:var(--color-gold);}
.occ-icon-wrap{width:58px;height:58px;border-radius:50%;background:rgba(201,169,110,.1);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--color-gold);flex-shrink:0;transition:all .4s ease;}
.occasion-card-v2:hover .occ-icon-wrap{background:var(--color-gold);color:white;transform:scale(1.1) rotate(5deg);}
.occ-content{flex:1;}
.occ-content h3{font-size:1.05rem;color:var(--color-text);margin-bottom:4px;}
.occ-content span{font-size:.82rem;color:var(--color-brown);}
.occ-arrow{color:var(--color-gold);font-size:1rem;opacity:.5;transition:all .3s ease;}
.occasion-card-v2:hover .occ-arrow{opacity:1;transform:translateX(-5px);}
body.ltr .occasion-card-v2:hover .occ-arrow{transform:translateX(5px);}

/* ── 5) Products ──────────────────────── */
.products-section{background:var(--color-bg);}
.products-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
.product-card-v2{background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:transform .4s cubic-bezier(.22,1,.36,1),box-shadow .4s cubic-bezier(.22,1,.36,1);position:relative;}
.product-card-v2:hover{transform:translateY(-10px);box-shadow:0 24px 56px rgba(0,0,0,.13);}
.product-card-v2 .product-card-image-wrap{position:relative;aspect-ratio:1/1;overflow:hidden;background:var(--color-surface);}
.product-card-v2 .product-card-image{width:100%;height:100%;object-fit:cover;transition:transform .6s cubic-bezier(.22,1,.36,1);}
.product-card-v2:hover .product-card-image{transform:scale(1.09);}
.product-card-v2 .product-card-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3.5rem;color:var(--color-gold-light);}

.product-badges{position:absolute;top:10px;inset-inline-end:10px;display:flex;flex-direction:column;gap:5px;z-index:2;}
.pbadge{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;}
.pbadge-sale{background:#E53935;color:white;}
.pbadge-stock{background:rgba(46,125,50,.9);color:white;}

.product-overlay{position:absolute;inset:0;background:rgba(44,31,20,.5);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s ease;}
.product-card-v2:hover .product-overlay{opacity:1;}
.overlay-btn{background:white;color:var(--color-text);padding:10px 20px;border-radius:30px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:6px;transform:translateY(10px);transition:transform .3s ease;font-family:var(--font-ar);}
.product-card-v2:hover .overlay-btn{transform:translateY(0);}

.product-card-v2 .product-card-body{padding:18px;}
.product-card-v2 .product-name{font-size:.95rem;margin-bottom:8px;}
.product-actions-v2{display:flex;gap:8px;margin-top:12px;align-items:center;}
.btn-wa-sm{width:40px;height:40px;border-radius:50%;background:#25D366;color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .3s ease,box-shadow .3s ease;font-size:1.1rem;}
.btn-wa-sm:hover{transform:scale(1.12);box-shadow:0 6px 20px rgba(37,211,102,.4);}

/* ── 6) Catalog Gallery ───────────────── */
.home-gallery-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
.hg-item{position:relative;display:block;aspect-ratio:1/1;border-radius:14px;overflow:hidden;background:#fff;transition:transform .45s cubic-bezier(.22,1,.36,1),box-shadow .45s ease;}
.hg-item:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(107,79,58,.18);}
.hg-item img{width:100%;height:100%;object-fit:cover;transition:transform .65s cubic-bezier(.22,1,.36,1);}
.hg-item:hover img{transform:scale(1.1);}
.hg-overlay{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:flex-end;padding:16px;gap:6px;background:linear-gradient(to top,rgba(44,31,20,.75) 0%,transparent 55%);opacity:0;transition:opacity .35s ease;}
.hg-item:hover .hg-overlay{opacity:1;}
.hg-title{color:#fff;font-size:.88rem;font-weight:600;transform:translateY(8px);transition:transform .35s ease;}
.hg-item:hover .hg-title{transform:translateY(0);}
.hg-zoom{position:absolute;top:14px;inset-inline-end:14px;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.92);color:var(--color-gold);display:flex;align-items:center;justify-content:center;font-size:.8rem;transform:scale(0);transition:transform .35s cubic-bezier(.22,1,.36,1) .08s;}
.hg-item:hover .hg-zoom{transform:scale(1);}

/* ── 7) Promo Banner ──────────────────── */
.promo-banner{background:linear-gradient(135deg,#2C1F14 0%,#4A3020 50%,#2C1F14 100%);position:relative;overflow:hidden;}
.promo-banner-inner{display:grid;grid-template-columns:1fr auto;align-items:center;gap:40px;max-width:var(--container-width);margin:0 auto;padding:60px 40px;}
.promo-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(201,169,110,.2);border:1px solid rgba(201,169,110,.4);color:var(--color-gold);padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;margin-bottom:14px;}
.promo-content h2{color:white;font-size:2rem;margin-bottom:10px;}
.promo-content p{color:rgba(232,213,176,.75);margin-bottom:24px;}
.btn-promo{display:inline-flex;align-items:center;gap:10px;background:var(--color-gold);color:#2C1F14;padding:14px 32px;border-radius:50px;font-weight:700;font-size:1rem;font-family:var(--font-ar);transition:all .3s ease;}
.btn-promo:hover{background:#E8C07A;transform:translateY(-2px);box-shadow:0 8px 24px rgba(201,169,110,.4);}
.promo-decoration{display:flex;gap:20px;align-items:center;}
.promo-circle{width:80px;height:80px;border-radius:50%;background:rgba(201,169,110,.1);border:1px solid rgba(201,169,110,.2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--color-gold);}
.promo-circle.c1{animation:float 4s ease-in-out infinite;}
.promo-circle.c2{animation:float 4s ease-in-out infinite 1.3s;}
.promo-circle.c3{animation:float 4s ease-in-out infinite 2.6s;}

/* ── 8) How It Works ──────────────────── */
.how-section{background:#fff;}
.how-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
.how-step{text-align:center;padding:36px 22px;border-radius:16px;border:1px solid transparent;transition:all .35s ease;}
.how-step:hover{border-color:var(--color-border);background:var(--color-bg);box-shadow:0 12px 36px rgba(107,79,58,.08);}
.how-step-icon{position:relative;width:78px;height:78px;border-radius:50%;background:rgba(201,169,110,.1);border:2px solid rgba(201,169,110,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.7rem;color:var(--color-gold);transition:all .4s ease;}
.how-step:hover .how-step-icon{background:var(--color-gold);color:white;transform:scale(1.08);box-shadow:0 12px 30px rgba(201,169,110,.4);}
.how-step-num{position:absolute;top:-8px;inset-inline-end:-8px;width:26px;height:26px;background:var(--color-gold);color:white;border-radius:50%;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;font-family:var(--font-en);}
.how-step h3{font-size:1rem;margin-bottom:10px;color:var(--color-text);}
.how-step p{font-size:.85rem;color:var(--color-brown);line-height:1.7;}

/* ── 9) Testimonials ──────────────────── */
.testimonials-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
.testimonial-card{background:white;border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:all .4s ease;border:1px solid var(--color-border);}
.testimonial-card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.1);border-color:var(--color-gold);}
.testimonial-stars{color:#F59E0B;font-size:.9rem;margin-bottom:14px;display:flex;gap:3px;}
.testimonial-text{font-size:.92rem;line-height:1.8;color:var(--color-brown);margin-bottom:20px;font-style:italic;}
.testimonial-author{display:flex;align-items:center;gap:14px;}
.testimonial-avatar{width:44px;height:44px;border-radius:50%;background:rgba(201,169,110,.15);display:flex;align-items:center;justify-content:center;color:var(--color-gold);font-size:1.1rem;flex-shrink:0;}
.testimonial-author strong{display:block;font-size:.9rem;color:var(--color-text);}
.testimonial-author span{font-size:.8rem;color:var(--color-brown);}

/* ── 10) WhatsApp CTA ─────────────────── */
.wa-cta-section{background:linear-gradient(135deg,#1A1009 0%,#2C1F14 50%,#1A1009 100%);padding:80px 20px;position:relative;overflow:hidden;}
.wa-cta-bg-shapes{position:absolute;inset:0;pointer-events:none;}
.wa-shape{position:absolute;border-radius:50%;background:rgba(201,169,110,.06);}
.wa-shape-1{width:500px;height:500px;top:-200px;inset-inline-end:-100px;}
.wa-shape-2{width:350px;height:350px;bottom:-150px;inset-inline-start:-50px;}
.wa-cta-inner{display:flex;align-items:center;gap:60px;position:relative;z-index:1;}
.wa-cta-icon-wrap{position:relative;flex-shrink:0;}
.wa-cta-icon-ring{position:absolute;inset:-20px;border-radius:50%;border:2px solid rgba(37,211,102,.3);animation:ringPulse 2.5s ease-in-out infinite;}
.wa-cta-icon{font-size:5rem;color:#25D366;display:block;animation:float 4s ease-in-out infinite;filter:drop-shadow(0 8px 24px rgba(37,211,102,.5));}
.wa-cta-content{flex:1;}
.wa-cta-content h2{color:white;margin-bottom:12px;}
.wa-cta-content p{color:rgba(232,213,176,.75);margin-bottom:28px;max-width:500px;}
.wa-cta-actions{display:flex;gap:16px;flex-wrap:wrap;}
.btn-wa-large{display:inline-flex;align-items:center;gap:10px;background:#25D366;color:white;padding:16px 36px;border-radius:50px;font-size:1.05rem;font-weight:700;font-family:var(--font-ar);transition:all .3s ease;}
.btn-wa-large:hover{background:#1EBE59;transform:translateY(-2px);box-shadow:0 10px 30px rgba(37,211,102,.5);}
.btn-wa-large i{font-size:1.3rem;}
.btn-wa-outline{display:inline-flex;align-items:center;padding:15px 32px;border:2px solid rgba(201,169,110,.4);color:var(--color-gold-light);border-radius:50px;font-size:1rem;font-family:var(--font-ar);transition:all .3s ease;}
.btn-wa-outline:hover{border-color:var(--color-gold);color:var(--color-gold);background:rgba(201,169,110,.1);}

/* ── Responsive ───────────────────────── */
@media(max-width:1024px){
  .products-grid{grid-template-columns:repeat(2,1fr);}
  .home-gallery-grid{grid-template-columns:repeat(3,1fr);}
  .occasions-grid-v2{grid-template-columns:repeat(2,1fr);}
  .how-grid{grid-template-columns:repeat(2,1fr);}
  .hero-v2-inner{grid-template-columns:1fr;text-align:center;padding:50px 20px;}
  .hero-v2-visual{display:none;}
  .hero-v2-visual.has-image{display:flex;order:-1;margin-bottom:6px;}
  .hero-v2-visual.has-image .hero-image-frame{width:230px;height:290px;border-radius:115px 115px 16px 16px;}
  .hero-v2-visual.has-image .hero-image-frame::before{border-radius:127px 127px 22px 22px;}
  .hero-v2-visual.has-image .hero-image-frame img,
  .hero-v2-visual.has-image .hero-image-shine{border-radius:115px 115px 16px 16px;}
  .hero-v2-visual.has-image .hero-float-badge{font-size:.7rem;padding:7px 10px;}
  .hero-trust{justify-content:center;}
  .hero-v2-actions{justify-content:center;}
  .hero-v2-desc{margin-inline:auto;}
  .wa-cta-inner{flex-direction:column;text-align:center;}
  .wa-cta-actions{justify-content:center;}
  .wa-cta-content p{margin-inline:auto;}
  .promo-banner-inner{grid-template-columns:1fr;text-align:center;}
  .promo-decoration{display:none;}
}
@media(max-width:768px){
  .stats-grid{grid-template-columns:repeat(2,1fr);row-gap:24px;}
  .stat-item:nth-child(2)::after{display:none;}
  .products-grid{grid-template-columns:1fr 1fr;gap:14px;}
  .home-gallery-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
  .how-grid,.testimonials-grid{grid-template-columns:1fr;}
  .occasions-grid-v2{grid-template-columns:1fr;}
  .hero-badge{font-size:.78rem;}
  .promo-banner-inner{padding:44px 20px;}
  .promo-content h2{font-size:1.4rem;}
}
@media(max-width:480px){
  .products-grid{grid-template-columns:1fr;}
}
</style>

<script>
/* ── Counter animation (stats strip) ──── */
document.addEventListener('DOMContentLoaded', function () {
    var counters = document.querySelectorAll('.stat-count');
    var started  = false;

    function startCounting() {
        if (started) return;
        started = true;
        counters.forEach(function (el) {
            var parent = el.closest('.stat-number');
            var target = parseFloat(parent.dataset.target);
            var isDecimal = target % 1 !== 0;
            var duration = 1800;
            var step = target / (duration / 16);
            var current = 0;

            var timer = setInterval(function () {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
            }, 16);
        });
    }

    var strip = document.querySelector('.stats-strip');
    if (strip && 'IntersectionObserver' in window) {
        var sio = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) { startCounting(); sio.disconnect(); }
        }, { threshold: 0.3 });
        sio.observe(strip);
    } else {
        startCounting();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
