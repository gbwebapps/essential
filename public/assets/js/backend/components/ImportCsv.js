/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, smoothReplace, handleValidationErrors } from '../backend.js';

export class ImportCsvManager {
    constructor(config = {}, hooks = {}) {

        this.config = Object.assign({
            controller: '',
            urlModal: urlbase + 'backend/import/showModal',
            modalContainerId: 'import-modal-container', 
            modalId: 'importModal', 
            linkId: '#import-entity', 
        }, config);

        this.hooks = Object.assign({
            onModalBefore: null,
            onModalAfter: null,
            onImportBefore: null,
            onImportAfter: null,
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
            const entity = btn.dataset.importEntity;
            
            await this.showModal(entity);
        });

        /* 2. Gestione Submit del Form di Importazione */
        document.addEventListener('submit', async e => {
            if (e.target.id === 'importForm') {
                e.preventDefault();
                await this.processImport(e.target);
            }
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
            console.error("Errore ImportCsvManager (showModal):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async processImport(formElement) {

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        try {
            const formData = new FormData(formElement);
            
            /* Determina l'URL in base allo step (upload file o conferma finale) */
            const isConfirmStep = formData.get('step') === 'confirm';
            const processUrl = isConfirmStep ? urlbase + 'backend/import/executeImport' : urlbase + 'backend/import/processCsv'; 

            const response = await apiFetch(processUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {

                /* Se era il primo step (upload), mostriamo l'anteprima */
                if ( ! isConfirmStep && data.output) {
                    const modalBody = document.querySelector('#' + this.config.modalId + ' .modal-body');
                    if (modalBody) {
                        smoothReplace(modalBody, data.output);
                    }
                } 
                
                /* Se era lo step finale, mostriamo il successo e chiudiamo il modale */
                else if (isConfirmStep) {
                    if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
                    
                    const modalEl = document.getElementById(this.config.modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }

                    if (typeof this.hooks.onImportAfter === 'function') {
                        this.hooks.onImportAfter(data);
                    }
                }
            }

        } catch (error) {
            console.error("Errore ImportCsvManager:", error);
            if (typeof showAlert === 'function') showAlert('danger', 'Errore di comunicazione con il server.');
        } finally {
            this.isSubmitting = false;
        }
    }
}