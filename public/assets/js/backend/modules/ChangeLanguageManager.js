import { apiFetch, showAlert } from '../backend.js';

/** Aggiorna esclusivamente etichette e attributi esplicitamente associati a una chiave. */
export class ChangeLanguageManager {
    constructor() {
        if (ChangeLanguageManager.instance) return ChangeLanguageManager.instance;
        ChangeLanguageManager.instance = this;
        this.root = document.getElementById('interface-preferences');
        this.translations = {};
        this.messages = new Map();
        this.pending = false;
        this.localeLoads = new Map();

        document.addEventListener('click', event => {
            const button = event.target.closest('[data-interface-language]');
            if (!button || !this.root) return;
            event.preventDefault();
            bootstrap.Dropdown.getInstance(button.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]'))?.hide();
            this.change(button.dataset.interfaceLanguage);
        });

        this.observer = new MutationObserver(mutations => {
            const scopes = new Set();
            mutations.forEach(mutation => {
                if (mutation.target instanceof Element) scopes.add(mutation.target);
                mutation.addedNodes.forEach(node => { if (node instanceof Element) scopes.add(node); });
            });
            scopes.forEach(scope => this.update(scope));
        });
        this.observer.observe(document.body, { childList: true, subtree: true });
    }

    format(template, parameters = []) {
        let index = 0;
        return template.replace(/%(?:(\d+)\$)?([+\-0\d]*)(?:\.(\d+))?([sdif%])/g, (match, position, width, precision, type) => {
            if (type === '%') return '%';
            const value = parameters[position ? Number(position) - 1 : index++];
            if (value === undefined) return match;
            let text = type === 'd' || type === 'i' ? String(parseInt(value, 10)) : type === 'f' ? Number(value).toFixed(precision === undefined ? 6 : Number(precision)) : String(value);
            if (width && Math.abs(Number(width)) > text.length) text = Number(width) < 0 ? text.padEnd(Math.abs(Number(width)), ' ') : text.padStart(Number(width), width.startsWith('0') ? '0' : ' ');
            return text;
        });
    }

    safeHtml(html) {
        const template = document.createElement('template');
        template.innerHTML = html;
        const allowed = new Set(['B', 'STRONG', 'BR', 'P', 'SPAN', 'DIV', 'EM', 'SMALL', 'UL', 'OL', 'LI']);
        template.content.querySelectorAll('*').forEach(element => {
            if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT'].includes(element.tagName)) { element.remove(); return; }
            if (!allowed.has(element.tagName)) { element.replaceWith(...element.childNodes); return; }
            Array.from(element.attributes).forEach(attribute => {
                if (attribute.name !== 'class' || !/^[a-zA-Z0-9 _-]*$/.test(attribute.value)) element.removeAttribute(attribute.name);
            });
        });
        return template.innerHTML;
    }

    render(binding) {
        if (typeof binding !== 'object' || binding === null) return String(binding);
        if (binding.parts) return binding.parts.map(part => this.render(part)).join('');
        const template = binding.variants?.[document.documentElement.lang] ?? this.translations[binding.key];
        if (typeof template !== 'string') return null;
        const parameters = (binding.parameters || []).map(value => typeof value === 'object' && value !== null ? this.render(value) : value);
        return (binding.prefix || '') + this.format(template, parameters) + (binding.suffix || '');
    }

    update(scope) {
        if (!Object.keys(this.translations).length) return;
        const nodes = [scope, ...scope.querySelectorAll('*')];
        nodes.forEach(node => {
            if (node.matches?.('[class^="error_"], [id^="error-img-"]') && node.textContent.trim() === '') node.removeAttribute('data-i18n-text');
            Array.from(node.attributes || []).forEach(attribute => {
                if (!attribute.name.startsWith('data-i18n-')) return;
                const target = attribute.name.slice(10);
                if (target !== 'text' && !/^(placeholder|title|alt|label|aria-[a-z-]+|data-(?!i18n)[a-z0-9-]+)$/.test(target)) return;
                let binding;
                try { binding = JSON.parse(attribute.value); } catch { return; }
                const text = this.render(binding);
                if (text === null) return;

                if (target !== 'text') {
                    if (node.getAttribute(target) !== text) node.setAttribute(target, text);
                } else if (binding.escaped) {
                    if (node.textContent !== text) node.textContent = text;
                } else {
                    const html = this.safeHtml(text);
                    if (node.innerHTML !== html) node.innerHTML = html;
                }
            });
        });
    }

    rememberMessages(bindings = {}) {
        Object.entries(bindings).forEach(([message, binding]) => this.messages.set(message, binding));
    }

    bindMessage(node, message, escaped = false) {
        node.removeAttribute('data-i18n-text');
        let binding = this.messages.get(message);
        if (!binding) {
            const sources = document.querySelectorAll('[data-i18n-data-message], [data-i18n-data-confirm], [data-i18n-data-required-message], #lang-definitions, #js-i18n-errors');
            for (const source of sources) {
                const attribute = Array.from(source.attributes).find(item => item.name.startsWith('data-i18n-data-') && source.getAttribute(item.name.slice(10)) === message);
                if (!attribute) continue;
                try { binding = JSON.parse(attribute.value); } catch { continue; }
                break;
            }
        }
        if (binding) node.setAttribute('data-i18n-text', JSON.stringify({ ...binding, escaped }));
    }

    async ensureLocale(language) {
        if (typeof flatpickr === 'undefined' || language === 'en' || flatpickr.l10ns[language]) return;
        if (!this.localeLoads.has(language)) {
            this.localeLoads.set(language, new Promise((resolve, reject) => {
                const script = document.createElement('script');
                const base = document.getElementById('hidden-urlbase').dataset.urlbase;
                script.src = base + 'assets/vendor/flatpickr/js/' + language + '.js';
                const fail = () => { clearTimeout(timeout); script.remove(); this.localeLoads.delete(language); reject(new Error('Locale unavailable')); };
                const timeout = setTimeout(fail, 15000);
                script.onload = () => { clearTimeout(timeout); resolve(); };
                script.onerror = fail;
                document.head.append(script);
            }));
        }
        await this.localeLoads.get(language);
    }

    localizeWidgets(language) {
        const definitions = document.getElementById('lang-definitions');
        const pickers = new Set();
        document.querySelectorAll('*').forEach(element => { if (element._flatpickr) pickers.add(element._flatpickr); });
        pickers.forEach(picker => {
            const value = picker.input.value;
            const alternative = picker.altInput?.value;
            picker.set('locale', language === 'en' ? 'default' : language);
            picker.input.value = value;
            if (picker.altInput) picker.altInput.value = alternative;
        });
        document.querySelectorAll('select.tomselected').forEach(select => {
            const instance = select.tomselect;
            if (!instance) return;
            const search = instance.control_input.value;
            instance.settings.placeholder = definitions?.dataset.select || '';
            instance.inputState();
            Array.from(select.options).forEach(option => {
                if (option.hasAttribute('data-i18n-text') && instance.options[option.value]) instance.updateOption(option.value, { ...instance.options[option.value], text: option.textContent });
            });
            instance.control_input.value = search;
            instance.control.querySelectorAll('.remove').forEach(button => button.title = definitions?.dataset.remove || '');
            const options = instance.plugins?.settings?.remove_button;
            if (options) options.title = definitions?.dataset.remove || '';
        });
    }

    async apply(data) {
        if (!['en', 'it', 'es', 'fr', 'de', 'zh'].includes(data.language) || !data.translations) throw new Error('Invalid language');
        await this.ensureLocale(data.language);
        const focused = document.activeElement;
        const selection = focused && typeof focused.selectionStart === 'number' ? [focused.selectionStart, focused.selectionEnd] : null;
        const scroll = [window.scrollX, window.scrollY];
        document.querySelectorAll('[class^="error_"], [id^="error-img-"]').forEach(node => {
            if (node.textContent.trim() && !node.hasAttribute('data-i18n-text')) this.bindMessage(node, node.textContent, true);
        });
        this.translations = data.translations;
        document.documentElement.lang = data.language;
        if (this.root) this.root.dataset.activeLanguage = data.language;
        this.update(document.documentElement);
        this.localizeWidgets(data.language);
        document.querySelectorAll('[data-interface-language]').forEach(button => {
            button.classList.toggle('active', button.dataset.interfaceLanguage === data.language);
            button.setAttribute('aria-pressed', String(button.dataset.interfaceLanguage === data.language));
        });
        document.dispatchEvent(new CustomEvent('interface:language', { detail: { language: data.language } }));
        if (focused?.isConnected && document.activeElement !== focused) focused.focus({ preventScroll: true });
        if (selection) focused.setSelectionRange(...selection);
        window.scrollTo(...scroll);
    }

    async change(language) {
        if (!['en', 'it', 'es', 'fr', 'de', 'zh'].includes(language)) return;
        if (this.pending) { this.nextLanguage = language; return; }
        if (language === document.documentElement.lang) return;
        this.pending = true;
        try {
            await this.ensureLocale(language);
            const form = new FormData();
            form.set('preference', 'language');
            form.set('value', language);
            const data = await (await apiFetch(this.root.dataset.url, { method: 'POST', body: form, reloadOnForbidden: false })).json();
            if (data.result !== true) throw new Error('Language rejected');
            await this.apply(data);
        } catch (error) {
            showAlert('danger', document.getElementById('lang-definitions')?.dataset.interfaceError || error.message);
        } finally {
            this.pending = false;
            const next = this.nextLanguage;
            this.nextLanguage = null;
            if (next && next !== document.documentElement.lang) this.change(next);
        }
    }
}
