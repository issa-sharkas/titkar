<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── All active products for bottom grid ────────────────────────────────────
$allStmt = $pdo->prepare(
    'SELECT p.*, pi.image_url AS main_image
     FROM products p
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
     WHERE p.status = "active"
     ORDER BY p.is_featured DESC, p.created_at DESC'
);
$allStmt->execute();
$allProducts = $allStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Filter pills ── */
.catalog-tabs{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:44px;}
.tab-btn{background:transparent;border:1px solid var(--color-gold);border-radius:30px;padding:9px 22px;font-family:var(--font-ar);font-size:.9rem;color:var(--color-gold);cursor:pointer;transition:all .2s;}
.tab-btn:hover,.tab-btn.active{background:var(--color-gold);color:white;}
/* ── Occasion cards ── */
.occ-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.occ-link{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:white;border-radius:var(--card-radius);padding:36px 20px;box-shadow:0 4px 20px rgba(0,0,0,.06);text-decoration:none;transition:transform .2s,box-shadow .2s;gap:14px;color:var(--color-text);}
.occ-link:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(0,0,0,.1);}
.occ-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;}
.occ-title{font-size:1.05rem;font-weight:600;}
.occ-desc{font-size:.8rem;color:var(--color-brown);}
/* ── Type / Style cards ── */
.type-card{background:white;border-radius:var(--card-radius);padding:28px 20px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.05);transition:transform .2s;}
.type-card:hover{transform:translateY(-3px);}
.type-icon{width:56px;height:56px;border-radius:50%;background:rgba(201,169,110,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.3rem;color:var(--color-gold);}
.style-card{border-radius:var(--card-radius);padding:36px 20px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;transition:transform .2s;color:white;}
.style-card:hover{transform:translateY(-3px);}
/* ── Catalog product cards ── */
.catalog-card{border-radius:var(--card-radius);overflow:hidden;background:white;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:transform .2s;}
.catalog-card:hover{transform:translateY(-4px);}
.catalog-card-img{aspect-ratio:3/4;background:var(--color-surface);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.catalog-card-img img{width:100%;height:100%;object-fit:cover;}
.catalog-card-body{padding:14px;}
.catalog-badge{font-size:.7rem;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:6px;}
.badge-direct{background:#E8F5E9;color:#2E7D32;}
.badge-custom{background:rgba(201,169,110,.15);color:#8B6914;}
.catalog-card-name{font-size:.9rem;font-weight:600;color:var(--color-text);margin-bottom:10px;line-height:1.4;}
/* Responsive */
@media(max-width:768px){
  .occ-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:480px){
  .occ-grid{grid-template-columns:1fr;}
}
</style>

<!-- Banner -->
<div class="page-banner">
    <div class="container">
        <div style="font-size:2.2rem;color:var(--color-gold);margin-bottom:12px;">
            <i class="fas fa-book-open"></i>
        </div>
        <h1><?= $isRTL ? 'كتالوج تذكار' : 'TITHKAR Catalogue' ?></h1>
        <p style="margin-top:10px;color:var(--color-brown);">
            <?= $isRTL ? 'أفكار هدايا فاخرة لكل مناسبة' : 'Luxury gift ideas for every occasion' ?>
        </p>
        <nav class="breadcrumb" style="justify-content:center;margin-top:14px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'الكتالوج' : 'Catalogue' ?></span>
        </nav>
    </div>
</div>

<!-- Main content -->
<section class="section">
<div class="container">

    <!-- Filter pills -->
    <div class="catalog-tabs">
        <button class="tab-btn active" onclick="filterCatalog('all', this)"><?= $isRTL ? 'الكل' : 'All' ?></button>
        <button class="tab-btn" onclick="filterCatalog('occasion', this)"><?= $isRTL ? 'حسب المناسبة' : 'By Occasion' ?></button>
        <button class="tab-btn" onclick="filterCatalog('type', this)"><?= $isRTL ? 'حسب النوع' : 'By Type' ?></button>
        <button class="tab-btn" onclick="filterCatalog('style', this)"><?= $isRTL ? 'حسب الستايل' : 'By Style' ?></button>
    </div>

    <!-- ═══ OCCASION SECTION ═══ -->
    <div data-section="occasion" style="margin-bottom:60px;">
        <div class="section-title animate" style="margin-bottom:28px;">
            <span class="section-subtitle"><?= $isRTL ? 'تسوق حسب المناسبة' : 'Shop by Occasion' ?></span>
            <h2><?= $isRTL ? 'مناسبتك تحدد هديتك' : 'Your Occasion Defines Your Gift' ?></h2>
        </div>
        <div class="occ-grid">
            <?php
            $occasions = [
                ['icon'=>'fa-rings-wedding','color'=>'#C9A96E','bg'=>'rgba(201,169,110,.12)','ar'=>'أفراح','en'=>'Weddings','descAr'=>'توزيعات وهدايا فاخرة للعرائس','descEn'=>'Luxury wedding favours & gifts','href'=>SITE_URL.'/pages/weddings.php'],
                ['icon'=>'fa-gem','color'=>'#8B1A2E','bg'=>'rgba(139,26,46,.1)','ar'=>'خطوبات','en'=>'Engagements','descAr'=>'هدايا رومانسية ومميزة','descEn'=>'Romantic & memorable gifts','href'=>SITE_URL.'/pages/engagements.php'],
                ['icon'=>'fa-cake-candles','color'=>'#C9A96E','bg'=>'rgba(201,169,110,.12)','ar'=>'أعياد ميلاد','en'=>'Birthdays','descAr'=>'باكدجات عيد ميلاد عطرية','descEn'=>'Fragrance birthday packages','href'=>SITE_URL.'/pages/birthdays.php'],
                ['icon'=>'fa-briefcase','color'=>'#6B4F3A','bg'=>'rgba(107,79,58,.1)','ar'=>'هدايا شركات','en'=>'Corporate','descAr'=>'هدايا مؤسسية راقية','descEn'=>'Premium corporate gifts','href'=>SITE_URL.'/pages/corporate.php'],
                ['icon'=>'fa-graduation-cap','color'=>'#C9A96E','bg'=>'rgba(201,169,110,.12)','ar'=>'تخرج','en'=>'Graduation','descAr'=>'هدايا تخرج مميزة','descEn'=>'Memorable graduation gifts','href'=>SITE_URL.'/pages/custom-order.php?event=graduation'],
                ['icon'=>'fa-baby','color'=>'#8B1A2E','bg'=>'rgba(139,26,46,.1)','ar'=>'استقبال مولود','en'=>'Baby Shower','descAr'=>'هدايا رقيقة وجميلة','descEn'=>'Delicate baby shower gifts','href'=>SITE_URL.'/pages/custom-order.php?event=baby_shower'],
            ];
            foreach ($occasions as $i => $occ):
            ?>
            <a href="<?= $occ['href'] ?>" class="occ-link animate fade-in-delay-<?= ($i % 3) + 1 ?>">
                <div class="occ-icon" style="background:<?= $occ['bg'] ?>;color:<?= $occ['color'] ?>;">
                    <i class="fas <?= $occ['icon'] ?>"></i>
                </div>
                <div class="occ-title"><?= $isRTL ? $occ['ar'] : $occ['en'] ?></div>
                <div class="occ-desc"><?= $isRTL ? $occ['descAr'] : $occ['descEn'] ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ BY TYPE SECTION ═══ -->
    <div data-section="type" style="margin-bottom:60px;">
        <div class="section-title animate" style="margin-bottom:28px;">
            <span class="section-subtitle"><?= $isRTL ? 'تسوق حسب النوع' : 'Shop by Type' ?></span>
            <h2><?= $isRTL ? 'اختر ما يناسبك' : 'Choose What Suits You' ?></h2>
        </div>
        <div class="grid-3">
            <?php
            $types = [
                ['fa-bottle-droplet','#C9A96E','زجاجات عطر صغيرة','Mini Perfume Bottles','عطور مصغّرة فاخرة بأسعار مناسبة','Luxury mini fragrances at accessible prices'],
                ['fa-gift','#8B1A2E','بوكسات هدايا عطرية','Perfume Gift Boxes','صناديق هدايا متكاملة جاهزة للتقديم','Complete gift boxes ready to present'],
                ['fa-heart','#E4405F','بوكسات ورد','Rose Boxes','مزيج من الورود والعطور الفاخرة','A blend of roses and luxury fragrances'],
                ['fa-pen-fancy','#6B4F3A','كروت مخصصة','Personalised Cards','كروت بتصاميم وخطوط عربية أنيقة','Cards with elegant Arabic typography'],
                ['fa-flask','#C9A96E','زجاجات فارغة بالجملة','Empty Bottles Wholesale','زجاجات عطر فارغة للشركات والموزعين','Empty perfume bottles for businesses'],
                ['fa-spray-can','#8B1A2E','مانيكير زجاجات','Manicure Bottles','زجاجات أنيقة للاستخدام المتعدد','Elegant bottles for multiple uses'],
            ];
            foreach ($types as $i => [$icon, $color, $ar, $en, $descAr, $descEn]):
            ?>
            <div class="type-card animate fade-in-delay-<?= ($i % 3) + 1 ?>">
                <div class="type-icon" style="color:<?= $color ?>;background:<?= $color === '#C9A96E' ? 'rgba(201,169,110,.12)' : 'rgba(139,26,46,.1)' ?>;">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <h3 style="font-size:1rem;margin-bottom:8px;"><?= $isRTL ? $ar : $en ?></h3>
                <p style="font-size:.85rem;color:var(--color-brown);"><?= $isRTL ? $descAr : $descEn ?></p>
                <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline btn-sm" style="margin-top:14px;display:inline-flex;">
                    <?= $isRTL ? 'استعرض' : 'Browse' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ BY STYLE SECTION ═══ -->
    <div data-section="style" style="margin-bottom:60px;">
        <div class="section-title animate" style="margin-bottom:28px;">
            <span class="section-subtitle"><?= $isRTL ? 'تسوق حسب الستايل' : 'Shop by Style' ?></span>
            <h2><?= $isRTL ? 'ما هو ذوقك؟' : "What's Your Style?" ?></h2>
        </div>
        <div class="grid-3">
            <?php
            $styles = [
                ['#C9A96E','#8B6914','فاخر','Luxury','fa-crown','هدايا بلمسة الفخامة والرقي','Gifts with a touch of luxury'],
                ['#E4405F','#9C2040','رومانسي','Romantic','fa-heart','لحظات رومانسية لا تُنسى','Unforgettable romantic moments'],
                ['#6B4F3A','#4A3728','مينيمال','Minimal','fa-minus','بساطة راقية وأناقة هادئة','Elegant simplicity and quiet sophistication'],
                ['#8B1A2E','#5C0F1D','كلاسيك','Classic','fa-star','أناقة خالدة لا تمل','Timeless elegance that never fades'],
                ['#2C1F14','#1A130C','مودرن','Modern','fa-bolt','تصاميم عصرية جريئة','Bold contemporary designs'],
                ['#2C7A5F','#1D5242','عربي','Arabic Style','fa-mosque','تراث عربي أصيل بلمسة عصرية','Authentic Arabic heritage with a modern twist'],
            ];
            foreach ($styles as $i => [$bg, $dark, $ar, $en, $icon, $descAr, $descEn]):
            ?>
            <div class="style-card animate fade-in-delay-<?= ($i % 3) + 1 ?>" style="background:linear-gradient(135deg,<?= $bg ?>,<?= $dark ?>);">
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div>
                    <div style="font-size:1.1rem;font-weight:700;margin-bottom:4px;"><?= $isRTL ? $ar : $en ?></div>
                    <div style="font-size:.8rem;opacity:.85;"><?= $isRTL ? $descAr : $descEn ?></div>
                </div>
                <a href="<?= SITE_URL ?>/pages/custom-order.php?style=<?= urlencode(strtolower($en)) ?>"
                   style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.5);color:white;padding:7px 18px;border-radius:20px;font-size:.8rem;text-decoration:none;transition:background .2s;"
                   onmouseover="this.style.background='rgba(255,255,255,.35)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
                    <?= $isRTL ? 'اطلب بهذا الستايل' : 'Order this style' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ ALL PRODUCTS ═══ -->
    <div data-section="products">
        <div class="section-title animate" style="margin-bottom:28px;">
            <span class="section-subtitle"><?= $isRTL ? 'مجموعتنا الكاملة' : 'Our Full Collection' ?></span>
            <h2><?= $isRTL ? 'كل المنتجات' : 'All Products' ?></h2>
        </div>

        <?php if ($allProducts): ?>
        <div class="grid-4">
            <?php foreach ($allProducts as $i => $product): ?>
            <div class="catalog-card animate fade-in-delay-<?= ($i % 4) + 1 ?>">
                <div class="catalog-card-img">
                    <?php if (!empty($product['main_image'])): ?>
                    <img src="<?= UPLOAD_URL . sanitize($product['main_image']) ?>"
                         alt="<?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>"
                         loading="lazy">
                    <?php else: ?>
                    <i class="fas fa-spray-can-sparkles" style="font-size:3rem;color:var(--color-gold);opacity:.4;"></i>
                    <?php endif; ?>

                    <?php if ($product['is_featured']): ?>
                    <div style="position:absolute;top:10px;<?= $isRTL ? 'right' : 'left' ?>:10px;background:var(--color-gold);color:white;font-size:.68rem;padding:3px 9px;border-radius:12px;">
                        <i class="fas fa-star" style="font-size:.6rem;"></i> <?= $isRTL ? 'مميز' : 'Featured' ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="catalog-card-body">
                    <span class="catalog-badge <?= in_array($product['order_mode'], ['buy_now','both']) ? 'badge-direct' : 'badge-custom' ?>">
                        <?= in_array($product['order_mode'], ['buy_now','both'])
                            ? ($isRTL ? 'للشراء المباشر' : 'Buy Directly')
                            : ($isRTL ? 'بطلب مخصص' : 'Custom Order') ?>
                    </span>
                    <div class="catalog-card-name">
                        <?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>
                    </div>
                    <div style="font-size:.85rem;color:var(--color-gold);font-weight:600;margin-bottom:10px;">
                        <?= $product['price'] ? formatPrice((float)($product['sale_price'] ?: $product['price'])) : ($isRTL ? 'حسب الطلب' : 'On request') ?>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($product['slug']) ?>"
                           class="btn-outline btn-sm" style="flex:1;justify-content:center;font-size:.78rem;">
                            <?= $isRTL ? 'التفاصيل' : 'Details' ?>
                        </a>
                        <?php if (in_array($product['order_mode'], ['buy_now','both'])): ?>
                        <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="cart-form" style="flex:1;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <button type="submit" class="btn-primary btn-sm add-to-cart-btn" style="width:100%;justify-content:center;font-size:.78rem;">
                                <i class="fas fa-bag-shopping"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="<?= getWhatsAppLink(($isRTL ? 'استفسار عن: ' : 'Inquiry: ') . sanitize($isRTL ? $product['name_ar'] : $product['name_en'])) ?>"
                           class="btn-whatsapp btn-sm" target="_blank" rel="noopener noreferrer" style="flex:1;justify-content:center;font-size:.78rem;">
                            <i class="fab fa-whatsapp"></i>
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
            <p><?= $isRTL ? 'نعمل على إضافة منتجات الكتالوج.' : 'We are adding catalogue products soon.' ?></p>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:36px;">
            <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline">
                <i class="fas fa-store" style="margin-<?= $isRTL ? 'left' : 'right' ?>:8px;"></i>
                <?= $isRTL ? 'انتقل للمتجر الكامل' : 'Go to Full Shop' ?>
            </a>
        </div>
    </div>

</div>
</section>

<!-- CTA -->
<section style="background:var(--color-text);padding:72px 20px;text-align:center;">
<div class="container">
    <div class="animate" style="max-width:600px;margin:0 auto;">
        <span class="section-subtitle" style="color:var(--color-gold);opacity:1;"><?= $isRTL ? 'طلب مخصص' : 'Custom Order' ?></span>
        <h2 style="color:white;margin-bottom:14px;">
            <?= $isRTL ? 'لم تجد ما يناسبك؟' : "Didn't find the right fit?" ?>
        </h2>
        <p style="color:rgba(232,213,176,.8);margin-bottom:32px;">
            <?= $isRTL
                ? 'نصمم لك هدية مخصصة تماماً بالعطر والتغليف والستايل الذي تريده.'
                : 'We design a fully custom gift with the scent, packaging, and style you want.' ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-primary">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'طلب مخصص' : 'Custom Order' ?>
            </a>
            <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'تحدث معنا' : 'Chat with Us' ?>
            </a>
        </div>
    </div>
</div>
</section>

<script>
function filterCatalog(filter, btn) {
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('[data-section]').forEach(function(sec) {
        if (filter === 'all') {
            sec.style.display = '';
        } else {
            // Always show "products" section; hide/show the others
            sec.style.display = (sec.dataset.section === filter || sec.dataset.section === 'products') ? '' : 'none';
        }
    });
    // Scroll to top of content
    var container = document.querySelector('[data-section]');
    if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
