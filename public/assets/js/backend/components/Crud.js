/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showAlert, askConfirm, smoothReplace, handleValidationErrors, handleValidationImages } from '../backend.js';

/* Import degli altri componenti (nella stessa cartella) */
import { UploadPreviewImgManager } from './UploadPreview.js';

/* --- LIST MANAGER (Custom SSR) --- */
export class ListManager {
    constructor(config = {}, hooks = {}) {

        this.config = Object.assign({
            controller: '',
            url: '',
            containerId: '', 
            searchFields: [], 
            searchDates: []
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

            searchFields: {},
            searchDates: {}
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

        /* Unifichiamo i campi per iterare sulla UI all'avvio */
        const allFields = [];

        /* I campi testo rimangono standard */
        this.config.searchFields.forEach(f => {
            allFields.push({ name: f, type: 'searchFields', isDate: false });
        });

        /* Per ogni colonna data, generiamo automaticamente le due varianti from e to */
        this.config.searchDates.forEach(f => {
            allFields.push({ name: `${f}-from`, type: 'searchDates', isDate: true });
            allFields.push({ name: `${f}-to`, type: 'searchDates', isDate: true });
        });

        allFields.forEach(field => {
            const key = `${this.config.controller}_${field.name}`;
            const value = localStorage.getItem(key) || '';
            
            /* Salva il valore nell'oggetto di stato corretto */
            this.state[field.type][field.name] = value;

            const inputEl = document.getElementById(`${this.config.controller}-${field.name}`);
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

        /* Input Ricerca (Generazione dinamica dei canali per testo e date) */
        const allFields = [];

        this.config.searchFields.forEach(f => {
            allFields.push({ name: f, type: 'searchFields', isDate: false });
        });

        this.config.searchDates.forEach(f => {
            allFields.push({ name: `${f}-from`, type: 'searchDates', isDate: true });
            allFields.push({ name: `${f}-to`, type: 'searchDates', isDate: true });
        });

        allFields.forEach(field => {
            const inputEl = document.getElementById(`${this.config.controller}-${field.name}`);
            if ( ! inputEl) return;

            const handleSearchUpdate = (useDebounce) => {
                const value = inputEl.value;
                localStorage.setItem(`${this.config.controller}_${field.name}`, value);
                this.state[field.type][field.name] = value;

                const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                if (resetBtn) resetBtn.style.display = value ? 'flex' : 'none';

                this.updateActiveSearchIndicator();

                const triggerSearch = () => {
                    if ( ! value) {
                        const errorDiv = document.querySelector(`.error_${this.config.controller}-${field.name}`);
                        if (errorDiv) errorDiv.innerHTML = '&nbsp;';
                    }
                    this.resetSortingAndPagination();
                    this.showAll();
                };

                if (useDebounce) {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(triggerSearch, 500);
                } else {
                    triggerSearch();
                }
            };

            if (field.isDate) {
                inputEl.addEventListener('change', () => handleSearchUpdate(false));
            } else {
                inputEl.addEventListener('input', () => handleSearchUpdate(true));
            }

            const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
            if (resetBtn) {
                resetBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const fp = inputEl._flatpickr || inputEl.closest('.input-group')?._flatpickr;

                    if (fp) {
                        fp.clear();
                    } else {
                        inputEl.value = '';
                        handleSearchUpdate(false);
                    }
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
        const allFields = [];

        this.config.searchFields.forEach(f => {
            allFields.push({ name: f, type: 'searchFields' });
        });

        this.config.searchDates.forEach(f => {
            allFields.push({ name: `${f}-from`, type: 'searchDates' });
            allFields.push({ name: `${f}-to`, type: 'searchDates' });
        });

        allFields.forEach(field => {
            const inputEl = document.getElementById(`${this.config.controller}-${field.name}`);
            if (inputEl) {
                const fp = inputEl._flatpickr || inputEl.closest('.input-group')?._flatpickr;

                if (fp) {
                    fp.clear();
                } else {
                    inputEl.value = '';
                    const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                    if (resetBtn) resetBtn.style.display = 'none';
                }
            }
            
            localStorage.setItem(`${this.config.controller}_${field.name}`, '');
            this.state[field.type][field.name] = '';
            
            const errorDiv = document.querySelector(`.error_${this.config.controller}-${field.name}`);
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

        const hasTextFields = Object.values(this.state.searchFields).some(val => val?.trim() !== '');
        const hasDateFields = Object.values(this.state.searchDates).some(val => val?.trim() !== '');
        
        linkSearch.classList.toggle('text-danger', hasTextFields || hasDateFields);
    }

    /* --- COMUNICAZIONE SERVER --- */
    async showAll() {

        if (this.isLoading) return;
        this.isLoading = true;

        const urlParams = new URLSearchParams();

        /* Costruzione dinamica parametri dividendo i due array strutturati */
        Object.keys(this.state).forEach(key => {
            if (key === 'searchFields') {
                Object.entries(this.state.searchFields).forEach(([subKey, val]) => {
                    urlParams.append(`searchFields[${subKey}]`, val);
                });
            } else if (key === 'searchDates') {
                Object.entries(this.state.searchDates).forEach(([subKey, val]) => {
                    urlParams.append(`searchDates[${subKey}]`, val);
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

            /* Recupero centralizzato dell'elemento del DOM */
            const showAllEl = document.getElementById(this.config.containerId);

            /* Controllo errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                
                /* Se presente l'elemento, svuota la tabella mostrando l'errore centralizzato */
                if (showAllEl && data.message) {
                    const errorTemplate = `<div class="text-center text-danger py-3 fw-bold">${data.message}</div>`;
                    smoothReplace(showAllEl, errorTemplate);
                }

                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);

                return;
            }

            /* Controllo fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            /* Successo (data.result === true) */
            if (data.result === true) {

                if (showAllEl && data.output) {
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
            formSelector: '', 
            url: '',
            resetSelector: '', 
            containerId: '', 
            imagePreviewManager: null,
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

        /* Hook opzionale prima dell'invio */
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

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Chiamata POST */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione Errori di Validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                
                if (this.config.imagePreviewManager && typeof handleValidationImages === 'function') {
                    handleValidationImages(data.errors);
                }
                
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {

				/* NUOVO: Sblocca manualmente prima di chiamare il reset */
                this.isSubmitting = false;
                
                /* Esegue il reset attendendo il rendering del DOM prima di sbloccare la sottomissione */
                await this.reset();

                if (typeof this.hooks.onAddAfter === 'function') {
                    this.hooks.onAddAfter(data);
                }

                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
            }
        } catch (error) {
            if (typeof this.hooks.onAddError === 'function') {
                this.hooks.onAddError(error);
            }
            console.error("Errore AddManager (add):", error);
        } finally {
            /* Rilascia il blocco solo adesso, a fine transazione */
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

            /* 2. Gestione fallimento reset */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* 3. Caso successo reset */
            if (data.result === true) {

                const showDataEl = document.getElementById(this.config.containerId);
                if (showDataEl && data.output) {
                    smoothReplace(showDataEl, data.output);
                }

                /* Struttura Professionale: Al gestore immagini diciamo solo di resettare lo stato interno. */
                /* Sarà lui, grazie alla delegazione degli eventi, a funzionare immediatamente sul nuovo HTML. */
                if (this.config.imagePreviewManager) {
                    this.config.imagePreviewManager.reset();
                }

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
            formSelector: '', 
            url: '',
            refreshSelector: '', 
            containerId: '', 
            imagePreviewManager: null,
            galleryOneImgManager: null
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

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {

        /* Unico punto di aggancio tramite delegazione globale */
        this.bindEvents();
    }

    bindEvents() {

        /* Impedisce l'accumulo di listener multipli */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione Invio Form Edit tramite delegazione */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.config.formSelector);
            if (!formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            await this.edit(formData);
        });

        /* Gestione Submit Form di Refresh (Annulla) tramite delegazione */
        document.addEventListener('submit', async e => {
            const refreshFormEl = e.target.closest(this.config.refreshSelector);
            if (!refreshFormEl) return;

            e.preventDefault();
            const message = refreshFormEl.dataset.message;
            const ok = await askConfirm(message);
            if ( ! ok) return;

            const formData = new FormData(refreshFormEl);
            await this.refresh(formData);
        });
    }

    async edit(formData) {

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Esegue hook personalizzato prima del salvataggio, blocca se ritorna false */
        if (typeof this.hooks.onEditBefore === 'function') {
            const stop = this.hooks.onEditBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
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

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {

            /* Invio al backend */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Visualizza eventuali errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                
                if (this.config.imagePreviewManager && typeof handleValidationImages === 'function') {
                    handleValidationImages(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                if (typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso positivo: salvataggio riuscito */
            if (data.result === true) {

                /* Recupera il form di refresh reale dal DOM per popolare correttamente i dati */
                const refreshFormEl = document.querySelector(this.config.refreshSelector);
                const refreshData = refreshFormEl ? new FormData(refreshFormEl) : new FormData();

                /* Se manca l'uuid nei dati del form, lo recupera come fallback */
                const uuidEl = document.getElementById('uuid');
                if (uuidEl && !refreshData.has('uuid')) {
                    refreshData.append('uuid', uuidEl.value);
                }

                /* Sblocca manualmente prima di chiamare il refresh */
                this.isSubmitting = false;

                /* Esegue il refresh dei dati */
                await this.refresh(refreshData); 

                /* Hook personalizzato dopo il salvataggio */
                if (typeof this.hooks.onEditAfter === 'function') {
                    this.hooks.onEditAfter(data);
                }

                /* Mostra toast di successo */
                if (typeof showAlert === 'function') showAlert('success', data.message);
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                this.hooks.onEditError(error);
            }
            console.error("Errore EditManager (edit):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {
        
        /* Se c'è già una richiesta in corso, blocca */
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
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Caso errore generico */
            if (data.result === false) {
                if (typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso positivo: rigenera markup e resetta lo stato dei componenti */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.config.containerId);
                if (showDataEl && data.output) {
                    smoothReplace(showDataEl, data.output);
                }

                /* Ripristina lo stato interno del gestore di anteprime per svuotare la coda dei file */
                if (this.config.imagePreviewManager) {
                    this.config.imagePreviewManager.reset();
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
            this.isSubmitting = false;
        }
    }
}

export class DeleteManager {
    constructor(config = {}, hooks = {}){

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
                await this.deleteRecord(formData);
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

        try {
            /* Chiamata POST all'endpoint */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            /* Caso successo */
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

                /* Ricarica la lista */
                if (listManager && typeof listManager.showAll === 'function') {
                    listManager.showAll();
                }

                /* Hook opzionale post-successo corretta */
                if (typeof this.hooks.onDeleteAfter === 'function') {
                    this.hooks.onDeleteAfter(data);
                }

                /* Messaggio di successo */
                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
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
    constructor(config = {}, hooks = {}) {

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
            await this.changeStatus(formData);
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

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

             /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {

                if (typeof this.hooks.onStatusAfter === 'function') {
                    this.hooks.onStatusAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
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

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {
               
               const generalDataEl = document.getElementById('generalData');
                if (generalDataEl && data.output) {
                    smoothReplace(generalDataEl, data.output);
                }

                if (typeof this.hooks.onGeneralDataAfter === 'function') {
                    this.hooks.onGeneralDataAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
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

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {
                
                /* Gestione del DOM interna come concordato per elementi ad ID fisso */
                const metaDataEl = document.getElementById('metaData');
                if (metaDataEl && data.output) {
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