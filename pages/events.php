<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.events-hero{background:linear-gradient(135deg,#FAF7F2 0%,#F3EDE3 100%);padding:90px 20px;text-align:center;}
.events-hero h1{font-size:clamp(1.8rem,4vw,2.8rem);color:var(--color-text);margin-bottom:16px;line-height:1.3;}
.events-hero p{font-size:1.05rem;color:var(--color-brown);max-width:560px;margin:0 auto 32px;}
.occ-card{background:white;border-radius:var(--card-radius);padding:32px 24px;box-shadow:0 4px 24px rgba(0,0,0,.07);text-align:center;transition:transform .25s,box-shadow .25s;display:flex;flex-direction:column;align-items:center;gap:14px;}
.occ-card:hover{transform:translateY(-5px);box-shadow:0 10px 40px rgba(0,0,0,.12);}
.occ-card-icon{font-size:2.8rem;line-height:1;}
.occ-card-title{font-size:1.15rem;font-weight:700;color:var(--color-text);}
.occ-card-desc{font-size:.875rem;color:var(--color-brown);line-height:1.5;}
.value-check{display:flex;align-items:flex-start;gap:14px;padding:14px 0;border-bottom:1px solid var(--color-border);}
.value-check:last-child{border-bottom:none;}
.value-check-icon{width:36px;height:36px;border-radius:50%;background:rgba(201,169,110,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-gold);}
.step-row{display:flex;align-items:flex-start;gap:16px;padding:16px 0;}
.step-num{width:38px;height:38px;border-radius:50%;background:var(--color-gold);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;font-size:.95rem;}
.step-connector{width:2px;background:rgba(201,169,110,.25);flex-shrink:0;margin:0 18px;}
</style>

<!-- Hero -->
<div class="events-hero">
    <div class="container">
        <div style="font-size:2.8rem;margin-bottom:16px;">🎁</div>
        <h1><?= $isRTL ? 'هدايا عطرية مخصصة لمناسبات لا تُنسى' : 'Custom Fragrance Gifts for Unforgettable Occasions' ?></h1>
        <p><?= $isRTL
            ? 'من الأفراح والخطوبات إلى أعياد الميلاد وفعاليات الشركات — نصنع لك هدية تدوم في الذاكرة.'
            : 'From weddings and engagements to birthdays and corporate events — we craft gifts that live in memory.' ?></p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-primary">
                <i class="fas fa-sparkles"></i>
                <?= $isRTL ? 'اطلب عرض سعر' : 'Request a Quote' ?>
            </a>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn-outline">
                <i class="fas fa-book-open"></i>
                <?= $isRTL ? 'تصفح الكتالوج' : 'Browse Catalogue' ?>
            </a>
        </div>
    </div>
</div>

<!-- Occasions Grid -->
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'اختر مناسبتك' : 'Choose Your Occasion' ?></span>
        <h2><?= $isRTL ? 'لكل مناسبة هديتها' : 'A Gift for Every Occasion' ?></h2>
    </div>
    <?php
    $occasions = [
        ['🤵💍','أفراح','Weddings','توزيعات وهدايا عطرية فاخرة للعرائس','Luxury wedding favour gifts',SITE_URL.'/pages/weddings.php','#C9A96E'],
        ['💎','خطوبات','Engagements','هدايا ناعمة بتفاصيل مخصصة','Delicate personalised engagement gifts',SITE_URL.'/pages/engagements.php','#8B1A2E'],
        ['🎂','أعياد ميلاد','Birthdays','هدايا مميزة تُبهج كل الأعمار','Special gifts that delight every age',SITE_URL.'/pages/birthdays.php','#C9A96E'],
        ['🏢','هدايا شركات','Corporate','هدايا مؤسسية تحمل هوية علامتك','Corporate gifts that carry your brand identity',SITE_URL.'/pages/corporate.php','#6B4F3A'],
        ['👶','استقبال مولود','Baby Shower','هدايا رقيقة وجميلة للمواليد الجدد','Delicate gifts for new arrivals',SITE_URL.'/pages/custom-order.php?event_type=baby_shower','#E4405F'],
        ['🎓','تخرج','Graduation','احتفل بالإنجاز بهدية لا تُنسى','Celebrate achievement with an unforgettable gift',SITE_URL.'/pages/custom-order.php?event_type=graduation','#8B1A2E'],
    ];
    ?>
    <div class="grid-3">
        <?php foreach ($occasions as $i => [$emoji, $ar, $en, $descAr, $descEn, $link, $color]): ?>
        <div class="occ-card animate fade-in-delay-<?= ($i % 3) + 1 ?>">
            <div class="occ-card-icon"><?= $emoji ?></div>
            <div class="occ-card-title"><?= $isRTL ? $ar : $en ?></div>
            <div class="occ-card-desc"><?= $isRTL ? $descAr : $descEn ?></div>
            <a href="<?= $link ?>" class="btn-primary btn-sm" style="margin-top:4px;">
                <?= $isRTL ? 'اعرف أكثر' : 'Learn More' ?>
                <i class="fas fa-arrow-<?= $isRTL ? 'left' : 'right' ?>" style="font-size:.75rem;margin-<?= $isRTL ? 'right' : 'left' ?>:6px;"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- Why TITHKAR -->
<section class="section section-alt">
<div class="container">
    <div class="grid-2" style="gap:48px;align-items:center;">
        <div class="animate">
            <span class="section-subtitle"><?= $isRTL ? 'لماذا تذكار؟' : 'Why TITHKAR?' ?></span>
            <h2><?= $isRTL ? 'ما الذي يجعلنا مختلفين' : 'What Makes Us Different' ?></h2>
            <p style="margin-bottom:24px;color:var(--color-brown);">
                <?= $isRTL
                    ? 'نؤمن أن كل مناسبة تستحق هدية استثنائية — مصنوعة بعناية، مخصصة بالكامل، ومُقدَّمة بأسلوب لا يُنسى.'
                    : 'We believe every occasion deserves an exceptional gift — crafted with care, fully personalised, and presented in a way you\'ll never forget.' ?>
            </p>
            <?php
            $values = $isRTL ? [
                ['fa-droplet',      'عطور مختارة بعناية',   'نختار أجود العطور التي تلائم كل مناسبة ومزاج.'],
                ['fa-box-open',     'تغليف فاخر ومخصص',     'من الريبون إلى البوكس — كل تفصيلة مدروسة.'],
                ['fa-fingerprint',  'تخصيص كامل لكل طلب',  'اسمك، تاريخك، ستايلك — الهدية ملكك 100%.'],
                ['fa-truck',        'توصيل لكل أنحاء القاهرة','نوصّل لبابك في الموعد المحدد بلا تأخير.'],
            ] : [
                ['fa-droplet',      'Carefully Selected Fragrances', 'We choose the finest fragrances to suit every occasion and mood.'],
                ['fa-box-open',     'Luxury Custom Packaging',       'From ribbon to box — every detail is thoughtfully designed.'],
                ['fa-fingerprint',  'Fully Personalised Orders',     'Your name, your date, your style — the gift is 100% yours.'],
                ['fa-truck',        'Cairo-Wide Delivery',           'We deliver to your door on time, every time.'],
            ];
            foreach ($values as [$icon, $title, $desc]): ?>
            <div class="value-check">
                <div class="value-check-icon"><i class="fas <?= $icon ?>"></i></div>
                <div>
                    <strong style="display:block;margin-bottom:3px;"><?= $title ?></strong>
                    <span style="font-size:.875rem;color:var(--color-brown);"><?= $desc ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Process Steps -->
        <div class="animate">
            <span class="section-subtitle"><?= $isRTL ? 'كيف تعمل؟' : 'How It Works' ?></span>
            <h2><?= $isRTL ? 'خمس خطوات بسيطة' : 'Five Simple Steps' ?></h2>
            <?php
            $steps = $isRTL ? [
                'تواصل معنا وأخبرنا عن مناسبتك وتفاصيلها.',
                'نرسل لك عرض سعر مخصص خلال 24 ساعة.',
                'تختار العطر والتغليف والتصميم الذي يعجبك.',
                'نجهّز طلبك بعناية ودقة متناهية.',
                'نوصّله لبابك في الموعد المحدد.',
            ] : [
                'Contact us and tell us about your occasion.',
                'We send you a personalised quote within 24 hours.',
                'You choose the fragrance, packaging, and design.',
                'We prepare your order with meticulous care.',
                'We deliver it to your door on schedule.',
            ];
            foreach ($steps as $idx => $step): ?>
            <div class="step-row">
                <div class="step-num"><?= $idx + 1 ?></div>
                <div style="padding-top:8px;">
                    <p style="margin:0;font-size:.95rem;color:var(--color-brown);"><?= $step ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</section>

<!-- CTA -->
<section style="background:var(--color-text);padding:72px 20px;text-align:center;">
<div class="container">
    <div class="animate">
        <span class="section-subtitle" style="color:var(--color-gold);opacity:1;"><?= $isRTL ? 'ابدأ الآن' : 'Get Started' ?></span>
        <h2 style="color:white;margin-bottom:14px;">
            <?= $isRTL ? 'ابدأ بطلب عرض سعر لمناسبتك' : 'Start with a quote for your occasion' ?>
        </h2>
        <p style="color:rgba(232,213,176,.8);margin-bottom:32px;max-width:500px;margin-inline:auto;">
            <?= $isRTL
                ? 'سيتواصل معك فريقنا خلال 24 ساعة بعرض سعر مخصص لمناسبتك.'
                : 'Our team will contact you within 24 hours with a personalised quote for your occasion.' ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-primary">
                <i class="fas fa-paper-plane"></i>
                <?= $isRTL ? 'اطلب عرض سعر' : 'Request a Quote' ?>
            </a>
            <a href="<?= $waLink ?>" class="btn-whatsapp" target="_blank" rel="noopener noreferrer" style="display:inline-flex;">
                <i class="fab fa-whatsapp"></i>
                <?= $isRTL ? 'تحدث معنا' : 'Chat with Us' ?>
            </a>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
