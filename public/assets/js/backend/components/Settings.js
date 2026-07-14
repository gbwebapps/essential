/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors, initTomSelects } from '../backend.js';

export class SettingsManager {
    constructor(loadRoute, saveRoute, deleteRoute, formId) {
        this.loadUrl = urlbase + loadRoute;
        this.saveUrl = urlbase + saveRoute;
        this.deleteUrl = urlbase + deleteRoute;
        this.formId = formId;
        this.isSubmitting = false;

        this.init();
    }

    init() {
        this.bindDynamicEvents();
    }

    bindDynamicEvents() {

        /* 1. Intercettiamo il submit del form associato */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id === this.formId) {
                /* Estraiamo l'ambiente (auth o upload) direttamente dall'ID del form */
                const env = e.target.id.replace('-settings', '');
                this.save(e, e.target, env);
            }
        });

        /* 2. Intercettiamo il click sul pulsante ripristino (refresh) */
        document.addEventListener('click', e => {
            const refreshBtn = e.target.closest('[class*="btn-refresh-"]');
            if (refreshBtn) {
                const form = refreshBtn.closest('form');
                if (form && form.id === this.formId) {
                    e.preventDefault();
                    const env = form.id.replace('-settings', '');
                    this.refresh(refreshBtn, env);
                }
            }
        });

        /* 3. Intercettiamo il click sul pulsante ripristino valori predefiniti (delete/restore) */
        document.addEventListener('click', e => {
            const deleteBtn = e.target.closest('[class*="btn-delete-"]');
            if (deleteBtn) {
                const form = deleteBtn.closest('form');
                if (form && form.id === this.formId) {
                    e.preventDefault();
                    const env = form.id.replace('-settings', '');
                    this.deleteSettings(deleteBtn, env);
                }
            }
        });
    }

    /* Carica lo scheletro iniziale (Chiamata alla rotta open dedicata) */
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
            container.innerHTML = '';
        }
    }

    /* Salva i dati usando la rotta save dedicata */
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

    /* Esegue il refresh richiamando la rotta open pulita */
    async refresh(btnEl, env) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message || 'Ripristinare i dati originali?';
        const ok = await askConfirm(message);
        if (!ok) return;

        this.isSubmitting = true;
        const form = btnEl.closest('form');
        const container = form.closest('.accordion-body');

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
            }
        } catch (error) {
            console.error("Errore durante il ripristino delle impostazioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Elimina le personalizzazioni a DB e ripristina i default dei file */
    async deleteSettings(btnEl, env) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message || 'Ripristinare i valori dei files?';
        const ok = await askConfirm(message);
        if (!ok) return;

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
                
                /* Dopo la cancellazione con successo, ricarichiamo il pannello per mostrare i fallback */
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