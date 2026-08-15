/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, handleValidationErrors } from '../backend.js';

export class ExportCsvManager {
    constructor(config = {}, hooks = {}) {

        this.config = Object.assign({
            controller: '',
            urlExport: urlbase + 'backend/export/generate',
            linkId: '#export-entity',
        }, config);

        this.hooks = Object.assign({
            onExportBefore: null,
            onExportAfter: null,
            onError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Delegazione per catturare il click sul link diretto di esportazione */
        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.linkId);
            if ( ! btn) return;

            e.preventDefault();
            const entity = btn.dataset.exportEntity;
            
            await this.triggerExport(entity);
        });
    }

    async triggerExport(entity) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onExportBefore === 'function') {
            const stop = this.hooks.onExportBefore(entity);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        const formData = new FormData();
        formData.append('entity', entity);

        /* Recupero dei filtri dal localStorage */
        const prefix = `${this.config.controller}_`;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith(prefix)) {
                const value = localStorage.getItem(key);
                const cleanKey = key.substring(prefix.length);
                formData.append(cleanKey, value);
            }
        }

        try {
            const response = await apiFetch(this.config.urlExport, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Controllo per gli errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {
                
                /* Mostra il messaggio di successo */
                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);

                /* Avvia il download contestuale */
                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.setAttribute('download', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                if (typeof this.hooks.onExportAfter === 'function') {
                    this.hooks.onExportAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (triggerExport):", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}