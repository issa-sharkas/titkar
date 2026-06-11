-- ═══════════════════════════════════════════════════════════════
-- TITHKAR | تذكار — Database Schema
-- Charset: utf8mb4 (full Unicode + Arabic support)
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS tithkar_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tithkar_db;

-- ─────────────────────────────────────
-- 1. Admin users
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('admin') NOT NULL DEFAULT 'admin',
    created_at    DATETIME      NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 2. Product & occasion categories
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name_ar   VARCHAR(255) NOT NULL,
    name_en   VARCHAR(255) NOT NULL,
    slug      VARCHAR(255) NOT NULL UNIQUE,
    type      ENUM('shop','occasion','style','product_type') NOT NULL,
    parent_id INT          NULL DEFAULT NULL,
    status    ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_category_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 3. Products
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    name_ar                VARCHAR(255) NOT NULL,
    name_en                VARCHAR(255) NOT NULL,
    slug                   VARCHAR(255) NOT NULL UNIQUE,
    short_description_ar   TEXT,
    short_description_en   TEXT,
    full_description_ar    TEXT,
    full_description_en    TEXT,
    price                  DECIMAL(10,2)  NULL,
    sale_price             DECIMAL(10,2)  NULL,
    category_id            INT            NULL,
    product_type           ENUM('ready_gift','event_giveaway','both') NOT NULL DEFAULT 'ready_gift',
    order_mode             ENUM('buy_now','request_quote','both')     NOT NULL DEFAULT 'buy_now',
    stock_quantity         INT            NOT NULL DEFAULT 0,
    stock_status           ENUM('in_stock','out_of_stock') NOT NULL DEFAULT 'in_stock',
    is_featured            TINYINT(1)     NOT NULL DEFAULT 0,
    preparation_time       VARCHAR(255)   NULL,
    package_contents       TEXT           NULL,
    customization_options  TEXT           NULL,
    status                 ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at             DATETIME       NOT NULL DEFAULT NOW(),
    updated_at             DATETIME       NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 4. Product images (one-to-many)
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    image_url  VARCHAR(255) NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    is_main    TINYINT(1)   NOT NULL DEFAULT 0,
    CONSTRAINT fk_image_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 5. Product search tags
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_tags (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT          NOT NULL,
    tag        VARCHAR(255) NOT NULL,
    CONSTRAINT fk_tag_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 6. Customer orders
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_number   VARCHAR(100)  NOT NULL UNIQUE,
    customer_name  VARCHAR(255)  NOT NULL,
    phone          VARCHAR(50)   NOT NULL,
    email          VARCHAR(255)  NULL,
    city           VARCHAR(255)  NOT NULL,
    area           VARCHAR(255)  NOT NULL,
    address        TEXT          NOT NULL,
    subtotal       DECIMAL(10,2) NOT NULL,
    shipping       DECIMAL(10,2) NOT NULL DEFAULT 0,
    total          DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash_on_delivery','bank_transfer','instapay') NOT NULL,
    order_status   ENUM('pending','confirmed','preparing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes          TEXT          NULL,
    created_at     DATETIME      NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 7. Line items per order
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    order_id           INT           NOT NULL,
    product_id         INT           NULL,
    product_name       VARCHAR(255)  NOT NULL,
    quantity           INT           NOT NULL,
    unit_price         DECIMAL(10,2) NOT NULL,
    total_price        DECIMAL(10,2) NOT NULL,
    customization_text TEXT          NULL,
    CONSTRAINT fk_item_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 8. Custom event gift requests
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS custom_requests (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    customer_name           VARCHAR(255)  NOT NULL,
    phone                   VARCHAR(50)   NOT NULL,
    email                   VARCHAR(255)  NULL,
    event_type              ENUM('wedding','engagement','birthday','corporate','graduation','baby_shower','other') NOT NULL,
    event_date              DATE          NULL,
    quantity                INT           NULL,
    city                    VARCHAR(255)  NULL,
    area                    VARCHAR(255)  NULL,
    approximate_budget      VARCHAR(255)  NULL,
    preferred_style         ENUM('luxury','romantic','minimal','classic','colorful','arabic') NULL,
    perfume_preference      TEXT          NULL,
    bottle_preference       TEXT          NULL,
    packaging_preference    TEXT          NULL,
    card_names              VARCHAR(255)  NULL,
    card_date               VARCHAR(255)  NULL,
    needs_custom_card       TINYINT(1)    NOT NULL DEFAULT 0,
    needs_packaging         TINYINT(1)    NOT NULL DEFAULT 0,
    uploaded_reference_image VARCHAR(255) NULL,
    notes                   TEXT          NULL,
    status                  ENUM('new','contacted','quotation_sent','confirmed','in_preparation','delivered','cancelled') NOT NULL DEFAULT 'new',
    admin_notes             TEXT          NULL,
    created_at              DATETIME      NOT NULL DEFAULT NOW(),
    updated_at              DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 9. Photo gallery
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS gallery (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NULL,
    image_url    VARCHAR(255) NOT NULL,
    category     VARCHAR(255) NULL,
    show_on_home TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order   INT          NOT NULL DEFAULT 0,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 10. Contact form messages
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    phone      VARCHAR(50)  NOT NULL,
    email      VARCHAR(255) NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────
-- 11. Site-wide key/value settings
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
