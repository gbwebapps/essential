/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, smoothReplace, handleValidationErrors } from '../backend.js';

export class ExportCsvManager {
    constructor(config = {}, hooks = {}) {

        this.config = Object.assign({
            controller: '',
            urlModal: urlbase + 'backend/export/showModal',
            modalContainerId: 'export-modal-container', 
            modalId: 'exportModal', 
            linkId: '#export-entity', 
            cancelBtnId: '#export-cancel-btn',
            urlExport: urlbase + 'backend/export/generate',
        }, config);

        this.hooks = Object.assign({
            onModalBefore: null,
            onModalAfter: null,
            onExportBefore: null,
            onExportAfter: null,
            onError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.isCancelled = false;
        /* Definizione rigorosa dello stato */
        this.currentFileName = null;
        this.exportFilters = new FormData();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* 1. Apertura Modale al click sul link di esportazione */
        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.linkId);
            if ( ! btn) return;

            e.preventDefault();
            const entity = btn.dataset.exportEntity;
            
            await this.showModal(entity);
        });

        /* 2. Annullamento Esportazione */
        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.cancelBtnId);
            if ( ! btn) return;

            e.preventDefault();
            
            /* Prevenzione del double-click (Race Condition) */
            if (this.isCancelled) return;
            
            this.isCancelled = true;
            btn.disabled = true; /* Disattiva immediatamente il pulsante UI */

            /* Recupera il nome del file attivo dallo stato e ripulisci */
            if (this.currentFileName) {
                const formData = new FormData();
                formData.append('fileName', this.currentFileName);
                
                try {
                    await apiFetch(urlbase + 'backend/export/remove', {
                        method: 'POST',
                        body: formData
                    });
                } catch (err) {
                    console.error("Errore pulizia file:", err);
                }
            }

            const modalEl = document.getElementById(this.config.modalId);
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            this.isSubmitting = false;
        });

        /* 3. Delegazione eventi modale per evitare Memory Leak */
        document.addEventListener('show.bs.modal', e => {
            if (e.target.id === this.config.modalId) {
                const backdropEl = document.getElementById('customBackdrop');
                if (backdropEl) backdropEl.classList.add('active');
            }
        });

        document.addEventListener('hidden.bs.modal', e => {
            if (e.target.id === this.config.modalId) {
                const backdropEl = document.getElementById('customBackdrop');
                if (backdropEl) backdropEl.classList.remove('active');
            }
        });

        /* 4. Evento: Seleziona / Deseleziona tutte le colonne */
        document.addEventListener('change', e => {
            if (e.target.id === 'export-check-all') {
                const isChecked = e.target.checked;
                document.querySelectorAll('.export-col-cb').forEach(cb => cb.checked = isChecked);
            }
        });

        /* 5. Evento: Avvia Esportazione (Click sul pulsante nel modale) */
        document.addEventListener('click', async e => {
            const startBtn = e.target.closest('#export-start-btn');
            if ( ! startBtn) return;

            e.preventDefault();

            /* Prevenzione double-click */
            if (this.isSubmitting) return;

            /* Validazione Client-Side severa */
            const selectedCheckboxes = document.querySelectorAll('.export-col-cb:checked');
            if (selectedCheckboxes.length === 0) {
                if (typeof showAlert === 'function') showAlert('warning', 'Seleziona almeno una colonna per proseguire.');
                return;
            }

            this.isSubmitting = true;
            this.isCancelled = false;
            startBtn.disabled = true;

            /* Switch Interfaccia: Nascondi checkbox, mostra loader e disattiva bottone start */
            const selectionArea = document.getElementById('export-selection-area');
            const spinnerArea = document.getElementById('export-spinner-area');
            
            if (selectionArea) selectionArea.classList.add('d-none');
            if (spinnerArea) spinnerArea.classList.remove('d-none');
            startBtn.classList.add('d-none');

            /* Aggiunta delle colonne all'oggetto FormData base */
            selectedCheckboxes.forEach(cb => {
                this.exportFilters.append('selected_columns[]', cb.value);
            });

            /* Innesco processo reale. Nessun parametro passato (iniziano tutti null/zero di default) */
            await this.triggerExport();
        });
    }

    /* Metodo isolato per catturare i filtri una volta sola (Ottimizzazione Performance) */
    prepareExportFilters(entity) {
        this.exportFilters = new FormData();
        this.exportFilters.append('entity', entity);

        const prefix = `${this.config.controller}_`;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith(prefix)) {
                const value = localStorage.getItem(key);
                const cleanKey = key.substring(prefix.length);
                this.exportFilters.append(cleanKey, value);
            }
        }
    }

    async showModal(entity) {
        if (this.isSubmitting) return;
        
        /* Reset totale dello stato per nuove esportazioni */
        this.isSubmitting = true;
        this.isCancelled = false;
        this.currentFileName = null;

        if (typeof this.hooks.onModalBefore === 'function') {
            const stop = this.hooks.onModalBefore(entity);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const formData = new FormData();
            formData.append('entity', entity);

            const response = await apiFetch(this.config.urlModal, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                this.isSubmitting = false;
                return;
            }

            if (data.result === true) {
                const container = document.getElementById(this.config.modalContainerId);
                
                if (container && data.output) {
                    smoothReplace(container, data.output);

                    const modalEl = document.getElementById(this.config.modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                            backdrop: false,
                            keyboard: false
                        });

                        /* Popola i filtri base. Le colonne verranno aggiunte al click su Avvia */
                        this.prepareExportFilters(entity);

                        modalInstance.show();
                    }
                }

                if (typeof this.hooks.onModalAfter === 'function') {
                    this.hooks.onModalAfter(data);
                }
                
                /* CRITICO: Sblocchiamo la submission per permettere l'interazione con il nuovo form */
                this.isSubmitting = false;
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (showModal):", error);
            this.isSubmitting = false;
        }
    }

    async triggerExport(currentLastId = null, currentFileName = '', processedCount = 0) {
        if (this.isCancelled) {
            this.isSubmitting = false;
            return;
        }

        try {
            /* Clona i filtri base catturati all'inizio, evitando di rileggere il localStorage */
            const formData = new FormData();
            for (let [key, value] of this.exportFilters.entries()) {
                formData.append(key, value);
            }
            
            formData.append('processedCount', processedCount);
            
            if (currentLastId !== null && currentLastId !== '') {
                formData.append('lastId', currentLastId);
            }
            if (currentFileName !== '') {
                formData.append('fileName', currentFileName);
            }

            const response = await apiFetch(this.config.urlExport, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.isCancelled) {
                this.isSubmitting = false;
                return;
            }

            if (data.errors || data.result === false) {
                if (data.errors && typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                
                const modalEl = document.getElementById(this.config.modalId);
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                }
                this.isSubmitting = false;
                return;
            }

            if (data.result === true) {

                this.currentFileName = data.fileName;
                
                if (data.isFinished === false) {
                    const progressText = document.getElementById('export-progress-text');
                    if (progressText && data.progressMessage) {
                        progressText.textContent = data.progressMessage;
                    }

                    await this.triggerExport(data.lastId, data.fileName, data.processedCount);
                    return; 
                }

                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);

                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.setAttribute('download', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                const modalEl = document.getElementById(this.config.modalId);
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                }

                if (typeof this.hooks.onExportAfter === 'function') {
                    this.hooks.onExportAfter(data);
                }
                
                this.isSubmitting = false;
            }

        } catch (error) {
            if (this.isCancelled) {
                this.isSubmitting = false;
                return;
            }
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (triggerExport):", error);
            this.isSubmitting = false;
        }
    }
}