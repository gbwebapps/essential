/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors, initTomSelects } from '../backend.js';

export class SettingsManager {
    constructor() {
        this.loadUrl = urlbase + 'backend/settings/openSettings';
        this.saveUrl = urlbase + 'backend/settings/saveSettings';
        this.deleteUrl = urlbase + 'backend/settings/deleteSettings';

        this.checkDeleteUrl = urlbase + 'backend/settings/checkDeleteSettings';

        this.isSubmitting = false;
        this.init();
    }

    init() {
        this.bindGlobalEvents();
    }

    bindGlobalEvents() {
        /* Registriamo i listener sul document UNA SOLA VOLTA globalmente */

        /* 1. Gestione Submit Form */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id.endsWith('-settings')) {
                const env = e.target.id.replace('-settings', '');
                this.save(e, e.target, env);
            }
        });

        /* 2. Gestione Click Pulsanti (Refresh e Delete) */
        document.addEventListener('click', async (e) => {
            /* Verifica pulsante Refresh */
            const refreshBtn = e.target.closest('[class*="btn-refresh-"]');
            if (refreshBtn) {
                const form = refreshBtn.closest('form');
                if (form) {
                    e.preventDefault();

                    const message = refreshBtn.dataset.message;
                    const ok = await askConfirm(message);
                    if ( ! ok) return;

                    const env = form.id.replace('-settings', '');
                    this.refresh(refreshBtn, env);
                }
                return;
            }

            /* Verifica pulsante Delete/Restore */
            const deleteBtn = e.target.closest('[class*="btn-delete-"]');
            if (deleteBtn) {
                const form = deleteBtn.closest('form');
                if (form) {
                    e.preventDefault();

                    const env = form.id.replace('-settings', '');

                    const formData = new FormData();
                    formData.append('env', env);

                    try {
                        /* 1. Chiamata di pre-flight al backend */
                        const response = await apiFetch(this.checkDeleteUrl, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();

                        /* 2. Se non ci sono dati, mostra l'avviso e blocca il flusso */
                        if (data.result === false) {
                            if (data.message && typeof showAlert === 'function') {
                                showAlert('info', data.message);
                            }
                            return;
                        }
                        
                        /* 3. Se ci sono dati, lancia la conferma classica */
                        const message = deleteBtn.dataset.message;
                        const ok = await askConfirm(message);
                        if ( ! ok) return;

                        /* 4. Esegue l'eliminazione effettiva */
                        this.deleteSettings(deleteBtn, env);

                    } catch (error) {
                        console.error("Errore durante il controllo preliminare:", error);
                    }
                }
            }
        });
    }

    /* Carica lo scheletro iniziale */
    async loadPanel(containerId, env) {
        const container = document.getElementById(containerId);
        if ( ! container) return false;

        const formData = new FormData();
        formData.append('env', env);

        try {
            const response = await apiFetch(this.loadUrl, { 
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true && data.output) {
                smoothReplace(container, data.output);
                initTomSelects();
                return true;
            }
            return false;
        } catch (error) {
            console.error("Errore caricamento del pannello impostazioni:", error);
            return false;
        }
    }

    /* Svuota il container alla chiusura */
    resetContainer(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            smoothReplace(container, '');
        }
    }

    /* Salva i dati */
    async save(e, form, env) {
        e.preventDefault();

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formData = new FormData(form);
        formData.append('env', env);

        const container = form.closest('.accordion-body');
        form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.saveUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true && data.output) {
                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
                smoothReplace(container, data.output);
                initTomSelects();
            }
        } catch (error) {
            console.error("Errore durante il salvataggio delle impostazioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Esegue il refresh in SettingsManager */
    async refresh(btnEl, env) {
        
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Recupera il contenitore esatto partendo dal pulsante cliccato */
        const form = btnEl.closest('form');
        const container = form.closest('.accordion-body');

        try {
            /* Affida il ricaricamento DOM e l'inizializzazione di TomSelect esclusivamente a loadPanel */
            await this.loadPanel(container.id, env);
        } catch (error) {
            console.error("Errore durante il ripristino delle impostazioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Elimina le personalizzazioni a DB */
    async deleteSettings(btnEl, env) {

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const form = btnEl.closest('form');
        const container = form.closest('.accordion-body');

        const formData = new FormData();
        formData.append('env', env);

        try {
            const response = await apiFetch(this.deleteUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();

            if (data.result === true) {
                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
                await this.loadPanel(container.id, env);
            } else {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
            }
        } catch (error) {
            console.error("Errore durante l'eliminazione delle impostazioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}