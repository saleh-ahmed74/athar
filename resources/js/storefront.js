const runtimeNode = document.getElementById('storefront-runtime');
const runtime = runtimeNode ? JSON.parse(runtimeNode.textContent || '{}') : {};
const scope = runtime.scope || 'guest';
const cartKey = `athar-cart-v1:${scope}`;
const tokenKey = `athar-checkout-token-v1:${scope}`;
const draftKey = `athar-checkout-draft-v1:${scope}`;
const offlineCartKey = `athar-offline-cart-v1:${scope}`;
const initialServerCart = runtime.initialServerCart || {};
const safeRead = key => { try { return localStorage.getItem(key); } catch { return null; } };
const safeWrite = (key, value) => { try { localStorage.setItem(key, value); return true; } catch { return false; } };
const offlineStatus = document.getElementById('offline-status');
const statusMessages = runtime.messages || {};
const showOfflineStatus = message => {
    if (!offlineStatus) return;
    offlineStatus.textContent = message;
    offlineStatus.hidden = false;
};
const refreshOfflineStatus = () => {
    const pending = safeRead(offlineCartKey);
    if (!navigator.onLine) showOfflineStatus(statusMessages.offline);
    else if (pending) showOfflineStatus(statusMessages.pending);
    else if (offlineStatus) offlineStatus.hidden = true;
};
window.captureCartOrigin = element => {
    const source = element.closest('.product-card')?.querySelector('.product-image img') || document.querySelector('.product-detail .detail-image:not(.is-hidden) img') || element;
    const rect = source.getBoundingClientRect();
    window.__atharCartOrigin = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2, src: source.matches('img') ? source.currentSrc || source.src : null, at: Date.now() };
};
window.flyCartFromOrigin = (flight, start) => {
    const origin = window.__atharCartOrigin; window.__atharCartOrigin = null;
    if (!origin || Date.now() - origin.at > 3000) return;
    const target = [...document.querySelectorAll('.cart-link, .mobile-cart-link')].find(link => link.getClientRects().length && getComputedStyle(link).visibility !== 'hidden');
    if (!target) return;
    const rect = target.getBoundingClientRect();
    flight.style.setProperty('--flight-x', `${rect.left + rect.width / 2 - origin.x}px`);
    flight.style.setProperty('--flight-y', `${rect.top + rect.height / 2 - origin.y}px`);
    flight.style.left = `${origin.x}px`; flight.style.top = `${origin.y}px`; flight.style.bottom = 'auto';
    const image = origin.src ? document.createElement('img') : null;
    if (image) { image.src = origin.src; image.alt = ''; flight.replaceChildren(image); }
    flight.classList.remove('is-flying');
    start();
    requestAnimationFrame(() => flight.classList.add('is-flying'));
    setTimeout(() => {
        flight.classList.remove('is-flying');
        flight.innerHTML = '<svg viewBox="0 0 24 24"><path d="M3 4h2l2.1 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 1.9-1.4L22 9H7"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg>';
    }, 900);
};
let savingDraft = false;
const saveDraft = () => {
    if (savingDraft || !document.querySelector('.checkout-form')) return;
    const draft = Object.fromEntries(['name', 'email', 'phone', 'phone-country', 'city', 'custom-city', 'address', 'notes'].map(id => [id, document.getElementById(id)?.value ?? '']));
    if (!safeWrite(draftKey, JSON.stringify(draft))) showOfflineStatus(statusMessages.storageError);
};
const restoreDraft = () => {
    if (!document.querySelector('.checkout-form')) return;
    let draft = {};
    try { draft = JSON.parse(safeRead(draftKey) || '{}'); } catch {}
    savingDraft = true;
    for (const [id, value] of Object.entries(draft)) {
        const field = document.getElementById(id);
        if (field && value !== undefined) {
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    savingDraft = false;
};
document.addEventListener('input', event => { if (event.target.closest('.checkout-form')) saveDraft(); });
document.addEventListener('change', event => { if (event.target.closest('.checkout-form')) saveDraft(); });
const updateOfflineCartLines = cart => {
    document.querySelectorAll('[data-offline-cart-line]').forEach(line => {
        const quantity = Number(cart[line.dataset.offlineCartLine] || 0);
        line.hidden = quantity < 1;
        const quantityLabel = line.querySelector('[data-offline-cart-quantity]');
        if (quantityLabel && quantity > 0) quantityLabel.textContent = String(quantity);
    });
};
window.addEventListener('cart-updated', event => {
    if (event.detail?.cart) {
        safeWrite(cartKey, JSON.stringify(event.detail.cart));
        if (event.detail.offline) updateOfflineCartLines(event.detail.cart);
    }
});
const recoveryState = window.__atharCartRecoveryState || (window.__atharCartRecoveryState = { scope, restoring: false, complete: false, lastAttempt: 0 });
if (recoveryState.scope !== scope) Object.assign(recoveryState, { scope, restoring: false, complete: false, lastAttempt: 0 });
if (typeof recoveryState.syncing === 'undefined') recoveryState.syncing = null;
const cartPaths = [runtime.cartPath, runtime.checkoutPath].filter(Boolean).map(path => new URL(path, location.origin).pathname.replace(/\/$/, '') || '/');
const readOfflineCart = () => {
    try {
        const savedCart = safeRead(cartKey);
        return savedCart === null ? initialServerCart : JSON.parse(savedCart);
    } catch { return initialServerCart; }
};
const syncOfflineCart = async () => {
    if (!navigator.onLine) { refreshOfflineStatus(); return false; }
    let pending;
    try { pending = JSON.parse(safeRead(offlineCartKey) || 'null'); } catch {}
    if (!pending || !pending.cart || typeof pending.cart !== 'object') { refreshOfflineStatus(); return false; }
    if (recoveryState.syncing) return recoveryState.syncing;
    recoveryState.syncing = (async () => {
        try {
            const response = await fetch(runtime.recoveryPath, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ cart: pending.cart, offline_sync: true }),
            });
            if (!response.ok) { refreshOfflineStatus(); return false; }
            const data = await response.json();
            let latest;
            try { latest = JSON.parse(safeRead(offlineCartKey) || 'null'); } catch {}
            if (latest?.revision === pending.revision) {
                safeWrite(cartKey, JSON.stringify(data.cart || {}));
                if (data.checkout_token) safeWrite(tokenKey, data.checkout_token);
                try { localStorage.removeItem(offlineCartKey); } catch {}
                if (window.Livewire) {
                    Livewire.dispatch('cart-session-restored', { checkoutToken: data.checkout_token });
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, cart: data.cart || {} } }));
                }
                showOfflineStatus(statusMessages.synced);
                setTimeout(refreshOfflineStatus, 2500);
            } else {
                setTimeout(syncOfflineCart, 0);
            }
            return true;
        } catch {
            refreshOfflineStatus();
            return false;
        } finally {
            recoveryState.syncing = null;
        }
    })();
    return recoveryState.syncing;
};
const queueOfflineCart = cart => {
    const snapshot = { cart, revision: `${Date.now()}-${Math.random().toString(36).slice(2)}` };
    if (!safeWrite(cartKey, JSON.stringify(cart)) || !safeWrite(offlineCartKey, JSON.stringify(snapshot))) {
        showOfflineStatus(statusMessages.storageError);
        return;
    }
    const count = Object.values(cart).reduce((total, quantity) => total + Number(quantity || 0), 0);
    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count, cart, offline: true } }));
    refreshOfflineStatus();
};
const handleOfflineCartClick = event => {
    const target = event.target instanceof Element ? event.target.closest('[data-offline-add-to-cart], [data-offline-cart-action]') : null;
    if (!target || (navigator.onLine && !safeRead(offlineCartKey))) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const cart = readOfflineCart();
    const productId = String(target.dataset.offlineAddToCart || target.dataset.productId || '');
    if (!/^\d+(?::(?:[0-9a-f-]{36}|-):(?:[0-9a-f-]{36}|-)|:[0-9a-f-]{36})?$/i.test(productId)) return;
    if (target.hasAttribute('data-offline-add-to-cart')) {
        const quantityInput = target.closest('.product-detail')?.querySelector('[data-offline-add-quantity]');
        const quantity = Math.max(1, Math.min(10, Number(quantityInput?.value || 1)));
        cart[productId] = Math.min(10, Number(cart[productId] || 0) + quantity);
        window.captureCartOrigin(target);
    } else if (target.dataset.offlineCartAction === 'increase') {
        cart[productId] = Math.min(10, Number(cart[productId] || 0) + 1);
    } else if (target.dataset.offlineCartAction === 'decrease') {
        const quantity = Number(cart[productId] || 0) - 1;
        if (quantity > 0) cart[productId] = quantity;
        else delete cart[productId];
    } else if (target.dataset.offlineCartAction === 'remove') {
        delete cart[productId];
    } else {
        return;
    }
    queueOfflineCart(cart);
    if (navigator.onLine) syncOfflineCart();
};
document.addEventListener('click', handleOfflineCartClick, true);
const restore = async () => {
    restoreDraft();
    if (location.pathname.startsWith('/order-confirmation/')) {
        try { localStorage.removeItem(cartKey); localStorage.removeItem(tokenKey); localStorage.removeItem(draftKey); localStorage.removeItem(offlineCartKey); } catch {}
        return;
    }
    if (safeRead(offlineCartKey)) {
        await syncOfflineCart();
        return;
    }
    if (!cartPaths.includes(location.pathname.replace(/\/$/, '') || '/') || recoveryState.restoring || recoveryState.complete || Date.now() - recoveryState.lastAttempt < 30000) return;
    recoveryState.restoring = true;
    recoveryState.lastAttempt = Date.now();
    try {
        let cart = {}; let draft = {};
        try { cart = JSON.parse(safeRead(cartKey) || '{}'); } catch {}
        try { draft = JSON.parse(safeRead(draftKey) || '{}'); } catch {}
        const token = safeRead(tokenKey);
        const checkoutPage = !!document.querySelector('.checkout-form');
        const hasSavedDraft = Object.values(draft).some(value => value !== '');
        if (!checkoutPage && Object.keys(cart).length === 0 && !token && !hasSavedDraft) return;
        const response = await fetch(runtime.recoveryPath, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify({ cart, checkout_token: token }) });
        if (!response.ok) return;
        const data = await response.json();
        recoveryState.complete = true;
        if (data.order_url) { try { localStorage.removeItem(cartKey); localStorage.removeItem(tokenKey); localStorage.removeItem(draftKey); } catch {} location.assign(data.order_url); return; }
        safeWrite(cartKey, JSON.stringify(data.cart || {})); safeWrite(tokenKey, data.checkout_token || '');
        if (document.querySelector('.checkout-form')) {
            safeWrite(draftKey, JSON.stringify(draft));
            restoreDraft();
        }
        if (window.Livewire) { Livewire.dispatch('cart-session-restored', { checkoutToken: data.checkout_token }); window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count, cart: data.cart || {} } })); }
    } catch { /* Keep browser data for the next recovery attempt. */ }
    finally { recoveryState.restoring = false; }
};
document.addEventListener('livewire:initialized', restore, { once: true });
document.addEventListener('livewire:navigated', restore);
document.addEventListener('livewire:navigating', saveDraft);
document.addEventListener('submit', event => {
    if (!navigator.onLine && event.target.matches('.checkout-form')) {
        event.preventDefault();
        saveDraft();
        showOfflineStatus(statusMessages.checkout);
    }
}, true);
window.addEventListener('online', () => { refreshOfflineStatus(); syncOfflineCart(); });
window.addEventListener('offline', refreshOfflineStatus);
document.addEventListener('visibilitychange', () => { if (!document.hidden) syncOfflineCart(); });
window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    const cart = readOfflineCart();
    const count = Object.values(cart).reduce((total, quantity) => total + Number(quantity || 0), 0);
    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count, cart } }));
    restoreDraft();
    refreshOfflineStatus();
});
refreshOfflineStatus();
if ('serviceWorker' in navigator && window.isSecureContext) {
    navigator.serviceWorker.register(runtime.serviceWorkerPath, { scope: '/' }).catch(() => {});
}
