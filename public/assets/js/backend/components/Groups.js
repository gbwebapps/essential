/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class GroupManager {
    constructor(config = {}) {
        this.config = Object.assign({
            getGroups: '',
            getGroup: '',
            urlGetExceptions: '' /* Questo punterà al metodo di ricerca, es: backend/groups/searchAdmins */
        }, config);

        /* Variabili di stato per evitare ricaricamenti inutili */
        this.isListLoaded = false;
        this.isExceptionsLoaded = false; 
        this.isAddLoaded = false;
        
        /* Proprietà per il controllo del debounce */
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        this.bindDynamicEvents();
    }

    /* Gestione dei listener sugli elementi generati dinamicamente via AJAX */
    bindDynamicEvents() {
        /* 1. Intercettiamo solo la CHIUSURA del singolo gruppo per svuotarlo */
        document.addEventListener('hidden.bs.collapse', e => {
            const subCollapse = e.target.closest('#groupsAccordion .accordion-collapse');
            if ( ! subCollapse) return;

            /* Blocchiamo il bubbling anche in chiusura */
            e.stopPropagation();

            const bodyContainer = subCollapse.querySelector('.template-container');
            if (bodyContainer) {
                bodyContainer.innerHTML = ''; 
            }
        });

        /* 2. Intercettiamo il submit del form di aggiunta nuovo gruppo */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id === 'new-group') {
                this.handleAddGroup(e);
            }
        });

        /* 3. Intercettiamo il submit dei form di edit generati dinamicamente */
        document.addEventListener('submit', e => {
            const editForm = e.target.closest('.edit-group-form');
            if (editForm) {
                this.handleEditGroup(e);
            }
        });

        /* 4. Intercettiamo il click sul pulsante ripristino (refresh) del gruppo */
        document.addEventListener('click', e => {
            const refreshBtn = e.target.closest('.btn-refresh-group');
            if (refreshBtn) {
                e.preventDefault();
                this.handleRefreshGroup(refreshBtn);
            }
        });

        /* 5. Intercettiamo il click sul pulsante elimina del gruppo */
        document.addEventListener('click', e => {
            const deleteBtn = e.target.closest('.btn-delete-group');
            if (deleteBtn) {
                e.preventDefault();
                this.handleDeleteGroup(deleteBtn);
            }
        });

        /* 6. Intercettiamo il reset del form di aggiunta */
        document.addEventListener('click', e => {
            const resetBtn = e.target.closest('.btn-reset-group');
            if (resetBtn) {
                e.preventDefault();
                this.handleResetGroup(resetBtn);
            }
        });

        /* 7. Intercettiamo la digitazione nel campo di ricerca amministratori */
        document.addEventListener('input', e => {
            if (e.target && e.target.id === 'search-admin') {
                this.handleAdminSearch(e.target);
            }
        });

        /* 8. Intercettiamo il click sulla selezione dell'amministratore dal dropdown */
        document.addEventListener('click', e => {
            const adminBtn = e.target.closest('.btn-select-admin');
            if (adminBtn) {
                e.preventDefault();
                this.handleSelectAdmin(adminBtn);
            }
        });

        /* 9. Intercettiamo il submit del form eccezioni permessi dell'amministratore */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id === 'edit-admin-exceptions-form') {
                this.handleSaveAdminPermissions(e);
            }
        });

        /* 10. Intercettiamo il click sul pulsante ricarica dati delle eccezioni dell'amministratore */
        document.addEventListener('click', e => {
            const refreshAdminBtn = e.target.closest('.btn-refresh-admin-perms');
            if (refreshAdminBtn) {
                e.preventDefault();
                this.handleRefreshAdminPermissions(refreshAdminBtn);
            }
        });

        /* 11. Intercettiamo il click sul pulsante X per svuotare la ricerca amministratori */
        document.addEventListener('click', e => {
            const resetSearchBtn = e.target.closest('.reset-search-field');
            if (resetSearchBtn) {
                e.preventDefault();
                this.handleResetAdminSearch(resetSearchBtn);
            }
        });
    }

    /**
     * Esegue la fetch asincrona per recuperare i dettagli di un singolo gruppo (Sotto-gruppo)
     * e inietta l'output nel contenitore designato.
     */
    async loadSingleGroupData(groupId, bodyContainer) {
        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(this.config.getGroup, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(bodyContainer, data.output);
                return true;
            }
            return false;
        } catch (error) {
            console.error("Errore caricamento form edit gruppo:", error);
            return false;
        }
    }

    /* Gestisce l'invio asincrono del form per la creazione di un nuovo gruppo */
    async handleAddGroup(e) {
        e.preventDefault();

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(urlbase + 'backend/groups/add', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }
                
                formEl.reset();

                this.isListLoaded = false;
                const mainCollapseList = document.getElementById('main_collapse_list');
                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }
        } catch (error) {
            console.error("Errore durante l'invio del form nuovo gruppo:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async handleResetGroup(btnEl) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message;
        const ok = await askConfirm(message);
        if ( ! ok) return;

        this.isSubmitting = true;
        
        const container = document.getElementById('add-groups-container');
        if ( ! container) {
            this.isSubmitting = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'reset');

            const response = await apiFetch(urlbase + 'backend/groups/add', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true && data.output) {
                smoothReplace(container, data.output);
            }

        } catch (error) {
            console.error("Errore durante il ripristino dei dati del gruppo:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Gestisce l'invio asincrono del form per la modifica di un gruppo esistente */
    async handleEditGroup(e) {
        e.preventDefault();

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(urlbase + 'backend/groups/edit', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const groupId = formEl.dataset.id;
                const toggleBtnSpan = document.querySelector(`[data-bs-target="#collapse_group_${groupId}"] span`);
                const nameInput = formEl.querySelector('input[name="name"]');
                if (toggleBtnSpan && nameInput) {
                    toggleBtnSpan.textContent = nameInput.value;
                }
            }
        } catch (error) {
            console.error("Errore durante l'invio del form modifica gruppo:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Richiede al backend i dati originali del gruppo e ripristina lo stato del form */
    async handleRefreshGroup(btnEl) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message;
        const ok = await askConfirm(message);
        if ( ! ok) return;

        this.isSubmitting = true;
        const groupId = btnEl.dataset.id;
        
        const formEl = document.querySelector(`.edit-group-form[data-id="${groupId}"]`);
        if ( ! formEl) {
            this.isSubmitting = false;
            return;
        }
        
        const container = formEl.closest('.accordion-body .template-container');
        if ( ! container) {
            this.isSubmitting = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', groupId);
            formData.append('action', 'refresh');

            const response = await apiFetch(urlbase + 'backend/groups/edit', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === true && data.output) {
                smoothReplace(container, data.output);
            }

        } catch (error) {
            console.error("Errore durante il ripristino dei dati del gruppo:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Gestisce l'eliminazione asincrona di un gruppo previa conferma dell'utente */
    async handleDeleteGroup(btnEl) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message;
        const ok = await askConfirm(message);
        if ( ! ok) return;

        this.isSubmitting = true;
        const groupId = btnEl.dataset.id;

        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(urlbase + 'backend/groups/del', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.errors) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                this.isListLoaded = false;
                const mainCollapseList = document.getElementById('main_collapse_list');
                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }
        } catch (error) {
            console.error("Errore durante l'eliminazione del gruppo:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Carica lo scheletro iniziale del pannello di aggiunta nuovo gruppo (Primo livello) */
    async loadAddGroupPanel() {
        if (this.isAddLoaded) return;

        const container = document.getElementById('add-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(urlbase + 'backend/groups/openAdd', { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isAddLoaded = true;
            }
        } catch (error) {
            console.error("Errore durante il caricamento del form aggiunta gruppo:", error);
        }
    }

    /* Svuota il container alla chiusura del pannello per distruggere il form dal DOM */
    resetAddContainer() {
        const container = document.getElementById('add-groups-container');
        if (container) {
            container.innerHTML = '';
            this.isAddLoaded = false; 
        }
    }

    /* Carica l'elenco dei gruppi (Primo livello AJAX) */
    async loadGroupsList() {
        if (this.isListLoaded) return;

        const container = document.getElementById('showAll-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(this.config.getGroups, { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isListLoaded = true;
            }
        } catch (error) {
            console.error("Errore caricamento lista gruppi:", error);
        }
    }

    /* Metodo pubblico per resettare lo stato della lista principale quando viene chiusa */
    resetListContainer() {
        const container = document.getElementById('showAll-groups-container');
        if (container) {
            container.innerHTML = ''; 
            this.isListLoaded = false; 
        }
    }

    /* Carica lo scheletro iniziale del pannello eccezioni (Terzo livello) */
    async loadExceptionsPanel() {
        if (this.isExceptionsLoaded) return;

        const container = document.getElementById('exceptions-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(urlbase + 'backend/groups/openExceptions', { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isExceptionsLoaded = true;
            }
        } catch (error) {
            console.error("Errore durante il caricamento del form aggiunta gruppo:", error);
        }
    }

    /* Svuota il container alla chiusura del pannello per distruggere il form dal DOM */
    resetExceptionsContainer() {
        const container = document.getElementById('exceptions-groups-container');
        if (container) {
            container.innerHTML = '';
            this.isExceptionsLoaded = false; 
        }
    }

    handleAdminSearch(inputEl) {

        /* Cancella il timeout precedente se l'utente sta ancora digitando */
        clearTimeout(this.searchTimeout);

        /* Gestione visibilità della X al volo */
        const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
        if (resetBtn) {
            resetBtn.style.display = inputEl.value.length > 0 ? 'flex' : 'none';
        }

        /* Rimuove la griglia dei permessi appena si digitano caratteri */
        const permissionsGrid = document.getElementById('admin-permissions-container');
        if (permissionsGrid) permissionsGrid.innerHTML = '';

        const query = inputEl.value.trim();
        const container = document.getElementById('dropdownAdmins');
        if (!container) return;

        /* Se la query è inferiore a 3 caratteri svuotiamo il dropdown e ci fermiamo */
        if (query.length < 3) {
            container.innerHTML = '';
            return;
        }

        /* Avviamo il timer di 500ms */
        this.searchTimeout = setTimeout(async () => {
            try {
                const formData = new FormData();
                formData.append('query', query);

                const response = await apiFetch(this.config.urlGetExceptions, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.result === true && data.output) {
                    smoothReplace(container, data.output);
                } else {
                    container.innerHTML = '';
                }
            } catch (error) {
                console.error("Errore durante la ricerca degli amministratori:", error);
            }
        }, 500);
    }

    /**
     * Gestisce il click sull'amministratore dal dropdown, aggiorna il campo testo,
     * distrugge il dropdown e carica via AJAX la griglia dei permessi/eccezioni.
     */
    async handleSelectAdmin(btnEl) {
        const adminUuid = btnEl.dataset.id;
        const adminIdentity = btnEl.dataset.identity;

        const searchInput = document.getElementById('search-admin');
        if (searchInput) {
            searchInput.value = adminIdentity;
        }

        const dropdownContainer = document.getElementById('dropdownAdmins');
        if (dropdownContainer) {
            dropdownContainer.innerHTML = '';
        }

        const permissionsContainer = document.getElementById('admin-permissions-container');
        if (!permissionsContainer) return;

        try {
            const formData = new FormData();
            formData.append('uuid', adminUuid);

            const response = await apiFetch(urlbase + 'backend/groups/getAdminPermissions', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true && data.output) {
                smoothReplace(permissionsContainer, data.output);
            } else if (data.message && typeof showToast === 'function') {
                showToast('danger', data.message);
            }
        } catch (error) {
            console.error("Errore caricamento permessi:", error);
        }
    }

    /* Gestisce l'invio asincrono del form delle eccezioni permessi per il salvataggio */
    async handleSaveAdminPermissions(e) {
        e.preventDefault();

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        const errorDiv = formEl.querySelector('.error_exceptions');
        if (errorDiv) errorDiv.innerHTML = '\u00A0';

        try {
            const response = await apiFetch(urlbase + 'backend/groups/saveExceptions', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }
            }
        } catch (error) {
            console.error("Errore durante il salvataggio delle eccezioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Ricarica ed esegue il refresh della griglia permessi dell'amministratore selezionato */
    async handleRefreshAdminPermissions(btnEl) {
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message;
        const ok = await askConfirm(message);
        if ( ! ok) return;

        this.isSubmitting = true;
        const adminUuid = btnEl.dataset.uuid;

        const permissionsContainer = document.getElementById('admin-permissions-container');
        if (!permissionsContainer) {
            this.isSubmitting = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('uuid', adminUuid);

            const response = await apiFetch(urlbase + 'backend/groups/getAdminPermissions', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true && data.output) {
                smoothReplace(permissionsContainer, data.output);
            }
        } catch (error) {
            console.error("Errore durante il ripristino delle eccezioni:", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Svuota il campo di testo ricerca, nasconde la X e azzera il DOM dei risultati e dei permessi */
    handleResetAdminSearch(btnEl) {
        const inputGroup = btnEl.closest('.input-group');
        const searchInput = inputGroup?.querySelector('#search-admin');
        
        if (searchInput) {
            searchInput.value = '';
        }

        /* Nascondiamo la X */
        btnEl.style.display = 'none';

        /* Svuotiamo dropdown e griglia permessi */
        const dropdownContainer = document.getElementById('dropdownAdmins');
        if (dropdownContainer) dropdownContainer.innerHTML = '';

        const permissionsContainer = document.getElementById('admin-permissions-container');
        if (permissionsContainer) permissionsContainer.innerHTML = '';
    }
}