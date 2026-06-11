<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$contactEmail = getSetting('email');
$instagram    = getSetting('instagram_url');
$facebook     = getSetting('facebook_url');
$phoneNumber  = getSetting('phone_number');
$waNumber     = getSetting('whatsapp_number') ?: WHATSAPP_DEFAULT;
$cAddress     = isRTL() ? getSetting('address_ar') : getSetting('address_en');
$cHours       = isRTL() ? getSetting('working_hours_ar') : getSetting('working_hours_en');
$deliveryRaw  = getSetting('delivery_areas');
$areas        = $deliveryRaw ? array_map('trim', explode(',', $deliveryRaw)) : ['New Cairo','Heliopolis','Nasr City','Madinaty','Rehab','Shorouk','Cairo','Giza'];

// ── POST: save contact message ─────────────────────────────────────────────
$msgSent  = false;
$msgError = '';
$formVals = ['name'=>'','phone'=>'','email'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formVals = [
        'name'    => sanitize($_POST['name']    ?? ''),
        'phone'   => sanitize($_POST['phone']   ?? ''),
        'email'   => sanitize($_POST['email']   ?? ''),
        'message' => sanitize($_POST['message'] ?? ''),
    ];
    $cErrors = [];
    if (empty($formVals['name']))    { $cErrors[] = 'الاسم مطلوب / Name required'; }
    if (empty($formVals['phone']))   { $cErrors[] = 'الهاتف مطلوب / Phone required'; }
    if (empty($formVals['message'])) { $cErrors[] = 'الرسالة مطلوبة / Message required'; }

    if (empty($cErrors)) {
        try {
            $pdo->prepare('INSERT INTO contact_messages (name, phone, email, message) VALUES (?, ?, ?, ?)')
                ->execute([$formVals['name'], $formVals['phone'], $formVals['email'], $formVals['message']]);
            $msgSent = true;
        } catch (Exception $e) {
            $msgError = 'db_error';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.contact-card{display:flex;align-items:center;gap:16px;background:white;border-radius:14px;padding:20px 22px;box-shadow:0 4px 20px rgba(0,0,0,.05);text-decoration:none;transition:transform .2s,box-shadow .2s;}
.contact-card:hover{transform:translateY(-3px);box-shadow:0 8px 32px rgba(0,0,0,.1);}
.contact-card-icon{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem;}
.contact-card-label{font-weight:600;color:var(--color-text);font-size:.95rem;margin-bottom:2px;}
.contact-card-sub{color:var(--color-brown);font-size:.85rem;}
.area-tag{display:inline-block;background:rgba(201,169,110,.1);color:var(--color-gold);border:1px solid rgba(201,169,110,.3);border-radius:20px;padding:4px 14px;font-size:.8rem;margin:3px;}
.cf-input{width:100%;padding:12px 14px;border:1px solid var(--color-border);border-radius:8px;font-family:var(--font-ar);font-size:.9rem;background:white;box-sizing:border-box;outline:none;transition:border-color .2s;color:var(--color-text);}
.cf-input:focus{border-color:var(--color-gold);}
</style>

<!-- Banner -->
<div class="page-banner">
    <div class="container">
        <h1><?= $isRTL ? 'تواصل معنا' : 'Contact Us' ?></h1>
        <p style="margin-top:10px;color:var(--color-brown);">
            <?= $isRTL ? 'نحن هنا للمساعدة — تواصل بأي طريقة تناسبك' : "We're here to help — reach us any way that suits you" ?>
        </p>
    </div>
</div>

<section class="section">
<div class="container">
<div class="grid-2" style="gap:48px;align-items:start;">

    <!-- LEFT: Contact Form -->
    <div class="animate">
        <span class="section-subtitle"><?= $isRTL ? 'أرسل رسالة' : 'Send a Message' ?></span>
        <h2 style="margin-bottom:24px;"><?= $isRTL ? 'كيف يمكننا مساعدتك؟' : 'How Can We Help?' ?></h2>

        <?php if ($msgSent): ?>
        <div class="alert alert-success" style="margin-bottom:24px;">
            <i class="fas fa-circle-check"></i>
            <?= $isRTL ? 'تم استلام رسالتك بنجاح! سنتواصل معك قريباً.' : 'Message received! We\'ll be in touch soon.' ?>
        </div>
        <?php elseif ($msgError === 'db_error'): ?>
        <div class="alert alert-error" style="margin-bottom:24px;">
            <i class="fas fa-circle-exclamation"></i>
            <?= $isRTL ? 'حدث خطأ مؤقت. تواصل معنا مباشرة على واتساب:' : 'A temporary error occurred. Contact us directly on WhatsApp:' ?>
            <a href="<?= $waLink ?>" style="color:inherit;font-weight:700;margin-<?= $isRTL ? 'right' : 'left' ?>:6px;" target="_blank"><i class="fab fa-whatsapp"></i></a>
        </div>
        <?php elseif (!empty($cErrors ?? [])): ?>
        <div class="alert alert-error" style="margin-bottom:24px;">
            <i class="fas fa-circle-exclamation"></i> <?= implode(' &nbsp;·&nbsp; ', $cErrors) ?>
        </div>
        <?php endif; ?>

        <?php if (!$msgSent): ?>
        <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
            <div class="form-row" style="gap:14px;">
                <div>
                    <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;"><?= $isRTL ? 'الاسم *' : 'Name *' ?></label>
                    <input class="cf-input" type="text" name="name" value="<?= htmlspecialchars($formVals['name'], ENT_QUOTES) ?>"
                           placeholder="<?= $isRTL ? 'مثال: أحمد محمد' : 'e.g. Ahmed Mohamed' ?>" required>
                </div>
                <div>
                    <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;"><?= $isRTL ? 'رقم الهاتف *' : 'Phone *' ?></label>
                    <input class="cf-input" type="tel" name="phone" value="<?= htmlspecialchars($formVals['phone'], ENT_QUOTES) ?>"
                           placeholder="01XXXXXXXXX" required>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;"><?= $isRTL ? 'البريد الإلكتروني' : 'Email' ?></label>
                <input class="cf-input" type="email" name="email" value="<?= htmlspecialchars($formVals['email'], ENT_QUOTES) ?>"
                       placeholder="<?= $isRTL ? 'اختياري' : 'optional' ?>">
            </div>
            <div>
                <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;"><?= $isRTL ? 'رسالتك *' : 'Message *' ?></label>
                <textarea class="cf-input" name="message" rows="5"
                          placeholder="<?= $isRTL ? 'اكتب استفسارك أو رسالتك هنا...' : 'Write your inquiry or message here...' ?>"
                          required><?= htmlspecialchars($formVals['message'], ENT_QUOTES) ?></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:1rem;">
                <i class="fas fa-paper-plane"></i>
                <?= $isRTL ? 'إرسال الرسالة' : 'Send Message' ?>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Contact Info -->
    <div class="animate">
        <span class="section-subtitle"><?= $isRTL ? 'طرق التواصل' : 'Contact Methods' ?></span>
        <h2 style="margin-bottom:24px;"><?= $isRTL ? 'نحن متواجدون دائماً' : 'We\'re Always Here' ?></h2>

        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:28px;">

            <!-- WhatsApp -->
            <a href="<?= $waLink ?>" target="_blank" rel="noopener noreferrer" class="contact-card"
               style="border:1.5px solid rgba(37,211,102,.2);">
                <div class="contact-card-icon" style="background:rgba(37,211,102,.1);color:#25D366;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <div class="contact-card-label"><?= $isRTL ? 'واتساب' : 'WhatsApp' ?></div>
                    <div class="contact-card-sub">+<?= sanitize($waNumber) ?></div>
                </div>
                <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>" style="margin-<?= $isRTL ? 'right' : 'left' ?>:auto;color:var(--color-gold);font-size:.85rem;"></i>
            </a>

            <!-- Instagram -->
            <?php if ($instagram): ?>
            <a href="<?= sanitize($instagram) ?>" target="_blank" rel="noopener noreferrer" class="contact-card">
                <div class="contact-card-icon" style="background:rgba(228,64,95,.08);color:#E4405F;">
                    <i class="fab fa-instagram"></i>
                </div>
                <div>
                    <div class="contact-card-label">Instagram</div>
                    <div class="contact-card-sub">@tithkar</div>
                </div>
            </a>
            <?php endif; ?>

            <!-- Facebook -->
            <?php if ($facebook): ?>
            <a href="<?= sanitize($facebook) ?>" target="_blank" rel="noopener noreferrer" class="contact-card">
                <div class="contact-card-icon" style="background:rgba(24,119,242,.08);color:#1877F2;">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div>
                    <div class="contact-card-label">Facebook</div>
                    <div class="contact-card-sub">تذكار</div>
                </div>
            </a>
            <?php endif; ?>

            <!-- Phone -->
            <?php if ($phoneNumber): ?>
            <a href="tel:<?= sanitize($phoneNumber) ?>" class="contact-card">
                <div class="contact-card-icon" style="background:rgba(107,79,58,.08);color:var(--color-brown);">
                    <i class="fas fa-phone"></i>
                </div>
                <div>
                    <div class="contact-card-label"><?= $isRTL ? 'الهاتف' : 'Phone' ?></div>
                    <div class="contact-card-sub" dir="ltr"><?= sanitize($phoneNumber) ?></div>
                </div>
            </a>
            <?php endif; ?>

            <!-- Email -->
            <?php if ($contactEmail): ?>
            <a href="mailto:<?= sanitize($contactEmail) ?>" class="contact-card">
                <div class="contact-card-icon" style="background:rgba(201,169,110,.1);color:var(--color-gold);">
                    <i class="far fa-envelope"></i>
                </div>
                <div>
                    <div class="contact-card-label"><?= $isRTL ? 'البريد الإلكتروني' : 'Email' ?></div>
                    <div class="contact-card-sub"><?= sanitize($contactEmail) ?></div>
                </div>
            </a>
            <?php endif; ?>

        </div>

        <!-- Address & working hours -->
        <?php if ($cAddress || $cHours): ?>
        <div style="background:var(--color-surface);border-radius:14px;padding:20px;margin-bottom:24px;">
            <?php if ($cAddress): ?>
            <div style="display:flex;align-items:center;gap:10px;font-size:.9rem;color:var(--color-brown);margin-bottom:<?= $cHours ? '10px' : '0' ?>;">
                <i class="fas fa-location-dot" style="color:var(--color-gold);"></i>
                <?= sanitize($cAddress) ?>
            </div>
            <?php endif; ?>
            <?php if ($cHours): ?>
            <div style="display:flex;align-items:center;gap:10px;font-size:.9rem;color:var(--color-brown);">
                <i class="far fa-clock" style="color:var(--color-gold);"></i>
                <?= sanitize($cHours) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Delivery areas -->
        <?php if ($areas): ?>
        <div style="background:var(--color-surface);border-radius:14px;padding:20px;margin-bottom:24px;">
            <h4 style="margin-bottom:14px;font-size:.95rem;">
                <i class="fas fa-location-dot" style="color:var(--color-gold);margin-<?= $isRTL ? 'left' : 'right' ?>:8px;"></i>
                <?= $isRTL ? 'مناطق التوصيل' : 'Delivery Areas' ?>
            </h4>
            <div>
                <?php foreach ($areas as $area): ?>
                <span class="area-tag"><?= sanitize($area) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Large WhatsApp CTA -->
        <a href="<?= $waLink ?>" target="_blank" rel="noopener noreferrer"
           class="btn-whatsapp" style="width:100%;justify-content:center;padding:16px;font-size:1rem;display:flex;">
            <i class="fab fa-whatsapp"></i>
            <?= $isRTL ? 'ابدأ محادثة الآن' : 'Start a Conversation Now' ?>
        </a>
        <p style="text-align:center;font-size:.8rem;color:var(--color-brown);margin-top:8px;">
            <?= $isRTL ? 'نرد خلال دقائق · متاح يومياً' : 'We reply within minutes · Available daily' ?>
        </p>

    </div>

</div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
