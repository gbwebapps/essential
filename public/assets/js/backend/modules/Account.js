/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class EditManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.formSelector = '#account-edit';
        this.url = urlbase + 'backend/account/edit';
        this.refreshSelector = '#edit-refresh';
        this.containerId = 'edit-account-container';

        /* Inizializza eventuali callback esterni da eseguire in momenti chiave */
        this.hooks = Object.assign({
            onEditBefore: null,
            onEditAfter: null,
            onEditError: null,
            onRefreshBefore: null,
            onRefreshAfter: null,
            onRefreshError: null,
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {

        /* Unico punto di aggancio tramite delegazione globale */
        this.bindEvents();
    }

    bindEvents() {

        /* Impedisce l'accumulo di listener multipli */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione Invio Form Edit tramite delegazione */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest(this.formSelector);
            if ( ! formEl) return;

            e.preventDefault();
            const formData = new FormData(formEl);
            await this.edit(formData);
        });

        /* Gestione Submit Form di Refresh (Annulla) tramite delegazione */
        document.addEventListener('submit', async e => {
            const refreshFormEl = e.target.closest(this.refreshSelector);
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

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Esegue hook personalizzato prima del salvataggio, blocca se ritorna false */
        if (typeof this.hooks.onEditBefore === 'function') {
            const stop = this.hooks.onEditBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            /* Invio al backend */
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Visualizza eventuali errori di validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                return;
            }

            /* Errore generico gestito dal backend */
            if (data.result === false) {
                if (typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso positivo: salvataggio riuscito */
            if (data.result === true) {
                
                /* Sfruttiamo l'output già pronto senza fare una seconda chiamata HTTP */
                if (data.output) {
                    const showDataEl = document.getElementById(this.containerId);
                    if (showDataEl) {
                        smoothReplace(showDataEl, data.output);
                    }
                }

                if (data.navBarTop) {
                    const navBarTop = document.getElementById('navbar-top-view');
                    if(navBarTop) {
                        smoothReplace(navBarTop, data.navBarTop);
                    }
                }

                if (typeof this.hooks.onEditAfter === 'function') {
                    this.hooks.onEditAfter(data);
                }

                if (typeof showAlert === 'function') showAlert('success', data.message);
            }
        } catch (error) {
            if (typeof this.hooks.onEditError === 'function') {
                this.hooks.onEditError(error);
            }
            console.error("Errore EditManager (edit):", error);
        } finally {

            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        /* Aggiunge parametro action=refresh per il backend */
        formData.append('action', 'refresh');

        /* Esegue hook prima del refresh (può bloccare se ritorna false) */
        if (typeof this.hooks.onRefreshBefore === 'function') {
            const stop = this.hooks.onRefreshBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Caso positivo: rigenera markup e reinizializza componenti */
            if (data.result === true) {
                const showDataEl = document.getElementById(this.containerId);
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

            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class GetPermissionsManager {
    constructor(hooks = {}) {
        
        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/account/permissions'; 
        this.formSelector = '#getPermissions';
        this.containerId = 'permissions-account-container';

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
            if ( ! e.target.matches(this.formSelector)) return;

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
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === true) {

                /* Utilizzo della proprietà hardcoded interna */
                const permissionsEl = document.getElementById(this.containerId);
                if (permissionsEl && data.output) {
                    smoothReplace(permissionsEl, data.output);
                }

                if (typeof this.hooks.onPermissionsAfter === 'function') {
                    this.hooks.onPermissionsAfter(data);
                }

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

export class GetTokensManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/account/tokens'; 
        this.formSelector = '#getTokens';
        this.containerId = 'tokens-account-container';

        this.hooks = Object.assign({
            onTokensBefore: null,
            onTokensAfter: null,
            onTokensError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            if ( ! e.target.matches(this.formSelector)) return;

            e.preventDefault();
            const formData = new FormData(e.target);

            await this.getTokens(formData);
        });
    }

    async getTokens(formData) {

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onTokensBefore === 'function') {
            const stop = this.hooks.onTokensBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Caso successo */
            if (data.result === true) {
                
                /* Utilizzo della proprietà hardcoded interna */
                const tokensEl = document.getElementById(this.containerId);
                if (tokensEl && data.output) {
                    smoothReplace(tokensEl, data.output);
                }

                if (typeof this.hooks.onTokensAfter === 'function') {
                    this.hooks.onTokensAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onTokensError === 'function') {
                this.hooks.onTokensError(error);
            }
            console.error("Errore TokensManager:", error);
        } finally {
            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class DeleteTokenManager {
    constructor(hooks = {})
    {
        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/account/deleteToken'; 
        this.containerId = 'tokens-account-container';

        this.hooks = Object.assign({
            onDeleteTokenBefore: null,
            onDeleteTokenAfter: null,
            onDeleteTokenError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Gestione dell'invio dei dati (cattura il click sul button type="submit") */
        document.addEventListener('submit', async e => {
            const formEl = e.target.closest('.deleteToken');
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            const ok = await askConfirm(message);

            if ( ! ok) {

                /* Se l'utente annulla la conferma, interrompiamo semplicemente il flusso */
                return;
            }

            /* Recuperiamo i dati dal form e li passiamo alla funzione */
            const formData = new FormData(formEl);
            await this.deleteToken(formData);
        });
    }

    async deleteToken(formData) {

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onDeleteTokenBefore === 'function') {
            const stop = this.hooks.onDeleteTokenBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {

                /* Utilizzo della proprietà hardcoded interna */
                const deleteToken = document.getElementById(this.containerId);
                if (deleteToken && data.output) {
                    smoothReplace(deleteToken, data.output);
                }

                if (typeof this.hooks.onDeleteTokenAfter === 'function') {
                    this.hooks.onDeleteTokenAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onDeleteTokenError === 'function') {
                this.hooks.onDeleteTokenError(error);
            }
            console.error("Errore DeleteToken:", error);
        } finally {
            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class ResetPasswordManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/account/resetPassword'; 
        this.formSelector = '#getResetPassword';
        this.containerId = 'reset-account-container';

        this.hooks = Object.assign({
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Event Delegation basato sul formSelector hardcoded */
        document.addEventListener('submit', async (e) => {
            const formEl = e.target.closest(this.formSelector);
            if ( ! formEl) return;

            e.preventDefault();

            const message = formEl.dataset.message;
            if (message) {
                const ok = await askConfirm(message);
                if ( ! ok) return;
            }

            const formData = new FormData(formEl);
            await this.resetPassword(formData);
        });
    }
    
    async resetPassword(formData) {
        
        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onResetBefore === 'function') {
            const stop = this.hooks.onResetBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Fallimento logico (es. email non trovata) */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Fallimento email (es. email non inviata) */
            if (data.result === 'db_committed_no_email') {
                
                const containerEl = document.getElementById(this.containerId);
                if (containerEl && data.output) {
                    smoothReplace(containerEl, data.output);
                }

                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);

                return;
            }

            /* Successo */
            if (data.result === true) {
                
                const containerEl = document.getElementById(this.containerId);
                if (containerEl && data.output) {
                    smoothReplace(containerEl, data.output);
                }

                if (typeof this.hooks.onResetAfter === 'function') {
                    this.hooks.onResetAfter(data);
                }

                if (data.message && typeof showAlert === 'function') showAlert('success', data.message);
            }

        } catch (error) {

            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onResetError === 'function') {
                this.hooks.onResetError(error);
            }
            console.error("Errore ResetPasswordManager:", error);
        } finally {

            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class SecurityManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione e selettori hardcoded */
        this.wrapperId = 'totp-setup-wrapper';
        this.saveBasicUrl = urlbase + 'backend/account/saveBasicMethod';
        this.setupTotpUrl = urlbase + 'backend/account/setupTotp';
        this.confirmTotpUrl = urlbase + 'backend/account/confirmTotp';

        this.hooks = Object.assign({
            onSecurityBefore: null,
            onSecurityAfter: null,
            onSecurityError: null
        }, hooks);

        /* Variabili di stato */
        this.eventsBound = false;
        this.isSubmitting = false;

        /* Rileva il metodo inizialmente attivo nel DB al caricamento della pagina */
        const activeRadio = document.querySelector('.twofactor-method-trigger:checked');
        this.currentActiveMethod = activeRadio ? activeRadio.value : 'none';
    }

    init() {

        /* Impedisce duplicazioni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        this.bindEvents();
    }

    bindEvents() {

        /* Intercetta il cambio di selezione sui radio button del metodo 2FA */
        document.addEventListener('change', async e => {
            if (! e.target.matches('.twofactor-method-trigger')) return;
            
            const method = e.target.value;
            const wrapper = document.getElementById(this.wrapperId);
            if (wrapper) wrapper.innerHTML = ''; /* Reset sotto-form TOTP */

            if (method === 'totp') {
                await this.requestTotpSetup();
                return;
            }

            /* Chiediamo conferma per None o Email leggendo il data-message dal radio */
            const message = e.target.dataset.message;
            if (message) {
                const ok = await askConfirm(message);
                if (! ok) {
                    /* Rollback dinamico sul metodo precedentemente attivo */
                    const previousRadio = document.querySelector(`.twofactor-method-trigger[value="${this.currentActiveMethod}"]`);
                    if (previousRadio) previousRadio.checked = true;
                    return;
                }
            }

            /* Se conferma, salva immediatamente la preferenza */
            await this.saveBasicMethod(method);

            /* Aggiorno lo stile del metodo salvato */
            this.updateVisualBorder(method);
            
            /* Aggiorno lo stato del metodo corrente attivo */
            this.currentActiveMethod = method; 
        });

        /* Intercetta l'invio del codice di verifica del TOTP caricato dinamicamente */
        document.addEventListener('submit', async e => {

            if ( ! e.target.matches('#confirm-totp-form')) return;

            e.preventDefault();

            const formData = new FormData(e.target);
            await this.initTotpConfirmation(formData);
        });

        /* Gestione della visibilità della chiave segreta TOTP */
        document.addEventListener('click', e => {
            if (! e.target.closest('#toggle-secret-visibility')) return;

            const input = document.getElementById('totp-secret-field');
            const icon = document.getElementById('toggle-secret-icon');
            
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }
        });
    }

    async saveBasicMethod(method) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formData = new FormData();
        formData.append('twoFactorMethod', method);

        if (typeof this.hooks.onSecurityBefore === 'function') {
            const stop = this.hooks.onSecurityBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.saveBasicUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {
                if (typeof showAlert === 'function' && data.message) showAlert('success', data.message);

                if (typeof this.hooks.onSecurityAfter === 'function') {
                    this.hooks.onSecurityAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onSecurityError === 'function') {
                this.hooks.onSecurityError(error);
            }
            console.error("Errore SecurityManager (saveBasicMethod):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async requestTotpSetup() {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formData = new FormData();

        if (typeof this.hooks.onSecurityBefore === 'function') {
            const stop = this.hooks.onSecurityBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.setupTotpUrl, {
                method: 'POST'
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {
                
                const wrapper = document.getElementById(this.wrapperId);
                if (data.output) smoothReplace(wrapper, data.output);

                if (typeof this.hooks.onSecurityAfter === 'function') {
                    this.hooks.onSecurityAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onSecurityError === 'function') {
                this.hooks.onSecurityError(error);
            }
            console.error("Errore SecurityManager (requestTotpSetup):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    async initTotpConfirmation(formData) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onSecurityBefore === 'function') {
            const stop = this.hooks.onSecurityBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        try {
            const response = await apiFetch(this.confirmTotpUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {
                
                this.currentActiveMethod = 'totp';
                this.updateVisualBorder('totp');

                if (typeof showAlert === 'function' && data.message) showAlert('success', data.message);
                
                const wrapper = document.getElementById(this.wrapperId);
                if (wrapper) wrapper.innerHTML = ''; /* Svuota il QR code ad attivazione completata */

                if (typeof this.hooks.onSecurityAfter === 'function') {
                    this.hooks.onSecurityAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onSecurityError === 'function') {
                this.hooks.onSecurityError(error);
            }
            console.error("Errore SecurityManager (initTotpConfirmation):", error);
        } finally {
            this.isSubmitting = false;
        }
    }

    updateVisualBorder(newMethod) {

        /* 1. Rimuovo le classi attive da TUTTE le card dei metodi */
        document.querySelectorAll('.twofactor-method-trigger').forEach(radio => {
            const card = radio.closest('.card');
            if (card) {
                card.classList.remove('border-primary', 'bg-light');
            }
        });

        /* 2. Aggiungo le classi attive solo alla card del nuovo metodo */
        const activeRadio = document.getElementById(`method-${newMethod}`);
        if (activeRadio) {
            const card = activeRadio.closest('.card');
            if (card) {
                card.classList.add('border-primary', 'bg-light');
            }
        }
    }
}