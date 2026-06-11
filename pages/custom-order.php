<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors       = [];
$formData     = [];
$success      = false;
$deliveryRaw  = getSetting('delivery_areas');
$deliveryAreas = $deliveryRaw ? array_map('trim', explode(',', $deliveryRaw)) : ['Cairo', 'Giza'];

// ── POST: save custom request ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'customer_name'       => sanitize($_POST['customer_name']       ?? ''),
        'phone'               => sanitize($_POST['phone']               ?? ''),
        'email'               => sanitize($_POST['email']               ?? ''),
        'event_type'          => sanitize($_POST['event_type']          ?? ''),
        'event_date'          => sanitize($_POST['event_date']          ?? ''),
        'quantity'            => (int)($_POST['quantity']               ?? 0),
        'city'                => sanitize($_POST['city']                ?? ''),
        'area'                => sanitize($_POST['area']                ?? ''),
        'approximate_budget'  => sanitize($_POST['approximate_budget']  ?? ''),
        'preferred_style'     => sanitize($_POST['preferred_style']     ?? ''),
        'perfume_preference'  => sanitize($_POST['perfume_preference']  ?? ''),
        'bottle_preference'   => sanitize($_POST['bottle_preference']   ?? ''),
        'packaging_preference'=> sanitize($_POST['packaging_preference']?? ''),
        'card_names'          => sanitize($_POST['card_names']          ?? ''),
        'card_date'           => sanitize($_POST['card_date']           ?? ''),
        'needs_custom_card'   => isset($_POST['needs_custom_card'])  ? 1 : 0,
        'needs_packaging'     => isset($_POST['needs_packaging'])     ? 1 : 0,
        'notes'               => sanitize($_POST['notes']               ?? ''),
    ];

    // Validate required
    if (empty($formData['customer_name'])) { $errors['customer_name'] = 'الاسم مطلوب / Name required'; }
    if (empty($formData['phone']))         { $errors['phone']         = 'الهاتف مطلوب / Phone required'; }
    if (empty($formData['event_type']))    { $errors['event_type']    = 'نوع المناسبة مطلوب / Event type required'; }
    if (empty($formData['quantity']))      { $errors['quantity']      = 'الكمية مطلوبة / Quantity required'; }

    // File upload
    $uploadedImage = null;
    if (!empty($_FILES['reference_image']['name'])) {
        $file    = $_FILES['reference_image'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors['reference_image'] = isRTL() ? 'صور JPG/PNG فقط' : 'JPG/PNG images only';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['reference_image'] = isRTL() ? 'الحجم الأقصى 5MB' : 'Max 5MB';
        } else {
            $ext           = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uploadedImage = uniqid('ref_', true) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $uploadedImage);
        }
    }

    if (empty($errors)) {
        $validEventTypes = ['wedding','engagement','birthday','corporate','graduation','baby_shower','other'];
        $validStyles     = ['luxury','romantic','minimal','classic','colorful','arabic',''];
        $eventType   = in_array($formData['event_type'], $validEventTypes) ? $formData['event_type'] : 'other';
        $prefStyle   = in_array($formData['preferred_style'], $validStyles) ? ($formData['preferred_style'] ?: null) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO custom_requests
             (customer_name, phone, email, event_type, event_date, quantity, city, area,
              approximate_budget, preferred_style, perfume_preference, bottle_preference,
              packaging_preference, card_names, card_date, needs_custom_card, needs_packaging,
              uploaded_reference_image, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $formData['customer_name'],
            $formData['phone'],
            $formData['email'],
            $eventType,
            $formData['event_date'] ?: null,
            $formData['quantity'] ?: null,
            $formData['city'],
            $formData['area'],
            $formData['approximate_budget'],
            $prefStyle,
            $formData['perfume_preference'],
            $formData['bottle_preference'],
            $formData['packaging_preference'],
            $formData['card_names'],
            $formData['card_date'],
            $formData['needs_custom_card'],
            $formData['needs_packaging'],
            $uploadedImage,
            $formData['notes'],
        ]);

        header('Location: ' . SITE_URL . '/pages/thank-you.php?type=custom&order=CR-' . date('Ymd') . rand(1000,9999));
        exit;
    }
}

// ── GET pre-fill (from quick forms on occasion pages) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $formData = [
        'customer_name'        => sanitize($_GET['customer_name']    ?? ''),
        'phone'                => sanitize($_GET['phone']            ?? ''),
        'email'                => sanitize($_GET['email']            ?? ''),
        'event_type'           => sanitize($_GET['event_type']       ?? ''),
        'event_date'           => sanitize($_GET['event_date']       ?? ''),
        'quantity'             => sanitize($_GET['quantity']         ?? ''),
        'city'                 => sanitize($_GET['city']             ?? ''),
        'area'                 => sanitize($_GET['area']             ?? ''),
        'approximate_budget'   => sanitize($_GET['approximate_budget'] ?? ''),
        'preferred_style'      => sanitize($_GET['preferred_style']  ?? ''),
        'perfume_preference'   => sanitize($_GET['perfume_preference'] ?? ''),
        'bottle_preference'    => sanitize($_GET['bottle_preference'] ?? ''),
        'packaging_preference' => sanitize($_GET['packaging_preference'] ?? ''),
        'card_names'           => sanitize($_GET['card_names']       ?? ''),
        'card_date'            => sanitize($_GET['card_date']        ?? ''),
        'needs_custom_card'    => 0,
        'needs_packaging'      => 0,
        'notes'                => sanitize($_GET['notes']            ?? ''),
    ];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══ Custom Order — Luxury Hero Banner ═══ */
.co-hero{
    position:relative;min-height:78vh;display:flex;align-items:center;justify-content:center;
    text-align:center;overflow:hidden;padding:100px 20px 90px;
    background:linear-gradient(160deg,#FBF8F3 0%,#F5EFE4 45%,#EFE5D3 100%);
}
.co-hero::before{
    content:'';position:absolute;top:-180px;inset-inline-end:-180px;width:520px;height:520px;border-radius:50%;
    background:radial-gradient(circle,rgba(201,169,110,.22) 0%,transparent 70%);
    animation:coGlow 7s ease-in-out infinite;
}
.co-hero::after{
    content:'';position:absolute;bottom:-200px;inset-inline-start:-160px;width:480px;height:480px;border-radius:50%;
    background:radial-gradient(circle,rgba(232,196,184,.3) 0%,transparent 70%);
    animation:coGlow 8s 1.5s ease-in-out infinite;
}
@keyframes coGlow{0%,100%{transform:scale(1);opacity:.8}50%{transform:scale(1.12);opacity:1}}

.co-arch{position:absolute;bottom:0;width:200px;height:300px;pointer-events:none;}
.co-arch-left{left:4%;}
.co-arch-right{right:4%;transform:scaleX(-1);}
@media(max-width:900px){.co-arch{width:120px;height:190px;opacity:.6}}

.co-particle{
    position:absolute;color:var(--color-gold);opacity:0;pointer-events:none;
    animation:coFloat 6s ease-in-out infinite;
}
@keyframes coFloat{
    0%,100%{transform:translateY(0) rotate(0deg);opacity:.25}
    50%{transform:translateY(-22px) rotate(12deg);opacity:.6}
}

.co-hero-content{position:relative;z-index:2;max-width:760px;}
.co-hero-eyebrow{
    font-family:var(--font-en);letter-spacing:.4em;text-transform:uppercase;font-size:.8rem;
    color:var(--color-gold);margin-bottom:18px;animation:fadeInUp .8s .1s cubic-bezier(.22,1,.36,1) both;
}
.co-hero h1{
    font-size:clamp(2rem,5vw,3.3rem);line-height:1.25;margin-bottom:20px;font-weight:400;
    animation:fadeInUp .9s .25s cubic-bezier(.22,1,.36,1) both;
}
.co-hero h1 .gold-text{
    background:linear-gradient(120deg,#B8925A 0%,#E8D5B0 45%,#C9A96E 60%,#B8925A 100%);
    background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent;
    animation:goldShine 4s linear infinite;font-weight:600;
}
@keyframes goldShine{to{background-position:200% center}}
.co-hero p{
    max-width:580px;margin:0 auto 32px;color:var(--color-brown);font-size:1.08rem;line-height:1.9;
    animation:fadeInUp .9s .4s cubic-bezier(.22,1,.36,1) both;
}
.co-hero-actions{
    display:flex;gap:14px;justify-content:center;flex-wrap:wrap;
    animation:fadeInUp .9s .55s cubic-bezier(.22,1,.36,1) both;
}
.co-hero-actions .btn-primary{font-size:1.05rem;padding:15px 38px;}
.co-hero-badges{
    display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:38px;
    animation:fadeInUp .9s .7s cubic-bezier(.22,1,.36,1) both;
}
.co-badge{
    display:flex;align-items:center;gap:9px;background:rgba(255,255,255,.72);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    border:1px solid var(--color-border);padding:10px 20px;border-radius:40px;
    font-size:.86rem;color:var(--color-brown);box-shadow:0 4px 16px rgba(201,169,110,.14);
    transition:transform .3s ease,box-shadow .3s ease;
}
.co-badge:hover{transform:translateY(-3px);box-shadow:0 10px 26px rgba(201,169,110,.25);}
.co-badge i{color:var(--color-gold);font-size:1rem;}

.co-scroll-down{
    position:absolute;bottom:24px;left:50%;z-index:2;width:44px;height:44px;
    display:flex;align-items:center;justify-content:center;border-radius:50%;
    border:1.5px solid var(--color-border);background:rgba(255,255,255,.65);
    color:var(--color-gold);font-size:1rem;animation:coBounce 2.2s ease-in-out infinite;
    transition:background .3s ease,color .3s ease;
}
.co-scroll-down:hover{background:var(--color-gold);color:#fff;}
@keyframes coBounce{0%,100%{transform:translate(-50%,0)}50%{transform:translate(-50%,9px)}}

/* ═══ Sticky step tracker ═══ */
.step-track{
    position:sticky;top:calc(var(--header-height) + 12px);z-index:50;
    display:flex;align-items:center;justify-content:center;gap:0;flex-wrap:nowrap;
    width:max-content;max-width:100%;margin:0 auto 40px;padding:8px 14px;
    background:rgba(250,247,242,.92);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
    border:1px solid var(--color-border);border-radius:50px;
    box-shadow:0 8px 28px rgba(107,79,58,.1);overflow-x:auto;
}
.step-badge{
    display:flex;align-items:center;gap:7px;font-size:.82rem;font-weight:600;
    padding:8px 16px;border-radius:24px;background:transparent;color:var(--color-brown);
    white-space:nowrap;transition:all .35s cubic-bezier(.22,1,.36,1);
}
.step-badge .step-num{
    width:22px;height:22px;border-radius:50%;background:rgba(201,169,110,.16);
    display:flex;align-items:center;justify-content:center;font-size:.72rem;
    transition:all .35s ease;flex-shrink:0;
}
.step-badge.active{
    background:linear-gradient(135deg,var(--color-gold),#B8925A);color:#fff;
    box-shadow:0 6px 18px rgba(201,169,110,.45);transform:scale(1.04);
}
.step-badge.active .step-num{background:rgba(255,255,255,.25);color:#fff;}
.step-badge.done{color:var(--color-gold);}
.step-badge.done .step-num{background:var(--color-gold);color:#fff;}
.step-line{width:24px;height:1.5px;background:var(--color-border);flex-shrink:0;}
@media(max-width:600px){
    .step-line{width:10px;}
    .step-badge{font-size:.72rem;padding:6px 9px;gap:5px;}
    .step-badge .step-num{width:18px;height:18px;font-size:.65rem;}
}

/* ═══ Form cards ═══ */
.co-card{
    background:#fff;border-radius:18px;padding:30px;margin-bottom:24px;
    border:1px solid rgba(201,169,110,.14);box-shadow:0 6px 28px rgba(107,79,58,.07);
    transition:box-shadow .4s ease,transform .4s ease;position:relative;overflow:hidden;
}
.co-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:3px;
    background:linear-gradient(90deg,transparent,var(--color-gold),transparent);
    opacity:0;transition:opacity .4s ease;
}
.co-card:hover{box-shadow:0 16px 48px rgba(107,79,58,.13);transform:translateY(-3px);}
.co-card:hover::before{opacity:1;}
.co-card-head{
    display:flex;align-items:center;gap:14px;margin-bottom:24px;
    padding-bottom:16px;border-bottom:1px dashed var(--color-border);
}
.co-card-icon{
    width:46px;height:46px;border-radius:13px;flex-shrink:0;
    background:linear-gradient(135deg,var(--color-gold),#B8925A);color:#fff;
    display:flex;align-items:center;justify-content:center;font-size:1.1rem;
    box-shadow:0 6px 18px rgba(201,169,110,.35);
}
.co-card-head h3{margin:0;line-height:1.3;}
.co-card-head small{display:block;color:var(--color-brown);font-size:.8rem;font-weight:400;margin-top:2px;}

/* ═══ Upload area ═══ */
.upload-area{
    border:2px dashed rgba(201,169,110,.5);border-radius:14px;padding:30px 20px;text-align:center;
    background:linear-gradient(135deg,#FBF8F3,#F6F0E5);cursor:pointer;
    transition:all .35s cubic-bezier(.22,1,.36,1);
}
.upload-area:hover,.upload-area.dragover{
    border-color:var(--color-gold);background:#fff;
    box-shadow:0 10px 28px rgba(201,169,110,.18);transform:translateY(-2px);
}
.upload-area input[type=file]{display:none;}
.upload-preview{display:none;margin:14px auto 0;max-height:150px;border-radius:10px;box-shadow:0 8px 22px rgba(0,0,0,.14);}
.upload-filename{font-size:.82rem;color:var(--color-gold);margin-top:10px;font-weight:600;display:none;}

/* ═══ Submit ═══ */
.co-submit{
    width:100%;justify-content:center;font-size:1.12rem;padding:18px;border-radius:60px;
    background:linear-gradient(135deg,var(--color-gold) 0%,#B8925A 100%);
    letter-spacing:.02em;box-shadow:0 10px 32px rgba(201,169,110,.35);
}
.co-submit:hover{transform:translateY(-3px);box-shadow:0 18px 44px rgba(201,169,110,.5);}

/* ═══ Wizard: one step at a time ═══ */
.co-step{display:none;--step-x:-46px;}
body.ltr .co-step{--step-x:46px;}
.co-step.back{--step-x:46px;}
body.ltr .co-step.back{--step-x:-46px;}
.co-step.active{display:block;animation:stepSlide .55s cubic-bezier(.22,1,.36,1) both;}
@keyframes stepSlide{
    from{opacity:0;transform:translateX(var(--step-x)) scale(.985);}
    to{opacity:1;transform:none;}
}

.co-progress{
    height:4px;background:rgba(201,169,110,.18);border-radius:4px;
    max-width:480px;margin:0 auto 38px;overflow:hidden;
}
.co-progress-fill{
    height:100%;width:25%;border-radius:4px;
    background:linear-gradient(90deg,var(--color-gold-light),var(--color-gold),#B8925A);
    transition:width .6s cubic-bezier(.22,1,.36,1);
}

.co-nav{display:flex;justify-content:space-between;gap:14px;margin-top:4px;}
.co-nav .btn-outline,.co-nav .btn-primary{min-width:140px;}
@media(max-width:480px){.co-nav .btn-outline,.co-nav .btn-primary{min-width:110px;padding:12px 20px;}}

#coSubmitArea{display:none;animation:fadeInUp .55s cubic-bezier(.22,1,.36,1) both;}
#coSubmitArea.show{display:block;}

.step-badge{cursor:pointer;}
.step-badge:not(.active):not(.done){cursor:default;}
</style>

<!-- ═══ Hero Banner ═══ -->
<section class="co-hero">

    <!-- Decorative Islamic arches -->
    <svg class="co-arch co-arch-left" viewBox="0 0 200 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M20 300 V120 Q20 60 60 40 Q90 26 100 0 Q110 26 140 40 Q180 60 180 120 V300" stroke="#C9A96E" stroke-width="1.5" opacity="0.35"/>
        <path d="M45 300 V130 Q45 82 75 62 Q95 50 100 30 Q105 50 125 62 Q155 82 155 130 V300" stroke="#C9A96E" stroke-width="1" opacity="0.22"/>
    </svg>
    <svg class="co-arch co-arch-right" viewBox="0 0 200 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M20 300 V120 Q20 60 60 40 Q90 26 100 0 Q110 26 140 40 Q180 60 180 120 V300" stroke="#C9A96E" stroke-width="1.5" opacity="0.35"/>
        <path d="M45 300 V130 Q45 82 75 62 Q95 50 100 30 Q105 50 125 62 Q155 82 155 130 V300" stroke="#C9A96E" stroke-width="1" opacity="0.22"/>
    </svg>

    <!-- Floating gold particles -->
    <span class="co-particle" style="top:18%;left:12%;font-size:1rem;animation-delay:0s;">✦</span>
    <span class="co-particle" style="top:30%;right:15%;font-size:.8rem;animation-delay:1.2s;">✦</span>
    <span class="co-particle" style="top:62%;left:20%;font-size:.7rem;animation-delay:2.4s;">✦</span>
    <span class="co-particle" style="top:14%;right:32%;font-size:.6rem;animation-delay:.8s;">✧</span>
    <span class="co-particle" style="top:70%;right:24%;font-size:1.1rem;animation-delay:3s;">✧</span>
    <span class="co-particle" style="top:48%;left:8%;font-size:.85rem;animation-delay:1.8s;">✧</span>

    <div class="co-hero-content">
        <div class="co-hero-eyebrow">تذكار ✦ TITHKAR</div>
        <h1>
            <?= $isRTL
                ? 'اصنع هدية <span class="gold-text">تُخلّد لحظاتك</span><br>لكل مناسبة غالية'
                : 'Craft a Gift That <span class="gold-text">Holds Every Memory</span><br>For Every Precious Occasion' ?>
        </h1>
        <p><?= $isRTL
            ? 'هدايا عطرية فاخرة مصممة خصيصاً لمناسبتك — أفراح، خطوبات، أعياد ميلاد وهدايا شركات. املأ التفاصيل وسنتواصل معك خلال 24 ساعة بعرض سعر مخصص.'
            : 'Luxurious perfume gifts designed exclusively for your occasion — weddings, engagements, birthdays & corporate. Fill in the details and we\'ll contact you within 24 hours with a personalised quote.' ?></p>
        <div class="co-hero-actions">
            <a href="#orderForm" class="btn-primary">
                <i class="fas fa-wand-magic-sparkles"></i>
                <?= $isRTL ? 'ابدأ طلبك الآن' : 'Start Your Order' ?>
            </a>
            <a href="<?= $waLink ?>" class="btn-outline" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'استشارة مجانية' : 'Free Consultation' ?>
            </a>
        </div>
        <div class="co-hero-badges">
            <div class="co-badge"><i class="fas fa-clock"></i> <?= $isRTL ? 'رد خلال 24 ساعة' : 'Reply within 24h' ?></div>
            <div class="co-badge"><i class="fas fa-gem"></i> <?= $isRTL ? 'تصميم مخصص بالكامل' : '100% Custom Design' ?></div>
            <div class="co-badge"><i class="fas fa-gift"></i> <?= $isRTL ? 'تغليف فاخر' : 'Premium Packaging' ?></div>
        </div>
    </div>

    <a href="#orderForm" class="co-scroll-down" aria-label="<?= $isRTL ? 'انتقل للنموذج' : 'Scroll to form' ?>">
        <i class="fas fa-chevron-down"></i>
    </a>
</section>

<section class="section">
<div class="container" style="max-width:820px;">

<!-- Step progress (scroll-spy driven) -->
<div class="step-track">
    <div class="step-badge active" data-step="1"><span class="step-num">١</span> <?= $isRTL ? 'بياناتك' : 'Your Info' ?></div>
    <div class="step-line"></div>
    <div class="step-badge" data-step="2"><span class="step-num">٢</span> <?= $isRTL ? 'المناسبة' : 'Occasion' ?></div>
    <div class="step-line"></div>
    <div class="step-badge" data-step="3"><span class="step-num">٣</span> <?= $isRTL ? 'التصميم' : 'Design' ?></div>
    <div class="step-line"></div>
    <div class="step-badge" data-step="4"><span class="step-num">٤</span> <?= $isRTL ? 'الإضافات' : 'Extras' ?></div>
</div>

<!-- Progress bar -->
<div class="co-progress"><div class="co-progress-fill" id="coProgressFill"></div></div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= $isRTL ? 'يرجى تصحيح الأخطاء.' : 'Please correct the errors.' ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="orderForm">

    <!-- ── Section 1: Contact ── -->
    <div class="co-card co-step active" data-step="1">
        <div class="co-card-head">
            <div class="co-card-icon"><i class="fas fa-user"></i></div>
            <h3>
                <?= $isRTL ? 'بيانات التواصل' : 'Contact Details' ?>
                <small><?= $isRTL ? 'كيف نتواصل معك؟' : 'How can we reach you?' ?></small>
            </h3>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= $isRTL ? 'الاسم الكامل *' : 'Full Name *' ?></label>
                <input type="text" name="customer_name" required value="<?= htmlspecialchars($formData['customer_name'] ?? '', ENT_QUOTES) ?>">
                <?php if (!empty($errors['customer_name'])): ?><div class="form-error"><?= $errors['customer_name'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'رقم الهاتف / واتساب *' : 'Phone / WhatsApp *' ?></label>
                <input type="tel" name="phone" required value="<?= htmlspecialchars($formData['phone'] ?? '', ENT_QUOTES) ?>">
                <?php if (!empty($errors['phone'])): ?><div class="form-error"><?= $errors['phone'] ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'البريد الإلكتروني (اختياري)' : 'Email (optional)' ?></label>
            <input type="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES) ?>">
        </div>
    </div>

    <!-- ── Section 2: Event ── -->
    <div class="co-card co-step" data-step="2">
        <div class="co-card-head">
            <div class="co-card-icon"><i class="fas fa-calendar-days"></i></div>
            <h3>
                <?= $isRTL ? 'تفاصيل المناسبة' : 'Event Details' ?>
                <small><?= $isRTL ? 'حدثنا عن مناسبتك' : 'Tell us about your event' ?></small>
            </h3>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= $isRTL ? 'نوع المناسبة *' : 'Event Type *' ?></label>
                <select name="event_type" required>
                    <option value=""><?= $isRTL ? '-- اختر --' : '-- Select --' ?></option>
                    <?php
                    $eventTypes = $isRTL ? [
                        'wedding'=>'فرح','engagement'=>'خطوبة','birthday'=>'عيد ميلاد',
                        'corporate'=>'هدايا شركات','graduation'=>'تخرج','baby_shower'=>'استقبال مولود','other'=>'أخرى'
                    ] : [
                        'wedding'=>'Wedding','engagement'=>'Engagement','birthday'=>'Birthday',
                        'corporate'=>'Corporate','graduation'=>'Graduation','baby_shower'=>'Baby Shower','other'=>'Other'
                    ];
                    foreach ($eventTypes as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= (($formData['event_type'] ?? '') === $val) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['event_type'])): ?><div class="form-error"><?= $errors['event_type'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'تاريخ المناسبة' : 'Event Date' ?></label>
                <input type="date" name="event_date" value="<?= htmlspecialchars($formData['event_date'] ?? '', ENT_QUOTES) ?>">
            </div>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'الكمية المطلوبة *' : 'Quantity Needed *' ?></label>
            <input type="number" name="quantity" min="1" required value="<?= (int)($formData['quantity'] ?? '') ?: '' ?>"
                   placeholder="<?= $isRTL ? 'مثال: 50' : 'e.g. 50' ?>">
            <?php if (!empty($errors['quantity'])): ?><div class="form-error"><?= $errors['quantity'] ?></div><?php endif; ?>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= $isRTL ? 'المدينة *' : 'City *' ?></label>
                <select name="city">
                    <option value=""><?= $isRTL ? '-- اختر --' : '-- Select --' ?></option>
                    <?php foreach ($deliveryAreas as $area): ?>
                    <option value="<?= htmlspecialchars($area, ENT_QUOTES) ?>"
                            <?= (($formData['city'] ?? '') === $area) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($area, ENT_QUOTES) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['city'])): ?><div class="form-error"><?= $errors['city'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'المنطقة *' : 'Area *' ?></label>
                <input type="text" name="area" value="<?= htmlspecialchars($formData['area'] ?? '', ENT_QUOTES) ?>">
                <?php if (!empty($errors['area'])): ?><div class="form-error"><?= $errors['area'] ?></div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Section 3: Preferences ── -->
    <div class="co-card co-step" data-step="3">
        <div class="co-card-head">
            <div class="co-card-icon"><i class="fas fa-sliders"></i></div>
            <h3>
                <?= $isRTL ? 'تفضيلاتك' : 'Your Preferences' ?>
                <small><?= $isRTL ? 'صمّم هديتك على ذوقك' : 'Design your gift, your way' ?></small>
            </h3>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'الستايل المفضل' : 'Preferred Style' ?></label>
            <select name="preferred_style">
                <option value=""><?= $isRTL ? '-- اختر (اختياري) --' : '-- Select (optional) --' ?></option>
                <?php
                $styles = $isRTL
                    ? ['luxury'=>'فاخر','romantic'=>'رومانسي','minimal'=>'مينيمال','classic'=>'كلاسيكي','colorful'=>'ملون','arabic'=>'عربي أصيل']
                    : ['luxury'=>'Luxury','romantic'=>'Romantic','minimal'=>'Minimal','classic'=>'Classic','colorful'=>'Colorful','arabic'=>'Arabic'];
                foreach ($styles as $val => $label):
                ?>
                <option value="<?= $val ?>" <?= (($formData['preferred_style'] ?? '') === $val) ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'تفضيل العطر' : 'Perfume Preference' ?></label>
            <textarea name="perfume_preference" rows="2"
                      placeholder="<?= $isRTL ? 'مثال: عطور شرقية، خفيفة، للنساء...' : 'e.g. Oriental, light, for women...' ?>"><?= htmlspecialchars($formData['perfume_preference'] ?? '', ENT_QUOTES) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= $isRTL ? 'تفضيل الزجاجة' : 'Bottle Preference' ?></label>
                <input type="text" name="bottle_preference" value="<?= htmlspecialchars($formData['bottle_preference'] ?? '', ENT_QUOTES) ?>"
                       placeholder="<?= $isRTL ? 'مثال: زجاجة صغيرة 10ml' : 'e.g. 10ml mini bottle' ?>">
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'تفضيل التغليف' : 'Packaging Preference' ?></label>
                <input type="text" name="packaging_preference" value="<?= htmlspecialchars($formData['packaging_preference'] ?? '', ENT_QUOTES) ?>"
                       placeholder="<?= $isRTL ? 'مثال: بوكس ذهبي، كيس' : 'e.g. gold box, bag' ?>">
            </div>
        </div>
    </div>

    <!-- ── Section 4: Card & Extras ── -->
    <div class="co-card co-step" data-step="4">
        <div class="co-card-head">
            <div class="co-card-icon"><i class="fas fa-heart"></i></div>
            <h3>
                <?= $isRTL ? 'الكارت والإضافات' : 'Card & Extras' ?>
                <small><?= $isRTL ? 'اللمسة الأخيرة لهديتك' : 'The final touch for your gift' ?></small>
            </h3>
        </div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px;">
            <label class="form-check">
                <input type="checkbox" name="needs_custom_card" <?= ($formData['needs_custom_card'] ?? 0) ? 'checked' : '' ?>>
                <?= $isRTL ? 'أريد كارت مخصص' : 'Custom card needed' ?>
            </label>
            <label class="form-check">
                <input type="checkbox" name="needs_packaging" <?= ($formData['needs_packaging'] ?? 0) ? 'checked' : '' ?>>
                <?= $isRTL ? 'أريد تغليف خاص' : 'Special packaging needed' ?>
            </label>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= $isRTL ? 'الأسماء على الكارت' : 'Names on Card' ?></label>
                <input type="text" name="card_names" value="<?= htmlspecialchars($formData['card_names'] ?? '', ENT_QUOTES) ?>"
                       placeholder="<?= $isRTL ? 'مثال: أحمد & سارة' : 'e.g. Ahmed & Sara' ?>">
            </div>
            <div class="form-group">
                <label><?= $isRTL ? 'التاريخ على الكارت' : 'Date on Card' ?></label>
                <input type="text" name="card_date" value="<?= htmlspecialchars($formData['card_date'] ?? '', ENT_QUOTES) ?>"
                       placeholder="<?= $isRTL ? 'مثال: 15 يناير 2025' : 'e.g. 15 January 2025' ?>">
            </div>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'صورة مرجعية للتصميم (اختياري)' : 'Reference Image (optional)' ?></label>
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-cloud-arrow-up" style="font-size:2rem;color:var(--color-gold);margin-bottom:8px;display:block;"></i>
                <p style="font-size:.875rem;color:var(--color-brown);margin-bottom:6px;">
                    <?= $isRTL ? 'اضغط هنا أو اسحب الصورة وأفلتها' : 'Click here or drag & drop your image' ?>
                </p>
                <div style="font-size:.75rem;color:var(--color-brown);"><?= $isRTL ? 'JPG/PNG/WebP حتى 5MB' : 'JPG/PNG/WebP up to 5MB' ?></div>
                <input type="file" name="reference_image" accept="image/jpeg,image/png,image/webp">
                <img class="upload-preview" id="uploadPreview" alt="">
                <div class="upload-filename" id="uploadFilename"></div>
            </div>
            <?php if (!empty($errors['reference_image'])): ?><div class="form-error"><?= $errors['reference_image'] ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label><?= $isRTL ? 'ملاحظات إضافية' : 'Additional Notes' ?></label>
            <textarea name="notes" rows="3"
                      placeholder="<?= $isRTL ? 'أي تفاصيل أو تعليمات إضافية...' : 'Any additional details or instructions...' ?>"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES) ?></textarea>
        </div>
    </div>

    <!-- Wizard navigation -->
    <div class="co-nav" id="coNav">
        <button type="button" class="btn-outline" id="coPrev" style="visibility:hidden;">
            <i class="fas fa-arrow-<?= $isRTL ? 'right' : 'left' ?>"></i>
            <?= $isRTL ? 'السابق' : 'Back' ?>
        </button>
        <button type="button" class="btn-primary" id="coNext">
            <?= $isRTL ? 'التالي' : 'Next' ?>
            <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>"></i>
        </button>
    </div>

    <!-- Submit area (shown on last step only) -->
    <div id="coSubmitArea">
        <button type="submit" class="btn-primary co-submit">
            <i class="fas fa-paper-plane"></i>
            <?= $isRTL ? 'إرسال الطلب' : 'Send Request' ?>
        </button>
        <p style="text-align:center;margin-top:12px;font-size:0.85rem;color:var(--color-brown);">
            <?= $isRTL
                ? 'سيتواصل معك فريقنا خلال 24 ساعة بعرض سعر مخصص.'
                : 'Our team will contact you within 24 hours with a personalised quote.' ?>
        </p>
        <div style="text-align:center;margin-top:20px;padding-top:20px;border-top:1px solid var(--color-border);">
            <p style="font-size:.875rem;color:var(--color-brown);margin-bottom:10px;"><?= $isRTL ? 'أو تواصل معنا مباشرة عبر:' : 'Or reach us directly via:' ?></p>
            <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'تواصل عبر واتساب' : 'Chat on WhatsApp' ?>
            </a>
        </div>
    </div>
</form>

</div>
</section>

<script>
(function () {
    'use strict';

    /* ── Multi-step wizard ────────────────────── */
    var steps      = document.querySelectorAll('.co-step[data-step]');
    var badges     = document.querySelectorAll('.step-badge[data-step]');
    var prevBtn    = document.getElementById('coPrev');
    var nextBtn    = document.getElementById('coNext');
    var navWrap    = document.getElementById('coNav');
    var submitArea = document.getElementById('coSubmitArea');
    var progress   = document.getElementById('coProgressFill');
    var form       = document.getElementById('orderForm');
    var total      = steps.length;
    var current    = 1;
    var maxVisited = 1;

    function stepEl(n) {
        return document.querySelector('.co-step[data-step="' + n + '"]');
    }

    function goTo(n, isBack, noScroll) {
        if (n < 1 || n > total) return;
        steps.forEach(function (s) { s.classList.remove('active', 'back'); });
        var target = stepEl(n);
        if (isBack) target.classList.add('back');
        target.classList.add('active');

        badges.forEach(function (b) {
            var s = parseInt(b.dataset.step, 10);
            b.classList.toggle('active', s === n);
            b.classList.toggle('done', s < n);
        });

        if (progress) progress.style.width = (n / total * 100) + '%';
        if (prevBtn)  prevBtn.style.visibility = (n === 1) ? 'hidden' : 'visible';
        if (nextBtn)  nextBtn.style.display = (n === total) ? 'none' : 'inline-flex';
        if (submitArea) submitArea.classList.toggle('show', n === total);

        current = n;
        if (n > maxVisited) maxVisited = n;

        /* Scroll back to the top of the wizard smoothly */
        if (!noScroll) {
            var track = document.querySelector('.step-track');
            if (track) {
                var y = track.getBoundingClientRect().top + window.pageYOffset - 100;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }
    }

    /* Validate only the fields inside the current step */
    function validateCurrentStep() {
        var fields = stepEl(current).querySelectorAll('input, select, textarea');
        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].checkValidity()) {
                fields[i].reportValidity();
                return false;
            }
        }
        return true;
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (validateCurrentStep()) goTo(current + 1);
        });
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', function () { goTo(current - 1, true); });
    }

    /* Badges: jump back to any step already visited */
    badges.forEach(function (b) {
        b.addEventListener('click', function () {
            var s = parseInt(b.dataset.step, 10);
            if (s <= maxVisited && s !== current) goTo(s, s < current);
        });
    });

    /* On submit: catch invalid fields hidden in earlier steps */
    if (form) {
        form.addEventListener('submit', function (e) {
            var fields = form.querySelectorAll('input, select, textarea');
            for (var i = 0; i < fields.length; i++) {
                if (!fields[i].checkValidity()) {
                    e.preventDefault();
                    var card = fields[i].closest('.co-step');
                    if (card) goTo(parseInt(card.dataset.step, 10), true);
                    setTimeout(function () { fields[i].reportValidity(); }, 600);
                    return;
                }
            }
        });
    }

    /* Server-side errors: open the first step that has an error */
    var firstError = document.querySelector('.co-step .form-error');
    if (firstError) {
        var errCard = firstError.closest('.co-step');
        if (errCard) {
            var errStep = parseInt(errCard.dataset.step, 10);
            maxVisited = total; /* fields were already filled once */
            goTo(errStep, false, true);
        }
    } else {
        goTo(1, false, true);
    }

    /* ── Upload area: click, drag & drop, preview ── */
    var area     = document.getElementById('uploadArea');
    var input    = area ? area.querySelector('input[type=file]') : null;
    var preview  = document.getElementById('uploadPreview');
    var filename = document.getElementById('uploadFilename');

    function showFile(file) {
        if (!file) return;
        filename.textContent = file.name;
        filename.style.display = 'block';
        if (file.type && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    if (area && input) {
        area.addEventListener('click', function (e) {
            if (e.target !== input) input.click();
        });
        input.addEventListener('change', function () { showFile(input.files[0]); });

        ['dragenter', 'dragover'].forEach(function (ev) {
            area.addEventListener(ev, function (e) {
                e.preventDefault();
                area.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            area.addEventListener(ev, function (e) {
                e.preventDefault();
                area.classList.remove('dragover');
            });
        });
        area.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                showFile(input.files[0]);
            }
        });
    }
}());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
