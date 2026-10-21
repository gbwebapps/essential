/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors, markUnsavedClean } from '../backend.js';

export class GroupsManager {
    constructor() {

        if (GroupsManager.instance) {
            return GroupsManager.instance;
        }
        GroupsManager.instance = this;

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.getGroupsUrl = urlbase + 'backend/groups/getGroups';
        this.getGroupUrl = urlbase + 'backend/groups/getGroup';
        this.getExceptionsUrl = urlbase + 'backend/groups/getDropdownAdmins';
        this.checkDeleteUrl = urlbase + 'backend/groups/checkDeleteConstraints';
        this.getAddUrl = urlbase + 'backend/groups/add';
        this.getEditUrl = urlbase + 'backend/groups/edit';
        this.getDeleteUrl = urlbase + 'backend/groups/del';
        this.getOpenAddGroup = urlbase + 'backend/groups/openAdd';
        this.getOpenExceptionsGroup = urlbase + 'backend/groups/openExceptions';
        this.getAdminPermissionsUrl = urlbase + 'backend/groups/getAdminPermissions';
        this.getSaveExceptionsUrl = urlbase + 'backend/groups/saveExceptions';

        /* Variabili di stato per evitare ricaricamenti inutili */
        this.isListLoaded = false;
        this.isExceptionsLoaded = false;
        this.isAddLoaded = false;

        /* Proprietà per il controllo del debounce */
        this.searchTimeout = null;

        /* Variabili di stato per la sicurezza */
        this.isSubmitting = false;
        this.isDeleting = false;
        this.isConfirming = false;
        this.destroyed = false;
        this.eventsBound = false;

        this.eventsController = new AbortController();
        this.pendingPanels = new Set();
        this.panelVersions = { add: 0, list: 0, exceptions: 0 };
        this.loadingGroups = new WeakSet();
        this.searchVersion = 0;
        this.permissionsVersion = 0;

        this.init();
    }

    init() {
        if (this.destroyed) return;

        this.bindDynamicEvents();
    }

    listen(type, handler) {
        document.addEventListener(type, handler, {
            signal: this.eventsController.signal
        });
    }

    /* Gestione dei listener sugli elementi generati dinamicamente via AJAX */
    bindDynamicEvents() {
        if (this.eventsBound || this.destroyed) return;
        this.eventsBound = true;

        /* 1. Intercettiamo solo la CHIUSURA del singolo gruppo per svuotarlo */
        this.listen('hidden.bs.collapse', e => {
            const subCollapse = e.target.closest('#groupsAccordion .accordion-collapse');
            if (!subCollapse) return;

            e.stopPropagation();

            const bodyContainer = subCollapse.querySelector('.template-container');

            if (bodyContainer) {
                smoothReplace(bodyContainer, '');
            }
        });

        /* 2. Intercettiamo il submit del form di aggiunta nuovo gruppo */
        this.listen('submit', e => {
            if (e.target && e.target.id === 'new-group') {
                this.handleAddGroup(e);
            }
        });

        /* 3. Intercettiamo il submit dei form di edit generati dinamicamente */
        this.listen('submit', e => {
            const editForm = e.target.closest('.edit-group-form');

            if (editForm) {
                e.preventDefault();
                this.handleEditGroup(e);
            }
        });

        /* 4. Intercettiamo il click sul pulsante ripristino del gruppo */
        this.listen('click', async e => {
            const refreshBtn = e.target.closest('.btn-refresh-group');

            if (refreshBtn) {
                e.preventDefault();

                if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

                this.isConfirming = true;

                try {
                    const ok = await askConfirm(refreshBtn.dataset.message);

                    if (ok && !this.destroyed) {
                        await this.handleRefreshGroup(refreshBtn);
                    }
                } catch (error) {
                    console.error('Errore conferma refresh gruppo:', error);
                } finally {
                    this.isConfirming = false;
                }
            }
        });

        /* 5. Intercettiamo il click sul pulsante elimina del gruppo */
        this.listen('click', async e => {
            const deleteBtn = e.target.closest('.btn-delete-group');

            if (deleteBtn) {
                e.preventDefault();

                if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

                this.isDeleting = true;
                this.isConfirming = true;

                try {
                    const isSafeToDelete = await this.checkDeleteConstraints(deleteBtn.dataset.id);

                    if (!isSafeToDelete || this.destroyed) return;

                    const ok = await askConfirm(deleteBtn.dataset.message);

                    if (!ok || this.destroyed) return;

                    await this.handleDeleteGroup(deleteBtn);
                } catch (error) {
                    console.error('Errore conferma eliminazione gruppo:', error);
                } finally {
                    this.isDeleting = false;
                    this.isConfirming = false;
                }
            }
        });

        /* 6. Intercettiamo il reset del form di aggiunta */
        this.listen('click', async e => {
            const resetBtn = e.target.closest('.btn-reset-group');

            if (resetBtn) {
                e.preventDefault();

                if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

                this.isConfirming = true;

                try {
                    const ok = await askConfirm(resetBtn.dataset.message);

                    if (ok && !this.destroyed) {
                        await this.handleResetGroup(resetBtn);
                    }
                } catch (error) {
                    console.error('Errore conferma reset gruppo:', error);
                } finally {
                    this.isConfirming = false;
                }
            }
        });

        /* 7. Intercettiamo la digitazione nel campo di ricerca amministratori */
        this.listen('input', e => {
            if (e.target && e.target.id === 'search-admin') {
                this.handleAdminSearch(e.target);
            }
        });

        /* 8. Intercettiamo la selezione dell'amministratore dal dropdown */
        this.listen('click', e => {
            const adminBtn = e.target.closest('.btn-select-admin');

            if (adminBtn) {
                e.preventDefault();
                this.handleSelectAdmin(adminBtn);
            }
        });

        /* 9. Intercettiamo il submit del form eccezioni */
        this.listen('submit', e => {
            if (e.target && e.target.id === 'edit-admin-exceptions-form') {
                this.handleSaveAdminPermissions(e);
            }
        });

        /* 10. Intercettiamo il refresh dei permessi dell'amministratore */
        this.listen('click', async e => {
            const refreshAdminBtn = e.target.closest('.btn-refresh-admin-perms');

            if (refreshAdminBtn) {
                e.preventDefault();

                if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

                this.isConfirming = true;

                try {
                    const ok = await askConfirm(refreshAdminBtn.dataset.message);

                    if (ok && !this.destroyed) {
                        await this.handleRefreshAdminPermissions(refreshAdminBtn);
                    }
                } catch (error) {
                    console.error('Errore conferma refresh permessi:', error);
                } finally {
                    this.isConfirming = false;
                }
            }
        });

        /* 11. Intercettiamo il pulsante X della ricerca amministratori */
        this.listen('click', e => {
            const resetSearchBtn = e.target.closest('.reset-search-field');

            if (resetSearchBtn) {
                e.preventDefault();
                this.handleResetAdminSearch(resetSearchBtn);
            }
        });
    }

    /**
     * Recupera i dettagli di un singolo gruppo e aggiorna il relativo contenitore.
     */
    async loadSingleGroupData(groupId, bodyContainer) {
        if (this.destroyed || !bodyContainer || this.loadingGroups.has(bodyContainer)) return false;

        this.loadingGroups.add(bodyContainer);

        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(this.getGroupUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed || !bodyContainer.isConnected) return false;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return false;
            }

            if (data.result === true && data.output) {
                smoothReplace(bodyContainer, data.output);
                return true;
            }

            return false;

        } catch (error) {
            console.error('Errore caricamento form edit gruppo:', error);
            return false;
        } finally {
            this.loadingGroups.delete(bodyContainer);
        }
    }

    /* Gestisce la creazione di un nuovo gruppo */
    async handleAddGroup(e) {
        e.preventDefault();

        if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.getAddUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }

                formEl.reset();
                markUnsavedClean(formEl);

                this.isListLoaded = false;

                const mainCollapseList = document.getElementById('main_collapse_list');

                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }

        } catch (error) {
            console.error('Errore durante l\'invio del form nuovo gruppo:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Ripristina il form di aggiunta */
    async handleResetGroup(btnEl) {
        if (this.isSubmitting || this.isDeleting || this.destroyed) return;

        this.isSubmitting = true;

        const container = document.getElementById('add-groups-container');

        if (!container) {
            this.isSubmitting = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'reset');

            const response = await apiFetch(this.getAddUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === true && data.output) {
                smoothReplace(container, data.output);
            }

        } catch (error) {
            console.error('Errore durante il ripristino dei dati del gruppo:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Gestisce la modifica di un gruppo esistente */
    async handleEditGroup(e) {
        if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.getEditUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                markUnsavedClean(formEl);

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }

                const groupId = formEl.dataset.id;
                const toggleBtnSpan = document.querySelector(`[data-bs-target="#collapse_group_${groupId}"] span`);
                const nameInput = formEl.querySelector('input[name="name"]');

                if (toggleBtnSpan && nameInput) {
                    toggleBtnSpan.textContent = nameInput.value;
                }

                this.isListLoaded = false;
            }

        } catch (error) {
            console.error('Errore durante l\'invio del form modifica gruppo:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Ripristina i dati originali di un gruppo */
    async handleRefreshGroup(btnEl) {
        if (this.isSubmitting || this.isDeleting || this.destroyed) return;

        this.isSubmitting = true;

        const groupId = btnEl.dataset.id;
        const formEl = document.querySelector(`.edit-group-form[data-id="${groupId}"]`);
        const container = formEl ? formEl.closest('.accordion-body .template-container') : null;

        try {
            const formData = new FormData();
            formData.append('id', groupId);
            formData.append('action', 'refresh');

            const response = await apiFetch(this.getEditUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true && data.output && container) {
                smoothReplace(container, data.output);
            }

        } catch (error) {
            console.error('Errore durante il ripristino del gruppo:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Gestisce l'eliminazione di un gruppo */
    async handleDeleteGroup(btnEl) {
        if (this.isSubmitting || this.destroyed) return;

        this.isSubmitting = true;

        const groupId = btnEl.dataset.id;

        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(this.getDeleteUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }

                this.isListLoaded = false;

                const mainCollapseList = document.getElementById('main_collapse_list');

                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }

        } catch (error) {
            console.error('Errore durante l\'eliminazione del gruppo:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Carica il pannello di aggiunta prima dell'apertura dell'accordion */
    async loadAddGroupPanel() {
        if (this.destroyed || this.isAddLoaded || this.pendingPanels.has('add')) return false;

        const container = document.getElementById('add-groups-container');
        if (!container) return false;

        const version = this.panelVersions.add;
        this.pendingPanels.add('add');

        try {
            const response = await apiFetch(this.getOpenAddGroup, {
                method: 'POST'
            });

            const data = await response.json();

            if (this.destroyed || version !== this.panelVersions.add) return false;

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isAddLoaded = true;
                return true;
            }

            return false;

        } catch (error) {
            console.error('Errore durante il caricamento del form aggiunta gruppo:', error);
            return false;
        } finally {
            this.pendingPanels.delete('add');
        }
    }

    /* Svuota il pannello di aggiunta alla chiusura */
    resetAddContainer() {
        this.panelVersions.add++;

        const container = document.getElementById('add-groups-container');

        if (container) {
            smoothReplace(container, '');
            this.isAddLoaded = false;
        }
    }

    /* Carica l'elenco gruppi prima dell'apertura dell'accordion */
    async loadGroupsList() {
        if (this.destroyed || this.isListLoaded || this.pendingPanels.has('list')) return false;

        const container = document.getElementById('showAll-groups-container');
        if (!container) return false;

        const version = this.panelVersions.list;
        this.pendingPanels.add('list');

        try {
            const response = await apiFetch(this.getGroupsUrl, {
                method: 'POST'
            });

            const data = await response.json();

            if (this.destroyed || version !== this.panelVersions.list) return false;

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isListLoaded = true;
                return true;
            }

            return false;

        } catch (error) {
            console.error('Errore caricamento lista gruppi:', error);
            return false;
        } finally {
            this.pendingPanels.delete('list');
        }
    }

    /* Svuota la lista alla chiusura */
    resetListContainer() {
        this.panelVersions.list++;

        const container = document.getElementById('showAll-groups-container');

        if (container) {
            smoothReplace(container, '');
            this.isListLoaded = false;
        }
    }

    /* Carica il pannello eccezioni prima dell'apertura dell'accordion */
    async loadExceptionsPanel() {
        if (this.destroyed || this.isExceptionsLoaded || this.pendingPanels.has('exceptions')) return false;

        const container = document.getElementById('exceptions-groups-container');
        if (!container) return false;

        const version = this.panelVersions.exceptions;
        this.pendingPanels.add('exceptions');

        try {
            const response = await apiFetch(this.getOpenExceptionsGroup, {
                method: 'POST'
            });

            const data = await response.json();

            if (this.destroyed || version !== this.panelVersions.exceptions) return false;

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isExceptionsLoaded = true;
                return true;
            }

            return false;

        } catch (error) {
            console.error('Errore durante il caricamento del pannello eccezioni:', error);
            return false;
        } finally {
            this.pendingPanels.delete('exceptions');
        }
    }

    /* Svuota il pannello eccezioni alla chiusura */
    resetExceptionsContainer() {
        this.panelVersions.exceptions++;

        clearTimeout(this.searchTimeout);
        ++this.searchVersion;
        ++this.permissionsVersion;

        const container = document.getElementById('exceptions-groups-container');

        if (container) {
            smoothReplace(container, '');
            this.isExceptionsLoaded = false;
        }
    }

    /* Ricerca amministratori con debounce e protezione dai risultati obsoleti */
    handleAdminSearch(inputEl) {
        if (this.destroyed) return;

        const version = ++this.searchVersion;
        ++this.permissionsVersion;

        clearTimeout(this.searchTimeout);

        const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');

        if (resetBtn) {
            resetBtn.style.display = inputEl.value.length > 0 ? 'flex' : 'none';
        }

        const permissionsGrid = document.getElementById('admin-permissions-container');

        if (permissionsGrid) {
            smoothReplace(permissionsGrid, '');
        }

        const query = inputEl.value.trim();
        const container = document.getElementById('dropdownAdmins');

        if (!container) return;

        if (query.length < 3) {
            smoothReplace(container, '');
            return;
        }

        this.searchTimeout = setTimeout(async () => {
            if (this.destroyed || version !== this.searchVersion || !inputEl.isConnected) return;

            try {
                const formData = new FormData();
                formData.append('query', query);

                const response = await apiFetch(this.getExceptionsUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (
                    this.destroyed ||
                    version !== this.searchVersion ||
                    !inputEl.isConnected ||
                    inputEl.value.trim() !== query
                ) return;

                if (data.result === false) {
                    if (data.message && typeof showAlert === 'function') {
                        showAlert('danger', data.message);
                    }
                    return;
                }

                if (data.result === true && data.output) {
                    smoothReplace(container, data.output);
                } else {
                    smoothReplace(container, '');
                }

            } catch (error) {
                console.error('Errore durante la ricerca degli amministratori:', error);
            }
        }, 500);
    }

    /* Seleziona l'amministratore e carica la griglia delle eccezioni */
    async handleSelectAdmin(btnEl) {
        if (this.destroyed) return;

        clearTimeout(this.searchTimeout);
        ++this.searchVersion;

        const version = ++this.permissionsVersion;
        const adminUuid = btnEl.dataset.id;
        const adminIdentity = btnEl.dataset.identity;

        const searchInput = document.getElementById('search-admin');

        if (searchInput) {
            searchInput.value = adminIdentity;
        }

        const dropdownContainer = document.getElementById('dropdownAdmins');

        if (dropdownContainer) {
            smoothReplace(dropdownContainer, '');
        }

        const permissionsContainer = document.getElementById('admin-permissions-container');

        if (!permissionsContainer) return;

        try {
            const formData = new FormData();
            formData.append('uuid', adminUuid);

            const response = await apiFetch(this.getAdminPermissionsUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (
                this.destroyed ||
                version !== this.permissionsVersion ||
                !permissionsContainer.isConnected
            ) return;

            if (data.result === true && data.output) {
                smoothReplace(permissionsContainer, data.output);
            } else if (data.message && typeof showAlert === 'function') {
                showAlert('danger', data.message);
            }

        } catch (error) {
            console.error('Errore caricamento permessi:', error);
        }
    }

    /* Salva le eccezioni dei permessi dell'amministratore */
    async handleSaveAdminPermissions(e) {
        e.preventDefault();

        if (this.isSubmitting || this.isDeleting || this.isConfirming || this.destroyed) return;

        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.getSaveExceptionsUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true) {
                markUnsavedClean(formEl);

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            console.error('Errore durante il salvataggio delle eccezioni:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Ripristina i permessi dell'amministratore selezionato */
    async handleRefreshAdminPermissions(btnEl) {
        if (this.isSubmitting || this.isDeleting || this.destroyed) return;

        const version = ++this.permissionsVersion;
        this.isSubmitting = true;

        const adminUuid = btnEl.dataset.uuid;
        const permissionsContainer = document.getElementById('admin-permissions-container');

        try {
            const formData = new FormData();
            formData.append('uuid', adminUuid);

            const response = await apiFetch(this.getAdminPermissionsUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (
                this.destroyed ||
                version !== this.permissionsVersion ||
                !permissionsContainer?.isConnected
            ) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            if (data.result === true && data.output && permissionsContainer) {
                smoothReplace(permissionsContainer, data.output);
            }

        } catch (error) {
            console.error('Errore durante il ripristino delle eccezioni:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Svuota ricerca, dropdown e griglia permessi */
    handleResetAdminSearch(btnEl) {
        if (this.destroyed) return;

        clearTimeout(this.searchTimeout);
        ++this.searchVersion;
        ++this.permissionsVersion;

        const inputGroup = btnEl.closest('.input-group');
        const searchInput = inputGroup?.querySelector('#search-admin');

        if (searchInput) {
            searchInput.value = '';
        }

        btnEl.style.display = 'none';

        const dropdownContainer = document.getElementById('dropdownAdmins');

        if (dropdownContainer) {
            smoothReplace(dropdownContainer, '');
        }

        const permissionsContainer = document.getElementById('admin-permissions-container');

        if (permissionsContainer) {
            smoothReplace(permissionsContainer, '');
        }
    }

    /* Verifica i vincoli di eliminazione del gruppo */
    async checkDeleteConstraints(groupId) {
        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(this.checkDeleteUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return false;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('info', data.message);
                }
                return false;
            }

            return data.result === true;

        } catch (error) {
            console.error('Errore durante il controllo vincoli di eliminazione:', error);
            return false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        clearTimeout(this.searchTimeout);

        ++this.searchVersion;
        ++this.permissionsVersion;

        this.panelVersions.add++;
        this.panelVersions.list++;
        this.panelVersions.exceptions++;

        this.pendingPanels.clear();

        if (GroupsManager.instance === this) {
            GroupsManager.instance = null;
        }
    }
}
