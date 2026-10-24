/* Il flusso del modale resta quello del componente Import condiviso. */
import { ImportCsvManager } from '../components/ImportCsv.js';
import { urlbase, apiFetch, showAlert, smoothReplace } from '../backend.js';

/* Pausa dimostrativa fra blocchi da cinque, senza rallentare i componenti CSV. */
const CHUNK_DELAY_MS = 350;

/**
 * Adatta al pacchetto XLSX gli endpoint del gestore Import, senza duplicarne la UI.
 */
export class WarehouseImportManager extends ImportCsvManager {
    constructor() {
        super({
            urlModal: urlbase + 'backend/warehouse/showImportModal',
            urlProcess: urlbase + 'backend/warehouse/prepareImport',
            urlExecute: urlbase + 'backend/warehouse/executeImport',
            urlDelete: urlbase + 'backend/warehouse/deleteImport',
            linkId: '#warehouse-import-link',
        });

        this.loadingOutput = '';
        this.confirmOutput = '';
        this.resumeBound = false;
        this.importRunning = false;

        this.hooks.onModalAfter = data => {
            this.confirmOutput = '';
            this.importRunning = false;
            this.loadingOutput = data.loadingOutput || '';
            this.activeImportId = data.importId || null;
            this.recoveryRequired = data.recoveryRequired === true;

        };

        this.hooks.onDeleteAfter = () => {
            document.getElementById('warehouse-resume-import-link')?.remove();
        };

        this.hooks.onImportAfter = () => {
            document.getElementById('warehouse-resume-import-link')?.remove();
        };
    }

    /** Aggiunge la sola apertura della preview persistita alla gestione eventi condivisa. */
    init() {
        super.init();

        if (this.resumeBound) return;
        this.resumeBound = true;

        /* Dopo la conferma l'unico commit non può essere interrotto chiudendo il modale. */
        document.addEventListener('hide.bs.modal', event => {
            if (event.target.id === this.config.modalId && this.isSubmitting && this.activeImportId) {
                event.preventDefault();
            }
        });

        document.addEventListener('click', async event => {
            if ( ! event.target.closest('#warehouse-resume-import-link')) return;
            event.preventDefault();

            if (this.isSubmitting) return;

            const originalUrl = this.config.urlModal;
            this.config.urlModal = originalUrl + '?resume=1';

            try {
                await this.showModal('warehouse');
            } finally {
                this.config.urlModal = originalUrl;
            }
        });
    }

    /** Mostra lo stesso pannello di lavoro durante l'unica transazione del pacchetto. */
    async fetchWithRetry(url, options) {
        if (url === this.config.urlExecute && this.loadingOutput && ! this.importRunning) {
            const content = document.getElementById('import-content-area');

            if (content) {
                this.confirmOutput = content.innerHTML;
                smoothReplace(content, this.loadingOutput);
                this.importRunning = true;
            }
        }

        return await super.fetchWithRetry(url, options);
    }

    /** Ripristina la preview dopo un errore; in recovery la conferma resta disabilitata. */
    async processImport(formElement, isContinuation = false) {
        if (this.isSubmitting && ! isContinuation) return;

        if (isContinuation) {
            await new Promise(resolve => setTimeout(resolve, CHUNK_DELAY_MS));
        }

        await super.processImport(formElement, isContinuation);

        if (this.activeImportId && this.confirmOutput) {
            const content = document.getElementById('import-content-area');
            if (content) smoothReplace(content, this.confirmOutput);
        }

        if ( ! isContinuation) this.importRunning = false;
    }
}

/**
 * Gestisce esclusivamente il pacchetto XLSX a schema fisso.
 * Condivide la vista Export, non i filtri, le selezioni o il singleton del CSV.
 */
export class WarehouseExportManager {
    constructor() {
        this.endpoints = {
            modal: urlbase + 'backend/warehouse/showExportModal',
            export: urlbase + 'backend/warehouse/export',
            remove: urlbase + 'backend/warehouse/removeExport',
        };

        this.eventsBound = false;
        this.state = 'idle';
        this.currentExportId = null;
        this.cancelRequested = false;
    }

    /** Collega soltanto i comandi Warehouse, senza intercettare i pulsanti del CSV. */
    init() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('click', async event => {
            if (event.target.closest('#warehouse-export-button')) {
                event.preventDefault();
                await this.showModal();
                return;
            }

            if ( ! event.target.closest('#exportModal')) return;

            if (event.target.closest('#warehouse-export-start-btn')) {
                event.preventDefault();
                await this.startExport();
            } else if (event.target.closest('#warehouse-export-cancel-btn')) {
                event.preventDefault();
                await this.cancelExport();
            }
        });
    }

    /** Apre la vista condivisa e conserva effetto vetro e tastiera disabilitata. */
    async showModal() {
        if (this.state !== 'idle') return;
        this.state = 'opening';

        try {
            const data = await this.request(this.endpoints.modal, { entity: 'warehouse' });

            if (data.result !== true || typeof data.output !== 'string' || ! data.output) {
                throw new Error(data.message || this.errorMessage());
            }

            const container = document.getElementById('export-modal-container');
            if ( ! container) throw new Error(this.errorMessage());

            smoothReplace(container, data.output);

            const modal = container.querySelector('#exportModal');
            if ( ! modal || ! modal.querySelector('#warehouse-export-start-btn')) {
                throw new Error(this.errorMessage());
            }

            modal.addEventListener('show.bs.modal', () => {
                document.getElementById('customBackdrop')?.classList.add('active');
            });

            modal.addEventListener('shown.bs.modal', () => {
                this.state = 'ready';
            });

            modal.addEventListener('hide.bs.modal', event => {
                if (this.state === 'exporting' || this.state === 'cancelling') event.preventDefault();
            });

            modal.addEventListener('hidden.bs.modal', () => {
                document.getElementById('customBackdrop')?.classList.remove('active');
                this.state = 'idle';
            });

            bootstrap.Modal.getOrCreateInstance(modal, { backdrop: false, keyboard: false }).show();
        } catch (error) {
            this.state = 'idle';
            this.reportError(error);
        }
    }

    /** Avvia il pacchetto completo e prosegue a blocchi, senza colonne o filtri dal browser. */
    async startExport() {
        if (this.state !== 'ready') return;

        this.state = 'exporting';
        this.cancelRequested = false;
        this.setWorking(true);

        try {
            /* Un annullamento fallito non deve far perdere l'identificativo da ripulire. */
            if ( ! await this.cleanupCurrentExport()) throw new Error(this.errorMessage());

            while ( ! this.cancelRequested) {
                const fields = this.currentExportId === null
                    ? { entity: 'warehouse' }
                    : { exportId: this.currentExportId };

                const data = await this.request(this.endpoints.export, fields);
                const validId = typeof data.exportId === 'string' && /^[a-f0-9]{64}$/.test(data.exportId);

                if (validId && this.currentExportId === null) this.currentExportId = data.exportId;

                /* Attende la risposta prima di eliminare anche il job appena creato. */
                if (this.cancelRequested) break;

                if (data.result !== true) throw new Error(data.message || this.errorMessage());

                if ( ! validId || data.exportId !== this.currentExportId || typeof data.isFinished !== 'boolean') {
                    throw new Error(this.errorMessage());
                }

                if (data.isFinished) {
                    if (typeof data.downloadUrl !== 'string' || ! data.downloadUrl) {
                        throw new Error(this.errorMessage());
                    }

                    this.download(data.downloadUrl);
                    this.currentExportId = null;
                    this.closeModal();

                    if (data.message) showAlert('success', data.message);
                    return;
                }

                const progress = document.querySelector('#exportModal #export-progress-text');

                if (progress && typeof data.progressMessage === 'string') {
                    progress.textContent = data.progressMessage;
                }

                await new Promise(resolve => setTimeout(resolve, CHUNK_DELAY_MS));
            }

            if ( ! await this.cleanupCurrentExport()) throw new Error(this.errorMessage());
            this.closeModal();
        } catch (error) {
            await this.cleanupCurrentExport();
            this.reportError(error);
        } finally {
            if (this.state === 'exporting' || this.state === 'cancelling') {
                this.state = 'ready';
                this.setWorking(false);
            }
        }
    }

    /** Durante una richiesta rinvia la pulizia al ciclo, evitando job orfani o doppi invii. */
    async cancelExport() {
        if (this.state === 'exporting') {
            this.cancelRequested = true;
            this.state = 'cancelling';
            document.getElementById('warehouse-export-cancel-btn').disabled = true;
            return;
        }

        if (this.state !== 'ready') return;
        this.state = 'cancelling';
        document.getElementById('warehouse-export-cancel-btn').disabled = true;

        if (await this.cleanupCurrentExport()) {
            this.closeModal();
        } else {
            this.state = 'ready';
            this.setWorking(false);
            this.reportError(new Error(this.errorMessage()));
        }
    }

    /** Elimina il job noto; conserva l'ID se il server non conferma la rimozione. */
    async cleanupCurrentExport() {
        if (this.currentExportId === null) return true;

        try {
            const data = await this.request(this.endpoints.remove, { exportId: this.currentExportId });
            if (data.result !== true) return false;

            this.currentExportId = null;
            return true;
        } catch (error) {
            console.error('Pulizia export Warehouse non riuscita:', error);
            return false;
        }
    }

    /** Mantiene l'invio POST e il rinnovo CSRF delle utility condivise. */
    async request(endpoint, fields) {
        const body = new FormData();
        Object.entries(fields).forEach(([key, value]) => body.append(key, value));

        const response = await apiFetch(endpoint, { method: 'POST', body });
        const data = await response.json();

        if ( ! data || typeof data !== 'object' || Array.isArray(data)) {
            throw new Error(this.errorMessage());
        }

        return data;
    }

    /** Alterna il riepilogo fisso e il progresso, ripristinando i comandi dopo un errore. */
    setWorking(working) {
        const modal = document.getElementById('exportModal');
        if ( ! modal) return;

        modal.querySelector('#export-selection-area')?.classList.toggle('d-none', working);
        modal.querySelector('#export-spinner-area')?.classList.toggle('d-none', ! working);

        const start = modal.querySelector('#warehouse-export-start-btn');
        const cancel = modal.querySelector('#warehouse-export-cancel-btn');

        if (start) {
            start.disabled = working;
            start.classList.toggle('d-none', working);
        }

        if (cancel) cancel.disabled = false;
    }

    /** Avvia il download protetto senza eliminare il file appena completato. */
    download(url) {
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', '');
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    /** Chiude solo dopo il completamento o la conferma di annullamento. */
    closeModal() {
        this.state = 'closing';
        bootstrap.Modal.getInstance(document.getElementById('exportModal'))?.hide();
    }

    /** Legge il messaggio localizzato dal modulo, senza testi CSV o stringhe italiane nel JS. */
    errorMessage() {
        return document.getElementById('export-modal-container')?.dataset.exportError || '';
    }

    /** Rende visibile l'errore e conserva il dettaglio tecnico nella console. */
    reportError(error) {
        console.error('Esportazione Warehouse non riuscita:', error);
        showAlert('danger', error instanceof Error ? error.message : this.errorMessage());
    }
}
