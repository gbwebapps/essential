/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors, handleValidationImages, handleValidationDocuments } from '../backend.js';

/* Import degli altri componenti (nella stessa cartella) */
import { UploadPreviewImgManager } from './UploadPreviewImgManager.js';
import { GalleryOneImgManager } from './GalleryOneImgManager.js';
import { UploadPreviewDocManager } from './UploadPreviewDocManager.js';
import { GalleryOneDocManager } from './GalleryOneDocManager.js';

/* --- LIST MANAGER (Custom SSR) --- */
export class ListManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            controller: '',
            url: '',
            searchFields: [],
            containerId: ''
        }, config);

        this.hooks = Object.assign({
            onShowBefore: null,
            onShowAfter: null,
            onShowError: null
        }, hooks);

        this.state = {
            column: localStorage.getItem(`${this.config.controller}_column`) || 'created_at',
            order: localStorage.getItem(`${this.config.controller}_order`) || 'asc',
            page: localStorage.getItem(`${this.config.controller}_page`) || 1,
            rows: localStorage.getItem(`${this.config.controller}_rows`) || 5,
            searchFields: {}
        };

        this.debounceTimer = null;
        this.container = document.querySelector(`#${this.config.containerId}`);

        /* NUOVO: Variabili di stato */
        this.eventsBound = false;
        this.isLoading = false;
    }

    init() {
        if ( ! this.container) return;

        this.initFilters();
        this.initSearchBar();
        this.updateActiveSearchIndicator();
        this.bindEvents();
        
        this.showAll();
    }

    /* --- INIZIALIZZAZIONE --- */
    initFilters() {
        this.config.searchFields.forEach(field => {
            const key = `${this.config.controller}_${field}`;
            const value = localStorage.getItem(key) || '';
            this.state.searchFields[field] = value;

            /* L'HTML usa id come "admins-firstname" */
            const inputEl = document.getElementById(`${this.config.controller}-${field}`);
            if (inputEl) {
                inputEl.value = value;
                if (value !== '') {
                    const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                    if (resetBtn) resetBtn.style.display = 'flex';
                }
            }
        });

        const rowsSelect = document.getElementById('changeNumRows');
        if (rowsSelect) {
            rowsSelect.value = this.state.rows;
        }
    }

    initSearchBar() {
        const key = `${this.config.controller}_search_bar_visible`;
        const searchBar = document.getElementById('search-bar');
        const link = document.getElementById('link-search');
        
        if ( ! searchBar) return;

        if (localStorage.getItem(key) === '1') {
            searchBar.classList.add('show');
            if (link) link.setAttribute('aria-expanded', 'true');
        }

        searchBar.addEventListener('shown.bs.collapse', () => localStorage.setItem(key, '1'));
        searchBar.addEventListener('hidden.bs.collapse', () => localStorage.setItem(key, '0'));
    }

    /* --- EVENTI --- */
    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Paginazione e Ordinamento (Delegation sul container) */
        this.container.addEventListener('click', (e) => {
            const sortEl = e.target.closest(`a.sort`);
            if (sortEl) {
                e.preventDefault();
                this.updateState('column', sortEl.dataset.column);
                this.updateState('order', sortEl.dataset.order);
                this.showAll();
            }

            const pageEl = e.target.closest(`.pagination li a`);
            if (pageEl) {
                e.preventDefault();
                this.updateState('page', pageEl.dataset.page);
                this.showAll();
            }
        });

        /* Modifica numero righe */
        document.getElementById('changeNumRows')?.addEventListener('change', (e) => {
            this.updateState('rows', e.target.value);
            this.resetSortingAndPagination();
            this.showAll();
        });

        /* Azioni Toolbar */
        document.getElementById('link-reset-search')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.resetFilters();
            this.resetSortingAndPagination();
            this.showAll();
        });

        document.getElementById('reset-sorting-link')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.resetSortingAndPagination();
            this.showAll();
        });

        document.getElementById('refresh-list')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showAll();
        });

        /* Input Ricerca */
        this.config.searchFields.forEach(field => {
            const inputEl = document.getElementById(`${this.config.controller}-${field}`);
            if ( ! inputEl) return;

            /* Digitando nell'input */
            inputEl.addEventListener('keyup', (e) => {
                if (['Shift', 'Control', 'Alt', 'AltGraph', 'CapsLock', 'Tab', 'Escape'].includes(e.key)) return;

                const value = inputEl.value;
                localStorage.setItem(`${this.config.controller}_${field}`, value);
                this.state.searchFields[field] = value;

                const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                if (resetBtn) resetBtn.style.display = value ? 'flex' : 'none';

                /* Se il campo è stato svuotato manualmente, pulisci l'errore associato */
                this.updateActiveSearchIndicator();

                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    /* Spostato nel timer: pulisce l'errore in concomitanza col reload */
                    if ( ! value) {
                        const errorDiv = document.querySelector(`.error_${field}`);
                        if (errorDiv) errorDiv.innerHTML = '&nbsp;';
                    }
                    
                    this.resetSortingAndPagination();
                    this.showAll();
                }, 500);
            });

            /* Click sulla "X" per svuotare il singolo campo */
            const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    inputEl.value = '';
                    localStorage.setItem(`${this.config.controller}_${field}`, '');
                    
                    this.state.searchFields[field] = '';
                    resetBtn.style.display = 'none';
                                        
                    /* Pulisci l'errore associato a questo campo */
                    const errorDiv = document.querySelector(`.error_${field}`);
                    if (errorDiv) errorDiv.innerHTML = '&nbsp;';

                    this.updateActiveSearchIndicator();
                    this.resetSortingAndPagination();
                    this.showAll();
                });
            }
        });
    }

    /* --- METODI OPERATIVI --- */
    updateState(key, value) {
        this.state[key] = value;
        localStorage.setItem(`${this.config.controller}_${key}`, value);
    }

    resetFilters() {
        this.config.searchFields.forEach(field => {
            const inputEl = document.getElementById(`${this.config.controller}-${field}`);
            if (inputEl) {
                inputEl.value = '';
                const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                if (resetBtn) resetBtn.style.display = 'none';
            }
            localStorage.setItem(`${this.config.controller}_${field}`, '');
            this.state.searchFields[field] = '';
            
            /* Pulisci tutti gli errori iterando sui campi */
            const errorDiv = document.querySelector(`.error_${field}`);
            if (errorDiv) errorDiv.innerHTML = '&nbsp;';
        });
        this.updateActiveSearchIndicator();
    }

    resetSortingAndPagination() {
        this.updateState('column', 'created_at');
        this.updateState('order', 'asc');
        this.updateState('page', 1);
    }

    updateActiveSearchIndicator() {
        const linkSearch = document.getElementById('link-search');
        if ( ! linkSearch) return;

        const hasFilters = Object.values(this.state.searchFields).some(val => val?.trim() !== '');
        linkSearch.classList.toggle('text-danger', hasFilters);
    }

    /* --- COMUNICAZIONE SERVER --- */
    async showAll() {

        /* NUOVO: Impedisce sovrapposizioni di chiamate */
        if (this.isLoading) return;
        this.isLoading = true;

        const urlParams = new URLSearchParams();

        /* Costruzione dinamica parametri */
        Object.keys(this.state).forEach(key => {
            if (key === 'searchFields') {
                Object.entries(this.state.searchFields).forEach(([subKey, val]) => {
                    urlParams.append(`searchFields[${subKey}]`, val);
                });
            } else {
                urlParams.append(key, this.state[key]);
            }
        });

        if (typeof this.hooks.onShowBefore === 'function') {
            const stop = this.hooks.onShowBefore(urlParams);
            if (stop === false) {
                this.isLoading = false; /* <--- AGGIUNTO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        /* Chiamata Fetch */
        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: urlParams
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (CORRETTA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Controllo fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 4. Successo */
            if (data.result === true) {

                const showAllEl = document.getElementById(this.config.containerId);
                if (showAllEl) {
                    smoothReplace(showAllEl, data.output);
                }

                if (typeof this.hooks.onShowAfter === 'function') {
                    this.hooks.onShowAfter(data);
                }
            }

        } catch (error) {
            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onShowError === 'function') {
                this.hooks.onShowError(error);
            }
            console.error("Errore ListManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isLoading = false;
        }
    }
}

export class AddManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            formSelector: '', /* <--- Es: '#admins_add' */
            url: '',
            resetSelector: '', /* <--- Es: '#add_reset' */
            containerId: '', 
            imagePreviewManager: null,
            galleryOneImgManager: null,
            docPreviewManager: null,
            galleryOneDocManager: null
        }, config);

        this.hooks = Object.assign({
            onAddBefore: null,
            onAddAfter: null,
            onAddError: null,
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        /* Variabili di stato per prevenire sovrapposizioni e doppi click */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        /* Impedisce l'accumulo di listener multipli sul document globale */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione Invio Form tramite delegazione */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.config.formSelector);
            if ( ! formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            await this.add(formData);
        });

        /* Gestione Submit Form di Reset tramite delegazione */
        document.addEventListener('submit', async e => {
            const resetFormEl = e.target.closest(this.config.resetSelector);
            if ( ! resetFormEl) return;

            e.preventDefault();
            const message = resetFormEl.dataset.message;
            const ok = await askConfirm(message);
            if ( ! ok) return;
            await this.reset();
        });
    }

    async add(formData) {

        /* Blocco esecuzione se c'è già una chiamata in corso */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Hook opzionale prima dell'invio: UNIFORMATO (mantiene stop per interruzione preventiva) */
        if (typeof this.hooks.onAddBefore === 'function') {
            const stop = this.hooks.onAddBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        /* Se presente, aggiunge le immagini selezionate */
        if (this.config.imagePreviewManager) {
            const files = this.config.imagePreviewManager.files;
            files.forEach(({ id, file }) => {
                formData.append(`images[${id}]`, file);
            });
        }

        /* Se presente, aggiunge i documenti selezionati */
        if (this.config.docPreviewManager) {
            const files = this.config.docPreviewManager.files;
            files.forEach(({ id, file }) => {
                formData.append(`documents[${id}]`, file);
            });
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Chiamata POST */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) - UNIFORMATO SULLA NUOVA ROTTA */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione Errori di Validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                
                if (this.config.imagePreviewManager && typeof handleValidationImages === 'function') {
                    handleValidationImages(data.errors);
                }
                
                if (this.config.docPreviewManager && typeof handleValidationDocuments === 'function') {
                    handleValidationDocuments(data.errors);
                }
                
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                
                return;
            }

            /* 3. Errore generico gestito dal backend (es. fallimento email o DB) */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                /* TIMING FIX: Mostra subito il messaggio di successo prima di lanciare il reset asincrono */
                if (data.message && typeof showToast === 'function') showToast('success', data.message);

                /* NUOVO: Sblocca manualmente prima di chiamare il reset */
                this.isSubmitting = false;
                
                /* Chiama il metodo reset in modo pulito per aggiornare il DOM */
                await this.reset();

                /* Refresh gallery se presente */
                if (this.config.galleryOneImgManager) {
                    this.config.galleryOneImgManager.refresh();
                }

                /* Refresh documents se presente */
                if (this.config.galleryOneDocManager) {
                    this.config.galleryOneDocManager.refresh();
                }

                /* Hook opzionale post-successo: UNIFORMATO (puro, senza stop/return ridondanti) */
                if (typeof this.hooks.onAddAfter === 'function') {
                    this.hooks.onAddAfter(data);
                }
            }
        } catch (error) {
            /* Hook errore: UNIFORMATO (puro, senza stop/return ridondanti) */
            if (typeof this.hooks.onAddError === 'function') {
                this.hooks.onAddError(error);
            }
            console.error("Errore AddManager (add):", error);
        } finally {
            /* Rilascia sempre il blocco al termine dell'operazione */
            this.isSubmitting = false;
        }
    }

    async reset() {

        /* Blocco esecuzione se c'è già una chiamata in corso */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Hook prima del reset: UNIFORMATO (mantiene stop per interruzione preventiva) */
        if (typeof this.hooks.onResetBefore === 'function') {
            const stop = this.hooks.onResetBefore();
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        if ( ! this.config.resetSelector) {
            this.isSubmitting = false;
            return;
        }
        
        const resetForm = document.querySelector(this.config.resetSelector);
        if ( ! resetForm) {
            this.isSubmitting = false;
            return;
        }

        /* Creazione diretta e pulita: prende sempre i dati dal form */
        const formData = new FormData(resetForm);
        formData.append('action', 'reset');

        try {
            /* Chiamata POST */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) - UNIFORMATO SULLA NUOVA ROTTA */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione fallimento reset */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Caso successo reset */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.config.containerId);
                if (showDataEl) {
                    smoothReplace(showDataEl, data.output);
                }

                /* Rimuove istanze precedenti dei preview manager */
                if (this.config.imagePreviewManager) this.config.imagePreviewManager.destroy();
                if (this.config.docPreviewManager) this.config.docPreviewManager.destroy();
                
                /* Reinstanzia UploadPreviewImgManager */
                const input = document.querySelector('#inputImages');
                const preview = document.querySelector('#preview_images');
                const button = document.querySelector('#buttonImages');

                if (input && preview && button) {
                    this.config.imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');
                }

                /* Reinstanzia UploadPreviewDocManager */
                const inputDoc = document.querySelector('#inputDocuments');
                const previewDoc = document.querySelector('#preview_documents');
                const buttonDoc = document.querySelector('#buttonDocuments');

                if (inputDoc && previewDoc && buttonDoc) {
                    this.config.docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');
                }

                /* Hook dopo il completamento del reset: UNIFORMATO (puro, senza stop/return ridondanti) */
                if (typeof this.hooks.onResetAfter === 'function') {
                    this.hooks.onResetAfter(data);
                }
            }
        } catch (error) {
            /* Hook errore: UNIFORMATO (puro, senza stop/return ridondanti) */
            if (typeof this.hooks.onResetError === 'function') {
                this.hooks.onResetError(error);
            }
            console.error("Errore AddManager (reset):", error);
        } finally {
            /* Rilascia sempre il blocco al termine dell'operazione */
            this.isSubmitting = false;
        }
    }
}

export class EditManager {
    constructor(config = {}, hooks = {}) {
        /* Inizializza la configurazione di base con i selettori dinamici */
        this.config = Object.assign({
            formSelector: '', /* <--- Es: '#admins_edit' */
            url: '',
            refreshSelector: '', /* <--- Es: '#edit_refresh' */
            containerId: '', 
            imagePreviewManager: null,
            galleryOneImgManager: null,
            docPreviewManager: null,
            galleryOneDocManager: null
        }, config);

        /* Inizializza eventuali callback esterni da eseguire in momenti chiave */
        this.hooks = Object.assign({
            onEditBefore: null,
            onEditAfter: null,
            onEditError: null,
            onRefreshBefore: null,
            onRefreshAfter: null,
            onRefreshError: null,
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        /* Unico punto di aggancio tramite delegazione globale */
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce l'accumulo di listener multipli */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione Invio Form Edit tramite delegazione */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.config.formSelector);
            if ( ! formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            await this.edit(formData);
        });

        /* Gestione Submit Form di Refresh (Annulla) tramite delegazione */
        document.addEventListener('submit', async e => {
            const refreshFormEl = e.target.closest(this.config.refreshSelector);
            if ( ! refreshFormEl) return;

            e.preventDefault();
            const message = refreshFormEl.dataset.message;
            const ok = await askConfirm(message);
            if ( ! ok) return;

            const formData = new FormData(refreshFormEl);
            await this.refresh(formData);
        });
    }

    async edit(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Esegue hook personalizzato prima del salvataggio, blocca se ritorna false */
        if (typeof this.hooks.onEditBefore === 'function') {
            const stop = this.hooks.onEditBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Se presente, aggiunge le immagini dal preview manager */
        if (this.config.imagePreviewManager) {
            const files = this.config.imagePreviewManager.files;
            files.forEach(({ id, file }) => {
                formData.append(`images[${id}]`, file);
            });
        }

        /* Se presente, aggiunge i documenti dal preview documents */
        if (this.config.docPreviewManager) {
            const files = this.config.docPreviewManager.files;
            files.forEach(({ id, file }) => {
                formData.append(`documents[${id}]`, file);
            });
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Invio al backend */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* Visualizza eventuali errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                
                if (this.config.imagePreviewManager && typeof handleValidationImages === 'function') {
                    handleValidationImages(data.errors);
                }
                
                if (this.config.docPreviewManager && typeof handleValidationDocuments === 'function') {
                    handleValidationDocuments(data.errors);
                }
                
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                if (typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* Caso positivo: salvataggio riuscito */
            if (data.result === true) {
                /* Mostra toast di successo */
                if (typeof showToast === 'function') showToast('success', data.message);

                /* Hook personalizzato dopo il salvataggio */
                if (typeof this.hooks.onEditAfter === 'function') {
                    this.hooks.onEditAfter(data);
                }

                /* Recupera il form di refresh reale dal DOM per popolare correttamente i dati */
                const refreshFormEl = document.querySelector(this.config.refreshSelector);
                const refreshData = refreshFormEl ? new FormData(refreshFormEl) : new FormData();

                /* Se manca l'uuid nei dati del form (es. elemento non presente), lo recupera come fallback */
                const uuidEl = document.getElementById('uuid');
                if (uuidEl && !refreshData.has('uuid')) {
                    refreshData.append('uuid', uuidEl.value);
                }

                /* NUOVO: Sblocca manualmente prima di chiamare il refresh */
                this.isSubmitting = false;

                /* Esegue il refresh dei dati */
                await this.refresh(refreshData);
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                this.hooks.onEditError(error);
            }
            console.error("Errore EditManager (edit):", error);
        } finally {

            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Aggiunge parametro action=refresh per il backend */
        formData.append('action', 'refresh');

        /* Se manca uuid, lo prende dal DOM */
        const uuidEl = document.getElementById('uuid');
        if (uuidEl && !formData.has('uuid')) {
            formData.append('uuid', uuidEl.value);
        }

        /* Esegue hook prima del refresh (può bloccare se ritorna false) */
        if (typeof this.hooks.onRefreshBefore === 'function') {
            const stop = this.hooks.onRefreshBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Controllo per utente non loggato (filtro MasterFilter) - UNIFORMATO */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* Caso errore generico */
            if (data.result === false) {
                if (typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* Caso positivo: rigenera markup e reinizializza componenti */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.config.containerId);

                /* Distrugge vecchie istanze di preview e gallery */
                if (this.config.imagePreviewManager) this.config.imagePreviewManager.destroy();
                if (this.config.galleryOneImgManager) this.config.galleryOneImgManager.destroy();

                /* Distrugge vecchie istanze di uploadManager e galleryOneDoc */
                if (this.config.docPreviewManager) this.config.docPreviewManager.destroy();
                if (this.config.galleryOneDocManager) this.config.galleryOneDocManager.destroy();

                /* Sostituisce il DOM e reinizializza i componenti */
                if (showDataEl) {
                    smoothReplace(showDataEl, data.output);
                }

                const input = document.querySelector('#inputImages');
                const preview = document.querySelector('#preview_images');
                const button = document.querySelector('#buttonImages');
                const gallery = document.querySelector('#images_data');

                if (input && preview && button) {
                    this.config.imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');
                }

                if (gallery) {
                    this.config.galleryOneImgManager = new GalleryOneImgManager('#images_data');
                }

                const inputDoc = document.querySelector('#inputDocuments');
                const previewDoc = document.querySelector('#preview_documents');
                const buttonDoc = document.querySelector('#buttonDocuments');
                const galleryDoc = document.querySelector('#documents_data');

                if (inputDoc && previewDoc && buttonDoc) {
                    this.config.docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');
                }

                if (galleryDoc) {
                    this.config.galleryOneDocManager = new GalleryOneDocManager('#documents_data');
                }

                /* Hook dopo il completamento del refresh */
                if (typeof this.hooks.onRefreshAfter === 'function') {
                    this.hooks.onRefreshAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onRefreshError === 'function') {
                this.hooks.onRefreshError(error);
            }
            console.error("Errore EditManager (refresh):", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class DeleteManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            controller: '',
            url: '',
            listManager: null
        }, config);

        this.hooks = Object.assign({
            onDeleteBefore: null,
            onDeleteAfter: null,
            onDeleteError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target.closest('.deleteRecord');
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            const formData = new FormData(formEl);

            const ok = await askConfirm(message);
            if (ok) {
                this.deleteRecord(formData);
            }
        });
    }

    async deleteRecord(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Hook opzionale prima dell'invio */
        if (typeof this.hooks.onDeleteBefore === 'function') {
            const stop = this.hooks.onDeleteBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Chiamata POST all'endpoint */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                const listManager = this.config.listManager;
                
                /* Calcolo arretramento pagina se eliminiamo l'ultimo record della pagina corrente (non la prima) */
                const lastItemEl = document.getElementById('lastItemPage');
                const lastItemPage = lastItemEl ? parseInt(lastItemEl.dataset.lastitempage, 10) : 0;

                const currentPage = parseInt(localStorage.getItem(`${this.config.controller}_page`), 10) || 1;
                
                if (currentPage > 1 && lastItemPage === 1) {
                    const newPage = currentPage - 1;
                    localStorage.setItem(`${this.config.controller}_page`, newPage);
                    if (listManager && listManager.state) {
                        listManager.state.page = newPage;
                    }
                }

                /* Messaggio di successo */
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                /* Ricarica la lista */
                if (listManager && typeof listManager.showAll === 'function') {
                    listManager.showAll();
                }

                /* Hook opzionale post-successo corretta */
                if (typeof this.hooks.onDeleteAfter === 'function') {
                    this.hooks.onDeleteAfter(data);
                }
            }

        } catch (error) {
            /* Errori di rete o crash del server */
            if (typeof this.hooks.onDeleteError === 'function') {
                this.hooks.onDeleteError(error);
            }
            console.error("Errore DeleteManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class ChangeStatusManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            controller: '',
            url: '',
            listManager: null
        }, config);

        this.hooks = Object.assign({
            onStatusBefore: null,
            onStatusAfter: null,
            onStatusError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione dell'invio dei dati (cattura il click sul button type="submit") */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest('.changeStatus');
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            const ok = await askConfirm(message);

            if ( ! ok) {
                /* Se l'utente annulla la conferma, interrompiamo semplicemente il flusso */
                return;
            }

            /* Recuperiamo i dati dal form e li passiamo alla funzione */
            const formData = new FormData(formEl);
            this.changeStatus(formData);
        });
    }

    async changeStatus(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onStatusBefore === 'function') {
            const stop = this.hooks.onStatusBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (CORRETTA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                if (typeof this.hooks.onStatusAfter === 'function') {
                    this.hooks.onStatusAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onStatusError === 'function') {
                this.hooks.onStatusError(error);
            }
            console.error("Errore ChangeStatusManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class GeneralDataManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            url: '', 
            formSelector: ''
        }, config);

        this.hooks = Object.assign({
            onGeneralDataBefore: null,
            onGeneralDataAfter: null,
            onGeneralDataError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            if ( ! e.target.matches(this.config.formSelector)) return;

            e.preventDefault();
            const formData = new FormData(e.target);

            await this.getGeneralData(formData);
        });
    }

    async getGeneralData(formData) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onGeneralDataBefore === 'function') {
            const stop = this.hooks.onGeneralDataBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (UNIFORMATA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const generalDataEl = document.getElementById('generalData');
                if (generalDataEl) {
                    smoothReplace(generalDataEl, data.output);
                }

                if (typeof this.hooks.onGeneralDataAfter === 'function') {
                    this.hooks.onGeneralDataAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onGeneralDataError === 'function') {
                this.hooks.onGeneralDataError(error);
            }
            console.error("Errore GeneralDataManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class MetaDataManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            url: '', 
            formSelector: '#getMetaData'
        }, config);

        this.hooks = Object.assign({
            onMetaDataBefore: null,
            onMetaDataAfter: null,
            onMetaDataError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            if ( ! e.target.matches(this.config.formSelector)) return;

            e.preventDefault();
            const formData = new FormData(e.target);
            await this.getMetaData(formData);
        });
    }

    async getMetaData(formData) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onMetaDataBefore === 'function') {
            const stop = this.hooks.onMetaDataBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                /* Gestione del DOM interna come concordato per elementi ad ID fisso */
                const metaDataEl = document.getElementById('metaData');
                if (metaDataEl) {
                    smoothReplace(metaDataEl, data.output);
                }

                if (typeof this.hooks.onMetaDataAfter === 'function') {
                    this.hooks.onMetaDataAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onMetaDataError === 'function') {
                this.hooks.onMetaDataError(error);
            }
            console.error("Errore MetaDataManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}