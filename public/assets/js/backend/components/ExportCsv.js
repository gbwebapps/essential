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
    }

    async showModal(entity) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

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
                        const backdropEl = document.getElementById('customBackdrop');
                        
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                            backdrop: false,
                            keyboard: false
                        });

                        if (backdropEl) {
                            modalEl.addEventListener('show.bs.modal', () => backdropEl.classList.add('active'));
                            modalEl.addEventListener('hidden.bs.modal', () => backdropEl.classList.remove('active'));
                        }

                        /* Avvia il trigger dei chunk SOLO quando il modale ha completato l'animazione di apertura */
                        modalEl.addEventListener('shown.bs.modal', async () => {
                            await this.triggerExport(entity);
                        }, { once: true });

                        modalInstance.show();
                    }
                }

                if (typeof this.hooks.onModalAfter === 'function') {
                    this.hooks.onModalAfter(data);
                }
                
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (showModal):", error);
            this.isSubmitting = false;
        }
    }

    async triggerExport(entity, currentOffset = 0, currentFileName = '') {
        try {
            const formData = new FormData();
            formData.append('entity', entity);
            formData.append('offset', currentOffset);
            
            if (currentFileName !== '') {
                formData.append('fileName', currentFileName);
            }

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

            const response = await apiFetch(this.config.urlExport, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
                
                if (data.isFinished === false) {
                    const progressText = document.getElementById('export-progress-text');
                    if (progressText && data.progressMessage) {
                        progressText.textContent = data.progressMessage;
                    }

                    await this.triggerExport(entity, data.nextOffset, data.fileName);
                    return; 
                }

                /* --- PROCESSO COMPLETATO --- */
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
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (triggerExport):", error);
            this.isSubmitting = false;
        }
    }
}