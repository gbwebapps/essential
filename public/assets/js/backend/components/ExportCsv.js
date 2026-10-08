/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, smoothReplace, handleValidationErrors } from '../backend.js';

export class ExportCsvManager {
    /**
     * Inizializza il manager Export CSV e normalizza configurazione ed hook.
     *
     * @param {Object} config Configurazione degli endpoint e dei selettori DOM.
     * @param {Object} hooks Callback opzionali del ciclo di vita export.
     * @return {ExportCsvManager} Istanza singleton del manager.
     */
    constructor(config = {}, hooks = {}) {
        if (ExportCsvManager.instance) return ExportCsvManager.instance;
        ExportCsvManager.instance = this;

        this.config = Object.assign({
            controller: '',
            urlModal: urlbase + 'backend/export/showModal',
            modalContainerId: 'export-modal-container',
            modalId: 'exportModal',
            linkId: '#export-entity',
            cancelBtnId: '#export-cancel-btn',
            urlExport: urlbase + 'backend/export/generate',
            urlRemove: urlbase + 'backend/export/remove'
        }, config);

        this.hooks = Object.assign({
            onModalBefore: null,
            onModalAfter: null,
            onExportBefore: null,
            onExportAfter: null,
            onError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.isCancelled = false;
        this.currentExportId = null;
        this.exportFilters = new FormData();
    }

    /**
     * Inizializza il manager registrando gli event listener una sola volta.
     *
     * @return {void}
     */
    init() {
        this.bindEvents();
    }

    /**
     * Registra gli handler delegati per apertura modale, selezione colonne, avvio e annullamento export.
     *
     * @return {void}
     */
    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.linkId);
            if (! btn) return;

            e.preventDefault();
            await this.showModal(btn.dataset.exportEntity);
        });

        document.addEventListener('click', async e => {
            const btn = e.target.closest(this.config.cancelBtnId);
            if (! btn || this.isCancelled) return;

            e.preventDefault();
            this.isCancelled = true;
            btn.disabled = true;

            await this.cleanupCurrentExport();
            this.hideModal();
            this.isSubmitting = false;
        });

        document.addEventListener('show.bs.modal', e => {
            if (e.target.id !== this.config.modalId) return;

            const backdropEl = document.getElementById('customBackdrop');
            if (backdropEl) backdropEl.classList.add('active');
        });

        document.addEventListener('hidden.bs.modal', e => {
            if (e.target.id !== this.config.modalId) return;

            const backdropEl = document.getElementById('customBackdrop');
            if (backdropEl) backdropEl.classList.remove('active');
        });

        document.addEventListener('change', e => {
            if (e.target.id !== 'export-check-all') return;

            document.querySelectorAll('.export-col-cb:not([data-required="1"])').forEach(cb => {
                cb.checked = e.target.checked;
            });
        });

        document.addEventListener('click', async e => {
            const startBtn = e.target.closest('#export-start-btn');
            if (! startBtn || this.isSubmitting) return;

            e.preventDefault();

            const selectedCheckboxes = document.querySelectorAll('.export-col-cb:checked');

            if (selectedCheckboxes.length === 0) {
                if (typeof showAlert === 'function') showAlert('warning', 'Seleziona almeno una colonna per proseguire.');
                return;
            }

            this.isSubmitting = true;
            this.isCancelled = false;
            this.currentExportId = null;
            startBtn.disabled = true;

            const selectionArea = document.getElementById('export-selection-area');
            const spinnerArea = document.getElementById('export-spinner-area');

            if (selectionArea) selectionArea.classList.add('d-none');
            if (spinnerArea) spinnerArea.classList.remove('d-none');
            startBtn.classList.add('d-none');

            selectedCheckboxes.forEach(cb => this.exportFilters.append('selected_columns[]', cb.value));

            await this.triggerExport();
        });
    }

    /**
     * Prepara i filtri iniziali dell'export a partire dall'entità e dallo stato persistito della lista.
     *
     * @param {string} entity Entità da esportare.
     * @return {void}
     */
    prepareExportFilters(entity) {
        this.exportFilters = new FormData();
        this.exportFilters.append('entity', entity);

        const prefix = `${this.config.controller}_`;

        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);

            if (key && key.startsWith(prefix)) {
                this.exportFilters.append(key.substring(prefix.length), localStorage.getItem(key));
            }
        }
    }

    /**
     * Richiede e visualizza la modale di esportazione per l'entità selezionata.
     *
     * @param {string} entity Entità da esportare.
     * @return {Promise<void>}
     */
    async showModal(entity) {
        if (this.isSubmitting) return;

        this.isSubmitting = true;
        this.isCancelled = false;
        this.currentExportId = null;

        if (typeof this.hooks.onModalBefore === 'function' && this.hooks.onModalBefore(entity) === false) {
            this.isSubmitting = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('entity', entity);

            const response = await apiFetch(this.config.urlModal, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                this.isSubmitting = false;
                return;
            }

            const container = document.getElementById(this.config.modalContainerId);

            if (container && data.output) {
                smoothReplace(container, data.output);

                const modalEl = document.getElementById(this.config.modalId);

                if (modalEl) {
                    this.prepareExportFilters(entity);
                    bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: false, keyboard: false }).show();
                }
            }

            if (typeof this.hooks.onModalAfter === 'function') this.hooks.onModalAfter(data);

            this.isSubmitting = false;
        } catch (error) {
            if (typeof this.hooks.onError === 'function') this.hooks.onError(error);

            console.error('Errore ExportCsvManager (showModal):', error);
            this.isSubmitting = false;
        }
    }

    /**
     * Avvia o continua l'esportazione.
     *
     * La prima richiesta invia entità, filtri e colonne; le successive inviano esclusivamente exportId.
     *
     * @return {Promise<void>}
     */
    async triggerExport() {
        try {
            const formData = new FormData();

            if (this.currentExportId === null) {
                for (const [key, value] of this.exportFilters.entries()) {
                    formData.append(key, value);
                }
            } else {
                formData.append('exportId', this.currentExportId);
            }

            const response = await apiFetch(this.config.urlExport, { method: 'POST', body: formData });
            const data = await response.json();

            if (typeof data.exportId === 'string' && data.exportId !== '') {
                this.currentExportId = data.exportId;
            }

            if (this.isCancelled) {
                await this.cleanupCurrentExport();
                this.isSubmitting = false;
                return;
            }

            if (data.errors || data.result === false) {
                if (data.errors && typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                await this.cleanupCurrentExport();
                this.hideModal();
                this.isSubmitting = false;

                return;
            }

            if (data.isFinished === false) {
                const progressText = document.getElementById('export-progress-text');

                if (progressText && data.progressMessage) {
                    progressText.textContent = data.progressMessage;
                }

                await this.triggerExport();

                return;
            }

            if (data.message && typeof showAlert === 'function') {
                showAlert('success', data.message);
            }

            if (data.downloadUrl) {
                const link = document.createElement('a');

                link.href = data.downloadUrl;
                link.setAttribute('download', '');

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            this.hideModal();

            if (typeof this.hooks.onExportAfter === 'function') {
                this.hooks.onExportAfter(data);
            }

            this.isSubmitting = false;
        } catch (error) {
            if (this.isCancelled) {
                await this.cleanupCurrentExport();
                this.isSubmitting = false;
                return;
            }

            if (typeof this.hooks.onError === 'function') {
                this.hooks.onError(error);
            }

            console.error('Errore ExportCsvManager (triggerExport):', error);

            await this.cleanupCurrentExport();
            this.isSubmitting = false;
        }
    }

    /**
     * Richiede al server la rimozione dell'export corrente tramite exportId.
     *
     * @return {Promise<void>}
     */
    async cleanupCurrentExport() {
        if (! this.currentExportId) return;

        const formData = new FormData();

        formData.append('exportId', this.currentExportId);

        try {
            const response = await apiFetch(this.config.urlRemove, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.result === true) {
                this.currentExportId = null;
            }
        } catch (error) {
            console.error('Errore pulizia export:', error);
        }
    }

    /**
     * Chiude la modale di esportazione se attualmente istanziata.
     *
     * @return {void}
     */
    hideModal() {
        const modalEl = document.getElementById(this.config.modalId);

        if (! modalEl) return;

        const modalInstance = bootstrap.Modal.getInstance(modalEl);

        if (modalInstance) {
            modalInstance.hide();
        }
    }
}