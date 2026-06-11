<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ── GET params ─────────────────────────────────────────────────────────────
$catFilter  = isset($_GET['category']) ? array_filter(array_map('intval', (array)$_GET['category'])) : [];
$typeFilter = (isset($_GET['type']) && in_array($_GET['type'], ['ready_gift','event_giveaway'])) ? $_GET['type'] : null;
$search     = sanitize($_GET['q'] ?? '');
$priceMax   = isset($_GET['price_max']) ? max(0, (int)$_GET['price_max']) : 0;
$validSorts = ['latest','price_asc','price_desc','featured'];
$sort       = in_array($_GET['sort'] ?? '', $validSorts) ? $_GET['sort'] : 'latest';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 12;
$offset     = ($page - 1) * $perPage;

// ── WHERE builder ──────────────────────────────────────────────────────────
$where  = ["p.status = 'active'"];
$params = [];

if (!empty($catFilter)) {
    $ph = implode(',', array_fill(0, count($catFilter), '?'));
    $where[]  = "p.category_id IN ($ph)";
    $params   = array_merge($params, array_values($catFilter));
}
if ($typeFilter) {
    $where[]  = '(p.product_type = ? OR p.product_type = "both")';
    $params[] = $typeFilter;
}
if ($search !== '') {
    $like     = "%$search%";
    $where[]  = '(p.name_ar LIKE ? OR p.name_en LIKE ? OR p.short_description_ar LIKE ?)';
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($priceMax > 0) {
    $where[]  = '(p.price IS NULL OR p.price <= ?)';
    $params[] = $priceMax;
}

$whereStr = implode(' AND ', $where);

// ── ORDER BY (whitelisted) ─────────────────────────────────────────────────
$orderBy = match($sort) {
    'price_asc'  => 'ISNULL(p.price) ASC, p.price ASC',
    'price_desc' => 'ISNULL(p.price) ASC, p.price DESC',
    'featured'   => 'p.is_featured DESC, p.created_at DESC',
    default      => 'p.created_at DESC',
};

// ── Count + fetch products ─────────────────────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereStr");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = max(1, (int)ceil($totalProducts / $perPage));

$stmt = $pdo->prepare(
    "SELECT p.*, pi.image_url AS main_image
     FROM products p
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
     WHERE $whereStr
     ORDER BY $orderBy
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$products = $stmt->fetchAll();

// ── Sidebar data ───────────────────────────────────────────────────────────
$allCats      = getCategories();
$occasionCats = array_filter($allCats, fn($c) => $c['type'] === 'occasion');

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.shop-layout{display:grid;grid-template-columns:260px 1fr;gap:32px;align-items:start;}
.shop-sidebar{background:white;border-radius:var(--card-radius);padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.06);position:sticky;top:100px;}
.filter-group{margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--color-border);}
.filter-group:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0;}
.filter-group h4{font-size:.85rem;font-weight:600;color:var(--color-text);margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;}
.filter-label{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:.875rem;color:var(--color-brown);cursor:pointer;line-height:1.4;}
.filter-label:hover{color:var(--color-text);}
.filter-label input[type="checkbox"],.filter-label input[type="radio"]{accent-color:var(--color-gold);width:15px;height:15px;flex-shrink:0;}
.price-slider{width:100%;accent-color:var(--color-gold);margin:8px 0;}
.filter-toggle{display:none;align-items:center;gap:8px;margin-bottom:12px;background:white;border:1px solid var(--color-border);border-radius:10px;padding:10px 16px;cursor:pointer;font-family:var(--font-ar);font-size:.9rem;color:var(--color-brown);width:100%;}
.sort-select{width:100%;padding:8px 12px;border:1px solid var(--color-border);border-radius:8px;font-family:var(--font-ar);font-size:.875rem;color:var(--color-text);background:white;cursor:pointer;}
@media(max-width:900px){
  .shop-layout{grid-template-columns:1fr;}
  .shop-sidebar{position:static;display:none;}
  .shop-sidebar.open{display:block;}
  .filter-toggle{display:flex;}
}
</style>

<div class="page-banner">
    <div class="container">
        <h1><?= $isRTL ? 'المتجر' : 'Shop' ?></h1>
        <p style="margin-top:8px;color:var(--color-brown);">
            <?= $isRTL ? 'هدايا عطرية فاخرة لكل مناسبة' : 'Luxury fragrance gifts for every occasion' ?>
        </p>
        <nav class="breadcrumb" style="justify-content:center;margin-top:12px;">
            <a href="<?= SITE_URL ?>/"><?= $isRTL ? 'الرئيسية' : 'Home' ?></a>
            <span class="breadcrumb-sep">/</span>
            <span><?= $isRTL ? 'المتجر' : 'Shop' ?></span>
        </nav>
    </div>
</div>

<section class="section">
<div class="container">

    <!-- Search bar -->
    <form method="GET" action="" style="margin-bottom:28px;">
        <?php foreach ($catFilter as $cid): ?>
        <input type="hidden" name="category[]" value="<?= $cid ?>">
        <?php endforeach; ?>
        <?php if ($typeFilter): ?><input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>"><?php endif; ?>
        <?php if ($priceMax): ?><input type="hidden" name="price_max" value="<?= $priceMax ?>"><?php endif; ?>
        <?php if ($sort !== 'latest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
        <div style="display:flex;gap:10px;max-width:540px;margin-inline:auto;">
            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                   placeholder="<?= $isRTL ? 'ابحث عن منتج...' : 'Search products...' ?>"
                   style="flex:1;padding:12px 18px;border:1px solid var(--color-border);border-radius:50px;font-family:var(--font-ar);font-size:.95rem;background:white;outline:none;">
            <button type="submit" class="btn-primary" style="border-radius:50px;padding:12px 20px;">
                <i class="fas fa-magnifying-glass"></i>
            </button>
        </div>
    </form>

    <!-- Mobile sidebar toggle -->
    <button class="filter-toggle" onclick="toggleSidebar()">
        <i class="fas fa-sliders" style="color:var(--color-gold);"></i>
        <?= $isRTL ? 'تصفية المنتجات' : 'Filter Products' ?>
        <i class="fas fa-chevron-down" style="margin-<?= $isRTL ? 'right' : 'left' ?>:auto;font-size:.75rem;"></i>
    </button>

    <div class="shop-layout">

        <!-- ── SIDEBAR ── -->
        <aside class="shop-sidebar" id="shopSidebar">
            <form id="filterForm" method="GET" action="">
                <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>

                <!-- Occasion filter -->
                <div class="filter-group">
                    <h4><?= $isRTL ? 'المناسبة' : 'Occasion' ?></h4>
                    <?php foreach ($occasionCats as $cat): ?>
                    <label class="filter-label">
                        <input type="checkbox" name="category[]" value="<?= $cat['id'] ?>"
                               <?= in_array((int)$cat['id'], $catFilter) ? 'checked' : '' ?>>
                        <?= sanitize($isRTL ? $cat['name_ar'] : $cat['name_en']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Product type filter -->
                <div class="filter-group">
                    <h4><?= $isRTL ? 'نوع الهدية' : 'Gift Type' ?></h4>
                    <?php
                    $typeOpts = $isRTL
                        ? ['' => 'الكل', 'ready_gift' => 'هدية جاهزة', 'event_giveaway' => 'هدية مناسبة']
                        : ['' => 'All', 'ready_gift' => 'Ready Gift', 'event_giveaway' => 'Event Giveaway'];
                    foreach ($typeOpts as $val => $label):
                    ?>
                    <label class="filter-label">
                        <input type="radio" name="type" value="<?= $val ?>"
                               <?= ($typeFilter ?? '') === $val ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Price range -->
                <div class="filter-group">
                    <h4><?= $isRTL ? 'نطاق السعر' : 'Price Range' ?></h4>
                    <input type="range" name="price_max" id="priceSlider" class="price-slider"
                           min="0" max="2000" value="<?= $priceMax ?: 2000 ?>"
                           oninput="document.getElementById('priceDisplay').textContent=this.value">
                    <div style="font-size:.85rem;color:var(--color-brown);margin-top:4px;">
                        <?= $isRTL ? 'حتى' : 'Up to' ?>
                        <strong id="priceDisplay"><?= $priceMax ?: 2000 ?></strong>
                        <?= $isRTL ? ' ج.م' : ' EGP' ?>
                    </div>
                </div>

                <!-- Sort -->
                <div class="filter-group">
                    <h4><?= $isRTL ? 'الترتيب' : 'Sort By' ?></h4>
                    <select name="sort" class="sort-select">
                        <?php
                        $sortOpts = $isRTL
                            ? ['latest'=>'الأحدث','price_asc'=>'السعر: الأقل','price_desc'=>'السعر: الأعلى','featured'=>'المميزة']
                            : ['latest'=>'Newest','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low','featured'=>'Featured'];
                        foreach ($sortOpts as $val => $label):
                        ?>
                        <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;margin-bottom:10px;">
                    <i class="fas fa-check"></i>
                    <?= $isRTL ? 'تطبيق الفلاتر' : 'Apply Filters' ?>
                </button>
                <a href="<?= SITE_URL ?>/pages/shop.php" style="display:block;text-align:center;font-size:.85rem;color:var(--color-brown);">
                    <?= $isRTL ? 'مسح الكل' : 'Clear all' ?>
                </a>
            </form>
        </aside>

        <!-- ── PRODUCTS ── -->
        <div>

            <!-- Active filters + results count -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
                <span style="color:var(--color-brown);font-size:.9rem;">
                    <?= $isRTL
                        ? "<strong>$totalProducts</strong> منتج"
                        : "<strong>$totalProducts</strong> product" . ($totalProducts !== 1 ? 's' : '') ?>
                    <?= $search ? ($isRTL ? " لـ \"$search\"" : " for \"$search\"") : '' ?>
                </span>
                <?php if (!empty($catFilter) || $typeFilter || $priceMax || $search): ?>
                <a href="<?= SITE_URL ?>/pages/shop.php" style="font-size:.8rem;color:var(--color-gold);text-decoration:none;">
                    <i class="fas fa-xmark"></i> <?= $isRTL ? 'مسح الفلاتر' : 'Clear filters' ?>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($products): ?>
            <div class="grid-3" style="gap:20px;">
                <?php foreach ($products as $i => $product): ?>
                <div class="product-card animate fade-in-delay-<?= ($i % 3) + 1 ?>">
                    <div class="product-card-image-wrap">
                        <?php if (!empty($product['main_image'])): ?>
                        <img src="<?= UPLOAD_URL . sanitize($product['main_image']) ?>"
                             alt="<?= sanitize($isRTL ? $product['name_ar'] : $product['name_en']) ?>"
                             class="product-card-image" loading="lazy">
                        <?php else: ?>
                        <div class="product-card-placeholder"><i class="fas fa-spray-can-sparkles"></i></div>
                        <?php endif; ?>

                        <?php if ($product['sale_price']): ?>
                        <span class="product-badge"><?= $isRTL ? 'خصم' : 'Sale' ?></span>
                        <?php elseif ($product['order_mode'] === 'request_quote'): ?>
                        <span class="product-badge badge-quote"><?= $isRTL ? 'بالتفاوض' : 'Quote' ?></span>
                        <?php endif; ?>

                        <?php if ($product['is_featured']): ?>
                        <span class="product-badge" style="top:auto;bottom:10px;background:var(--color-gold);color:white;font-size:.7rem;">
                            <i class="fas fa-star"></i> <?= $isRTL ? 'مميز' : 'Featured' ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-body">
                        <div style="font-size:.72rem;color:var(--color-brown);margin-bottom:4px;opacity:.8;">
                            <?= $product['product_type'] === 'ready_gift'
                                ? ($isRTL ? '🎁 هدية جاهزة' : '🎁 Ready Gift')
                                : ($isRTL ? '✨ هدية مناسبة' : '✨ Event Giveaway') ?>
                        </div>
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
                                <span class="product-price-request"><?= $isRTL ? 'السعر بالتفاوض' : 'Price on request' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <?php if (in_array($product['order_mode'], ['buy_now', 'both'])): ?>
                            <form method="POST" action="<?= SITE_URL ?>/pages/cart.php" class="cart-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <button type="submit" class="btn-primary btn-sm add-to-cart-btn">
                                    <i class="fas fa-shopping-bag"></i>
                                    <?= $isRTL ? 'أضف للسلة' : 'Add to Cart' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (in_array($product['order_mode'], ['request_quote', 'both'])): ?>
                            <a href="<?= getWhatsAppLink(($isRTL ? 'أريد الاستفسار عن: ' : 'Inquiry about: ') . sanitize($isRTL ? $product['name_ar'] : $product['name_en'])) ?>"
                               class="btn-whatsapp btn-sm" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-whatsapp"></i>
                                <?= $isRTL ? 'واتساب' : 'WhatsApp' ?>
                            </a>
                            <?php endif; ?>
                            <?php if ($product['order_mode'] === 'request_quote'): ?>
                            <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($product['slug']) ?>"
                               class="btn-outline btn-sm" style="flex:1;justify-content:center;">
                                <?= $isRTL ? 'التفاصيل' : 'Details' ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="display:flex;justify-content:center;gap:8px;margin-top:48px;flex-wrap:wrap;">
                <?php
                $baseParams = array_filter(['q'=>$search,'type'=>$typeFilter,'sort'=>($sort!=='latest'?$sort:null),'price_max'=>($priceMax?:null)]);
                foreach ($catFilter as $cid) { /* pass through below */ }

                if ($page > 1):
                    $prevParams = array_merge($baseParams, ['page' => $page - 1]);
                    $prevHref   = '?' . http_build_query($prevParams) . (!empty($catFilter) ? '&' . http_build_query(['category' => $catFilter]) : '');
                ?>
                <a href="<?= $prevHref ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:white;color:var(--color-brown);border:1px solid var(--color-border);text-decoration:none;font-size:.875rem;">
                    <i class="fas fa-chevron-<?= $isRTL ? 'right' : 'left' ?>"></i>
                </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++):
                    $pParams = array_merge($baseParams, ['page' => $p]);
                    $pHref   = '?' . http_build_query($pParams) . (!empty($catFilter) ? '&' . http_build_query(['category' => $catFilter]) : '');
                ?>
                <a href="<?= $pHref ?>"
                   style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:8px;font-size:.875rem;text-decoration:none;<?= $p === $page ? 'background:var(--color-gold);color:white;' : 'background:white;color:var(--color-brown);border:1px solid var(--color-border);' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages):
                    $nextParams = array_merge($baseParams, ['page' => $page + 1]);
                    $nextHref   = '?' . http_build_query($nextParams) . (!empty($catFilter) ? '&' . http_build_query(['category' => $catFilter]) : '');
                ?>
                <a href="<?= $nextHref ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:white;color:var(--color-brown);border:1px solid var(--color-border);text-decoration:none;font-size:.875rem;">
                    <i class="fas fa-chevron-<?= $isRTL ? 'left' : 'right' ?>"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3><?= $isRTL ? 'لا توجد منتجات' : 'No Products Found' ?></h3>
                <p><?= $isRTL ? 'جرّب تغيير الفلاتر أو البحث بكلمة مختلفة.' : 'Try different filters or another search term.' ?></p>
                <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline" style="margin-top:20px;">
                    <?= $isRTL ? 'عرض كل المنتجات' : 'View All Products' ?>
                </a>
            </div>
            <?php endif; ?>

        </div><!-- /products -->
    </div><!-- /shop-layout -->
</div>
</section>

<script>
function toggleSidebar() {
    var sb = document.getElementById('shopSidebar');
    sb.classList.toggle('open');
}
// Price slider live preview
var slider = document.getElementById('priceSlider');
if (slider) {
    slider.addEventListener('input', function() {
        document.getElementById('priceDisplay').textContent = this.value;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
