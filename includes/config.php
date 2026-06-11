<?php
// ─────────────────────────────────────
// Database connection constants
// ─────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'tithkar_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ─────────────────────────────────────
// Site URL & upload paths
// ─────────────────────────────────────
define('SITE_URL', 'http://localhost/tithkar');
define('UPLOAD_PATH', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');

// ─────────────────────────────────────
// WhatsApp default number
// ─────────────────────────────────────
define('WHATSAPP_DEFAULT', '201000000000');

// ─────────────────────────────────────
// Start session once
// ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
