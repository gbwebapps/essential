/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showAlert, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class ResetPasswordManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.formSelector = '.resetAdmin';
        this.url = `${urlbase}backend/admins/resetPassword`;

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
            await this.resetPassword(formData, formEl);
        });
    }
    
    async resetPassword(formData, formEl) {
        
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

            /* Successo */
            if (data.result === true) {

                if (typeof this.hooks.onResetAfter === 'function') {
                    this.hooks.onResetAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
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

export class ChangeGroupManager {
    constructor(hooks = {})
    {
        /* Parametro di configurazione della sezione dedicato e hardcoded */
        this.url = urlbase + 'backend/admins/changeGroup';

        this.hooks = Object.assign({
            onGroupBefore: null,
            onGroupAfter: null,
            onGroupError: null
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

        /* Memorizzo il valore iniziale al caricamento della pagina, se presente */
        const initialGroup = document.getElementById('group_id');
        let previousGroupId = initialGroup ? initialGroup.value : '';

        /* Agganciamo il listener al document: sopravvive a qualsiasi rimpiazzo HTML */
        document.addEventListener('change', async e => {
            const selectEl = e.target.closest('#group_id');
            if ( ! selectEl) return; /* Se non è la nostra select, ignora l'evento */

            /* Intercettiamo la conferma dell'utente prima di aggiornare lo stato */
            const message = selectEl.dataset.message;
            const ok = await askConfirm(message);

            if ( ! ok) {

                /* Se l'utente annulla, ripristino visivamente il gruppo precedente sulla select e blocco il flusso */
                selectEl.value = previousGroupId;
                return;
            }

            const groupId = selectEl.value;
            const uuidEl = document.getElementById('uuid');
            const uuid = uuidEl ? uuidEl.value : '';

            if (!groupId || !uuid) return;

            /* Aggiorno il vecchio valore con quello nuovo solo dopo la conferma */
            previousGroupId = groupId;

            const formData = new FormData();
            formData.append('group_id', groupId);
            formData.append('uuid', uuid);

            await this.changeGroup(formData);
        });
    }

    async changeGroup(formData) {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onGroupBefore === 'function') {
            const stop = this.hooks.onGroupBefore(formData);
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

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            if (data.result === true) {

                /* Aggiorno chirurgicamente solo il contenitore dei permessi parziali */
                const permissionsEl = document.getElementById('permissions');
                if (permissionsEl && data.output) {
                    smoothReplace(permissionsEl, data.output);
                }

                if (typeof this.hooks.onGroupAfter === 'function') {
                    this.hooks.onGroupAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onGroupError === 'function') {
                this.hooks.onGroupError(error);
            }
            console.error("Errore ChangeGroup:", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}

export class GetPermissionsManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/admins/getPermissions'; 
        this.formSelector = '#getPermissions';

        this.hooks = Object.assign({
            onPermissionsBefore: null,
            onPermissionsAfter: null,
            onPermissionsError: null
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

            await this.getPermissions(formData);
        });
    }

    async getPermissions(formData) {
        
        /* Se c'è già una richiesta in corso, blocca */
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

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {
                
                const permissionsEl = document.getElementById('permissions');
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

            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class ChangePermissionManager {
    constructor(hooks = {}) {

        /* Parametro di configurazione della sezione dedicato e hardcoded */
        this.url = urlbase + 'backend/admins/changePermission';

        this.hooks = Object.assign({
            onPermissionBefore: null,
            onPermissionAfter: null,
            onPermissionError: null
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
            const formEl = e.target.closest('.changePermission');
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
            await this.changePermission(formData);
        });
    }

    async changePermission(formData) {

        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onPermissionBefore === 'function') {
            const stop = this.hooks.onPermissionBefore(formData);
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

                const PermissionsEl = document.getElementById('permissions');
                if (PermissionsEl && data.permissionsView) {
                    smoothReplace(PermissionsEl, data.permissionsView);
                }

                const MetaDataEl = document.getElementById('metaData');
                if (MetaDataEl && data.metaView) {
                    smoothReplace(MetaDataEl, data.metaView);
                }

                if (typeof this.hooks.onPermissionAfter === 'function') {
                    this.hooks.onPermissionAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onPermissionError === 'function') {
                this.hooks.onPermissionError(error);
            }
            console.error("Errore ChangePermission:", error);
        } finally {

            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class GetTokensManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded */
        this.url = urlbase + 'backend/admins/getTokens'; 
        this.formSelector = '#getTokens';

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

            /* Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Caso successo */
            if (data.result === true) {

                const tokensEl = document.getElementById('tokens');
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
    constructor(hooks = {}) { 

        /* Parametro di configurazione della sezione dedicato e hardcoded */
        this.url = urlbase + 'backend/admins/deleteToken';

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
                
                const deleteToken = document.getElementById('tokens');
                if (deleteToken && data.tokensView) {
                    smoothReplace(deleteToken, data.tokensView);
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