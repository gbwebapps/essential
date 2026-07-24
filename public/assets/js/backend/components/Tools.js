/* Import delle utility da backend.js */
import { urlbase, apiFetch, showAlert, askConfirm, handleValidationErrors, smoothReplace } from '../backend.js';

export class ToolsManager {
    constructor() {
        this.loadUrl = urlbase + 'backend/tools/openTools';
        this.isSubmitting = false;

        /* Mappa delle rotte per le azioni di business */
        this.routes = {
            manageAudits: {
                validate: 'backend/tools/validateAuditsDateRequest',
                delete: 'backend/tools/deleteAudits',
                export: 'backend/tools/exportAudits'
            },
            dbMaintenance: 'backend/tools/dbMaintenance',
            backup: 'backend/tools/backup'
        };

        this.currentExportForm = null;
        this.currentExportUrl = null;

        this.init();
    }

    init() {
        this.bindGlobalEvents();
    }

    bindGlobalEvents() {

        /* 1. Gestione Click Pulsanti Azione (Delete / Export) con pre-validazione */
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

                    /* Se la validazione fallisce, stampa gli errori e si ferma (niente modale) */
                    if (data.errors) {
                        if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                        if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                        return;
                    }

                    /* Se i dati sono corretti, procede con l'azione specifica */
                    if (data.result === true) {
                        const actionUrl = urlbase + this.routes.manageAudits[actionType];

                        /* Flusso Delete: mostra il modale di conferma, poi esegue */
                        if (actionType === 'delete') {
                            const warningMessage = actionBtn.dataset.warning;
                            if (warningMessage) {
                                const ok = await askConfirm(warningMessage);
                                if ( ! ok) return;
                            }
                            this.executeAction(form, actionUrl, 'manageAudits');
                        } 
                        
                        /* Flusso Export: mostra il modale delle colonne */
                        else if (actionType === 'export') {
                            this.currentExportForm = form;
                            this.currentExportUrl = actionUrl;

                            const modalEl = document.getElementById('exportColumnsModal');
                            if (modalEl) {
                                const exportModal = new bootstrap.Modal(modalEl);
                                exportModal.show();
                            }
                        }
                    }
                } catch (error) {
                    console.error("Operazione interrotta: ", error);
                    return false;
                }
            }
        });

        /* 2. Gestione Submit Form per moduli singoli (dbMaintenance, backup) */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id.endsWith('-tools-form')) {
                const env = e.target.id.replace('-tools-form', '');
                
                if (typeof this.routes[env] === 'string') {
                    e.preventDefault();
                    const actionUrl = urlbase + this.routes[env];
                    this.executeAction(e.target, actionUrl, env);
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
                    this.reset(resetBtn, env);
                }
            }
        });

        /* 4. Gestione Conferma Esportazione dal Modale */
        document.addEventListener('click', e => {
            if (e.target && e.target.id === 'btnConfirmExport') {
                e.preventDefault();

                const modalEl = document.getElementById('exportColumnsModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();

                /* Rimuove eventuali input nascosti precedenti per evitare duplicati */
                this.currentExportForm.querySelectorAll('.hidden-export-column').forEach(el => el.remove());

                /* Aggiunge le colonne selezionate come input hidden nel form principale */
                modalEl.querySelectorAll('.export-column-checkbox:checked').forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'columns[]';
                    input.value = cb.value;
                    input.className = 'hidden-export-column';
                    this.currentExportForm.appendChild(input);
                });

                /* Esegue la chiamata AJAX definitiva */
                this.executeAction(this.currentExportForm, this.currentExportUrl, 'manageAudits');
            }
        });

        /* 5. Gestione dinamica dei range dei calendari HTML5 */
        document.addEventListener('change', e => {
            if (e.target && e.target.id === 'fromDate') {
                const toDate = document.getElementById('toDate');
                if (toDate && e.target.value) {
                    toDate.min = e.target.value;
                }
            }
            
            if (e.target && e.target.id === 'toDate') {
                const fromDate = document.getElementById('fromDate');
                if (fromDate && e.target.value) {
                    fromDate.max = e.target.value;
                }
            }
        });
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
                await smoothReplace(container, data.output);
                return true;
            }
            return false;
        } catch (error) {
            console.error("Operazione interrotta: ", error);
            return false;
        }
    }

    /* Ricarica la vista parziale per resettare il form */
    async reset(resetBtn, env) {
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
            container.innerHTML = '';
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
                
                const containerId = `${env}-tools-container`;
                await this.loadPanel(containerId, env);
            }
        } catch (error) {
            console.error("Operazione interrotta: ", error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }
}