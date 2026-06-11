# TITHKAR | تذكار

> Luxury Perfume Gift Brand — PHP + MySQL web application

---

## Project Overview

TITHKAR (تذكار) is a bilingual (Arabic / English) e-commerce and custom-order platform for a luxury perfume gift brand. Features include:

- Public storefront: shop, product pages, occasion pages, custom order form
- Session-based shopping cart with AJAX updates
- Custom quote request system (stored in DB, managed via admin)
- Full admin panel: orders, custom requests, products, categories, gallery, settings

**Stack:** PHP 8.0+, MySQL 5.7+, Apache (XAMPP / Hostinger), vanilla JS

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0 or higher |
| MySQL | 5.7 / 8.0 |
| Apache | 2.4+ |
| XAMPP (local) | 8.x recommended |

---

## Local Setup (XAMPP)

### 1. Start XAMPP
Open **XAMPP Control Panel** and start both **Apache** and **MySQL**.

### 2. Copy project files
Place the project folder in:
```
C:\xampp\htdocs\tithkar\
```

### 3. Create the database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **New** → create a database named `tithkar_db` (UTF-8 / utf8mb4_unicode_ci)
3. Select `tithkar_db` → click **Import**
4. Import `database/schema.sql` first
5. Import `database/seed.sql` second (optional — seeds demo data and default settings)

### 4. Configure the app
Open `includes/config.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tithkar_db');
define('DB_USER', 'root');
define('DB_PASS', '');                        // blank by default on XAMPP
define('SITE_URL', 'http://localhost/tithkar');
```

### 5. Open in browser
- **Frontend:** `http://localhost/tithkar/`
- **Admin panel:** `http://localhost/tithkar/admin/`

**Default admin credentials:**
| Field | Value |
|---|---|
| Email | `admin@tithkar.local` |
| Password | `admin123` |

> ⚠️ **Change the admin password immediately after first login.**

---

## Hostinger Deployment

### 1. Upload files
Upload all project files to your Hostinger public folder (e.g. `public_html/tithkar/`) via **File Manager** or **FTP**.

### 2. Create the database
In the **Hostinger hPanel** → Databases → MySQL Databases:
1. Create a new database (e.g. `u123456_tithkar`)
2. Create a DB user and assign it full privileges
3. Import `database/schema.sql` via phpMyAdmin
4. Import `database/seed.sql`

### 3. Update config
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');          // usually 'localhost' on shared hosting
define('DB_NAME', 'u123456_tithkar');   // your Hostinger DB name
define('DB_USER', 'u123456_user');      // your Hostinger DB user
define('DB_PASS', 'your_password');
define('SITE_URL', 'https://yourdomain.com/tithkar');   // or https://yourdomain.com if at root
define('WHATSAPP_DEFAULT', '201000000000');              // your WhatsApp number
```

### 4. Set directory permissions
Upload directories must be writable by the web server:
```bash
chmod 755 assets/images/uploads
chmod 755 assets/images/gallery
```
Or use the Hostinger File Manager to set permissions to **755**.

### 5. Configure 404 error page
Create or edit the `.htaccess` file in the project root and add:
```apache
ErrorDocument 404 /tithkar/404.php
```
If the site is at the domain root (not a subdirectory), use:
```apache
ErrorDocument 404 /404.php
```

---

## Upload Directories

| Directory | Purpose |
|---|---|
| `assets/images/uploads/` | Product images |
| `assets/images/gallery/` | Gallery images |

Both directories must exist and be writable. They include `.gitkeep` to preserve the folder in version control. The application creates the files via PHP `move_uploaded_file()`.

---

## Admin Panel

**URL:** `{SITE_URL}/admin/`

| Page | URL |
|---|---|
| Dashboard | `/admin/index.php` |
| Products | `/admin/products.php` |
| Add / Edit Product | `/admin/product-form.php` |
| Orders | `/admin/orders.php` |
| Custom Requests | `/admin/custom-requests.php` |
| Categories | `/admin/categories.php` |
| Gallery | `/admin/gallery.php` |
| Settings | `/admin/settings.php` |

---

## Directory Structure

```
tithkar/
├── admin/                   # Admin panel pages
│   ├── partials/            # Sidebar + topbar
│   ├── index.php            # Dashboard
│   ├── products.php
│   ├── product-form.php
│   ├── orders.php
│   ├── order-details.php
│   ├── custom-requests.php
│   ├── custom-request-details.php
│   ├── categories.php
│   ├── gallery.php
│   ├── settings.php
│   ├── login.php
│   └── logout.php
├── assets/
│   ├── css/
│   │   ├── style.css        # Public stylesheet
│   │   └── admin.css        # Admin stylesheet
│   ├── js/
│   │   ├── main.js          # Public JS
│   │   └── cart.js          # Cart AJAX logic
│   └── images/
│       ├── uploads/         # Product images (writable)
│       └── gallery/         # Gallery images (writable)
├── database/
│   ├── schema.sql           # Table definitions
│   └── seed.sql             # Default admin user + settings
├── includes/
│   ├── config.php           # DB credentials + constants
│   ├── db.php               # PDO connection
│   ├── auth.php             # Admin auth + CSRF helpers
│   ├── functions.php        # Helper functions
│   ├── header.php           # Public site header
│   └── footer.php           # Public site footer
├── pages/                   # Public-facing pages
│   ├── home.php
│   ├── shop.php
│   ├── product.php
│   ├── catalog.php
│   ├── cart.php
│   ├── checkout.php
│   ├── thank-you.php
│   ├── custom-order.php
│   ├── events.php
│   ├── weddings.php
│   ├── engagements.php
│   ├── birthdays.php
│   ├── corporate.php
│   ├── about.php
│   └── contact.php
├── index.php                # Redirect to pages/home.php
├── 404.php                  # Custom error page
└── README.md
```

---

## Key Functions (includes/functions.php)

| Function | Description |
|---|---|
| `getSetting($key)` | Fetch a value from the `settings` table |
| `getCategories($type)` | Fetch active categories, optionally filtered by type |
| `getFeaturedProducts($limit)` | Fetch featured products with main image |
| `getProductBySlug($slug)` | Fetch a single product by URL slug |
| `formatPrice($price)` | Format a float as currency string |
| `getWhatsAppLink($msg)` | Build a `wa.me` deep link with pre-filled message |
| `generateOrderNumber()` | Generate a unique `TK-YYYYMMDD-XXXX` order number |
| `sanitize($input)` | Strip tags + trim a string |
| `isRTL()` | Return true if current session language is Arabic |

---

## Security Notes

- Admin pages protected by `requireAdminLogin()` in `includes/auth.php`
- CSRF tokens on all critical admin POST forms (`csrf_token()` / `csrf_verify()`)
- All DB queries use PDO prepared statements
- File uploads validated with `getimagesize()` and extension whitelist
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- All output HTML-escaped with `htmlspecialchars(..., ENT_QUOTES)`

---

## Changing the Admin Password

To reset the admin password, run this SQL in phpMyAdmin (replace `NewPassword123` with your desired password):

```sql
UPDATE users
SET password_hash = '$2y$12$XXXXXXXXXX'  -- generate via PHP: password_hash('NewPassword123', PASSWORD_DEFAULT)
WHERE email = 'admin@tithkar.local';
```

Or run this PHP snippet once and then delete it:
```php
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
$hash = password_hash('YOUR_NEW_PASSWORD', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@tithkar.local'")->execute([$hash]);
echo 'Done — delete this file now.';
```
