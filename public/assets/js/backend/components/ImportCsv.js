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
            
            /* --- INIZIO MODIFICA CHUNKING: Accodiamo l'offset corrente alla richiesta --- */
            formData.append('offset', currentOffset);
            /* --- FINE MODIFICA CHUNKING --- */
            
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
                this.isSubmitting = false; // Sblocca in caso di errore
                return;
            }

            if (data.result === true) {

                /* Se era il primo step (upload), mostriamo l'anteprima */
                if ( ! isConfirmStep && data.output) {
                    const modalBody = document.querySelector('#' + this.config.modalId + ' .modal-body');
                    if (modalBody) {
                        smoothReplace(modalBody, data.output);
                    }
                    this.isSubmitting = false; // Sblocca perché attende il click dell'utente per il secondo step
                } 
                
                /* --- INIZIO MODIFICA CHUNKING: Gestione ricorsione per lo step finale --- */
                else if (isConfirmStep) {
                    
                    /* Aggiorniamo i totali globali con quelli ricevuti da questo specifico blocco */
                    this.totalInserted += (data.inserted || 0);
                    this.totalUpdated += (data.updated || 0);

                    /* Se il backend ci dice che non ha ancora finito, richiamiamo la funzione con il nuovo offset */
                    if (data.isFinished === false && data.nextOffset) {
                        
                        /* Opzionale: Qui potresti aggiornare un testo nel modale es. "Elaborate 500 righe..." */
                        
                        /* Chiamata ricorsiva al prossimo blocco */
                        await this.processImport(formElement, data.nextOffset);
                        return; // Usciamo da questa iterazione per non eseguire il codice sottostante
                    }
                    
                    /* Se arriviamo qui, l'importazione è finita totalmente. Mostriamo l'esito reale. */
                    
                    /* Se i totali sono zero, mostriamo il messaggio neutro, altrimenti costruiamo la stringa di successo */
                    let finalMessage = data.message; 
                    if ((this.totalInserted + this.totalUpdated) === 0) {
                        finalMessage = 'Importazione completata: nessun record modificato poiché i dati sono già allineati.';
                    } else {
                        /* Questa è una costruzione di fallback qualora il server restituisca solo il testo dell'ultimo blocco. 
                           Se usi una logica multilingua stretta, potresti dover restituire le stringhe dal backend. */
                        finalMessage = `Importazione completata. Inseriti: ${this.totalInserted}, Aggiornati: ${this.totalUpdated}.`;
                    }

                    if (finalMessage && typeof showAlert === 'function') showAlert('success', finalMessage);
                    
                    const modalEl = document.getElementById(this.config.modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }

                    if (typeof this.hooks.onImportAfter === 'function') {
                        this.hooks.onImportAfter(data);
                    }
                    
                    this.isSubmitting = false; // Sblocco definitivo a fine processo
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