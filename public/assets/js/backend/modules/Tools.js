/* Import delle utility da backend.js */
import { urlbase, apiFetch, showAlert, askConfirm, handleValidationErrors, smoothReplace, initRangeDatePicker } from '../backend.js';

export class ToolsManager {
    constructor() {
        this.loadUrl = urlbase + 'backend/tools/openTools';
        this.isSubmitting = false;
        this.eventsBound = false;

        /* Mappa delle rotte per le azioni di business */
        this.routes = {
            manageAudits: {
                validate: 'backend/tools/validateAuditsDateRequest',
                delete: 'backend/tools/deleteAudits'
            },
            dbMaintenance: 'backend/tools/optimizeTable',
            backups: 'backend/tools/backups',
            cleanSpace: 'backend/tools/cleanFolder'
        };

        this.datePickers = { from: null, to: null };

        this.init();
    }

    init() {
        this.bindGlobalEvents();
    }

    bindGlobalEvents() {

        if (this.eventsBound) return;
        this.eventsBound = true;

        /* 1. Gestione Click Pulsante Azione (Delete) con pre-validazione */
        document.addEventListener('click', async e => {
            const actionBtn = e.target.closest('.btn-action-audits');
            if (actionBtn) {
                e.preventDefault();

                const form = actionBtn.closest('form');
                const actionType = actionBtn.dataset.action;
                
                if ( ! form || !this.routes.manageAudits[actionType]) return;

                /* Pulisce i messaggi di errore precedenti */
                form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

                /* Esegue la pre-validazione invisibile */
                const formData = new FormData(form);
                const validateUrl = urlbase + this.routes.manageAudits.validate;

                try {
                    const response = await apiFetch(validateUrl, { method: 'POST', body: formData });
                    const data = await response.json();

                    /* Se la validazione formale fallisce, stampa gli errori e si ferma */
                    if (data.errors) {
                        if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                        if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                        return;
                    }

                    /* Gestisce gli errori logici (es. data inizio successiva a data fine) */
                    if (data.result === false && data.message) {
                        if (typeof showAlert === 'function') showAlert('danger', data.message);
                        return;
                    }

                    /* Se i dati sono corretti, procede con l'azione specifica */
                    if (data.result === true) {
                        const actionUrl = urlbase + this.routes.manageAudits[actionType];

                        /* Flusso Delete: controlla il conteggio, mostra il modale, poi esegue */
                        if (actionType === 'delete') {
                            
                            /* Se non ci sono record, mostra l'avviso e blocca il flusso */
                            if (data.count === 0) {
                                if (data.noDataMessage && typeof showAlert === 'function') {
                                    showAlert('info', data.noDataMessage);
                                }
                                await this.reset('manageAudits');
                                return;
                            }

                            /* Se ci sono record, usa il messaggio preformattato dal backend */
                            const warningMessage = data.confirmMessage;
                            if (warningMessage) {
                                const ok = await askConfirm(warningMessage);
                                if ( ! ok) return;
                            }
                            
                            await this.executeAction(form, actionUrl, 'manageAudits');
                        }
                    }
                } catch (error) {
                    console.error("Operazione interrotta: ", error);
                    return false;
                }
            }
        });

        /* 2. Gestione Submit Form per moduli singoli (dbMaintenance, backups) */
        document.addEventListener('submit', async e => {
            if (e.target && e.target.id.endsWith('-tools-form')) {
                const env = e.target.id.replace('-tools-form', '');
                
                if (typeof this.routes[env] === 'string') {
                    e.preventDefault();
                    const actionUrl = urlbase + this.routes[env];
                    await this.executeAction(e.target, actionUrl, env);
                }
            }
        });

        /* 3. Gestione Reset dell'Interfaccia */
        document.addEventListener('click', async e => {
            const resetBtn = e.target.closest('.btn-reset-tools');
            if (resetBtn) {
                e.preventDefault();

                const message = resetBtn.dataset.confirm;
                if (message) {
                    const ok = await askConfirm(message);
                    if ( ! ok) return;
                }

                const form = resetBtn.closest('form');
                if (form && form.id.endsWith('-tools-form')) {
                    const env = form.id.replace('-tools-form', '');
                    await this.reset(env);
                }
            }
        });

        /* 6. Gestione ottimizzazione tabelle Database (Singola e Massiva) */
        document.addEventListener('click', async e => {
            const btn = e.target.closest('.btn-optimize-table, .btn-optimize-all');
            if (btn) {
                e.preventDefault();

                const form = btn.closest('form');
                if ( ! form) return;

                const tempInputs = [];
                let tablesToProcess = [];
                let inputName = 'table';

                /* Determina se è una tabella singola o un array dal dataset */
                if (btn.dataset.table) {
                    tablesToProcess.push(btn.dataset.table);
                } else if (btn.dataset.tables) {
                    tablesToProcess = JSON.parse(btn.dataset.tables);
                    inputName = 'table[]'; /* Dichiara a PHP che arriverà un array */
                }

                if (tablesToProcess.length === 0) return;

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
                
                /* Pulisce il form a fine operazione */
                tempInputs.forEach(input => input.remove());
            }
        });

        /* 8. Gestione Generazione Backup Database */
        document.addEventListener('click', async e => {
            const btnBackup = e.target.closest('.btn-generate-backups');
            if (btnBackup) {
                e.preventDefault();

                const form = btnBackup.closest('form');
                if ( ! form) return;

                /* Inietta un input temporaneo per specificare al PHP l'azione richiesta */
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'generateBackups';
                form.appendChild(actionInput);

                /* Utilizza la rotta dedicata ai backups */
                const actionUrl = urlbase + this.routes.backups; 
                
                await this.executeAction(form, actionUrl, 'backups');
                
                /* Pulisce il form eliminando l'input temporaneo */
                actionInput.remove();
            }
        });

        /* 9. Gestione Eliminazione Backup */
        document.addEventListener('click', async e => {
            const btnDelete = e.target.closest('.btn-delete-backups');
            if (btnDelete) {
                e.preventDefault();
                
                const filename = btnDelete.dataset.filename;
                const message = btnDelete.dataset.message;
                
                /* Attendiamo la risposta del modale */
                const ok = await askConfirm(message);
                if ( ! ok) return;

                /* Creazione di un form temporaneo per la chiamata AJAX */
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
                
                /* Esegue l'azione e ricarica il pannello backups */
                await this.executeAction(tempForm, actionUrl, 'backups');
            }
        });

        /* Gestione Download Backup */
        document.addEventListener('click', async e => {
            const btnDownload = e.target.closest('.btn-download-backups');
            if (btnDownload) {
                e.preventDefault();
                
                const filename = btnDownload.dataset.filename;
                
                /* Creazione form temporaneo per la chiamata POST */
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
                
                /* Passa null come env per evitare il ricaricamento del DOM */
                await this.executeAction(tempForm, actionUrl, null);
            }
        });

        /* 10. Gestione Svuotamento Cartelle (cleanSpace) */
        document.addEventListener('click', async e => {
            const btnClean = e.target.closest('.btn-clean-folder');
            if (btnClean) {
                e.preventDefault();

                const folder = btnClean.dataset.folder;
                const message = btnClean.dataset.confirm;

                /* Attendiamo la risposta del modale di conferma */
                if (message) {
                    const ok = await askConfirm(message);
                    if ( ! ok) return;
                }

                const form = btnClean.closest('form');
                if ( ! form) return;

                /* Inietta un input temporaneo per passare il nome della cartella al controller */
                const folderInput = document.createElement('input');
                folderInput.type = 'hidden';
                folderInput.name = 'folder';
                folderInput.value = folder;
                form.appendChild(folderInput);

                const actionUrl = urlbase + this.routes.cleanSpace;

                /* Esegue l'azione e ricarica il pannello passando l'env 'cleanSpace' */
                await this.executeAction(form, actionUrl, 'cleanSpace');

                /* Pulisce il form eliminando l'input temporaneo */
                folderInput.remove();
            }
        });
    }

    /* Distrugge le istanze di Flatpickr per liberare memoria e DOM */
    destroyDatePickers() {
        if (this.datePickers.from) this.datePickers.from.destroy();
        if (this.datePickers.to) this.datePickers.to.destroy();
        this.datePickers = { from: null, to: null };
    }

    /* Inizializza Flatpickr solo se l'ambiente lo richiede */
    initDatePickers(env) {
        if (env === 'manageAudits') {
            const { pickerFrom, pickerTo } = initRangeDatePicker('#wrapper-audits-created_at-from', '#wrapper-audits-created_at-to');
            this.datePickers.from = pickerFrom;
            this.datePickers.to = pickerTo;
        }
    }

    /* Carica asincronamente il partial HTML dell'accordion selezionato */
    async loadPanel(containerId, env) {
        const container = document.getElementById(containerId);
        if ( ! container) return false;

        const formData = new FormData();
        formData.append('env', env);

        try {
            const response = await apiFetch(this.loadUrl, { 
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.result === true && data.output) {

                if (env === 'manageAudits') {
                    this.destroyDatePickers();
                }

                await smoothReplace(container, data.output);
                this.initDatePickers(env);

                return true;
            }
            return false;
        } catch (error) {
            console.error("Operazione interrotta: ", error);
            return false;
        }
    }

    /* Ricarica la vista parziale per resettare il form */
    async reset(env) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        try {
            const containerId = `${env}-tools-container`;
            await this.loadPanel(containerId, env);
        } catch (error) {
            console.error("Operazione interrotta: ", error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }

    /* Svuota il contenitore alla chiusura dell'accordion */
    resetContainer(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            
            if (containerId === 'manageAudits-tools-container') {
                this.destroyDatePickers();
            }

            smoothReplace(container, '');
        }
    }

    /* Esegue l'invio dell'azione al server */
    async executeAction(form, actionUrl, env) {

        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formData = new FormData(form);

        form.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(actionUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

           if (data.result === true) {
                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);

                /* Avvia il download contestuale se il backend restituisce un URL */
                if (data.downloadUrl) {
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.setAttribute('download', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                /* Gestione differenziata e massiva del refresh DOM */
                if (env === 'updateTablesDOM' && data.tableData) {
                    
                    /* Forza il dato in array per evitare errori JS fatali */
                    const tables = Array.isArray(data.tableData) ? data.tableData : [data.tableData];
                    
                    let totalSize = 0;
                    let totalOverhead = 0;

                    /* Estrai le stringhe tradotte dal form */
                    const langTotalSpace = form.dataset.langTotalSpace;
                    const langOverhead = form.dataset.langOverhead;
                    const langRows = form.dataset.langRows;

                    tables.forEach(table => {
                        if ( ! table || ! table.name) return;

                        /* 1. Trova la singola riga */
                        const row = document.querySelector(`li[data-table="${table.name}"]`);
                        
                        if (row) {
                            /* Cerca il div con la classe text-muted */
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

                        /* 2. Raccoglie i dati per il totale globale */
                        totalSize += parseFloat(table.size) || 0;
                        totalOverhead += parseFloat(table.overhead) || 0;
                    });

                    /* 3. Se l'array ha più di 1 elemento (Ottimizza Tutte), aggiorna anche l'header Database */
                    if (tables.length > 1) {
                        const headerSmall = document.querySelector('.text-muted.infoDb');
                        if (headerSmall) {
                            /* INIETTA LE NUOVE CLASSI RESPONSIVE: mb-2 mb-lg-0 me-lg-4 */
                            const htmlHeader = `
                                <span class="mb-2 mb-lg-0 me-lg-4"><i class="fa-solid fa-hard-drive me-2"></i>${langTotalSpace.replace('%s', totalSize.toFixed(2))}</span>
                                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>${langOverhead.replace('%s', totalOverhead.toFixed(2))}</span>
                            `;
                            smoothReplace(headerSmall, htmlHeader);
                        }
                    }

                } else if (env) {
                    /* Comportamento standard per tutti gli altri env: ricarica l'intero pannello */
                    const containerId = `${env}-tools-container`;
                    await this.loadPanel(containerId, env);
                }
            }
            
        } catch (error) {
            console.error("Operazione interrotta: ", error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }
}