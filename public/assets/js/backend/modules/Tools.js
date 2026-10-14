/* Import delle utility da backend.js */
import { urlbase, apiFetch, showAlert, askConfirm, handleValidationErrors, smoothReplace, initRangeDatePicker } from '../backend.js';

export class ToolsManager {
    constructor() {

        if (ToolsManager.instance) {
            return ToolsManager.instance;
        }
        ToolsManager.instance = this;

        this.loadUrl = urlbase + 'backend/tools/openTools';

        this.isSubmitting = false;
        this.isConfirming = false;
        this.destroyed = false;
        this.eventsBound = false;

        /* Mappa delle rotte per le azioni di business */
        this.routes = {
            manageAudits: {
                validate: 'backend/tools/validateAuditsDateRequest',
                delete: 'backend/tools/deleteAudits'
            },
            manageLogs: {
                validate: 'backend/tools/validateLogsDateRequest',
                delete: 'backend/tools/deleteLogs'
            },
            dbMaintenance: 'backend/tools/optimizeTable',
            backups: 'backend/tools/backups',
            cleanSpace: 'backend/tools/cleanFolder'
        };

        this.datePickers = {};
        this.requireDatePickers = ['manageAudits', 'manageLogs'];

        /* Gestione del ciclo di vita */
        this.eventsController = new AbortController();
        this.pendingPanels = new Set();
        this.panelVersions = new Map();

        this.init();
    }

    init() {
        if (this.destroyed) return;

        this.bindGlobalEvents();
    }

    /* Registra un listener delegato rimovibile */
    listen(type, handler) {
        document.addEventListener(type, handler, {
            signal: this.eventsController.signal
        });
    }

    /* Verifica se è già in corso un'operazione */
    isBusy() {
        return this.destroyed || this.isSubmitting || this.isConfirming;
    }

    /* Recupera la versione corrente di un pannello */
    getPanelVersion(containerId) {
        return this.panelVersions.get(containerId) || 0;
    }

    /* Verifica che un pannello non sia stato chiuso o sostituito */
    isPanelCurrent(containerId, version, container) {
        return !this.destroyed &&
            this.getPanelVersion(containerId) === version &&
            container.isConnected &&
            document.getElementById(containerId) === container;
    }

    /* Verifica se un ambiente è ancora associato al contenitore originale */
    getPanelContext(env) {
        if (!env || env === 'updateTablesDOM') return null;

        const containerId = `${env}-tools-container`;
        const container = document.getElementById(containerId);

        if (!container) return null;

        return {
            containerId,
            container,
            version: this.getPanelVersion(containerId)
        };
    }

    isContextCurrent(context) {
        return !context || this.isPanelCurrent(
            context.containerId,
            context.version,
            context.container
        );
    }

    bindGlobalEvents() {

        if (this.eventsBound || this.destroyed) return;
        this.eventsBound = true;

        /* 1. Gestione Click Pulsante Azione (Delete) con pre-validazione */
        this.listen('click', async e => {

            const actionBtn = e.target.closest('.btn-action-audits, .btn-action-logs');
            if (!actionBtn) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const form = actionBtn.closest('form');
            const actionType = actionBtn.dataset.action;

            /* Determina dinamicamente l'ambiente */
            const env = actionBtn.classList.contains('btn-action-audits')
                ? 'manageAudits'
                : 'manageLogs';

            if (!form || !this.routes[env][actionType]) return;

            this.isConfirming = true;

            /* Pulisce i messaggi di errore precedenti */
            form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

            const formData = new FormData(form);
            const validateUrl = urlbase + this.routes[env].validate;
            const context = this.getPanelContext(env);

            try {
                const response = await apiFetch(validateUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (this.destroyed || !this.isContextCurrent(context)) return;

                /* Validazione formale */
                if (data.errors) {
                    if (typeof handleValidationErrors === 'function') {
                        handleValidationErrors(data.errors);
                    }

                    if (data.message && typeof showAlert === 'function') {
                        showAlert('danger', data.message);
                    }

                    return;
                }

                /* Errori logici */
                if (data.result === false) {
                    if (data.message && typeof showAlert === 'function') {
                        showAlert('danger', data.message);
                    }

                    return;
                }

                if (data.result !== true) return;

                const actionUrl = urlbase + this.routes[env][actionType];

                if (actionType === 'delete') {

                    /* Nessun record da eliminare */
                    if (data.count === 0) {
                        if (data.noDataMessage && typeof showAlert === 'function') {
                            showAlert('info', data.noDataMessage);
                        }

                        await this.reset(env);
                        return;
                    }

                    /* Conferma con messaggio restituito dal backend */
                    if (data.confirmMessage) {
                        const ok = await askConfirm(data.confirmMessage);

                        if (!ok || this.destroyed || !this.isContextCurrent(context)) {
                            return;
                        }
                    }

                    await this.executeAction(form, actionUrl, env);
                }

            } catch (error) {
                console.error('Operazione interrotta:', error);
            } finally {
                this.isConfirming = false;
            }
        });

        /* 2. Gestione Submit Form per moduli singoli */
        this.listen('submit', async e => {

            const form = e.target;

            if (!form || !form.id.endsWith('-tools-form')) return;

            const env = form.id.replace('-tools-form', '');

            if (typeof this.routes[env] !== 'string') return;

            e.preventDefault();

            if (this.isBusy()) return;

            const actionUrl = urlbase + this.routes[env];

            await this.executeAction(form, actionUrl, env);
        });

        /* 3. Gestione Reset dell'Interfaccia */
        this.listen('click', async e => {

            const resetBtn = e.target.closest('.btn-reset-tools');
            if (!resetBtn) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const form = resetBtn.closest('form');

            if (!form || !form.id.endsWith('-tools-form')) return;

            const env = form.id.replace('-tools-form', '');
            const context = this.getPanelContext(env);

            this.isConfirming = true;

            try {
                const message = resetBtn.dataset.confirm;

                if (message) {
                    const ok = await askConfirm(message);

                    if (!ok || this.destroyed || !this.isContextCurrent(context)) {
                        return;
                    }
                }

                await this.reset(env);

            } catch (error) {
                console.error('Errore reset strumenti:', error);
            } finally {
                this.isConfirming = false;
            }
        });

        /* 4. Gestione ottimizzazione tabelle Database (Singola e Massiva) */
        this.listen('click', async e => {

            const btn = e.target.closest('.btn-optimize-table, .btn-optimize-all');
            if (!btn) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const form = btn.closest('form');
            if (!form) return;

            const tempInputs = [];
            let tablesToProcess = [];
            let inputName = 'table';

            try {
                /* Determina la tabella singola o l'array dal dataset */
                if (btn.dataset.table) {
                    tablesToProcess.push(btn.dataset.table);
                } else if (btn.dataset.tables) {
                    tablesToProcess = JSON.parse(btn.dataset.tables);
                    inputName = 'table[]';
                }

                if (!Array.isArray(tablesToProcess) || tablesToProcess.length === 0) {
                    return;
                }

                /* Inietta gli input nascosti nel form */
                tablesToProcess.forEach(tableName => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = inputName;
                    input.value = tableName;

                    form.appendChild(input);
                    tempInputs.push(input);
                });

                const actionUrl = urlbase + this.routes.dbMaintenance;

                await this.executeAction(form, actionUrl, 'updateTablesDOM');

            } catch (error) {
                console.error('Errore ottimizzazione tabelle:', error);
            } finally {
                /* Pulizia garantita anche in caso di errore */
                tempInputs.forEach(input => input.remove());
            }
        });

        /* 5. Gestione Generazione Backup Database */
        this.listen('click', async e => {

            const btnBackup = e.target.closest('.btn-generate-backups');
            if (!btnBackup) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const form = btnBackup.closest('form');
            if (!form) return;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'generateBackups';

            form.appendChild(actionInput);

            try {
                const actionUrl = urlbase + this.routes.backups;

                await this.executeAction(form, actionUrl, 'backups');
            } finally {
                actionInput.remove();
            }
        });

        /* 6. Gestione Eliminazione Backup */
        this.listen('click', async e => {

            const btnDelete = e.target.closest('.btn-delete-backups');
            if (!btnDelete) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const filename = btnDelete.dataset.filename;
            const message = btnDelete.dataset.message;
            const context = this.getPanelContext('backups');

            this.isConfirming = true;

            try {
                const ok = await askConfirm(message);

                if (!ok || this.destroyed || !this.isContextCurrent(context)) {
                    return;
                }

                const tempForm = document.createElement('form');

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'deleteBackups';
                tempForm.appendChild(actionInput);

                const fileInput = document.createElement('input');
                fileInput.type = 'hidden';
                fileInput.name = 'filename';
                fileInput.value = filename;
                tempForm.appendChild(fileInput);

                const actionUrl = urlbase + this.routes.backups;

                await this.executeAction(tempForm, actionUrl, 'backups');

            } catch (error) {
                console.error('Errore eliminazione backup:', error);
            } finally {
                this.isConfirming = false;
            }
        });

        /* 7. Gestione Download Backup */
        this.listen('click', async e => {

            const btnDownload = e.target.closest('.btn-download-backups');
            if (!btnDownload) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const filename = btnDownload.dataset.filename;
            const tempForm = document.createElement('form');

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'downloadBackups';
            tempForm.appendChild(actionInput);

            const fileInput = document.createElement('input');
            fileInput.type = 'hidden';
            fileInput.name = 'filename';
            fileInput.value = filename;
            tempForm.appendChild(fileInput);

            const actionUrl = urlbase + this.routes.backups;

            /* env null: nessun ricaricamento del DOM */
            await this.executeAction(tempForm, actionUrl, null);
        });

        /* 8. Gestione Svuotamento Cartelle */
        this.listen('click', async e => {

            const btnClean = e.target.closest('.btn-clean-folder');
            if (!btnClean) return;

            e.preventDefault();

            if (this.isBusy()) return;

            const form = btnClean.closest('form');
            if (!form) return;

            const folder = btnClean.dataset.folder;
            const message = btnClean.dataset.confirm;
            const context = this.getPanelContext('cleanSpace');

            this.isConfirming = true;

            try {
                if (message) {
                    const ok = await askConfirm(message);

                    if (!ok || this.destroyed || !this.isContextCurrent(context)) {
                        return;
                    }
                }

                const folderInput = document.createElement('input');
                folderInput.type = 'hidden';
                folderInput.name = 'folder';
                folderInput.value = folder;

                form.appendChild(folderInput);

                try {
                    const actionUrl = urlbase + this.routes.cleanSpace;

                    await this.executeAction(form, actionUrl, 'cleanSpace');
                } finally {
                    folderInput.remove();
                }

            } catch (error) {
                console.error('Errore pulizia cartella:', error);
            } finally {
                this.isConfirming = false;
            }
        });
    }

    /* Distrugge le istanze di Flatpickr per liberare memoria e DOM */
    destroyDatePickers(env) {
        if (!this.datePickers[env]) return;

        const pickers = this.datePickers[env];

        if (pickers.from && typeof pickers.from.destroy === 'function') {
            pickers.from.destroy();
        }

        if (pickers.to && typeof pickers.to.destroy === 'function') {
            pickers.to.destroy();
        }

        delete this.datePickers[env];
    }

    /* Inizializza Flatpickr solo se l'ambiente lo richiede */
    initDatePickers(env) {
        if (this.destroyed || !this.requireDatePickers.includes(env)) return;

        const prefix = env.replace('manage', '').toLowerCase();

        const { pickerFrom, pickerTo } = initRangeDatePicker(
            `#wrapper-${prefix}-created_at-from`,
            `#wrapper-${prefix}-created_at-to`
        );

        this.datePickers[env] = {
            from: pickerFrom,
            to: pickerTo
        };
    }

    /* Carica asincronamente il partial HTML dell'accordion selezionato */
    async loadPanel(containerId, env) {
        if (this.destroyed || this.pendingPanels.has(containerId)) return false;

        const container = document.getElementById(containerId);
        if (!container) return false;

        const version = this.getPanelVersion(containerId);

        this.pendingPanels.add(containerId);

        try {
            const formData = new FormData();
            formData.append('env', env);

            const response = await apiFetch(this.loadUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!this.isPanelCurrent(containerId, version, container)) {
                return false;
            }

            if (data.result !== true || !data.output) {
                return false;
            }

            /* Distrugge eventuali calendari del pannello precedente */
            if (this.requireDatePickers.includes(env)) {
                this.destroyDatePickers(env);
            }

            /* Mantiene l'ordine originale: HTML, poi Flatpickr */
            await smoothReplace(container, data.output);

            if (!this.isPanelCurrent(containerId, version, container)) {
                return false;
            }

            this.initDatePickers(env);

            return true;

        } catch (error) {
            console.error('Errore caricamento pannello strumenti:', error);
            return false;
        } finally {
            this.pendingPanels.delete(containerId);
        }
    }

    /* Ricarica la vista parziale per resettare il form */
    async reset(env) {
        if (this.destroyed || this.isSubmitting) return false;

        this.isSubmitting = true;

        try {
            const containerId = `${env}-tools-container`;

            return await this.loadPanel(containerId, env);

        } catch (error) {
            console.error('Errore reset strumenti:', error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Svuota il contenitore alla chiusura dell'accordion */
    resetContainer(containerId) {
        if (this.destroyed) return;

        /* Invalida le eventuali richieste ancora in corso */
        this.panelVersions.set(
            containerId,
            this.getPanelVersion(containerId) + 1
        );

        const container = document.getElementById(containerId);
        if (!container) return;

        const env = containerId.replace('-tools-container', '');

        if (this.requireDatePickers.includes(env)) {
            this.destroyDatePickers(env);
        }

        smoothReplace(container, '');
    }

    /* Esegue l'invio dell'azione al server */
    async executeAction(form, actionUrl, env) {

        if (this.destroyed || this.isSubmitting) return false;

        this.isSubmitting = true;

        const formData = new FormData(form);
        const context = env === 'updateTablesDOM'
            ? this.getPanelContext('dbMaintenance')
            : this.getPanelContext(env);

        form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(actionUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed || !this.isContextCurrent(context)) {
                return false;
            }

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

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }

                /* Avvia il download contestuale se il backend restituisce un URL */
                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.setAttribute('download', '');

                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }

                /* Gestione differenziata e massiva del refresh DOM */
                if (env === 'updateTablesDOM' && data.tableData) {

                    const tables = Array.isArray(data.tableData)
                        ? data.tableData
                        : [data.tableData];

                    let totalSize = 0;
                    let totalOverhead = 0;

                    const langTotalSpace = form.dataset.langTotalSpace;
                    const langOverhead = form.dataset.langOverhead;
                    const langRows = form.dataset.langRows;

                    tables.forEach(table => {
                        if (!table || !table.name) return;

                        /* 1. Aggiorna la singola riga */
                        const row = document.querySelector(`li[data-table="${CSS.escape(String(table.name))}"]`);

                        if (row) {
                            const dataContainer = row.querySelector('.text-muted.d-flex');

                            if (dataContainer) {
                                const htmlContent = `
                                    <span class="mb-2 mb-lg-0"><i class="fa-solid fa-list me-2"></i>${langRows.replace('%d', table.rows)}</span>
                                    <span class="mb-2 mb-lg-0"><i class="fa-solid fa-hard-drive me-2"></i>${langTotalSpace.replace('%s', table.size.toFixed(2))}</span>
                                    <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>${langOverhead.replace('%s', table.overhead.toFixed(2))}</span>
                                `;

                                smoothReplace(dataContainer, htmlContent);
                            }
                        }

                        /* 2. Calcola i totali */
                        totalSize += parseFloat(table.size) || 0;
                        totalOverhead += parseFloat(table.overhead) || 0;
                    });

                    /* 3. Aggiorna l'header dopo l'ottimizzazione massiva */
                    if (tables.length > 1) {
                        const headerSmall = document.querySelector('.text-muted.infoDb');

                        if (headerSmall) {
                            const htmlHeader = `
                                <span class="mb-2 mb-lg-0 me-lg-4"><i class="fa-solid fa-hard-drive me-2"></i>${langTotalSpace.replace('%s', totalSize.toFixed(2))}</span>
                                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>${langOverhead.replace('%s', totalOverhead.toFixed(2))}</span>
                            `;

                            smoothReplace(headerSmall, htmlHeader);
                        }
                    }

                } else if (env) {
                    /* Ricarica l'intero pannello per gli altri ambienti */
                    const containerId = `${env}-tools-container`;

                    await this.loadPanel(containerId, env);
                }

                return true;
            }

            return false;

        } catch (error) {
            console.error('Operazione interrotta:', error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Libera listener e componenti gestiti dalla classe */
    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;

        this.eventsController.abort();
        this.eventsBound = false;

        /* Invalida tutti i pannelli conosciuti */
        this.panelVersions.forEach((version, containerId) => {
            this.panelVersions.set(containerId, version + 1);
        });

        this.pendingPanels.clear();

        /* Distrugge tutte le istanze Flatpickr */
        Object.keys(this.datePickers).forEach(env => {
            this.destroyDatePickers(env);
        });

        if (ToolsManager.instance === this) {
            ToolsManager.instance = null;
        }
    }
}
