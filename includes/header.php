<?php
// ─────────────────────────────────────
// Bootstrap: cascades → db.php → config.php
// ─────────────────────────────────────
require_once __DIR__ . '/functions.php';

// ─────────────────────────────────────
// Language switcher
// ─────────────────────────────────────
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = in_array($_GET['lang'], ['ar', 'en']) ? $_GET['lang'] : 'ar';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : SITE_URL . '/';
    header('Location: ' . $referer);
    exit;
}

$lang      = $_SESSION['lang'] ?? 'ar';
$isRTL     = isRTL();
$dir       = $isRTL ? 'rtl' : 'ltr';
$bodyClass = $isRTL ? '' : 'ltr';

// ─────────────────────────────────────
// Cart item count
// ─────────────────────────────────────
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) ($item['quantity'] ?? 1);
    }
}

// ─────────────────────────────────────
// Active page detection
// ─────────────────────────────────────
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage === 'index' || $currentPage === 'home') {
    $currentPage = 'home';
}

// ─────────────────────────────────────
// WhatsApp header CTA
// ─────────────────────────────────────
$waLink = getWhatsAppLink($isRTL
    ? 'مرحباً، أريد الاستفسار عن منتجاتكم'
    : 'Hello, I would like to inquire about your products');

// ─────────────────────────────────────
// Brand identity (admin-controlled via settings)
// ─────────────────────────────────────
$siteNameAr  = getSetting('site_name_ar') ?: 'تذكار';
$siteNameEn  = getSetting('site_name_en') ?: 'TITHKAR';
$siteTagline = ($isRTL ? getSetting('site_tagline_ar') : getSetting('site_tagline_en'))
    ?: ($isRTL ? 'هدايا عطرية فاخرة' : 'Premium Perfume Gifts');
$siteLogo    = getSetting('site_logo');
$siteFavicon = getSetting('site_favicon');

// Reusable logo markup (header + mobile drawer)
$logoHtml = $siteLogo
    ? '<img src="' . UPLOAD_URL . sanitize($siteLogo) . '" alt="' . sanitize($siteNameAr . ' | ' . $siteNameEn) . '">'
    : sanitize($siteNameAr) . ' | <span>' . sanitize($siteNameEn) . '</span>';

// ─────────────────────────────────────
// Nav items: admin-managed via menu_items table
// (add / edit / hide / reorder from admin → القائمة)
// ─────────────────────────────────────
$menuItems = getMenuItems();

// Build [slug, ar, en, href] rows the templates below expect
$navItems = [];
foreach ($menuItems as $mi) {
    $href = menuItemHref($mi);
    $path = parse_url($href, PHP_URL_PATH) ?? '';
    $slug = basename($path, '.php');
    if ($slug === '' || $slug === 'index' || $slug === basename(rtrim(SITE_URL, '/'))) {
        $slug = 'home';
    }
    $navItems[] = [$slug, $mi['label_ar'], $mi['label_en'], $href];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $isRTL
        ? sanitize($siteNameAr) . ' - ' . sanitize($siteTagline) . ' للمناسبات. أفراح، خطوبات، أعياد ميلاد، وهدايا شركات.'
        : sanitize($siteNameEn) . ' - ' . sanitize($siteTagline) . ' for Every Occasion. Weddings, Engagements, Birthdays & Corporate.' ?>">
    <title><?= $isRTL
        ? sanitize($siteNameAr) . ' | ' . sanitize($siteTagline)
        : sanitize($siteNameEn) . ' | ' . sanitize($siteTagline) ?></title>
    <?php if ($siteFavicon): ?>
    <link rel="icon" href="<?= UPLOAD_URL . sanitize($siteFavicon) ?>">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">

    <!-- Global JS config (available to all scripts) -->
    <script>
    var TITHKAR = {
        cartUrl  : '<?= SITE_URL ?>/pages/cart.php',
        siteUrl  : '<?= SITE_URL ?>',
        lang     : '<?= $isRTL ? "ar" : "en" ?>',
        currency : '<?= htmlspecialchars(getSetting("currency") ?: "EGP", ENT_QUOTES) ?>'
    };
    </script>
</head>
<body class="<?= $bodyClass ?>">

<!-- ═══════════════════════════════════
     SITE HEADER
════════════════════════════════════ -->
<header class="site-header">

    <!-- Logo -->
    <a href="<?= SITE_URL ?>/" class="logo<?= $siteLogo ? ' logo-img' : '' ?>">
        <?= $logoHtml ?>
    </a>

    <!-- Desktop navigation -->
    <ul class="main-nav">
        <?php foreach ($navItems as [$slug, $ar, $en, $href]): ?>
        <li>
            <a href="<?= $href ?>" class="<?= $currentPage === $slug ? 'active' : '' ?>">
                <?= $isRTL ? $ar : $en ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Header actions -->
    <div class="header-actions">

        <!-- Language switcher -->
        <a href="?lang=<?= $isRTL ? 'en' : 'ar' ?>" class="lang-switcher" title="Switch language">
            <?= $isRTL ? 'EN' : 'عربي' ?>
        </a>

        <!-- Cart icon -->
        <a href="<?= SITE_URL ?>/pages/cart.php" class="cart-icon" title="<?= $isRTL ? 'سلة التسوق' : 'Cart' ?>">
            <i class="fas fa-shopping-bag"></i>
            <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>

        <!-- Mobile hamburger -->
        <button class="hamburger" id="hamburger" aria-label="<?= $isRTL ? 'القائمة' : 'Menu' ?>" aria-expanded="false" aria-controls="mobileNav">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div><!-- /header-actions -->

</header><!-- /site-header -->

<!-- ═══════════════════════════════════
     MOBILE NAVIGATION OVERLAY
════════════════════════════════════ -->
<div class="mobile-nav-overlay" id="mobileOverlay" aria-hidden="true"></div>

<nav class="mobile-nav" id="mobileNav" aria-label="<?= $isRTL ? 'القائمة الرئيسية' : 'Main navigation' ?>">

    <!-- Brand mark inside drawer -->
    <a href="<?= SITE_URL ?>/" class="logo<?= $siteLogo ? ' logo-img' : '' ?>" style="margin-bottom:24px; display:block;">
        <?= $logoHtml ?>
    </a>

    <?php foreach ($navItems as [$slug, $ar, $en, $href]): ?>
    <a href="<?= $href ?>" class="<?= $currentPage === $slug ? 'active' : '' ?>">
        <?= $isRTL ? $ar : $en ?>
    </a>
    <?php endforeach; ?>

    <!-- WhatsApp CTA inside drawer -->
    <a href="<?= $waLink ?>" class="btn-whatsapp mobile-whatsapp" target="_blank" rel="noopener noreferrer" style="margin-top:32px; justify-content:center;">
        <i class="fab fa-whatsapp"></i>
        <?= $isRTL ? 'تواصل واتساب' : 'Chat on WhatsApp' ?>
    </a>

</nav><!-- /mobile-nav -->
