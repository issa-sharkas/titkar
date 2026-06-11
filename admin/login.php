<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in → go to dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'طلب غير صالح. يرجى إعادة المحاولة.';
    }
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$error && $email && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: ' . SITE_URL . '/admin/index.php');
            exit;
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
    } else {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — لوحة تذكار</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Tajawal',sans-serif;background:linear-gradient(135deg,#FAF7F2 0%,#F0EBE4 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.login-card{background:white;border-radius:20px;padding:48px 40px;box-shadow:0 20px 60px rgba(0,0,0,.1);width:100%;max-width:420px;}
.login-logo{text-align:center;margin-bottom:32px;}
.login-logo .brand{font-size:1.8rem;font-weight:700;color:#3D2B1F;letter-spacing:.05em;}
.login-logo .brand span{color:#C9A96E;}
.login-logo p{font-size:.85rem;color:#8B6F47;margin-top:6px;}
.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:.875rem;font-weight:600;color:#3D2B1F;margin-bottom:8px;}
.form-group .input-wrap{position:relative;}
.form-group .input-wrap i{position:absolute;top:50%;transform:translateY(-50%);right:14px;color:#C9A96E;font-size:.9rem;}
.form-group input{width:100%;padding:13px 42px 13px 16px;border:1.5px solid #E8DDD0;border-radius:10px;font-family:'Tajawal',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s;color:#3D2B1F;background:#FDFAF7;}
.form-group input:focus{border-color:#C9A96E;background:white;}
.btn-login{width:100%;padding:15px;background:#C9A96E;color:white;border:none;border-radius:10px;font-family:'Tajawal',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;}
.btn-login:hover{background:#b8945a;}
.alert-error{background:#FFF0F0;border:1px solid #FFCDD2;color:#C62828;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.875rem;display:flex;align-items:center;gap:10px;}
.hint{text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid #F0EBE4;font-size:.8rem;color:#8B6F47;}
.hint code{background:#FAF7F2;padding:2px 8px;border-radius:6px;font-size:.85rem;font-family:monospace;}
</style>
</head>
<body>

<div class="login-card">
    <div class="login-logo">
        <div class="brand">TITH<span>KAR</span></div>
        <p>لوحة التحكم | Admin Panel</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fas fa-circle-exclamation"></i>
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
            <label>البريد الإلكتروني</label>
            <div class="input-wrap">
                <i class="far fa-envelope"></i>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                       placeholder="admin@tithkar.local" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label>كلمة المرور</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-right-to-bracket"></i>
            تسجيل الدخول
        </button>
    </form>

    <div class="hint">
        البيانات الافتراضية: <code>admin@tithkar.local</code> / <code>admin123</code>
    </div>
</div>

</body>
</html>
