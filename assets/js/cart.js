/* TITHKAR | تذكار — Cart JavaScript */
(function () {
    'use strict';

    /* ── Add-to-cart AJAX ──────────────────────── */
    document.querySelectorAll('.cart-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('.add-to-cart-btn');
            if (btn) { btn.disabled = true; }

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    updateCartCount(data.cartCount);
                    showToast(document.documentElement.lang === 'ar'
                        ? 'تمت الإضافة إلى السلة ✓'
                        : 'Added to cart ✓');
                }
            })
            .catch(function () {
                form.submit(); // graceful fallback
            })
            .finally(function () {
                if (btn) { btn.disabled = false; }
            });
        });
    });

    /* ── Update cart count badge ────────────────── */
    function updateCartCount(count) {
        var icon  = document.querySelector('.cart-icon');
        var badge = document.querySelector('.cart-count');

        if (!icon) { return; }

        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            } else {
                badge = document.createElement('span');
                badge.className   = 'cart-count';
                badge.textContent = count;
                icon.appendChild(badge);
            }
        } else {
            if (badge) { badge.remove(); }
        }
    }

    /* ── Toast notification ─────────────────────── */
    function showToast(msg) {
        // Inject styles once
        if (!document.getElementById('toast-style')) {
            var s = document.createElement('style');
            s.id  = 'toast-style';
            s.textContent = [
                '.tk-toast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%) translateY(20px);',
                'background:#2C1F14;color:#E8D5B0;padding:12px 24px;border-radius:50px;',
                'font-size:0.9rem;opacity:0;transition:all 0.3s ease;z-index:9999;white-space:nowrap;',
                'box-shadow:0 8px 24px rgba(0,0,0,0.2);}',
                '.tk-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}'
            ].join('');
            document.head.appendChild(s);
        }

        var toast       = document.createElement('div');
        toast.className = 'tk-toast';
        toast.textContent = msg;
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('show'); });
        });

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 350);
        }, 2600);
    }

    /* ── Qty stepper (cart page) ────────────────── */
    document.querySelectorAll('.qty-form').forEach(function (form) {
        var input = form.querySelector('.qty-input');
        if (!input) { return; }

        form.querySelector('.qty-minus')?.addEventListener('click', function () {
            var v = parseInt(input.value, 10);
            if (v > 1) { input.value = v - 1; submitQtyForm(form); }
        });

        form.querySelector('.qty-plus')?.addEventListener('click', function () {
            var v = parseInt(input.value, 10);
            input.value = v + 1;
            submitQtyForm(form);
        });

        input.addEventListener('change', function () {
            submitQtyForm(form);
        });
    });

    /* ── Cart URL helper ─────────────────────────── */
    function cartUrl() {
        return (window.TITHKAR && window.TITHKAR.cartUrl)
            || (window.location.origin + '/pages/cart.php');
    }

    /* ── Subtotal display update ────────────────── */
    function updateSubtotalDisplay(subtotal) {
        var el = document.querySelector('.cart-subtotal-val');
        if (!el || subtotal === undefined) { return; }
        var currency = (window.TITHKAR && window.TITHKAR.currency) || 'EGP';
        el.textContent = Math.round(subtotal).toLocaleString() + ' ' + currency;
    }

    /* ── Qty form — AJAX submit with DOM update ─── */
    function submitQtyForm(form) {
        clearTimeout(form._timer);
        form._timer = setTimeout(function () {
            // Optimistic row-total update
            var price = parseFloat(form.dataset.price || '0');
            var pid   = form.dataset.pid;
            var input = form.querySelector('.qty-input');
            var qty   = parseInt(input ? input.value : '1', 10) || 1;
            if (price && pid) {
                var rowTotalEl = document.querySelector('.row-total[data-pid="' + pid + '"]');
                if (rowTotalEl) {
                    var currency = (window.TITHKAR && window.TITHKAR.currency) || 'EGP';
                    rowTotalEl.textContent = Math.round(price * qty).toLocaleString() + ' ' + currency;
                }
            }
            fetch(cartUrl(), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    updateCartCount(data.cartCount);
                    if (typeof data.subtotal !== 'undefined') {
                        updateSubtotalDisplay(data.subtotal);
                    }
                }
            })
            .catch(function () { form.submit(); }); // graceful fallback
        }, 400);
    }

    /* ── AJAX remove with row fade-out ──────────── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-remove-id]');
        if (!btn) { return; }
        e.preventDefault();
        var productId = btn.dataset.removeId;
        var row = btn.closest('tr') || btn.closest('.cart-item');
        var fd = new FormData();
        fd.append('action', 'remove');
        fd.append('product_id', productId);
        fetch(cartUrl(), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(function () {
                        row.remove();
                        updateSubtotalDisplay(data.subtotal);
                    }, 300);
                }
                updateCartCount(data.cartCount);
            }
        })
        .catch(function () {});
    });

    /* ── Expose helpers globally ─────────────────── */
    window.updateCartCount = updateCartCount;
    window.showToast       = showToast;

    /* ── addToCart() — callable from any page ───── */
    window.addToCart = function (productId, quantity, customization) {
        quantity     = quantity || 1;
        customization = customization || '';
        var fd = new FormData();
        fd.append('action', 'add');
        fd.append('product_id', productId);
        fd.append('quantity', quantity);
        fd.append('customization_text', customization);
        fetch(cartUrl(), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.cartCount);
                showToast(document.documentElement.lang === 'ar'
                    ? 'تمت الإضافة إلى السلة ✓'
                    : 'Added to cart ✓');
            }
        })
        .catch(function () {});
    };

    /* ── .add-to-cart[data-product-id] handler ─── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.add-to-cart[data-product-id]');
        if (!btn) { return; }
        e.preventDefault();
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        window.addToCart(
            btn.dataset.productId,
            parseInt(btn.dataset.quantity || '1', 10),
            ''
        );
        setTimeout(function () { btn.disabled = false; btn.innerHTML = orig; }, 1200);
    });

}());
