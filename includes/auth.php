<?php
require_once __DIR__ . '/config.php';

// ─────────────────────────────────────
// Redirect to admin login if not authenticated.
// Call this at the top of every protected admin page.
// ─────────────────────────────────────
function requireAdminLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

// ─────────────────────────────────────
// Check whether an admin session is active.
// ─────────────────────────────────────
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

// ─────────────────────────────────────
// CSRF helpers
// ─────────────────────────────────────

/**
 * Return the current CSRF token, generating one if needed.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token submitted via POST field '_csrf'.
 * Returns true on match, false otherwise.
 */
function csrf_verify(): bool
{
    $submitted = $_POST['_csrf'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    return $stored !== '' && hash_equals($stored, $submitted);
}
