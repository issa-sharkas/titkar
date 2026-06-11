-- ═══════════════════════════════════════════════════════════════
-- TITHKAR | تذكار — Demo / seed data
-- Run AFTER schema.sql
-- ═══════════════════════════════════════════════════════════════

USE tithkar_db;

-- ─────────────────────────────────────
-- Admin user
-- password: admin123  (bcrypt hash generated via PHP)
-- ─────────────────────────────────────
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@tithkar.local', '$2y$10$Sj3/u1rv4a8IMAlCgvqKmuLyi7LYsEKepFYdeebAkcTiZXQJ.DAj.', 'admin');

-- ─────────────────────────────────────
-- Categories (8)
-- ─────────────────────────────────────
INSERT INTO categories (name_ar, name_en, slug, type) VALUES
('هدايا جاهزة',       'Ready Gifts',          'ready-gifts',          'shop'),
('أفراح',             'Weddings',              'weddings',             'occasion'),
('خطوبات',            'Engagements',           'engagements',          'occasion'),
('أعياد ميلاد',       'Birthdays',             'birthdays',            'occasion'),
('هدايا شركات',       'Corporate Gifts',       'corporate',            'occasion'),
('فالنتاين',          'Valentine',             'valentine',            'occasion'),
('بوكسات عطور',       'Perfume Boxes',         'perfume-boxes',        'product_type'),
('زجاجات صغيرة',      'Mini Perfume Bottles',  'mini-perfume-bottles', 'product_type');

-- ─────────────────────────────────────
-- Products (4)
-- ─────────────────────────────────────
INSERT INTO products
    (name_ar, name_en, slug, short_description_ar, short_description_en, price, sale_price, category_id, product_type, order_mode, stock_quantity, stock_status, is_featured, status)
VALUES
(
    'بوكس القلب الرومانسي',
    'Romantic Heart Box',
    'romantic-heart-box',
    'بوكس هدية فاخر على شكل قلب مع عطر مميز',
    'Luxury heart-shaped gift box with a signature perfume',
    450.00, NULL,
    (SELECT id FROM categories WHERE slug = 'ready-gifts'),
    'ready_gift', 'buy_now', 20, 'in_stock', 1, 'active'
),
(
    'زجاجات عطر صغيرة للأفراح',
    'Mini Wedding Perfume Giveaway',
    'mini-wedding-perfume-giveaway',
    'زجاجات عطر صغيرة مخصصة لتوزيعات الأفراح',
    'Personalised mini perfume bottles ideal for wedding favours',
    NULL, NULL,
    (SELECT id FROM categories WHERE slug = 'weddings'),
    'event_giveaway', 'request_quote', 0, 'in_stock', 1, 'active'
),
(
    'باكدج عيد ميلاد',
    'Birthday Perfume Gift Box',
    'birthday-perfume-gift-box',
    'مجموعة عطرية متكاملة لعيد الميلاد',
    'A complete perfume gift set for birthdays',
    380.00, NULL,
    (SELECT id FROM categories WHERE slug = 'birthdays'),
    'both', 'both', 15, 'in_stock', 1, 'active'
),
(
    'هدايا شركات عطرية',
    'Corporate Perfume Set',
    'corporate-perfume-set',
    'طقم عطور راقٍ لهدايا الشركات والمناسبات الرسمية',
    'Premium perfume set tailored for corporate gifting',
    NULL, NULL,
    (SELECT id FROM categories WHERE slug = 'corporate'),
    'event_giveaway', 'request_quote', 0, 'in_stock', 0, 'active'
);

-- ─────────────────────────────────────
-- Site settings (8 defaults)
-- ─────────────────────────────────────
INSERT INTO settings (setting_key, setting_value) VALUES
('whatsapp_number',  '201000000000'),
('instagram_url',    'https://instagram.com/tithkar'),
('facebook_url',     'https://facebook.com/tithkar'),
('email',            'info@tithkar.com'),
('currency',         'EGP'),
('default_language', 'ar'),
('delivery_areas',   'New Cairo,Heliopolis,Nasr City,Madinaty,Rehab,Shorouk,Cairo,Giza'),
('site_name',        'TITHKAR | تذكار');
