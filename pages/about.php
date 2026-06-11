<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.about-hero{background:linear-gradient(135deg,#FAF7F2 0%,#F3EDE3 100%);padding:90px 20px;text-align:center;}
.about-hero h1{font-size:clamp(1.8rem,4vw,2.8rem);margin-bottom:16px;line-height:1.3;}
.about-hero p{max-width:580px;margin:0 auto;color:var(--color-brown);font-size:1.05rem;line-height:1.8;}
.mvv-card{background:white;border-radius:var(--card-radius);padding:32px 24px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.07);border-top:3px solid var(--color-gold);}
.mvv-icon{width:64px;height:64px;border-radius:50%;background:rgba(201,169,110,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;color:var(--color-gold);}
.val-card{display:flex;align-items:flex-start;gap:16px;background:white;border-radius:14px;padding:20px;box-shadow:0 2px 14px rgba(0,0,0,.05);}
.val-icon{width:44px;height:44px;border-radius:50%;background:rgba(201,169,110,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;color:var(--color-gold);}
.stat-item{text-align:center;}
.stat-num{font-size:2.5rem;font-weight:800;color:var(--color-gold);display:block;line-height:1.1;}
.stat-label{font-size:.875rem;color:rgba(232,213,176,.75);margin-top:4px;display:block;}
</style>

<!-- Hero -->
<div class="about-hero">
    <div class="container">
        <span class="section-subtitle"><?= $isRTL ? 'عن تذكار' : 'About TITHKAR' ?></span>
        <h1><?= $isRTL ? 'لأن كل لحظة جميلة تستحق أن تُحفظ' : 'Because Every Beautiful Moment Deserves to Be Kept' ?></h1>
        <p><?= $isRTL
            ? 'نصنع هدايا عطرية مخصصة تحمل اسمك وقصتك وتاريخك — لتبقى في الذاكرة مهما مرّ الوقت.'
            : 'We craft personalised fragrance gifts that carry your name, story, and date — to live in memory no matter how much time passes.' ?></p>
    </div>
</div>

<!-- Brand Story -->
<section class="section">
<div class="container">
    <div style="max-width:760px;margin:0 auto;text-align:center;" class="animate">
        <span class="section-subtitle"><?= $isRTL ? 'قصتنا' : 'Our Story' ?></span>
        <h2 style="margin-bottom:28px;"><?= $isRTL ? 'تذكار — من أين بدأنا' : 'TITHKAR — Where We Started' ?></h2>
        <p style="font-size:1.1rem;line-height:2;color:var(--color-brown);margin-bottom:20px;">
            <?= $isRTL
                ? 'تذكار هو براند متخصص في العطور المختارة بعناية والهدايا المخصصة للمناسبات. نؤمن أن كل لحظة جميلة تستحق أن تُحفظ، وأن الرائحة قادرة على إعادة الذكرى مهما مر الوقت.'
                : 'TITHKAR is a brand specialised in carefully selected fragrances and custom gifts for every occasion. We believe that every beautiful moment deserves to be preserved, and that a scent can bring back a memory no matter how much time has passed.' ?>
        </p>
        <p style="font-size:1.05rem;line-height:2;color:var(--color-brown);margin-bottom:20px;">
            <?= $isRTL
                ? 'نصنع باكدجات هدايا جاهزة، ونصمم توزيعات عطرية مخصصة للأفراح، الخطوبات، أعياد الميلاد، الشركات، والمناسبات الخاصة. كل هدية تُصنع بعناية وتفكير — لأن الهدية الجيدة لا تُشترى فقط، بل تُصنع.'
                : 'We create ready gift packages and design personalised fragrance favours for weddings, engagements, birthdays, corporate events, and special occasions. Every gift is made with care and thought — because a great gift isn\'t just bought, it\'s crafted.' ?>
        </p>
        <p style="font-size:1.05rem;line-height:2;color:var(--color-brown);">
            <?= $isRTL
                ? 'بدأنا بحلم بسيط: تحويل العطور الفاخرة إلى تجارب هدايا لا تُنسى. واليوم، كل طلب نستقبله هو فرصة لنصنع ذكرى جديدة.'
                : 'We started with a simple dream: to turn luxury fragrances into unforgettable gifting experiences. Today, every order we receive is an opportunity to create a new memory.' ?>
        </p>
    </div>
</div>
</section>

<!-- Mission / Vision / Values -->
<section class="section section-alt">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'من نحن' : 'Who We Are' ?></span>
        <h2><?= $isRTL ? 'الرسالة والرؤية والقيم' : 'Mission, Vision & Values' ?></h2>
    </div>
    <div class="grid-3">
        <?php
        $mvv = $isRTL ? [
            ['fa-bullseye', 'الرسالة', 'أن نحول كل هدية إلى ذكرى لا تُنسى — بعناية في كل تفصيلة، وحرفية في كل منتج.'],
            ['fa-eye',      'الرؤية',  'أن يصبح تذكار الاسم الأول في مصر للهدايا العطرية المخصصة، ومرجعاً لكل من يبحث عن هدية مميزة.'],
            ['fa-gem',      'قيمنا',   'الجودة · الذوق الرفيع · التخصيص الكامل · الاهتمام بأدق التفاصيل · الصدق مع عملائنا.'],
        ] : [
            ['fa-bullseye', 'Mission', 'To turn every gift into an unforgettable memory — with care in every detail and craftsmanship in every product.'],
            ['fa-eye',      'Vision',  'To make TITHKAR the first name in Egypt for personalised fragrance gifts, and the go-to for anyone seeking a meaningful present.'],
            ['fa-gem',      'Values',  'Quality · Refined taste · Full personalisation · Attention to the finest details · Honesty with our clients.'],
        ];
        foreach ($mvv as $i => [$icon, $title, $desc]):
        ?>
        <div class="mvv-card animate fade-in-delay-<?= $i + 1 ?>">
            <div class="mvv-icon"><i class="fas <?= $icon ?>"></i></div>
            <h3 style="margin-bottom:12px;"><?= $title ?></h3>
            <p style="font-size:.9rem;color:var(--color-brown);line-height:1.7;"><?= $desc ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- 5 Values -->
<section class="section">
<div class="container">
    <div class="section-title animate">
        <span class="section-subtitle"><?= $isRTL ? 'ما يميزنا' : 'What Sets Us Apart' ?></span>
        <h2><?= $isRTL ? 'خمس ركائز نؤمن بها' : 'Five Pillars We Believe In' ?></h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        <?php
        $vals = $isRTL ? [
            ['fa-droplet',     'عطور مختارة بعناية',    'نختار أجود العطور التي تلائم كل مناسبة ومزاج، بمعايير جودة صارمة.'],
            ['fa-box-open',    'تغليف فاخر ومخصص',      'من الريبون إلى البوكس إلى الكارت — كل تفصيلة مدروسة ومصممة لتُبهر.'],
            ['fa-fingerprint', 'تخصيص كامل لكل طلب',   'اسمك، تاريخك، ستايلك — الهدية ملكك 100% ولا تشبه أي هدية أخرى.'],
            ['fa-truck',       'توصيل لأنحاء القاهرة',  'نوصّل لبابك في الموعد المحدد بلا تأخير، بعناية في التغليف أثناء التوصيل.'],
            ['fa-heart',       'اهتمام حقيقي بكل طلب',  'كل طلب نتعامل معه كأنه لشخص غالٍ علينا — لأن كل مناسبة تستحق ذلك.'],
        ] : [
            ['fa-droplet',     'Carefully Selected Fragrances', 'We choose the finest fragrances suited to every occasion and mood, to the highest standards.'],
            ['fa-box-open',    'Luxury Custom Packaging',       'From ribbon to box to card — every detail is considered and designed to impress.'],
            ['fa-fingerprint', 'Fully Personalised Orders',     'Your name, your date, your style — the gift is 100% yours and unlike any other.'],
            ['fa-truck',       'Cairo-Wide Delivery',           'We deliver to your door on time, every time, with careful packaging throughout transit.'],
            ['fa-heart',       'Genuine Care for Every Order',  'Every request is treated as if it\'s for someone we love — because every occasion deserves it.'],
        ];
        foreach ($vals as $i => [$icon, $title, $desc]):
        ?>
        <div class="val-card animate fade-in-delay-<?= ($i % 3) + 1 ?>">
            <div class="val-icon"><i class="fas <?= $icon ?>"></i></div>
            <div>
                <strong style="display:block;margin-bottom:5px;font-size:.95rem;"><?= $title ?></strong>
                <span style="font-size:.85rem;color:var(--color-brown);line-height:1.6;"><?= $desc ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- Stats Strip -->
<section style="background:var(--color-text);padding:56px 20px;">
<div class="container">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;text-align:center;" class="animate">
        <?php
        $stats = $isRTL ? [
            ['+1000', 'عميل سعيد'],
            ['+50',   'منتج فاخر'],
            ['+5',    'سنوات خبرة'],
        ] : [
            ['+1000', 'Happy Clients'],
            ['+50',   'Luxury Products'],
            ['+5',    'Years of Experience'],
        ];
        foreach ($stats as [$num, $label]):
        ?>
        <div class="stat-item">
            <span class="stat-num"><?= $num ?></span>
            <span class="stat-label"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- CTA -->
<section style="background:var(--color-gold);padding:72px 20px;text-align:center;">
<div class="container animate">
    <h2 style="color:white;margin-bottom:14px;">
        <?= $isRTL ? 'جاهز لتصميم هديتك المميزة؟' : 'Ready to Design Your Special Gift?' ?>
    </h2>
    <p style="color:rgba(255,255,255,.85);margin-bottom:32px;max-width:480px;margin-inline:auto;">
        <?= $isRTL
            ? 'تواصل معنا الآن ونبدأ معاً في صنع هدية لا تُنسى تعكس ذوقك وتحكي قصتك.'
            : 'Get in touch now and let\'s create together an unforgettable gift that reflects your taste and tells your story.' ?>
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a href="<?= SITE_URL ?>/pages/custom-order.php" class="btn-primary" style="background:white;color:var(--color-gold);border-color:white;">
            <i class="fas fa-sparkles"></i>
            <?= $isRTL ? 'ابدأ طلبك الآن' : 'Start Your Order' ?>
        </a>
        <a href="<?= SITE_URL ?>/pages/contact.php" class="btn-outline" style="border-color:white;color:white;">
            <i class="fas fa-envelope"></i>
            <?= $isRTL ? 'تواصل معنا' : 'Contact Us' ?>
        </a>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
