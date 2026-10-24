/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, smoothReplace } from '../backend.js';

export class ImportCsvManager {
    constructor(config = {}, hooks = {}) {

        if (ImportCsvManager.instance) {
            return ImportCsvManager.instance;
        }
        ImportCsvManager.instance = this;

        this.config = Object.assign({
            urlModal: urlbase + 'backend/import/showModal',
            urlProcess: urlbase + 'backend/import/processCsv',
            urlExecute: urlbase + 'backend/import/executeImport',
            modalContainerId: 'import-modal-container', 
            modalId: 'importModal', 
            linkId: '#import-entity', 
            removeId: '#btnCancelImport, #btnCancelImportTop',
            urlDelete: urlbase + 'backend/import/deleteFile',
            maxNetworkRetries: 2,
            retryDelayMs: 500,
        }, config);

        this.hooks = Object.assign({
            onModalBefore: null,
            onModalAfter: null,
            onImportBefore: null,
            onImportAfter: null,
            onDeleteBefore: null,
            onDeleteAfter: null,
            onError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.activeImportId = null;
        this.recoveryRequired = false;
        
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        if (this.eventsBound) return;
        this.eventsBound = true;

        /* 1. Apertura Modale */
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

        /* Eliminazione file temporaneo se l'admin clicca su annulla nel modale di importazione */
        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.removeId);
            if ( ! btn) return;

            e.preventDefault();
            
            await this.deleteTempFile();
        });

    }

    async showModal(entity) {

        if (this.isSubmitting) return;
        this.activeImportId = null;
        this.recoveryRequired = false;
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

                this.updateModalControls(Boolean(this.activeImportId), data.hasProcessableData !== false);
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

    /* Mantiene allineati i comandi in testata e nel footer del modale. */
    updateModalControls(hasPreview, hasProcessableData = true) {
        const modal = document.getElementById(this.config.modalId);
        if ( ! modal) return;

        modal.querySelectorAll('button[form="importForm"]').forEach(button => {
            button.hidden = ! hasProcessableData;
            button.disabled = this.recoveryRequired;
        });

        modal.querySelectorAll('[data-import-close-label]').forEach(label => {
            label.textContent = hasPreview && hasProcessableData ? label.dataset.cancel : label.dataset.close;
        });
    }

    async deleteTempFile() {

        const importIdEl = document.querySelector('input[name="importId"]');
        const importId = importIdEl?.value || this.activeImportId;

        if ( ! importId) {
            const modalEl = document.getElementById(this.config.modalId);
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            return;
        }

        /* In recovery lo staging e il backup sono artefatti diagnostici: chiudiamo la UI senza cancellarli. */
        if (this.recoveryRequired) {
            const modalEl = document.getElementById(this.config.modalId);
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            return;
        }

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onDeleteBefore === 'function') {
            const stop = this.hooks.onDeleteBefore(importId);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const formData = new FormData();
            formData.append('importId', importId);

            const response = await apiFetch(this.config.urlDelete, {
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
                this.activeImportId = null;
                this.recoveryRequired = false;

                if (typeof this.hooks.onDeleteAfter === 'function') {
                    this.hooks.onDeleteAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore ImportCsvManager (deleteTempFile):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async fetchWithRetry(url, options) {
        let attempt = 0;

        while (true) {
            try {
                return await apiFetch(url, options);
            } catch (error) {
                if (attempt >= this.config.maxNetworkRetries) throw error;

                attempt++;
                await new Promise(resolve => setTimeout(resolve, this.config.retryDelayMs * attempt));
            }
        }
    }

    restoreRetryForm() {
        if ( ! this.activeImportId || document.getElementById('importForm')) return;

        const modalBody = document.getElementById('import-content-area');
        if ( ! modalBody) return;

        const form = document.createElement('form');
        form.id = 'importForm';
        form.hidden = true;

        const importIdInput = document.createElement('input');
        importIdInput.type = 'hidden';
        importIdInput.name = 'importId';
        importIdInput.value = this.activeImportId;

        const stepInput = document.createElement('input');
        stepInput.type = 'hidden';
        stepInput.name = 'step';
        stepInput.value = 'confirm';

        form.append(importIdInput, stepInput);
        modalBody.appendChild(form);
    }

    async processImport(formElement, isContinuation = false) {

        if (this.isSubmitting && ! isContinuation) return;
        this.isSubmitting = true;

        if ( ! isContinuation && typeof this.hooks.onImportBefore === 'function') {
            const stop = this.hooks.onImportBefore(formElement);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            if ( ! isContinuation) {
                const alerts = document.getElementById('import-alert-container');
                if (alerts) smoothReplace(alerts, '');
                document.querySelector('#alert-container [data-import-validation-alert]')?.remove();
            }

            const formData = isContinuation && this.activeImportId
                ? new FormData()
                : new FormData(formElement);

            if (isContinuation && this.activeImportId) {
                formData.append('importId', this.activeImportId);
                formData.append('step', 'confirm');
            }

            /* Determina l'URL in base allo step (upload file o conferma finale) */
            const isConfirmStep = formData.get('step') === 'confirm';

            if (isConfirmStep && ! this.activeImportId) {
                this.activeImportId = formData.get('importId');
            }

            const processUrl = isConfirmStep ? this.config.urlExecute : this.config.urlProcess;

            const response = await this.fetchWithRetry(processUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.recoveryRequired === true) {
                    this.recoveryRequired = true;
                    this.activeImportId = data.importId || this.activeImportId;

                    /* Nessun retry applicativo è sicuro in uno stato ambiguo/parzialmente committed. */
                    document.querySelectorAll('#' + this.config.modalId + ' button[form="importForm"]').forEach(button => {
                        button.disabled = true;
                    });
                }

                /* 1. Caso errori massivi: inietta l'HTML pre-compilato nel contenitore del modale */
                if (data.errorOutput) {
                    const modalAlertContainer = document.getElementById('import-alert-container');
                    
                    if (modalAlertContainer) {
                        smoothReplace(modalAlertContainer, data.errorOutput);

                        const modalBody = modalAlertContainer.closest('.modal-body');
                        if (modalBody) modalBody.scrollTop = 0;

                        const currentAlert = modalAlertContainer.querySelector('.alert');
                        if (currentAlert) {
                            currentAlert.addEventListener('closed.bs.alert', function () {
                                smoothReplace(modalAlertContainer, '');
                            });
                        }
                    }

                    if (data.message && typeof showAlert === 'function') {
                        showAlert('danger', data.message);
                        document.querySelector('#alert-container .alert')?.setAttribute('data-import-validation-alert', '');
                    }
                } 
                /* 2. Caso errore generico: utilizza la funzione globale standard */
                else if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                
                this.isSubmitting = false;
                return;
            }

            if (data.result === true) {

                /* Se era il primo step (upload), mostriamo l'anteprima */
                if ( ! isConfirmStep && data.output) {
                    this.activeImportId = data.importId || null;

                    const modalBody = document.getElementById('import-content-area');
                    if (modalBody) {
                        smoothReplace(modalBody, data.output);
                    }

                    /* Nasconde la conferma quando il CSV è già allineato al database. */
                    this.updateModalControls(true, data.hasProcessableData !== false);

                    const scrollBody = document.querySelector('#' + this.config.modalId + ' .modal-body');
                    if (scrollBody) scrollBody.scrollTop = 0;
                    
                    this.isSubmitting = false; // Sblocca perché attende il click dell'utente per il secondo step
                } 
                
                /* Esecuzione progressiva a chunk. */
                else if (isConfirmStep) {

                    const modalBody = document.getElementById('import-content-area');
                    
                    if (data.isFinished === false && data.progressOutput && modalBody) {
                        if ( ! isContinuation) {
                            smoothReplace(modalBody, data.progressOutput);
                        } else {
                            const progressText = document.getElementById('import-progress-text');
                            if (progressText && data.progressMessage) {
                                progressText.textContent = data.progressMessage;
                            }
                        }
                    }


                    if (data.isFinished === false) {
                        await this.processImport(formElement, true);
                        return;
                    }
                    
                    if (data.message && typeof showAlert === 'function') showAlert('success', data.message);

                    this.activeImportId = null;
                    this.recoveryRequired = false;
                    
                    const modalEl = document.getElementById(this.config.modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }

                    if (typeof this.hooks.onImportAfter === 'function') {
                        this.hooks.onImportAfter(data);
                    }
                    
                    this.isSubmitting = false;
                }
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }

            this.restoreRetryForm();

            if (typeof showAlert === 'function') {
                showAlert('danger', error?.message || 'Errore di comunicazione con il server.');
            }

            console.error("Errore di comunicazione con il server:", error);
            this.isSubmitting = false;
        }
    }
}
