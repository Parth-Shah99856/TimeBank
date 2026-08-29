import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ========================================
// Stitch TIMEBANK Alpine.js Utilities & Stores
// ========================================

/**
 * CSRF-aware fetch helper without intrusive auto-reload
 */
window.tbFetch = async function(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    const config = {
        ...defaults,
        ...options,
        headers: {
            ...defaults.headers,
            ...(options.headers || {}),
        },
    };

    if (options.body instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    try {
        const response = await fetch(url, config);
        return response;
    } catch (e) {
        console.warn('Network request failed:', e);
        return null;
    }
};

/**
 * Stitch Toast Notification Store
 */
Alpine.store('toast', {
    items: [],
    _id: 0,

    show(message, type = 'success', duration = 4000) {
        const id = ++this._id;
        this.items.push({ id, message, type, visible: true });

        setTimeout(() => {
            this.dismiss(id);
        }, duration);
    },

    success(message) { this.show(message, 'success'); },
    error(message)   { this.show(message, 'error', 6000); },
    info(message)    { this.show(message, 'info'); },
    warning(message) { this.show(message, 'warning', 5000); },

    dismiss(id) {
        const item = this.items.find(i => i.id === id);
        if (item) item.visible = false;
        setTimeout(() => {
            this.items = this.items.filter(i => i.id !== id);
        }, 300);
    },
});

/**
 * Navigation / Mobile Menu Store
 */
Alpine.store('nav', {
    mobileMenu: false,
    toggleMobile() { this.mobileMenu = !this.mobileMenu; },
    closeMobile() { this.mobileMenu = false; },
});

/**
 * Notification Badge Store (authenticated only, no auto-reload loops)
 */
Alpine.store('notifications', {
    unreadCount: 0,
    _interval: null,

    init() {
        const isAuth = document.querySelector('meta[name="auth-check"]')?.content === '1';
        if (!isAuth) return;

        this.fetchCount();
        this._interval = setInterval(() => this.fetchCount(), 60000);
    },

    async fetchCount() {
        try {
            const isAuth = document.querySelector('meta[name="auth-check"]')?.content === '1';
            if (!isAuth) return;

            const res = await window.tbFetch('/notifications');
            if (res && res.ok && res.status === 200) {
                const data = await res.json();
                this.unreadCount = Array.isArray(data)
                    ? data.filter(n => !n.read_at).length
                    : 0;
            }
        } catch (e) {
            // silent catch for offline or guest
        }
    },
});

/**
 * Modal helper component
 */
Alpine.data('tbModal', () => ({
    open: false,
    show() { this.open = true; document.body.style.overflow = 'hidden'; },
    close() { this.open = false; document.body.style.overflow = ''; },
}));

/**
 * Format TC credits helper
 */
window.formatTC = function(amount) {
    const num = parseFloat(amount);
    if (isNaN(num)) return '0.00';
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

Alpine.start();
