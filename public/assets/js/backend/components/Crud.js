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
    }

    init() {
        if (!this.container) return;

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

            // L'HTML usa id come "admins-firstname"
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
        if (rowsSelect) rowsSelect.value = this.state.rows;
    }

    initSearchBar() {
        const key = `${this.config.controller}_search_bar_visible`;
        const searchBar = document.getElementById('search-bar');
        const link = document.getElementById('link-search');
        
        if (!searchBar) return;

        if (localStorage.getItem(key) === '1') {
            searchBar.classList.add('show');
            if (link) link.setAttribute('aria-expanded', 'true');
        }

        searchBar.addEventListener('shown.bs.collapse', () => localStorage.setItem(key, '1'));
        searchBar.addEventListener('hidden.bs.collapse', () => localStorage.setItem(key, '0'));
    }

    /* --- EVENTI --- */
    bindEvents() {
        // Paginazione e Ordinamento (Delegation sul container)
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

        // Modifica numero righe
        document.getElementById('changeNumRows')?.addEventListener('change', (e) => {
            this.updateState('rows', e.target.value);
            this.resetSortingAndPagination();
            this.showAll();
        });

        // Azioni Toolbar
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

        // Input Ricerca
        this.config.searchFields.forEach(field => {
            const inputEl = document.getElementById(`${this.config.controller}-${field}`);
            if (!inputEl) return;

            // Digitando nell'input
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
                    if (!value) {
                        const errorDiv = document.querySelector(`.error_${field}`);
                        if (errorDiv) errorDiv.innerHTML = '&nbsp;';
                    }
                    
                    this.resetSortingAndPagination();
                    this.showAll();
                }, 500);
            });

            // Click sulla "X" per svuotare il singolo campo
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
        if (!linkSearch) return;

        const hasFilters = Object.values(this.state.searchFields).some(val => val?.trim() !== '');
        linkSearch.classList.toggle('text-danger', hasFilters);
    }

    /* --- COMUNICAZIONE SERVER --- */
    async showAll() {
        if (typeof this.hooks.onShowBefore === 'function') {
            this.hooks.onShowBefore(this.state);
        }

        const urlParams = new URLSearchParams();
        
        // Costruzione dinamica parametri
        Object.keys(this.state).forEach(key => {
            if (key === 'searchFields') {
                Object.entries(this.state.searchFields).forEach(([subKey, val]) => {
                    urlParams.append(`searchFields[${subKey}]`, val);
                });
            } else {
                urlParams.append(key, this.state[key]);
            }
        });

        // Chiamata Fetch
        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: urlParams // URLSearchParams imposta automaticamente l'header x-www-form-urlencoded
            });

            const data = await response.json();

            if (data.result === 'no_current_user_logged') return window.location.href = `${urlbase}admin/auth/login`;
            if (data.result === '404') return window.location.href = `${urlbase}admin/404`;

            if (data.result === false) {
                if (typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            if (data.result === true) {
                if (typeof this.hooks.onShowAfter === 'function') this.hooks.onShowAfter(data);
                if (typeof smoothReplace === 'function') {
                    smoothReplace(this.container, data.output);
                } else {
                    this.container.innerHTML = data.output;
                }
            }

        } catch (error) {
            /* apiFetch lancia un oggetto { status, data } per errori 422 e 401 */
            if (error.status === 422 && error.data?.errors) {
                const searchScope = document.getElementById('search-bar');
                if (searchScope && typeof handleValidationErrors === 'function') {
                    handleValidationErrors(error.data.errors, searchScope);
                }
                if (typeof showToast === 'function') showToast('danger', error.data.message);
                return;
            }

            /* Fallback per altri tipi di errore non intercettati dal gestore globale */
            if (typeof this.hooks.onShowError === 'function') this.hooks.onShowError(error);
        }
    }
}

export class AddManager {
    constructor(config = {}, hooks = {})
    {
        /* Configurazione di base: ID form, endpoint, reset, preview, gallery */
        this.config = Object.assign({
            formIds: [],
            url: '',
            resetId: '',
            containerId: '', 
            imagePreviewManager: null,
            galleryOneImgManager: null,
            docPreviewManager: null,
            galleryOneDocManager: null
        }, config);

        /* Callback opzionali eseguibili in vari momenti del flusso */
        this.hooks = Object.assign({
            onAddBefore: null,
            onAddAfter: null,
            onAddError: null,
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        /* Collega gli eventi submit ai form di aggiunta */
        this.bindForms();

        /* Collega gli eventi submit al form di reset */
        this.bindReset();
    }

    bindForms() {
        /* Collega ogni form (by ID) al submit per aggiunta */
        this.config.formIds.forEach(id => {
            const form = document.getElementById(id);
            if ( ! form) return;

            form.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(form);
                void this.add(formData);
            });
        });
    }

    bindReset() {
        /* Se non specificato l'ID per il reset, ignora */
        if ( ! this.config.resetId) return;
        const resetForm = document.getElementById(this.config.resetId);
        if ( ! resetForm) return;

        /* Gestione submit del form di reset con conferma */
        resetForm.addEventListener('submit', async e => {
            e.preventDefault();
            const message = e.target.dataset.message;
            const ok = await askConfirm(message);
            if ( ! ok) return;
            await this.reset();
        });
    }

    async add(formData) {

        /* Hook opzionale prima dell'invio */
        if (typeof this.hooks.onAddBefore === 'function') {
            const stop = this.hooks.onAddBefore(formData);
            if (stop === false) return;
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

        try {
            /* Chiamata POST: rimosso l'header CSRF manuale, lo gestisce apiFetch */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Pulisce eventuali errori di validazione visivi precedenti */
            document.querySelectorAll("[class^='error_']").forEach(el => {
                el.innerHTML = '\u00A0';
            });

            /* Errore generico gestito dal backend (es. fallimento email o DB) */
            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {
                
                /* Chiama il metodo reset in modo pulito */
                await this.reset();

                /* Mostra messaggio di successo */
                showToast('success', data.message);

                /* Refresh gallery se presente */
                if (this.config.galleryOneImgManager) {
                    this.config.galleryOneImgManager.refresh();
                }

                /* Refresh documents se presente */
                if (this.config.galleryOneDocManager) {
                    this.config.galleryOneDocManager.refresh();
                }

                /* Hook opzionale post-successo */
                if (typeof this.hooks.onAddAfter === 'function') {
                    this.hooks.onAddAfter(data);
                }
            }
        } catch (error) {
            
            /* Gestione Utente non loggato (401 lanciato da apiFetch) */
            if (error.status === 401 && error.data?.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            /* Gestione Errori di Validazione (422 lanciato da apiFetch) */
            if (error.status === 422 && error.data?.errors) {
                
                /* Pulisce eventuali errori visivi precedenti prima di mostrare i nuovi */
                document.querySelectorAll("[class^='error_']").forEach(el => {
                    el.innerHTML = '\u00A0';
                });
                
                handleValidationErrors(error.data.errors);
                if (this.config.imagePreviewManager) handleValidationImages(error.data.errors);
                if (this.config.docPreviewManager) handleValidationDocuments(error.data.errors);
                showToast('danger', error.data.message);
                return;
            }

            /* Altri errori non gestiti o provenienti dagli hook */
            if (typeof this.hooks.onAddError === 'function') {
                this.hooks.onAddError(error);
            }
        }
    }

    async reset() {

        /* 2. Hook prima del reset (aggiornato) */
        if (typeof this.hooks.onResetBefore === 'function') {
            const stop = this.hooks.onResetBefore();
            if (stop === false) return;
        }

        if (!this.config.resetId) return;
        const resetForm = document.getElementById(this.config.resetId);
        if (!resetForm) return;

        /* 3. Creazione diretta e pulita: prende sempre i dati dal form */
        const formData = new FormData(resetForm);
        formData.append('action', 'reset');

        try 
        {
            /* Chiamata POST: rimosso l'header CSRF manuale */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione fallimento reset */
            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            /* Caso successo reset */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.config.containerId);

                /* Rimuove istanze precedenti dei preview manager */
                if (this.config.imagePreviewManager) this.config.imagePreviewManager.destroy();
                if (this.config.docPreviewManager) this.config.docPreviewManager.destroy();

                /* Sostituisce il markup del form con quello aggiornato */
                await smoothReplace(showDataEl, data.output);
                this.bindForms();

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

                /* Hook dopo il completamento del reset */
                if (typeof this.hooks.onResetAfter === 'function') {
                    this.hooks.onResetAfter(data);
                }
            }
        } catch (error) {
            
            /* Gestione Utente non loggato (401 lanciato da apiFetch) */
            if (error.status === 401 && error.data?.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            /* Altri errori non gestiti o provenienti dagli hook */
            if (typeof this.hooks.onResetError === 'function') {
                this.hooks.onResetError(error);
            }
        }
    }
}

export class EditManager {
    constructor(config = {}, hooks = {})
    {
        /* Inizializza la configurazione di base con i riferimenti ai form, preview e gallery */
        this.config = Object.assign({
            formIds: [],
            url: '',
            refreshId: '', 
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

        /* Collega gli eventi submit ai form di modifica */
        this.bindEditForms();

        /* Collega gli eventi submit al form di refresh (conferma + replace markup) */
        this.bindRefresh();
    }

    bindEditForms() {
        /* Cicla su tutti gli ID dei form edit e lega il submit */
        this.config.formIds.forEach(id => {
            const form = document.getElementById(id);
            if (!form) return;

            form.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(form);
                void this.edit(formData);
            });
        });
    }

    bindRefresh() {
        /* Se non è stato fornito un ID di refresh, non fa nulla */
        if (!this.config.refreshId) return;

        const refreshForm = document.getElementById(this.config.refreshId);
        if ( ! refreshForm) return;

        /* Collega evento submit al form di reset con conferma */
        refreshForm.addEventListener('submit', async e => {
            e.preventDefault();

            const message = e.target.dataset.message;
            const ok = await askConfirm(message);
            if (!ok) return;

            const formData = new FormData(e.target);
            await this.refresh(formData);
        });
    }

    async edit(formData) {

        /* Esegue hook personalizzato prima del salvataggio, blocca se ritorna false */
        if (typeof this.hooks.onEditBefore === 'function') {
            const stop = this.hooks.onEditBefore(formData);
            if (stop === false) return;
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

        try {
            /* Invio al backend con csrf token */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="X-CSRF-TOKEN"]')?.content ?? '' },
                body: formData
            });

            const data = await response.json();

            /* Controllo autenticazione */
            if (data.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            /* Controllo pagina non trovata */
            if (data.result === '404') {
                window.location.href = urlbase + 'backend/404';
                return;
            }

            /* Reset eventuali errori di validazione visivi */
            document.querySelectorAll("[class^='error_']").forEach(el => el.innerHTML = '\u00A0');

            /* Visualizza eventuali errori di validazione */
            if (data.errors) {
                handleValidationErrors(data.errors);
                handleValidationImages(data.errors);
                handleValidationDocuments(data.errors);
                showToast('danger', data.message);
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            /* Caso positivo: salvataggio riuscito */
            if (data.result === true) {
                /* Prepara nuovo FormData per la chiamata di refresh */
                const refreshData = new FormData();
                refreshData.append('X-CSRF-TOKEN', formData.get('X-CSRF-TOKEN'));
                const uuidEl = document.getElementById('uuid');
                if (uuidEl) refreshData.append('uuid', uuidEl.value);

                /* Esegue il refresh dei dati */
                await this.refresh(refreshData);

                /* Mostra toast di successo */
                showToast('success', data.message);

                /* Hook personalizzato dopo il salvataggio */
                if (typeof this.hooks.onEditAfter === 'function') {
                    this.hooks.onEditAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                this.hooks.onEditError(error);
            }
        }
    }

    async refresh(formData) {
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
            if (stop === false) return;
        }

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="X-CSRF-TOKEN"]')?.content ?? '' },
                body: formData
            });

            const data = await response.json();

            /* Controllo autenticazione */
            if (data.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            /* Controllo pagina non trovata */
            if (data.result === '404') {
                window.location.href = urlbase + 'backend/404';
                return;
            }

            /* Caso errore generico */
            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            /* Caso positivo: rigenera markup e reinizializza componenti */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.config.containerId);
                if ( ! showDataEl) return;

                /* Distrugge vecchie istanze di preview e gallery */
                if (this.config.imagePreviewManager)  this.config.imagePreviewManager.destroy();
                if (this.config.galleryOneImgManager)  this.config.galleryOneImgManager.destroy();

                /* Distrugge vecchie istanze di uploadManager e galleryOneDoc */
                if (this.config.docPreviewManager)  this.config.docPreviewManager.destroy();
                if (this.config.galleryOneDocManager)  this.config.galleryOneDocManager.destroy();

                /* Sostituisce il DOM e reinizializza i componenti */
                await smoothReplace(showDataEl, data.output);
                this.bindEditForms();

                const input   = document.querySelector('#inputImages');
                const preview = document.querySelector('#preview_images');
                const button  = document.querySelector('#buttonImages');
                const gallery = document.querySelector('#images_data');

                if (input && preview && button) {
                    this.config.imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');
                }

                if (gallery) {
                    this.config.galleryOneImgManager = new GalleryOneImgManager('#images_data');
                }

                const inputDoc   = document.querySelector('#inputDocuments');
                const previewDoc = document.querySelector('#preview_documents');
                const buttonDoc  = document.querySelector('#buttonDocuments');
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
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest('.delete_record');
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            const form_data = new FormData(formEl);

            const ok = await askConfirm(message);
            if (ok) {
                this.deleteRecord(form_data);
            }
        });
    }

    deleteRecord(form_data) {

        if (typeof this.hooks.onDeleteBefore === 'function') {
            this.hooks.onDeleteBefore(form_data);
        }

        apiFetch(this.config.url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="X-CSRF-TOKEN"]')?.content ?? ''
            },
            body: form_data
        })
        .then(response => response.json())
        .then(data => {
            if (data.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            if (data.result === '404') {
                window.location.href = urlbase + 'backend/404';
                return;
            }

            if (data.errors) {
                showToast('danger', data.errors);
                return;
            }

            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            if (data.result === true) {
                const listManager = this.config.listManager;
                const table = listManager.table;

                if (table) {
                    const info = table.page.info();
                    const rowsOnPage = table.rows({ page: 'current' }).count();

                    /* Se è l'ultimo record e non siamo in prima pagina, arretriamo il puntatore */
                    if (info.page > 0 && rowsOnPage === 1) {
                        table.page('previous');
                    }
                }

                showToast('success', data.message);

                /* Il tuo metodo che esegue ajax.reload(null, false) */
                listManager.showAll();

                if (typeof this.hooks.onDeleteAfter === 'function') {
                    this.hooks.onDeleteAfter(data);
                }
            }
        })
        .catch(error => {
            if (typeof this.hooks.onDeleteError === 'function') {
                this.hooks.onDeleteError(error);
            }
        });
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
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        /* 1. Listener per intercettare il click sullo switch e avviare il submit */
        document.addEventListener('change', e => {
            const switchEl = e.target.closest('.change_status .form-check-input');
            if (switchEl) {
                switchEl.form.requestSubmit();
            }
        });

        /* 2. Gestione dell'invio dei dati */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest('.change_status');
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            const ok = await askConfirm(message);

            if ( ! ok) {
                /* Se l'utente annulla, rimettiamo lo switch com'era prima */
                const checkbox = formEl.querySelector('.form-check-input');
                checkbox.checked = !checkbox.checked;
                return;
            }

            /* Recuperiamo i dati dal form e li passiamo alla funzione */
            const form_data = new FormData(formEl);
            this.changeStatus(form_data);
        });
    }

    changeStatus(form_data) {

        if (typeof this.hooks.onStatusBefore === 'function') {
            this.hooks.onStatusBefore(form_data);
        }

        apiFetch(this.config.url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="X-CSRF-TOKEN"]')?.content ?? ''
            },
            body: form_data /* Inviata la variabile form_data nel corpo della richiesta */
        })
        .then(response => response.json())
        .then(data => {
            if (data.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            if (data.result === '404') {
                window.location.href = urlbase + 'backend/404';
                return;
            }

            if (data.errors) {
                showToast('danger', data.errors);
                return;
            }

            if (data.result === false) {
                showToast('danger', data.message);
                return;
            }

            if (data.result === true) {
                const listManager = this.config.listManager;

                showToast('success', data.message);

                /* Esegui il reload solo se il manager esiste (evita il crash nello Show) */
                if (this.config.listManager) {
                    this.config.listManager.showAll();
                }

                /* Ora l'hook verrà chiamato correttamente */
                if (typeof this.hooks.onStatusAfter === 'function') {
                    this.hooks.onStatusAfter(data);
                }
            }
        })
        .catch(error => {
            if (typeof this.hooks.onStatusError === 'function') {
                this.hooks.onStatusError(error);
            }
        });
    }
}

export class GeneralDataManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            url: ''
        }, config);

        this.hooks = Object.assign({
            onGeneralDataAfter: null,
            onGeneralDataError: null
        }, hooks);
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('submit', e => {
            if (!e.target.matches('#get_general_data')) return;

            e.preventDefault();
            const form_data = new FormData(e.target);
            this.getGeneralData(form_data);
        });
    }

    getGeneralData(form_data) {
        apiFetch(this.config.url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': form_data.get('csrf_token') },
            body: form_data
        })
            .then(response => response.json())
            .then(data => {
                if (data.result === 'no_current_user_logged') {
                    window.location.href = urlbase + 'backend/auth/login';
                    return;
                }

                if (data.result === '404') {
                    window.location.href = urlbase + 'backend/404';
                    return;
                }

                if (data.errors) {
                    showToast('danger', data.errors);
                    return;
                }

                if (data.result === false) {
                    showToast('danger', data.message);
                    return;
                }

                if (data.result === true) {
                    const generalDataEl = document.getElementById('general_data');
                    if (generalDataEl) {
                        smoothReplace(generalDataEl, data.output);
                    }

                    if (typeof this.hooks.onGeneralDataAfter === 'function') {
                        this.hooks.onGeneralDataAfter(data);
                    }
                }
            })
            .catch(error => {
                if (typeof this.hooks.onGeneralDataError === 'function') {
                    this.hooks.onGeneralDataError(error);
                }
            });
    }
}

export class MetaDataManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            url: ''
        }, config);

        this.hooks = Object.assign({
            onMetaDataAfter: null,
            onMetaDataError: null
        }, hooks);
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('submit', e => {
            if (!e.target.matches('#get_meta_data')) return;

            e.preventDefault();
            const form_data = new FormData(e.target);
            this.getMetaData(form_data);
        });
    }

    getMetaData(form_data) {
        apiFetch(this.config.url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': form_data.get('csrf_token') },
            body: form_data
        })
            .then(response => response.json())
            .then(data => {
                if (data.result === 'no_current_user_logged') {
                    window.location.href = urlbase + 'backend/auth/login';
                    return;
                }

                if (data.result === '404') {
                    window.location.href = urlbase + 'backend/404';
                    return;
                }

                if (data.errors) {
                    showToast('danger', data.errors);
                    return;
                }

                if (data.result === false) {
                    showToast('danger', data.message);
                    return;
                }

                if (data.result === true) {
                    const metaDataEl = document.getElementById('meta_data');
                    if (metaDataEl) {
                        smoothReplace(metaDataEl, data.output);
                    }

                    if (typeof this.hooks.onMetaDataAfter === 'function') {
                        this.hooks.onMetaDataAfter(data);
                    }
                }
            })
            .catch(error => {
                if (typeof this.hooks.onMetaDataError === 'function') {
                    this.hooks.onMetaDataError(error);
                }
            });
    }
}