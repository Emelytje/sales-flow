/* =============================================================================
   SalesFlow Enterprise — Core client runtime
   Theme, navigation, API helper (CSRF-aware), toasts, modals, dropdowns.
   Vanilla JS, no dependencies.
   ========================================================================== */
(function () {
    'use strict';

    const SF = window.SF = window.SF || {};

    /* ---- Theme ------------------------------------------------------------ */
    SF.theme = {
        get() { return localStorage.getItem('sf-theme') || 'system'; },
        apply(mode) {
            const root = document.documentElement;
            if (mode === 'system') {
                root.removeAttribute('data-theme');
            } else {
                root.setAttribute('data-theme', mode);
            }
            localStorage.setItem('sf-theme', mode);
            document.querySelectorAll('[data-theme-value]').forEach((el) => {
                el.classList.toggle('active', el.dataset.themeValue === mode);
            });
        },
        toggle() {
            const cur = document.documentElement.getAttribute('data-theme');
            this.apply(cur === 'dark' ? 'light' : 'dark');
        },
        init() { this.apply(this.get()); }
    };
    SF.theme.init();

    /* ---- CSRF token ------------------------------------------------------- */
    SF.csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ---- API helper ------------------------------------------------------- */
    SF.api = async function (url, options = {}) {
        const opts = Object.assign({ method: 'GET', headers: {} }, options);
        opts.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': SF.csrf(),
            'Accept': 'application/json'
        }, opts.headers);

        if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(opts.body);
        }
        const res = await fetch(url, opts);
        const ct = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json() : await res.text();
        if (!res.ok) {
            const msg = (data && data.error) || (data && data.errors && Object.values(data.errors)[0]?.[0]) || 'Er ging iets mis.';
            SF.toast(msg, 'error');
            throw Object.assign(new Error(msg), { status: res.status, data });
        }
        return data;
    };

    /* ---- Toasts ----------------------------------------------------------- */
    SF.toast = function (message, type = 'success', timeout = 4000) {
        let stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<span>${message}</span><span class="close">&times;</span>`;
        el.querySelector('.close').onclick = () => el.remove();
        stack.appendChild(el);
        if (timeout) setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 250); }, timeout);
    };

    /* ---- Modals ----------------------------------------------------------- */
    SF.modal = {
        open(html, opts = {}) {
            this.close();
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            backdrop.innerHTML = `<div class="modal ${opts.large ? 'modal-lg' : ''}" role="dialog" aria-modal="true">${html}</div>`;
            backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop && !opts.persistent) this.close(); });
            document.body.appendChild(backdrop);
            document.body.style.overflow = 'hidden';
            backdrop.querySelectorAll('[data-close]').forEach((b) => b.onclick = () => this.close());
            this._el = backdrop;
            return backdrop.querySelector('.modal');
        },
        close() {
            if (this._el) { this._el.remove(); this._el = null; document.body.style.overflow = ''; }
        }
    };
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') SF.modal.close(); });

    /* ---- Confirm dialog --------------------------------------------------- */
    SF.confirm = function (message, onYes, opts = {}) {
        const m = SF.modal.open(`
            <div class="modal-body" style="text-align:center;padding:2rem;">
                <h3 style="margin-bottom:.5rem;">${opts.title || 'Ben je zeker?'}</h3>
                <p class="text-muted">${message}</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" data-close>Annuleren</button>
                <button class="btn ${opts.danger ? 'btn-danger' : 'btn-primary'}" data-yes>${opts.confirmText || 'Bevestigen'}</button>
            </div>`);
        m.querySelector('[data-yes]').onclick = () => { SF.modal.close(); onYes(); };
    };

    /* ---- Sidebar (mobile) ------------------------------------------------- */
    function initSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.menu-toggle');
        if (!sidebar || !toggle) return;
        const open = () => {
            sidebar.classList.add('open');
            const bd = document.createElement('div');
            bd.className = 'sidebar-backdrop';
            bd.onclick = close;
            document.body.appendChild(bd);
        };
        const close = () => {
            sidebar.classList.remove('open');
            document.querySelector('.sidebar-backdrop')?.remove();
        };
        toggle.onclick = () => sidebar.classList.contains('open') ? close() : open();
    }

    /* ---- Dropdowns -------------------------------------------------------- */
    function initDropdowns() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-dropdown]');
            document.querySelectorAll('.dropdown-menu.show').forEach((m) => {
                if (!trigger || m !== document.getElementById(trigger.dataset.dropdown)) m.classList.remove('show');
            });
            if (trigger) {
                const menu = document.getElementById(trigger.dataset.dropdown);
                if (menu) menu.classList.toggle('show');
            }
        });
    }

    /* ---- Theme toggle buttons -------------------------------------------- */
    function initThemeButtons() {
        document.querySelectorAll('[data-theme-toggle]').forEach((b) => b.onclick = () => SF.theme.toggle());
        document.querySelectorAll('[data-theme-value]').forEach((b) => b.onclick = () => SF.theme.apply(b.dataset.themeValue));
    }

    /* ---- Async form submit ------------------------------------------------ */
    SF.bindForm = function (form, onSuccess) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>'; }
            try {
                const method = (form.dataset.method || form.method || 'POST').toUpperCase();
                const data = await SF.api(form.action, { method, body: new FormData(form) });
                onSuccess ? onSuccess(data) : (data.redirect ? location.assign(data.redirect) : location.reload());
            } catch (err) {
                if (err.data && err.data.errors) SF.renderErrors(form, err.data.errors);
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
            }
        });
    };

    SF.renderErrors = function (form, errors) {
        form.querySelectorAll('.field-error').forEach((e) => e.remove());
        form.querySelectorAll('.error').forEach((e) => e.classList.remove('error'));
        Object.entries(errors).forEach(([field, messages]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const err = document.createElement('div');
                err.className = 'field-error';
                err.textContent = messages[0];
                input.insertAdjacentElement('afterend', err);
            }
        });
    };

    /* ---- Notifications polling ------------------------------------------- */
    SF.notifications = {
        async refresh() {
            try {
                const data = await SF.api('/notifications?json=1');
                const dot = document.querySelector('.notif-dot');
                if (dot) dot.classList.toggle('hidden', !data.unread);
                const count = document.querySelector('[data-notif-count]');
                if (count) count.textContent = data.unread || '';
            } catch (_) { /* silent */ }
        }
    };

    /* ---- CTI screen-pop (incoming calls) ---------------------------------- */
    SF.cti = {
        seen: new Set(),
        start() {
            if (this._timer) return;
            const tick = () => this.poll();
            this._timer = setInterval(tick, 3000);
            tick();
        },
        async poll() {
            let data;
            try { data = await SF.api('/api/v1/cti/poll'); } catch (_) { return; }
            (data.calls || []).forEach((c) => {
                if (this.seen.has(c.id)) return;
                this.seen.add(c.id);
                this.show(c);
            });
        },
        show(call) {
            const initials = (call.name || '?').split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();
            const known = !!call.name;
            const html = `
                <div class="modal-body" style="text-align:center;padding:2rem;">
                    <div class="avatar avatar-lg" style="margin:0 auto 1rem;width:68px;height:68px;font-size:1.5rem;${known ? '' : 'background:linear-gradient(135deg,#9C8F98,#6B5E68);'}">${known ? initials : '?'}</div>
                    <div class="badge badge-rose badge-dot" style="margin-bottom:.6rem;">Inkomende oproep</div>
                    <h2 style="margin-bottom:.2rem;">${known ? call.name : 'Onbekende beller'}</h2>
                    <p class="text-soft" style="font-weight:600;">${call.number || ''}</p>
                </div>
                <div class="modal-foot" style="justify-content:center;">
                    ${call.url ? `<a class="btn btn-primary" href="${call.url}">Open klantkaart</a>` : `<a class="btn btn-primary" href="/customers/create?phone=${encodeURIComponent(call.number || '')}">Nieuwe klant</a>`}
                    <button class="btn btn-ghost" data-close>Sluiten</button>
                </div>`;
            SF.modal.open(html);
            try { new Audio('data:audio/wav;base64,UklGRl9vAAA=').play().catch(() => {}); } catch (_) {}
        }
    };

    /* ---- Boot ------------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initDropdowns();
        initThemeButtons();
        document.querySelectorAll('form[data-ajax]').forEach((f) => SF.bindForm(f));
        document.querySelectorAll('[data-anim]').forEach((el, i) => { el.style.animationDelay = (i * 0.05) + 's'; el.classList.add('anim-in'); });
        if (document.querySelector('.notif-dot')) {
            SF.notifications.refresh();
            setInterval(() => SF.notifications.refresh(), 60000);
        }
        // Start CTI screen-pop polling inside the authenticated app shell.
        if (document.querySelector('.app')) {
            SF.cti.start();
        }
        // Register the service worker for PWA/offline support.
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js').catch(() => {});
        }
    });
})();
