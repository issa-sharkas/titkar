<?php
// ─────────────────────────────────────
// Pull live settings for footer columns
// ($isRTL already set in header.php, same request scope)
// ─────────────────────────────────────
$deliveryAreas = getSetting('delivery_areas');
$areas         = $deliveryAreas ? array_map('trim', explode(',', $deliveryAreas)) : [];
$instagram     = getSetting('instagram_url');
$facebook      = getSetting('facebook_url');
$tiktok        = getSetting('tiktok_url');
$youtube       = getSetting('youtube_url');
$snapchat      = getSetting('snapchat_url');
$contactEmail  = getSetting('email');
$phoneNumber   = getSetting('phone_number');
$waNumber      = getSetting('whatsapp_number') ?: WHATSAPP_DEFAULT;
$footerDesc    = $isRTL ? getSetting('footer_description_ar') : getSetting('footer_description_en');
$footerAddress = $isRTL ? getSetting('address_ar') : getSetting('address_en');
$footerHours   = $isRTL ? getSetting('working_hours_ar') : getSetting('working_hours_en');
$ftNameAr      = getSetting('site_name_ar') ?: 'تذكار';
$ftNameEn      = getSetting('site_name_en') ?: 'TITHKAR';
$waFloatLink   = getWhatsAppLink($isRTL
    ? 'مرحباً، أريد الاستفسار عن منتجاتكم'
    : 'Hello, I would like to inquire about your products');

// Quick links mirror the admin-managed navigation menu
$footerLinks = [];
foreach (getMenuItems() as $mi) {
    $footerLinks[] = [$mi['label_ar'], $mi['label_en'], menuItemHref($mi)];
}
?>

<!-- ═══════════════════════════════════
     SITE FOOTER
════════════════════════════════════ -->
<footer class="site-footer">
    <div class="footer-grid">

        <!-- Column 1: Brand -->
        <div class="footer-col">
            <a href="<?= SITE_URL ?>/" class="footer-logo">
                <?= sanitize($ftNameAr) ?> | <span><?= sanitize($ftNameEn) ?></span>
            </a>
            <p class="footer-description">
                <?= $footerDesc ? sanitize($footerDesc) : ($isRTL
                    ? 'هدايا عطرية فاخرة مصنوعة بعناية لكل مناسبة. نصنع ذكريات لا تُنسى في قالب من العطر والأناقة.'
                    : 'Exquisite perfume gifts crafted with care for every occasion. We create unforgettable memories wrapped in fragrance and elegance.') ?>
            </p>
            <!-- Social icons -->
            <div class="footer-social">
                <?php if ($instagram): ?>
                <a href="<?= sanitize($instagram) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <?php endif; ?>
                <?php if ($facebook): ?>
                <a href="<?= sanitize($facebook) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <?php endif; ?>
                <?php if ($tiktok): ?>
                <a href="<?= sanitize($tiktok) ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
                <?php endif; ?>
                <?php if ($youtube): ?>
                <a href="<?= sanitize($youtube) ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
                <?php endif; ?>
                <?php if ($snapchat): ?>
                <a href="<?= sanitize($snapchat) ?>" target="_blank" rel="noopener noreferrer" aria-label="Snapchat">
                    <i class="fab fa-snapchat"></i>
                </a>
                <?php endif; ?>
                <a href="<?= $waFloatLink ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div class="footer-col">
            <h4><?= $isRTL ? 'روابط سريعة' : 'Quick Links' ?></h4>
            <div class="footer-links">
                <?php foreach ($footerLinks as [$ar, $en, $href]): ?>
                <a href="<?= $href ?>"><?= $isRTL ? $ar : $en ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Column 3: Contact -->
        <div class="footer-col">
            <h4><?= $isRTL ? 'تواصل معنا' : 'Contact Us' ?></h4>

            <div class="footer-contact-item">
                <i class="fab fa-whatsapp"></i>
                <a href="<?= $waFloatLink ?>" target="_blank" rel="noopener noreferrer">
                    +<?= sanitize($waNumber) ?>
                </a>
            </div>

            <?php if ($contactEmail): ?>
            <div class="footer-contact-item">
                <i class="far fa-envelope"></i>
                <a href="mailto:<?= sanitize($contactEmail) ?>">
                    <?= sanitize($contactEmail) ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($instagram): ?>
            <div class="footer-contact-item">
                <i class="fab fa-instagram"></i>
                <a href="<?= sanitize($instagram) ?>" target="_blank" rel="noopener noreferrer">
                    @tithkar
                </a>
            </div>
            <?php endif; ?>

            <?php if ($facebook): ?>
            <div class="footer-contact-item">
                <i class="fab fa-facebook-f"></i>
                <a href="<?= sanitize($facebook) ?>" target="_blank" rel="noopener noreferrer">
                    <?= sanitize($ftNameEn) ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($phoneNumber): ?>
            <div class="footer-contact-item">
                <i class="fas fa-phone"></i>
                <a href="tel:<?= sanitize($phoneNumber) ?>"><?= sanitize($phoneNumber) ?></a>
            </div>
            <?php endif; ?>

            <?php if ($footerAddress): ?>
            <div class="footer-contact-item">
                <i class="fas fa-location-dot"></i>
                <span><?= sanitize($footerAddress) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($footerHours): ?>
            <div class="footer-contact-item">
                <i class="far fa-clock"></i>
                <span><?= sanitize($footerHours) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Column 4: Delivery Areas -->
        <div class="footer-col">
            <h4><?= $isRTL ? 'مناطق التوصيل' : 'Delivery Areas' ?></h4>
            <?php if ($areas): ?>
            <div class="footer-areas">
                <?php foreach ($areas as $area): ?>
                <span class="footer-area-tag"><?= sanitize($area) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /footer-grid -->

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <p>
            &copy; <?= date('Y') ?> <?= sanitize($ftNameAr) ?> | <?= sanitize($ftNameEn) ?>.
            <?= $isRTL ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' ?>
        </p>
    </div>

</footer><!-- /site-footer -->

<!-- ═══════════════════════════════════
     WHATSAPP FLOATING BUTTON
════════════════════════════════════ -->
<a href="<?= $waFloatLink ?>"
   class="whatsapp-float"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="<?= $isRTL ? 'تواصل عبر واتساب' : 'Chat on WhatsApp' ?>">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- ═══════════════════════════════════
     BACK TO TOP BUTTON
════════════════════════════════════ -->
<button type="button"
        id="backToTop"
        class="back-to-top"
        aria-label="<?= $isRTL ? 'العودة للأعلى' : 'Back to top' ?>">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- ═══════════════════════════════════
     SCRIPTS
════════════════════════════════════ -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script src="<?= SITE_URL ?>/assets/js/cart.js"></script>

<script>
(function () {
    'use strict';

    /* ── Mobile menu ─────────────────────────── */
    var hamburger    = document.getElementById('hamburger');
    var mobileNav    = document.getElementById('mobileNav');
    var mobileOverlay = document.getElementById('mobileOverlay');

    function openMenu() {
        mobileNav.classList.add('open');
        mobileOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        hamburger.setAttribute('aria-expanded', 'true');
        hamburger.classList.add('open');
    }

    function closeMenu() {
        mobileNav.classList.remove('open');
        mobileOverlay.classList.remove('open');
        document.body.style.overflow = '';
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.classList.remove('open');
    }

    if (hamburger) {
        hamburger.addEventListener('click', function () {
            mobileNav.classList.contains('open') ? closeMenu() : openMenu();
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMenu);
    }

    document.querySelectorAll('.mobile-nav a').forEach(function (a) {
        a.addEventListener('click', closeMenu);
    });

    /* ── Scroll-in animations (IntersectionObserver) ── */
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.animate, .reveal').forEach(function (el) {
            io.observe(el);
        });

        /* Auto-observe section titles for the subtitle underline */
        document.querySelectorAll('.section-title').forEach(function (el) {
            el.classList.add('reveal');
            io.observe(el);
        });
    } else {
        document.querySelectorAll('.animate, .reveal').forEach(function (el) {
            el.classList.add('visible');
        });
    }

    /* ── Sticky header scroll class ──────────── */
    var header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function () {
            header.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    /* ── Stagger product/occasion cards on load ── */
    document.querySelectorAll('.product-card, .occasion-card').forEach(function (card, i) {
        card.style.opacity = '0';
        card.style.animation = 'cardEntrance 0.6s cubic-bezier(0.22,1,0.36,1) ' + (i * 0.08) + 's both';
    });

    /* ── Button ripple effect ────────────────── */
    document.querySelectorAll('.btn-primary, .btn-outline').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var rect = btn.getBoundingClientRect();
            var ripple = document.createElement('span');
            var size = Math.max(rect.width, rect.height);
            ripple.style.cssText = [
                'position:absolute',
                'border-radius:50%',
                'background:rgba(255,255,255,0.35)',
                'width:' + size + 'px',
                'height:' + size + 'px',
                'top:' + (e.clientY - rect.top - size/2) + 'px',
                'left:' + (e.clientX - rect.left - size/2) + 'px',
                'transform:scale(0)',
                'animation:ripple 0.55s ease-out forwards',
                'pointer-events:none'
            ].join(';');
            btn.style.position = btn.style.position || 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    });

}());
</script>

</body>
</html>
