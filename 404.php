<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
http_response_code(404);

$pageTitle = 'الصفحة غير موجودة — تذكار';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<style>
.error-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 60px 20px;
}
.error-inner {
    max-width: 560px;
}
.error-code {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(6rem, 20vw, 10rem);
    font-weight: 600;
    color: var(--color-gold, #C9A96E);
    line-height: 1;
    margin-bottom: 8px;
    letter-spacing: -.02em;
}
.error-divider {
    width: 60px;
    height: 2px;
    background: var(--color-gold, #C9A96E);
    margin: 0 auto 24px;
    border-radius: 2px;
}
.error-title {
    font-size: 1.6rem;
    font-weight: 600;
    color: var(--color-text, #2C1F14);
    margin-bottom: 14px;
}
.error-sub {
    font-size: 1rem;
    color: var(--color-brown, #6B4F3A);
    line-height: 1.8;
    margin-bottom: 36px;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}
.error-btns {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}
.error-icon {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: .6;
}
</style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main>
    <div class="error-page">
        <div class="error-inner">
            <div class="error-icon">🕯️</div>
            <div class="error-code">404</div>
            <div class="error-divider"></div>
            <h1 class="error-title">الصفحة غير موجودة</h1>
            <p class="error-sub">
                يبدو أن هذه الصفحة تحوّلت إلى ذكرى... لكن هناك الكثير لتكتشفه في تذكار.
            </p>
            <div class="error-btns">
                <a href="<?= SITE_URL ?>/" class="btn-primary">
                    <i class="fas fa-house"></i>
                    العودة للرئيسية
                </a>
                <a href="<?= SITE_URL ?>/pages/shop.php" class="btn-outline">
                    <i class="fas fa-bag-shopping"></i>
                    تسوق الآن
                </a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
