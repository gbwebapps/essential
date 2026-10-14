/* Import delle utility risalendo di un livello */
import { apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors, handleValidationImages } from '../backend.js';

/* Import degli altri componenti (nella stessa cartella) */
import { UploadPreviewImgManager } from '../components/UploadPreview.js';

/* --- LIST MANAGER (Custom SSR) --- */
export class ListManager {
    static instances = {};

    constructor(config = {}, hooks = {}) {
        if (!config.rootId || !config.containerId || !config.controller || !config.url) {
            throw new Error('ListManager: rootId, containerId, controller e url sono obbligatori.');
        }

        if (ListManager.instances[config.containerId]) {
            return ListManager.instances[config.containerId];
        }

        this.config = Object.assign({
            controller: '',
            url: '',
            rootId: '',
            containerId: '',
            storageKey: '',
            searchFields: [],
            searchDates: [],
            debounceTime: 500
        }, config);

        this.hooks = Object.assign({
            onShowBefore: null,
            onShowAfter: null,
            onShowError: null
        }, hooks);

        // Default retrocompatibile; per due liste dello stesso controller
        // configurare due storageKey differenti.
        this.storageKey = this.config.storageKey || this.config.controller;
        const read = (key, fallback) => localStorage.getItem(`${this.storageKey}_${key}`) ?? fallback;

        this.state = {
            column: read('column', 'id'),
            order: read('order', 'desc'),
            page: read('page', '1'),
            rows: read('rows', '5'),
            trash_filter: read('trash_filter', 'active'),
            searchFields: {},
            searchDates: {}
        };

        this.root = document.getElementById(this.config.rootId);
        this.container = document.getElementById(this.config.containerId);

        if (!this.root || !this.container || !this.root.contains(this.container)) {
            throw new Error('ListManager: il contenitore dei risultati deve trovarsi nel root indicato.');
        }

        this.debounceTimer = null;
        this.eventsBound = false;
        this.isLoading = false;
        this.isResetting = false;
        this.initialized = false;
        this.pendingRefresh = false;
        this.loadingPromise = null;
        this.destroyed = false;
        this.eventsController = new AbortController();

        ListManager.instances[this.config.containerId] = this;
    }

    getFields() {
        return [
            ...this.config.searchFields.map(name => ({ name, type: 'searchFields', isDate: false })),
            ...this.config.searchDates.flatMap(name => [
                { name: `${name}-from`, type: 'searchDates', isDate: true },
                { name: `${name}-to`, type: 'searchDates', isDate: true }
            ])
        ];
    }

    getField(name) {
        return Array.from(this.root.querySelectorAll('[data-search-field]'))
            .find(input => input.dataset.searchField === name) || null;
    }

    getFieldError(name) {
        return Array.from(this.root.querySelectorAll('[data-search-error]'))
            .find(el => el.dataset.searchError === name) || null;
    }

    init() {
        if (this.destroyed || !this.root.isConnected || !this.container.isConnected) return;
        if (this.initialized) return this.loadingPromise;

        this.initialized = true;
        this.initFilters();
        this.initSearchBar();
        this.initTrashFilterUI();
        this.updateActiveSearchIndicator();
        this.bindEvents();
        return this.showAll();
    }

    initFilters() {
        this.getFields().forEach(field => {
            const value = localStorage.getItem(`${this.storageKey}_${field.name}`) ?? '';
            this.state[field.type][field.name] = value;

            const inputEl = this.getField(field.name);
            if (!inputEl) return;
            inputEl.value = value;
            const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
            if (resetBtn) resetBtn.style.display = value ? 'flex' : 'none';
        });

        const rowsSelect = this.root.querySelector('.changeNumRows');
        if (rowsSelect) rowsSelect.value = String(this.state.rows);
    }

    initTrashFilterUI() {
        this.root.querySelectorAll('[data-trash-filter]').forEach(el => {
            el.classList.toggle('active', el.dataset.trashFilter === this.state.trash_filter);
            el.classList.toggle('text-success', el.dataset.trashFilter === this.state.trash_filter);
            el.classList.toggle('fw-bold', el.dataset.trashFilter === this.state.trash_filter);
        });
    }

    initSearchBar() {
        const searchBar = this.root.querySelector('.search-bar');
        const link = this.root.querySelector('.link-search');
        if (!searchBar) return;

        if (localStorage.getItem(`${this.storageKey}_search_bar_visible`) === '1') {
            searchBar.classList.add('show');
            link?.setAttribute('aria-expanded', 'true');
        }

        searchBar.addEventListener('shown.bs.collapse', () => {
            localStorage.setItem(`${this.storageKey}_search_bar_visible`, '1');
        }, { signal: this.eventsController.signal });

        searchBar.addEventListener('hidden.bs.collapse', () => {
            localStorage.setItem(`${this.storageKey}_search_bar_visible`, '0');
        }, { signal: this.eventsController.signal });
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;
        this.eventsBound = true;
        const signal = this.eventsController.signal;

        this.root.querySelectorAll('[data-trash-filter]').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                this.updateState('trash_filter', el.dataset.trashFilter);
                this.initTrashFilterUI();
                this.resetSortingAndPagination();
                this.showAll();
            }, { signal });
        });

        // Il contenitore AJAX è stabile; i suoi figli sono sostituiti a ogni refresh.
        this.container.addEventListener('click', e => {
            const sortEl = e.target.closest('a.sort');
            if (sortEl && this.container.contains(sortEl)) {
                e.preventDefault();
                this.updateState('column', sortEl.dataset.column);
                this.updateState('order', sortEl.dataset.order);
                this.showAll();
                return;
            }

            const pageEl = e.target.closest('.pagination li a');
            if (pageEl && this.container.contains(pageEl)) {
                e.preventDefault();
                this.updateState('page', pageEl.dataset.page);
                this.showAll();
            }
        }, { signal });

        this.root.querySelector('.changeNumRows')?.addEventListener('change', e => {
            this.updateState('rows', e.target.value);
            this.resetSortingAndPagination();
            this.showAll();
        }, { signal });

        this.root.querySelector('.link-reset-search')?.addEventListener('click', async e => {
            e.preventDefault();
            if (this.isResetting) return;
            this.isResetting = true;
            try {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
                this.resetFilters();
                this.resetSortingAndPagination();
                await this.showAll();
            } finally {
                this.isResetting = false;
            }
        }, { signal });

        this.root.querySelector('.reset-sorting-link')?.addEventListener('click', e => {
            e.preventDefault();
            this.resetSortingAndPagination();
            this.showAll();
        }, { signal });

        this.root.querySelector('.refresh-list')?.addEventListener('click', e => {
            e.preventDefault();
            this.showAll();
        }, { signal });

        this.getFields().forEach(field => {
            const inputEl = this.getField(field.name);
            if (!inputEl) return;

            const handleSearchUpdate = useDebounce => {
                if (this.isResetting || this.destroyed) return;
                const value = inputEl.value;
                this.updateNestedState(field.type, field.name, value);

                const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                if (resetBtn) resetBtn.style.display = value ? 'flex' : 'none';
                this.updateActiveSearchIndicator();

                const triggerSearch = () => {
                    if (this.destroyed || this.isResetting) return;
                    if (!value) {
                        const errorDiv = this.getFieldError(field.name);
                        if (errorDiv) smoothReplace(errorDiv, '&nbsp;');
                    }
                    this.resetSortingAndPagination();
                    this.showAll();
                };

                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
                if (useDebounce) {
                    this.debounceTimer = setTimeout(() => {
                        this.debounceTimer = null;
                        triggerSearch();
                    }, this.config.debounceTime);
                } else {
                    triggerSearch();
                }
            };

            inputEl.addEventListener(field.isDate ? 'change' : 'input', () => {
                handleSearchUpdate(!field.isDate);
            }, { signal });

            const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
            if (resetBtn) {
                resetBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    const fp = inputEl._flatpickr || inputEl.closest('.input-group')?._flatpickr;
                    if (fp) {
                        fp.clear();
                        // Flatpickr normalmente emette change in clear().
                    } else {
                        inputEl.value = '';
                        handleSearchUpdate(false);
                    }
                }, { signal });
            }
        });
    }

    updateState(key, value) {
        this.state[key] = value;
        localStorage.setItem(`${this.storageKey}_${key}`, String(value));
    }

    updateNestedState(group, key, value) {
        this.state[group][key] = value;
        localStorage.setItem(`${this.storageKey}_${key}`, String(value));
    }

    resetFilters() {
        this.getFields().forEach(field => {
            const inputEl = this.getField(field.name);
            if (inputEl) {
                const fp = inputEl._flatpickr || inputEl.closest('.input-group')?._flatpickr;
                if (fp) fp.clear();
                else inputEl.value = '';

                const resetBtn = inputEl.closest('.input-group')?.querySelector('.reset-search-field');
                if (resetBtn) resetBtn.style.display = 'none';
            }

            this.updateNestedState(field.type, field.name, '');
            const errorDiv = this.getFieldError(field.name);
            if (errorDiv) smoothReplace(errorDiv, '&nbsp;');
        });
        this.updateActiveSearchIndicator();
    }

    resetSortingAndPagination() {
        this.updateState('column', 'id');
        this.updateState('order', 'desc');
        this.updateState('page', 1);
    }

    updateActiveSearchIndicator() {
        const link = this.root.querySelector('.link-search');
        if (!link) return;
        const hasText = Object.values(this.state.searchFields).some(value => value?.trim() !== '');
        const hasDates = Object.values(this.state.searchDates).some(value => value?.trim() !== '');
        link.classList.toggle('text-danger', hasText || hasDates);
        link.classList.toggle('fw-bold', hasText || hasDates);
    }

    showAll() {
        if (this.destroyed) return Promise.resolve();
        if (this.isLoading) {
            this.pendingRefresh = true;
            return this.loadingPromise;
        }
        this.isLoading = true;
        this.loadingPromise = Promise.resolve().then(() => this.runRefresh());
        return this.loadingPromise;
    }

    async runRefresh() {
        try {
            do {
                this.pendingRefresh = false;
                if (this.destroyed) break;

                const urlParams = new URLSearchParams();
                Object.keys(this.state).forEach(key => {
                    if (key === 'searchFields' || key === 'searchDates') {
                        Object.entries(this.state[key]).forEach(([field, value]) => {
                            urlParams.append(`${key}[${field}]`, value);
                        });
                    } else {
                        urlParams.append(key, this.state[key]);
                    }
                });

                try {
                    if (typeof this.hooks.onShowBefore === 'function') {
                        const stop = await this.hooks.onShowBefore(urlParams);
                        if (stop === false) continue;
                    }

                    this.root.querySelectorAll('[data-search-error]').forEach(el => {
                        el.innerHTML = '\u00A0';
                    });

                    const response = await apiFetch(this.config.url, {
                        method: 'POST',
                        body: urlParams
                    });
                    const data = await response.json();

                    if (this.destroyed || this.pendingRefresh) continue;

                    if (data.errors) {
                        if (typeof handleValidationErrors === 'function') {
                            handleValidationErrors(data.errors);
                        }
                        if (data.message) {
                            await smoothReplace(this.container,
                                `<div class="text-center text-danger py-3 fw-bold">${data.message}</div>`);
                            if (typeof showAlert === 'function') showAlert('danger', data.message);
                        }
                        continue;
                    }

                    if (data.result === false) {
                        if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                        continue;
                    }

                    if (data.result === true) {
                        if (data.output) await smoothReplace(this.container, data.output);
                        if (typeof this.hooks.onShowAfter === 'function') {
                            await this.hooks.onShowAfter(data);
                        }
                    }
                } catch (error) {
                    if (typeof this.hooks.onShowError === 'function') {
                        try {
                            await this.hooks.onShowError(error);
                        } catch (hookError) {
                            console.error('Errore hook ListManager:', hookError);
                        }
                    }
                    console.error('Errore ListManager:', error);
                    break;
                }
            } while (this.pendingRefresh);
        } finally {
            this.isLoading = false;
            this.loadingPromise = null;
        }
    }

    destroy() {
        if (this.destroyed) return;
        this.destroyed = true;
        clearTimeout(this.debounceTimer);
        this.eventsController.abort();
        this.eventsBound = false;
        this.pendingRefresh = false;
        this.initialized = false;
        if (ListManager.instances[this.config.containerId] === this) {
            delete ListManager.instances[this.config.containerId];
        }
    }
}

export class AddManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const formSelector = config.formSelector;

        if (!formSelector) {
            throw new Error('AddManager: formSelector obbligatorio.');
        }

        if (AddManager.instances.has(formSelector)) {
            return AddManager.instances.get(formSelector);
        }

        this.config = Object.assign({
            formSelector: '',
            url: '',
            resetSelector: '',
            containerId: '',
            imagePreviewManager: null
        }, config);

        this.hooks = Object.assign({
            onAddBefore: null,
            onAddAfter: null,
            onAddError: null,
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        AddManager.instances.set(formSelector, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        const signal = this.eventsController.signal;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.config.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting) return;

            const formData = new FormData(formEl);

            await this.add(formData);
        }, { signal });

        if (this.config.resetSelector) {
            document.addEventListener('submit', async e => {
                const resetFormEl = e.target;

                if (!(resetFormEl instanceof HTMLFormElement)) return;
                if (!resetFormEl.matches(this.config.resetSelector)) return;

                e.preventDefault();

                if (this.isSubmitting) return;

                const message = resetFormEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.isSubmitting || this.destroyed) return;

                await this.reset();
            }, { signal });
        }
    }

    async add(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onAddBefore === 'function') {
                const stop = await this.hooks.onAddBefore(formData);

                if (stop === false) return;
            }

            if (this.config.imagePreviewManager) {
                const files = this.config.imagePreviewManager.files;

                files.forEach(({ id, file }) => {
                    formData.append(`images[${id}]`, file);
                });
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            document.querySelectorAll('[class^="error_"]').forEach(el => { el.innerHTML = '\u00A0'; });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (
                    this.config.imagePreviewManager &&
                    typeof handleValidationImages === 'function'
                ) {
                    handleValidationImages(data.errors);
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
                // Reset interno: mantiene il blocco dell'operazione.
                const resetCompleted = await this.performReset();

                if (resetCompleted && typeof this.hooks.onAddAfter === 'function') {
                    await this.hooks.onAddAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onAddError === 'function') {
                try {
                    await this.hooks.onAddError(error);
                } catch (hookError) {
                    console.error('Errore hook AddManager (add):', hookError);
                }
            }

            console.error('Errore AddManager (add):', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async reset() {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.performReset();
        } finally {
            this.isSubmitting = false;
        }
    }

    async performReset() {
        try {
            if (typeof this.hooks.onResetBefore === 'function') {
                const stop = await this.hooks.onResetBefore();

                if (stop === false) return false;
            }

            if (!this.config.resetSelector) return false;

            const resetForm = document.querySelector(this.config.resetSelector);

            if (!resetForm) return false;

            const formData = new FormData(resetForm);
            formData.append('action', 'reset');

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return false;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return false;
            }

            if (data.result === true) {
                if (!this.destroyed) {
                    const showDataEl = document.getElementById(this.config.containerId);

                    if (showDataEl && data.output) {
                        smoothReplace(showDataEl, data.output);
                    }

                    if (this.config.imagePreviewManager) {
                        this.config.imagePreviewManager.reset();
                    }

                    if (typeof this.hooks.onResetAfter === 'function') {
                        await this.hooks.onResetAfter(data);
                    }
                }

                return true;
            }

            return false;
        } catch (error) {
            if (typeof this.hooks.onResetError === 'function') {
                try {
                    await this.hooks.onResetError(error);
                } catch (hookError) {
                    console.error('Errore hook AddManager (reset):', hookError);
                }
            }

            console.error('Errore AddManager (reset):', error);

            return false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (AddManager.instances.get(this.config.formSelector) === this) {
            AddManager.instances.delete(this.config.formSelector);
        }
    }
}

export class EditManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const formSelector = config.formSelector;

        if (!formSelector) {
            throw new Error('EditManager: formSelector obbligatorio.');
        }

        if (EditManager.instances.has(formSelector)) {
            return EditManager.instances.get(formSelector);
        }

        this.config = Object.assign({
            formSelector: '',
            url: '',
            refreshSelector: '',
            containerId: '',
            imagePreviewManager: null,
            galleryOneManager: null
        }, config);

        this.hooks = Object.assign({
            onEditBefore: null,
            onEditAfter: null,
            onEditError: null,
            onRefreshBefore: null,
            onRefreshAfter: null,
            onRefreshError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        EditManager.instances.set(formSelector, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        const signal = this.eventsController.signal;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.config.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting) return;

            await this.edit(new FormData(formEl));
        }, { signal });

        if (this.config.refreshSelector) {
            document.addEventListener('submit', async e => {
                const formEl = e.target;

                if (!(formEl instanceof HTMLFormElement)) return;
                if (!formEl.matches(this.config.refreshSelector)) return;

                e.preventDefault();

                if (this.isSubmitting) return;

                const message = formEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.isSubmitting || this.destroyed) return;

                await this.refresh(new FormData(formEl));
            }, { signal });
        }
    }

    async edit(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onEditBefore === 'function') {
                const stop = await this.hooks.onEditBefore(formData);

                if (stop === false) return;
            }

            if (this.config.imagePreviewManager) {
                const files = this.config.imagePreviewManager.files;

                files.forEach(({ id, file }) => {
                    formData.append(`images[${id}]`, file);
                });
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            document.querySelectorAll('[class^="error_"]').forEach(el => { el.innerHTML = '\u00A0'; });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (
                    this.config.imagePreviewManager &&
                    typeof handleValidationImages === 'function'
                ) {
                    handleValidationImages(data.errors);
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
                const refreshFormEl = this.config.refreshSelector
                    ? document.querySelector(this.config.refreshSelector)
                    : null;

                const refreshData = refreshFormEl
                    ? new FormData(refreshFormEl)
                    : new FormData();

                const uuidEl = document.getElementById('uuid');

                if (uuidEl && !refreshData.has('uuid')) {
                    refreshData.append('uuid', uuidEl.value);
                }

                // Il refresh interno mantiene attivo il blocco isSubmitting.
                const refreshCompleted = await this.performRefresh(refreshData);

                if (
                    refreshCompleted &&
                    typeof this.hooks.onEditAfter === 'function'
                ) {
                    await this.hooks.onEditAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                try {
                    await this.hooks.onEditError(error);
                } catch (hookError) {
                    console.error('Errore hook EditManager (edit):', hookError);
                }
            }

            console.error('Errore EditManager (edit):', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            return await this.performRefresh(formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async performRefresh(formData) {
        try {
            formData.set('action', 'refresh');

            const uuidEl = document.getElementById('uuid');

            if (uuidEl && !formData.has('uuid')) {
                formData.append('uuid', uuidEl.value);
            }

            if (typeof this.hooks.onRefreshBefore === 'function') {
                const stop = await this.hooks.onRefreshBefore(formData);

                if (stop === false) return false;
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return false;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return false;
            }

            if (data.result === true) {
                if (this.destroyed) return false;

                const showDataEl = document.getElementById(this.config.containerId);

                if (showDataEl && data.output) {
                    smoothReplace(showDataEl, data.output);
                }

                if (this.config.imagePreviewManager) {
                    this.config.imagePreviewManager.reset();
                }

                if (typeof this.hooks.onRefreshAfter === 'function') {
                    await this.hooks.onRefreshAfter(data);
                }

                return true;
            }

            return false;
        } catch (error) {
            if (typeof this.hooks.onRefreshError === 'function') {
                try {
                    await this.hooks.onRefreshError(error);
                } catch (hookError) {
                    console.error('Errore hook EditManager (refresh):', hookError);
                }
            }

            console.error('Errore EditManager (refresh):', error);

            return false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (EditManager.instances.get(this.config.formSelector) === this) {
            EditManager.instances.delete(this.config.formSelector);
        }
    }
}

export class DeleteManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const controller = config.controller;

        if (!controller) {
            throw new Error('DeleteManager: controller obbligatorio.');
        }

        if (DeleteManager.instances.has(controller)) {
            return DeleteManager.instances.get(controller);
        }

        this.config = Object.assign({
            controller: '',
            urls: {},
            listManager: null
        }, config);

        this.hooks = Object.assign({
            onDeleteBefore: null,
            onDeleteAfter: null,
            onDeleteError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        DeleteManager.instances.set(controller, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;

            const selector = [
                '.deleteRecord',
                '.restoreRecord',
                '.hardDeleteRecord',
                '.disconnectSessionRecord'
            ].join(', ');

            if (!formEl.matches(selector)) return;

            // Se sono presenti più DeleteManager, ogni istanza
            // deve gestire soltanto i form del proprio controller.
            const formController = formEl.dataset.controller;

            if (formController && formController !== this.config.controller) {
                return;
            }

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            let targetUrl = '';

            if (formEl.classList.contains('deleteRecord')) {
                targetUrl = this.config.urls.softDelete;
            } else if (formEl.classList.contains('restoreRecord')) {
                targetUrl = this.config.urls.restoreDelete;
            } else if (formEl.classList.contains('hardDeleteRecord')) {
                targetUrl = this.config.urls.hardDelete;
            } else if (formEl.classList.contains('disconnectSessionRecord')) {
                targetUrl = this.config.urls.disconnectSession;
            }

            if (!targetUrl) return;

            // Blocchiamo anche il periodo di attesa della conferma.
            this.isSubmitting = true;

            try {
                const message = formEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.destroyed) return;

                const formData = new FormData(formEl);

                await this.executeAction(targetUrl, formData);
            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async processAction(url, formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.executeAction(url, formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async executeAction(url, formData) {
        try {
            if (typeof this.hooks.onDeleteBefore === 'function') {
                const stop = await this.hooks.onDeleteBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
                if (this.destroyed) return;

                const listManager = this.config.listManager;

                const lastItemEl = listManager?.root?.querySelector('.lastItemPage');

                const lastItemPage = lastItemEl
                    ? parseInt(lastItemEl.dataset.lastitempage, 10)
                    : 0;

                const storageKey = listManager?.storageKey || this.config.controller;
                const pageKey = `${storageKey}_page`;

                const currentPage = parseInt(localStorage.getItem(pageKey), 10) || 1;

                if (currentPage > 1 && lastItemPage === 1) {
                    const newPage = currentPage - 1;

                    localStorage.setItem(pageKey, String(newPage));

                    if (listManager?.state) {
                        listManager.state.page = newPage;
                    }
                }

                if (listManager && typeof listManager.showAll === 'function') {
                    await listManager.showAll();
                }

                if (typeof this.hooks.onDeleteAfter === 'function') {
                    await this.hooks.onDeleteAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            await this.handleError(error);
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onDeleteError === 'function') {
            try {
                await this.hooks.onDeleteError(error);
            } catch (hookError) {
                console.error('Errore hook DeleteManager:', hookError);
            }
        }

        console.error('Errore DeleteManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;

        this.eventsController.abort();

        this.eventsBound = false;

        if (DeleteManager.instances.get(this.config.controller) === this) {
            DeleteManager.instances.delete(this.config.controller);
        }
    }
}

export class ChangeStatusManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const controller = config.controller;

        if (!controller) {
            throw new Error('ChangeStatusManager: controller obbligatorio.');
        }

        if (ChangeStatusManager.instances.has(controller)) {
            return ChangeStatusManager.instances.get(controller);
        }

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

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        ChangeStatusManager.instances.set(controller, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches('.changeStatus')) return;

            // Associa il form al controller corretto quando
            // sono presenti più istanze nella stessa pagina.
            const formController = formEl.dataset.controller;

            if (formController && formController !== this.config.controller) {
                return;
            }

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            // Blocca anche la fase di conferma.
            this.isSubmitting = true;

            try {
                const message = formEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.destroyed) return;

                const formData = new FormData(formEl);

                await this.executeStatusChange(formData);
            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async changeStatus(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.executeStatusChange(formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async executeStatusChange(formData) {
        try {
            if (typeof this.hooks.onStatusBefore === 'function') {
                const stop = await this.hooks.onStatusBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
                if (this.destroyed) return;

                if (typeof this.hooks.onStatusAfter === 'function') {
                    await this.hooks.onStatusAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            await this.handleError(error);
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onStatusError === 'function') {
            try {
                await this.hooks.onStatusError(error);
            } catch (hookError) {
                console.error('Errore hook ChangeStatusManager:', hookError);
            }
        }

        console.error('Errore ChangeStatusManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (ChangeStatusManager.instances.get(this.config.controller) === this) {
            ChangeStatusManager.instances.delete(this.config.controller);
        }
    }
}

export class GeneralDataManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const formSelector = config.formSelector;

        if (!formSelector) {
            throw new Error('GeneralDataManager: formSelector obbligatorio.');
        }

        if (GeneralDataManager.instances.has(formSelector)) {
            return GeneralDataManager.instances.get(formSelector);
        }

        this.config = Object.assign({
            url: '',
            formSelector: '',
            containerId: 'generalData'
        }, config);

        this.hooks = Object.assign({
            onGeneralDataBefore: null,
            onGeneralDataAfter: null,
            onGeneralDataError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        GeneralDataManager.instances.set(formSelector, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.config.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.getGeneralData(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    async getGeneralData(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onGeneralDataBefore === 'function') {
                const stop = await this.hooks.onGeneralDataBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
                if (this.destroyed) return;

                const generalDataEl = document.getElementById(
                    this.config.containerId
                );

                if (generalDataEl && data.output) {
                    smoothReplace(generalDataEl, data.output);
                }

                if (typeof this.hooks.onGeneralDataAfter === 'function') {
                    await this.hooks.onGeneralDataAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onGeneralDataError === 'function') {
                try {
                    await this.hooks.onGeneralDataError(error);
                } catch (hookError) {
                    console.error(
                        'Errore hook GeneralDataManager:',
                        hookError
                    );
                }
            }

            console.error('Errore GeneralDataManager:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (
            GeneralDataManager.instances.get(this.config.formSelector) === this
        ) {
            GeneralDataManager.instances.delete(this.config.formSelector);
        }
    }
}

export class MetaDataManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const formSelector = config.formSelector || '#getMetaData';

        if (MetaDataManager.instances.has(formSelector)) {
            return MetaDataManager.instances.get(formSelector);
        }

        this.config = Object.assign({
            url: '',
            formSelector: '#getMetaData',
            containerId: 'metaData'
        }, config);

        this.hooks = Object.assign({
            onMetaDataBefore: null,
            onMetaDataAfter: null,
            onMetaDataError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        MetaDataManager.instances.set(formSelector, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.config.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.getMetaData(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    async getMetaData(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onMetaDataBefore === 'function') {
                const stop = await this.hooks.onMetaDataBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
                if (this.destroyed) return;

                const metaDataEl = document.getElementById(
                    this.config.containerId
                );

                if (metaDataEl && data.output) {
                    await smoothReplace(metaDataEl, data.output);
                }

                if (typeof this.hooks.onMetaDataAfter === 'function') {
                    await this.hooks.onMetaDataAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onMetaDataError === 'function') {
                try {
                    await this.hooks.onMetaDataError(error);
                } catch (hookError) {
                    console.error('Errore hook MetaDataManager:', hookError);
                }
            }

            console.error('Errore MetaDataManager:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (MetaDataManager.instances.get(this.config.formSelector) === this) {
            MetaDataManager.instances.delete(this.config.formSelector);
        }
    }
}
