/* Import delle utility condivise del backend */
import { apiFetch, showAlert, smoothReplace, handleValidationErrors } from '../backend.js';

export class GetTreeManager {
    static instances = new Map();

    constructor(config = {}, hooks = {}) {
        const formSelector = config.formSelector || '#getTree';

        if (GetTreeManager.instances.has(formSelector)) {
            return GetTreeManager.instances.get(formSelector);
        }

        this.config = Object.assign({
            url: '',
            formSelector: '#getTree',
            containerIds: ['categoryTreePanel', 'categoryTreeModalPanel'],
            modalSelector: '#categoriesTreeModal',
            backdropId: 'customBackdrop'
        }, config);

        this.hooks = Object.assign({
            onTreeBefore: null,
            onTreeAfter: null,
            onTreeError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        GetTreeManager.instances.set(formSelector, this);
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('show.bs.modal', e => {
            if (!(e.target instanceof HTMLElement)) return;
            if (!e.target.matches(this.config.modalSelector)) return;

            const backdropEl = document.getElementById(this.config.backdropId);

            if (backdropEl) {
                backdropEl.classList.add('active');
            }
        }, {
            signal: this.eventsController.signal
        });

        document.addEventListener('hidden.bs.modal', e => {
            if (!(e.target instanceof HTMLElement)) return;
            if (!e.target.matches(this.config.modalSelector)) return;

            const backdropEl = document.getElementById(this.config.backdropId);

            if (backdropEl) {
                backdropEl.classList.remove('active');
            }
        }, {
            signal: this.eventsController.signal
        });

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.config.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.getTree(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    async getTree(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onTreeBefore === 'function') {
                const stop = await this.hooks.onTreeBefore(formData);

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

                this.config.containerIds.forEach(containerId => {
                    const treeEl = document.getElementById(containerId);

                    if (treeEl && data.output) {
                        smoothReplace(treeEl, data.output);
                    }
                });

                if (typeof this.hooks.onTreeAfter === 'function') {
                    await this.hooks.onTreeAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onTreeError === 'function') {
                try {
                    await this.hooks.onTreeError(error);
                } catch (hookError) {
                    console.error('Errore hook GetTreeManager:', hookError);
                }
            }

            console.error('Errore GetTreeManager:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (GetTreeManager.instances.get(this.config.formSelector) === this) {
            GetTreeManager.instances.delete(this.config.formSelector);
        }
    }
}

