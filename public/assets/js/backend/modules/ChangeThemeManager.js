import { apiFetch, showAlert } from '../backend.js';

export class ChangeThemeManager {
    constructor() {
        if (ChangeThemeManager.instance) return ChangeThemeManager.instance;
        ChangeThemeManager.instance = this;
        this.root = document.getElementById('interface-preferences');
        this.pending = false;

        document.addEventListener('click', event => {
            const button = event.target.closest('[data-interface-theme]');
            if (!button || !this.root) return;
            event.preventDefault();
            bootstrap.Dropdown.getInstance(button.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]'))?.hide();
            this.change(button.dataset.interfaceTheme);
        });
    }

    getActiveTheme() {
        return document.getElementById('backend-theme')?.getAttribute('href')?.match(/\/(white|black|grey|dark)\.css(?:\?.*)?$/)?.[1] || null;
    }

    async prepare(theme) {
        const current = document.getElementById('backend-theme');
        if (!['white', 'black', 'grey', 'dark'].includes(theme) || !current) throw new Error('Invalid theme');
        const next = current.cloneNode(false);
        next.removeAttribute('id');
        next.media = 'not all';
        next.href = current.href.replace(/\/(white|black|grey|dark)\.css(?:\?.*)?$/, '/' + theme + '.css');

        await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => { next.remove(); reject(new Error('Theme timeout')); }, 15000);
            next.addEventListener('load', () => { clearTimeout(timeout); resolve(); }, { once: true });
            next.addEventListener('error', () => { clearTimeout(timeout); next.remove(); reject(new Error('Theme unavailable')); }, { once: true });
            current.after(next);
        });

        return { current, next };
    }

    commit({ current, next }, theme) {
        next.media = current.media;
        current.remove();
        next.id = 'backend-theme';
        if (this.root) this.root.dataset.activeTheme = theme;
        document.querySelectorAll('[data-interface-theme]').forEach(button => {
            button.classList.toggle('active', button.dataset.interfaceTheme === theme);
            button.setAttribute('aria-pressed', String(button.dataset.interfaceTheme === theme));
        });
        const dashboard = document.getElementById('index-dashboard-container');
        if (dashboard) dashboard.dataset.dashboardTheme = theme;
        document.dispatchEvent(new CustomEvent('interface:theme', { detail: { theme } }));
    }

    async apply(theme) {
        if (theme !== this.getActiveTheme()) this.commit(await this.prepare(theme), theme);
    }

    async change(theme) {
        if (!['white', 'black', 'grey', 'dark'].includes(theme)) return;
        if (this.pending) { this.nextTheme = theme; return; }
        if (theme === this.getActiveTheme()) return;
        this.pending = true;
        let prepared;

        try {
            prepared = await this.prepare(theme);
            const form = new FormData();
            form.set('preference', 'theme');
            form.set('value', theme);
            const data = await (await apiFetch(this.root.dataset.url, { method: 'POST', body: form, reloadOnForbidden: false })).json();
            if (data.result !== true) throw new Error('Theme rejected');
            this.commit(prepared, theme);
        } catch (error) {
            prepared?.next.remove();
            showAlert('danger', document.getElementById('lang-definitions')?.dataset.interfaceError || error.message);
        } finally {
            this.pending = false;
            const next = this.nextTheme;
            this.nextTheme = null;
            if (next && next !== this.getActiveTheme()) this.change(next);
        }
    }
}
