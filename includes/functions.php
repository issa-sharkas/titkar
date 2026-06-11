<?php
require_once __DIR__ . '/db.php';

// ─────────────────────────────────────
// Retrieve a single value from the settings table.
// ─────────────────────────────────────
function getSetting(string $key): string
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string) $row['setting_value'] : '';
}

// ─────────────────────────────────────
// Build a wa.me deep-link with a pre-filled message.
// ─────────────────────────────────────
function getWhatsAppLink(string $message): string
{
    $number = getSetting('whatsapp_number') ?: WHATSAPP_DEFAULT;
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}

// ─────────────────────────────────────
// Format a price for display (e.g. "450 EGP").
// ─────────────────────────────────────
function formatPrice(float $price): string
{
    $currency = getSetting('currency') ?: 'EGP';
    return number_format($price, 0) . ' ' . $currency;
}

// ─────────────────────────────────────
// Sanitize user-supplied strings before output.
// ─────────────────────────────────────
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ─────────────────────────────────────
// Generate a unique order reference number.
// ─────────────────────────────────────
function generateOrderNumber(): string
{
    return 'TK-' . date('Ymd') . rand(1000, 9999);
}

// ─────────────────────────────────────
// Fetch a single product by its URL slug, including its images.
// ─────────────────────────────────────
function getProductBySlug(string $slug): array|false
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT p.*, GROUP_CONCAT(pi.image_url ORDER BY pi.sort_order SEPARATOR ",") AS images
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id
         WHERE p.slug = ? AND p.status = "active"
         GROUP BY p.id
         LIMIT 1'
    );
    $stmt->execute([$slug]);
    $product = $stmt->fetch();

    if ($product && $product['images']) {
        $product['images'] = explode(',', $product['images']);
    } else if ($product) {
        $product['images'] = [];
    }

    return $product;
}

// ─────────────────────────────────────
// Fetch featured active products for the homepage.
// ─────────────────────────────────────
function getFeaturedProducts(int $limit = 4): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT p.*, pi.image_url AS main_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
         WHERE p.is_featured = 1 AND p.status = "active"
         ORDER BY p.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ─────────────────────────────────────
// Fetch active categories, optionally filtered by type.
// ─────────────────────────────────────
function getCategories(?string $type = null): array
{
    global $pdo;

    if ($type !== null) {
        $stmt = $pdo->prepare(
            'SELECT * FROM categories WHERE status = "active" AND type = ? ORDER BY name_en'
        );
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query(
            'SELECT * FROM categories WHERE status = "active" ORDER BY type, name_en'
        );
    }

    return $stmt->fetchAll();
}

// ─────────────────────────────────────
// Determine if the current language is RTL (Arabic).
// Defaults to Arabic when no language is set.
// ─────────────────────────────────────
function isRTL(): bool
{
    return empty($_SESSION['lang']) || $_SESSION['lang'] === 'ar';
}

// ─────────────────────────────────────
// Navigation menu (admin-managed).
// Auto-creates the table and seeds the default links on first run.
// ─────────────────────────────────────
function ensureMenuTable(): void
{
    global $pdo;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS menu_items (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            label_ar   VARCHAR(255) NOT NULL,
            label_en   VARCHAR(255) NOT NULL,
            url        VARCHAR(255) NOT NULL DEFAULT '',
            sort_order INT          NOT NULL DEFAULT 0,
            status     ENUM('active','inactive') NOT NULL DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $count = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
    if ($count === 0) {
        $defaults = [
            ['الرئيسية',   'Home',         '',                       1],
            ['المتجر',     'Shop',         'pages/shop.php',         2],
            ['الكتالوج',   'Catalog',      'pages/catalog.php',      3],
            ['المناسبات',  'Events',       'pages/events.php',       4],
            ['طلب مخصص',  'Custom Order', 'pages/custom-order.php', 5],
            ['من نحن',     'About',        'pages/about.php',        6],
            ['تواصل معنا', 'Contact',      'pages/contact.php',      7],
        ];
        $ins = $pdo->prepare('INSERT INTO menu_items (label_ar, label_en, url, sort_order) VALUES (?,?,?,?)');
        foreach ($defaults as $d) { $ins->execute($d); }
    }
}

function getMenuItems(bool $activeOnly = true): array
{
    global $pdo;
    ensureMenuTable();
    $sql = 'SELECT * FROM menu_items'
         . ($activeOnly ? " WHERE status = 'active'" : '')
         . ' ORDER BY sort_order ASC, id ASC';
    return $pdo->query($sql)->fetchAll();
}

// Resolve a menu item URL: empty → home, http(s) → external, else relative to site.
function menuItemHref(array $item): string
{
    $url = trim((string) ($item['url'] ?? ''));
    if ($url === '') {
        return SITE_URL . '/';
    }
    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }
    return SITE_URL . '/' . ltrim($url, '/');
}

// ─────────────────────────────────────
// Gallery images flagged for the homepage.
// ─────────────────────────────────────
function getHomeGallery(int $limit = 8): array
{
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM gallery
         WHERE status = 'active' AND show_on_home = 1
         ORDER BY sort_order ASC, id DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
