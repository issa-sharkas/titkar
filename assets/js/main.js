// TITHKAR | تذكار — Main JavaScript

// ── Image gallery (product page) ────────────────────────────────────────────
function switchImage(src, thumbEl) {
    var main = document.getElementById('mainProductImage');
    if (!main) return;
    main.src = src;
    document.querySelectorAll('.thumb-item').forEach(function(t) { t.classList.remove('active'); });
    if (thumbEl) thumbEl.classList.add('active');
}

// ── Product page tabs ────────────────────────────────────────────────────────
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    var panel = document.getElementById(tabId);
    if (panel) panel.classList.add('active');
    if (btn)   btn.classList.add('active');
}

// ── Quantity steppers (product page + cart) ──────────────────────────────────
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.qty-btn');
    if (!btn) return;
    var wrap = btn.closest('.qty-wrap');
    if (!wrap) return;
    var input = wrap.querySelector('.qty-input');
    if (!input) return;

    var val  = parseInt(input.value, 10) || 1;
    var min  = parseInt(input.min, 10)   || 1;
    var max  = parseInt(input.max, 10)   || 99;

    if (btn.dataset.dir === 'up')   val = Math.min(val + 1, max);
    if (btn.dataset.dir === 'down') val = Math.max(val - 1, min);
    input.value = val;

    // Auto-submit cart update forms with debounce
    var form = wrap.closest('.qty-form');
    if (form) {
        clearTimeout(form._debounce);
        form._debounce = setTimeout(function() { form.submit(); }, 400);
    }
});

// ── Smooth-scroll anchor links ────────────────────────────────────────────────
document.addEventListener('click', function(e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a) return;
    var id = a.getAttribute('href').slice(1);
    var target = document.getElementById(id);
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

// ── Back-to-top (if element exists) ──────────────────────────────────────────
var btt = document.getElementById('backToTop');
if (btt) {
    window.addEventListener('scroll', function() {
        btt.classList.toggle('visible', window.scrollY > 400);
    });
    btt.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ── Alert auto-dismiss ────────────────────────────────────────────────────────
document.querySelectorAll('.alert[data-dismiss]').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 400);
    }, parseInt(el.dataset.dismiss, 10) || 4000);
});

// ── Sticky header — .scrolled class after 50 px ──────────────────────────────
(function() {
    var header = document.querySelector('.site-header');
    if (!header) { return; }
    function onScroll() {
        header.classList.toggle('scrolled', window.scrollY > 50);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // apply immediately on load
}());
