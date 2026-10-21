/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors, initTomSelects, resetTomSelects, markUnsavedClean } from '../backend.js';

export class SettingsManager {

    constructor() {
        if (SettingsManager.instance) {
            return SettingsManager.instance;
        }

        SettingsManager.instance = this;

        this.loadUrl = urlbase + 'backend/settings/openSettings';
        this.saveUrl = urlbase + 'backend/settings/saveSettings';
        this.deleteUrl = urlbase + 'backend/settings/deleteSettings';
        this.checkDeleteUrl = urlbase + 'backend/settings/checkDeleteSettings';

        this.isSubmitting = false;
        this.isConfirming = false;
        this.destroyed = false;
        this.eventsBound = false;

        this.eventsController = new AbortController();
        this.pendingPanels = new Set();
        this.panelVersions = new Map();
        this.tomSelectTimeout = null;

        this.init();
    }

    init() {
        if (this.destroyed) return;

        this.bindGlobalEvents();
    }

    bindGlobalEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        /* 1. Gestione Submit Form */
        document.addEventListener('submit', e => {
            const form = e.target;

            if (!(form instanceof HTMLFormElement)) return;
            if (!form.id.endsWith('-settings')) return;

            e.preventDefault();

            if (this.destroyed || this.isSubmitting || this.isConfirming) return;

            const env = form.id.replace(/-settings$/, '');

            this.save(e, form, env);
        }, {
            signal: this.eventsController.signal
        });

        /* 2. Gestione Click Pulsanti (Refresh e Delete) */
        document.addEventListener('click', async e => {
            const refreshBtn = e.target.closest('[class*="btn-refresh-"]');

            if (refreshBtn) {
                const form = refreshBtn.closest('form');
                if (!form) return;

                e.preventDefault();

                if (this.destroyed || this.isSubmitting || this.isConfirming) return;

                this.isConfirming = true;

                try {
                    const ok = await askConfirm(refreshBtn.dataset.message);

                    if (!ok || this.destroyed) return;

                    const env = form.id.replace(/-settings$/, '');

                    await this.refresh(refreshBtn, env);
                } catch (error) {
                    console.error('Errore conferma refresh impostazioni:', error);
                } finally {
                    this.isConfirming = false;
                }

                return;
            }

            const deleteBtn = e.target.closest('[class*="btn-delete-"]');

            if (deleteBtn) {
                const form = deleteBtn.closest('form');
                if (!form) return;

                e.preventDefault();

                if (this.destroyed || this.isSubmitting || this.isConfirming) return;

                this.isConfirming = true;

                const env = form.id.replace(/-settings$/, '');

                try {
                    const formData = new FormData();
                    formData.append('env', env);

                    const response = await apiFetch(this.checkDeleteUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (this.destroyed) return;

                    if (data.result !== true) {
                        if (data.message && typeof showAlert === 'function') {
                            showAlert('info', data.message);
                        }

                        return;
                    }

                    const ok = await askConfirm(deleteBtn.dataset.message);

                    if (!ok || this.destroyed) return;

                    await this.deleteSettings(deleteBtn, env);
                } catch (error) {
                    console.error('Errore durante il controllo preliminare:', error);
                } finally {
                    this.isConfirming = false;
                }
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    /* Recupera la versione corrente del contenitore */
    getPanelVersion(containerId) {
        return this.panelVersions.get(containerId) || 0;
    }

    /* Verifica che il contenitore appartenga ancora alla vista corrente */
    isPanelCurrent(containerId, version, container) {
        return !this.destroyed &&
            this.getPanelVersion(containerId) === version &&
            container.isConnected &&
            document.getElementById(containerId) === container;
    }

    /* Distrugge soltanto le istanze TomSelect presenti nel contenitore */
    destroyTomSelects(container) {
        if (!container) return;

        container.querySelectorAll('select.tomselected').forEach(select => {
            if (select.tomselect) {
                select.tomselect.destroy();
            }
        });
    }

    /* Restituisce il tema attualmente caricato dal foglio di stile principale */
    getActiveTheme() {
        const themeStylesheet = document.getElementById('backend-theme');
        const stylesheetPath = themeStylesheet?.getAttribute('href') || '';
        const match = stylesheetPath.match(/\/(white|black|grey|dark)\.css(?:\?.*)?$/);

        return match ? match[1] : null;
    }

    /* Carica il nuovo tema e rimuove il precedente soltanto quando il CSS è disponibile */
    async applyTheme(theme) {
        const allowedThemes = ['white', 'black', 'grey', 'dark'];
        const currentStylesheet = document.getElementById('backend-theme');

        if (!allowedThemes.includes(theme) || !currentStylesheet) {
            window.location.reload();
            return;
        }

        const nextStylesheet = currentStylesheet.cloneNode(false);
        nextStylesheet.id = 'backend-theme-loading';
        nextStylesheet.href = `${urlbase}assets/css/backend/${theme}.css`;

        await new Promise(resolve => {
            nextStylesheet.addEventListener('load', () => {
                currentStylesheet.remove();
                nextStylesheet.id = 'backend-theme';
                resolve();
            }, { once: true });

            nextStylesheet.addEventListener('error', () => {
                nextStylesheet.remove();
                window.location.reload();
                resolve();
            }, { once: true });

            currentStylesheet.after(nextStylesheet);
        });
    }

    /* Carica il contenuto prima dell'apertura dell'accordion */
    async loadPanel(containerId, env) {
        if (this.destroyed || this.pendingPanels.has(containerId)) return false;

        const container = document.getElementById(containerId);
        if (!container) return false;

        const version = this.getPanelVersion(containerId);

        this.pendingPanels.add(containerId);

        try {
            const formData = new FormData();
            formData.append('env', env);

            const response = await apiFetch(this.loadUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!this.isPanelCurrent(containerId, version, container)) {
                return false;
            }

            if (data.result === true && data.output) {
                this.destroyTomSelects(container);
                smoothReplace(container, data.output);
                initTomSelects();

                return true;
            }

            return false;
        } catch (error) {
            console.error('Errore caricamento del pannello impostazioni:', error);
            return false;
        } finally {
            this.pendingPanels.delete(containerId);
        }
    }

    /* Svuota il container alla chiusura */
    resetContainer(containerId) {
        if (this.destroyed) return;

        this.panelVersions.set(
            containerId,
            this.getPanelVersion(containerId) + 1
        );

        clearTimeout(this.tomSelectTimeout);
        this.tomSelectTimeout = null;

        const container = document.getElementById(containerId);

        if (container) {
            this.destroyTomSelects(container);
            smoothReplace(container, '');
        }
    }

    /* Salva i dati */
    async save(e, form, env) {
        e.preventDefault();

        if (this.destroyed || this.isSubmitting || this.isConfirming) return;

        this.isSubmitting = true;

        const formData = new FormData(form);
        formData.append('env', env);

        const submittedTheme = env === 'general' ? formData.get('theme') : null;
        const themeChanged = typeof submittedTheme === 'string' && submittedTheme !== this.getActiveTheme();

        const container = form.closest('.accordion-body');
        const containerId = container?.id;
        const version = containerId ? this.getPanelVersion(containerId) : null;

        form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.saveUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (
                !containerId ||
                !this.isPanelCurrent(containerId, version, container)
            ) return;

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                markUnsavedClean(form);

                if (themeChanged) {
                    await this.applyTheme(submittedTheme);
                }

                if (data.output) {
                    this.destroyTomSelects(container);
                    smoothReplace(container, data.output);

                    if (env === 'general') {
                        clearTimeout(this.tomSelectTimeout);

                        this.tomSelectTimeout = setTimeout(() => {
                            this.tomSelectTimeout = null;

                            if (!this.isPanelCurrent(containerId, version, container)) {
                                return;
                            }

                            resetTomSelects();
                        }, 100);
                    }
                }

                if (data.fragments) {
                    Object.entries(data.fragments).forEach(([selector, htmlContent]) => {
                        const fragmentContainer = document.querySelector(selector);

                        if (fragmentContainer) {
                            this.destroyTomSelects(fragmentContainer);
                            smoothReplace(fragmentContainer, htmlContent);
                        }
                    });
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            console.error('Errore durante il salvataggio delle impostazioni:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Esegue il refresh delle impostazioni */
    async refresh(btnEl, env) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            const form = btnEl.closest('form');
            const container = form?.closest('.accordion-body');

            if (!container) return;

            await this.loadPanel(container.id, env);
        } catch (error) {
            console.error('Errore durante il ripristino delle impostazioni:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Elimina le personalizzazioni a DB */
    async deleteSettings(btnEl, env) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        const form = btnEl.closest('form');
        const container = form?.closest('.accordion-body');
        const containerId = container?.id;
        const version = containerId ? this.getPanelVersion(containerId) : null;

        const formData = new FormData();
        formData.append('env', env);

        try {
            const response = await apiFetch(this.deleteUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (
                !containerId ||
                !this.isPanelCurrent(containerId, version, container)
            ) return;

            if (data.result === true) {
                markUnsavedClean(form);

                if (data.reload === true) {
                    window.location.reload();
                    return;
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }

                await this.loadPanel(containerId, env);
            } else {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
            }
        } catch (error) {
            console.error('Errore durante l\'eliminazione delle impostazioni:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        clearTimeout(this.tomSelectTimeout);
        this.tomSelectTimeout = null;

        this.panelVersions.forEach((version, containerId) => {
            this.panelVersions.set(containerId, version + 1);
        });

        document.querySelectorAll('[id$="-settings-container"]').forEach(container => {
            this.destroyTomSelects(container);
        });

        this.pendingPanels.clear();

        if (SettingsManager.instance === this) {
            SettingsManager.instance = null;
        }
    }
}
