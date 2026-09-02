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
            removeId: '#btnCancelImport', 
            urlDelete: urlbase + 'backend/import/deleteFile',
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
        
        /* --- INIZIO MODIFICA CHUNKING: Contatori totali per l'intero processo --- */
        this.totalInserted = 0;
        this.totalUpdated = 0;
        /* --- FINE MODIFICA CHUNKING --- */
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
                /* --- INIZIO MODIFICA CHUNKING: Reset contatori all'avvio di una nuova importazione --- */
                this.totalInserted = 0;
                this.totalUpdated = 0;
                /* --- FINE MODIFICA CHUNKING --- */
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

    async deleteTempFile() {

        /* Recupera l'elemento tramite il selettore dell'attributo name */
        const tempFileEl = document.querySelector('input[name="tempFile"]');
        if ( ! tempFileEl || ! tempFileEl.value) return;

        const tempFile = tempFileEl.value;

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onDeleteBefore === 'function') {
            const stop = this.hooks.onDeleteBefore(tempFile);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const formData = new FormData();
            formData.append('file', tempFile);

            /* Assicurati di avere urlDelete definito nella tua configurazione (es. this.config.urlDelete) */
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

    /* --- INIZIO MODIFICA CHUNKING: Aggiunto parametro offset alla firma della funzione --- */
    async processImport(formElement, currentOffset = 0) {
    /* --- FINE MODIFICA CHUNKING --- */

        /* Blocchiamo il submit multiplo solo se siamo al primo giro (offset 0) */
        if (this.isSubmitting && currentOffset === 0) return;
        this.isSubmitting = true;

        if (currentOffset === 0 && typeof this.hooks.onImportBefore === 'function') {
            const stop = this.hooks.onImportBefore(formElement);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const formData = new FormData(formElement);
            formData.append('offset', currentOffset);
            
            /* Invia al server il totale accumulato fino al giro precedente */
            formData.append('accumulatedInserted', this.totalInserted || 0);
            formData.append('accumulatedUpdated', this.totalUpdated || 0);
            
            /* Determina l'URL in base allo step (upload file o conferma finale) */
            const isConfirmStep = formData.get('step') === 'confirm';
            const processUrl = isConfirmStep ? urlbase + 'backend/import/executeImport' : urlbase + 'backend/import/processCsv'; 

            const response = await apiFetch(processUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                /* 1. Caso errori massivi: inietta l'HTML pre-compilato nel contenitore del modale */
                if (data.errorOutput) {
                    const modalAlertContainer = document.getElementById('import-alert-container');
                    
                    if (modalAlertContainer) {
                        smoothReplace(modalAlertContainer, data.errorOutput);

                        const currentAlert = modalAlertContainer.querySelector('.alert');
                        if (currentAlert) {
                            currentAlert.addEventListener('closed.bs.alert', function () {
                                smoothReplace(modalAlertContainer, '');
                            });
                        }
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
                    const modalBody = document.getElementById('import-content-area');
                    if (modalBody) {
                        smoothReplace(modalBody, data.output);
                    }

                    /* --- INIZIO MODIFICA: Nasconde il pulsante se non ci sono dati da elaborare --- */
                    const submitBtn = document.querySelector('#' + this.config.modalId + ' button[form="importForm"]');
                    if (submitBtn) {
                        if (data.hasProcessableData === false) {
                            submitBtn.style.display = 'none';
                        } else {
                            submitBtn.style.display = ''; /* Ripristina la visualizzazione di default */
                        }
                    }
                    /* --- FINE MODIFICA --- */
                    
                    this.isSubmitting = false; // Sblocca perché attende il click dell'utente per il secondo step
                } 
                
                /* --- INIZIO MODIFICA CHUNKING: Gestione ricorsione per lo step finale --- */
                else if (isConfirmStep) {

                    const modalBody = document.getElementById('import-content-area');
                    
                    if (data.isFinished === false && data.progressOutput && modalBody) {
                        if (currentOffset === 0) {
                            smoothReplace(modalBody, data.progressOutput);
                        } else {
                            const progressText = document.getElementById('import-progress-text');
                            if (progressText && data.progressMessage) {
                                progressText.textContent = data.progressMessage;
                            }
                        }
                    }

                    this.totalInserted += (data.inserted || 0);
                    this.totalUpdated += (data.updated || 0);

                    if (data.isFinished === false && data.nextOffset) {
                        await this.processImport(formElement, data.nextOffset);
                        return;
                    }
                    
                    if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
                    
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
                /* --- FINE MODIFICA CHUNKING --- */
            }

        } catch (error) {
            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }
            console.error("Errore di comunicazione con il server:", error);
            this.isSubmitting = false;
        }
    }
}