/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, smoothReplace, handleValidationErrors } from '../backend.js';

export class ExportCsvManager {
    constructor(config = {}, hooks = {}) {

        this.config = Object.assign({
            controller: '',
            urlModal: urlbase + 'backend/export/showModal',
            urlExport: urlbase + 'backend/export/generate',
            modalContainerId: 'export-modal-container', 
            modalId: 'exportColumnsModal', 
            formSelector: '#exportColumnsForm', 
            linkId: '#export-entity', 
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

        /* 1. Apertura Modale (Delegazione per catturare il click sul link di esportazione) */
        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.linkId);
            if ( ! btn) return;

            e.preventDefault();
            const entity = btn.dataset.exportEntity;
            
            await this.showModal(entity);
        });

        /* 2. Generazione Export (Delegazione per catturare il submit del form nel modale) */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.config.formSelector);
            if ( ! formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            
            await this.generateExport(formData);
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

            /* Controllo fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {
                
                const container = document.getElementById(this.config.modalContainerId);
                
                /* ALLINEATO: Utilizzo di data.output inviato dal Controller PHP */
                if (container && data.output) {
                    smoothReplace(container, data.output);

                    /* Avvia il modale Bootstrap (cerca il modale dentro il container appena popolato) */
                    const modalEl = document.getElementById(this.config.modalId);
                    if (modalEl) {
                        /* Aggancio al backdrop statico globale */
                        const backdropEl = document.getElementById('customBackdrop');
                        
                        /* Prevenzione memory leak: recupera o crea l'istanza */
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                            backdrop: false,
                            keyboard: false
                        });

                        if (backdropEl) {
                            modalEl.addEventListener('show.bs.modal', () => backdropEl.classList.add('active'));
                            modalEl.addEventListener('hidden.bs.modal', () => backdropEl.classList.remove('active'));
                        }

                        /* Checkbox Seleziona/Deseleziona tutte specifico per export */
                        const selectAllCb = modalEl.querySelector('#selectAllExportColumns');
                        if (selectAllCb) {
                            selectAllCb.addEventListener('change', () => {
                                const checkboxes = modalEl.querySelectorAll('input[name="columns[]"]');
                                checkboxes.forEach(cb => cb.checked = selectAllCb.checked);
                            });
                        }

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
        } finally {
            this.isSubmitting = false;
        }
    }

    async generateExport(formData) {
        
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onExportBefore === 'function') {
            const stop = this.hooks.onExportBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
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

                /* Avvia il download contestuale se il backend restituisce l'URL del file generato */
                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.setAttribute('download', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                /* Chiusura del modale a operazione conclusa */
                const modalEl = document.getElementById(this.config.modalId);
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                }

                if (typeof this.hooks.onExportAfter === 'function') {
                    this.hooks.onExportAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ExportCsvManager (generateExport):", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}