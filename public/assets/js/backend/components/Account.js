/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class EditManager {
    constructor(config = {}, hooks = {}) {
        /* Inizializza la configurazione di base con i selettori dinamici */
        this.config = Object.assign({
            formSelector: '', /* <--- Es: '#admins_edit' */
            url: '',
            refreshSelector: '', /* <--- Es: '#edit_refresh' */
            containerId: '', 
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

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        /* Unico punto di aggancio tramite delegazione globale */
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce l'accumulo di listener multipli */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione Invio Form Edit tramite delegazione */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.config.formSelector);
            if ( ! formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            await this.edit(formData);
        });

        /* Gestione Submit Form di Refresh (Annulla) tramite delegazione */
        document.addEventListener('submit', async e => {
            const refreshFormEl = e.target.closest(this.config.refreshSelector);
            if ( ! refreshFormEl) return;

            e.preventDefault();
            const message = refreshFormEl.dataset.message;
            const ok = await askConfirm(message);
            if ( ! ok) return;

            const formData = new FormData(refreshFormEl);
            await this.refresh(formData);
        });
    }

    async edit(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Esegue hook personalizzato prima del salvataggio, blocca se ritorna false */
        if (typeof this.hooks.onEditBefore === 'function') {
            const stop = this.hooks.onEditBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Invio al backend */
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Visualizza eventuali errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                if (typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* Caso positivo: salvataggio riuscito */
            if (data.result === true) {
                if (typeof showToast === 'function') showToast('success', data.message);

                if (typeof this.hooks.onEditAfter === 'function') {
                    this.hooks.onEditAfter(data);
                }

                /* Sfruttiamo l'output già pronto senza fare una seconda chiamata HTTP */
                if (data.output) {
                    const showDataEl = document.getElementById(this.config.containerId);
                    if (showDataEl) {
                        smoothReplace(showDataEl, data.output);
                    }

                    const navBarTop = document.getElementById('navbar-top-view');
                    if (navBarTop && data.navBarTop) {
                        smoothReplace(navBarTop, data.navBarTop);
                    }
                }
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                this.hooks.onEditError(error);
            }
            console.error("Errore EditManager (edit):", error);
        } finally {

            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Aggiunge parametro action=refresh per il backend */
        formData.append('action', 'refresh');

        /* Esegue hook prima del refresh (può bloccare se ritorna false) */
        if (typeof this.hooks.onRefreshBefore === 'function') {
            const stop = this.hooks.onRefreshBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Caso errore generico */
            if (data.result === false) {
                if (typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* Caso positivo: rigenera markup e reinizializza componenti */
            if (data.result === true) {

                const showDataEl = document.getElementById(this.config.containerId);
                if (showDataEl) {
                    smoothReplace(showDataEl, data.output);
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
            console.error("Errore EditManager (refresh):", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class GetPermissionsManager {
    constructor(config = {}, hooks = {}) {
        /* Inizializza la configurazione con selettori dinamici coerenti */
        this.config = Object.assign({
            url: '', 
            formSelector: '',
            containerId: '' /* <--- Reso dinamico */
        }, config);

        this.hooks = Object.assign({
            onPermissionsBefore: null,
            onPermissionsAfter: null,
            onPermissionsError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            if ( ! e.target.matches(this.config.formSelector)) return;

            e.preventDefault();
            const formData = new FormData(e.target);

            await this.getPermissions(formData);
        });
    }

    async getPermissions(formData) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onPermissionsBefore === 'function') {
            const stop = this.hooks.onPermissionsBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }
        
        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Utilizzo della proprietà dinamica da config */
            const permissionsEl = document.getElementById(this.config.containerId);
            if (permissionsEl && data.output) {
                smoothReplace(permissionsEl, data.output);
            }

            if (typeof this.hooks.onPermissionsAfter === 'function') {
                this.hooks.onPermissionsAfter(data);
            }

        } catch (error) {
            if (typeof this.hooks.onPermissionsError === 'function') {
                this.hooks.onPermissionsError(error);
            }
            console.error("Errore PermissionsManager:", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}