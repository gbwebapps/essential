/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class GetPermissionsManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            url: '', 
            formSelector: ''
        }, config);

        this.hooks = Object.assign({
            onPermissionsBefore: null,
            onPermissionsAfter: null,
            onPermissionsError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
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
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onPermissionsBefore === 'function') {
            const stop = this.hooks.onPermissionsBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (UNIFORMATA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const permissionsEl = document.getElementById('permissions');
                if (permissionsEl) {
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
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class ChangePermissionManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            controller: '',
            url: '',
        }, config);

        this.hooks = Object.assign({
            onPermissionBefore: null,
            onPermissionAfter: null,
            onPermissionError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
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
            this.changePermission(formData);
        });
    }

    async changePermission(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onPermissionBefore === 'function') {
            const stop = this.hooks.onPermissionBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (CORRETTA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const PermissionsEl = document.getElementById('permissions');
                const MetaDataEl = document.getElementById('metaData');

                smoothReplace(PermissionsEl, data.permissionsView);
                smoothReplace(MetaDataEl, data.metaView);

                if (typeof this.hooks.onPermissionAfter === 'function') {
                    this.hooks.onPermissionAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onPermissionError === 'function') {
                this.hooks.onPermissionError(error);
            }
            console.error("Errore ChangePermission:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class GetTokensManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            url: '', 
            formSelector: ''
        }, config);

        this.hooks = Object.assign({
            onTokensBefore: null,
            onTokensAfter: null,
            onTokensError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            if ( ! e.target.matches(this.config.formSelector)) return;

            e.preventDefault();
            const formData = new FormData(e.target);

            await this.getTokens(formData);
        });
    }

    async getTokens(formData) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onTokensBefore === 'function') {
            const stop = this.hooks.onTokensBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (UNIFORMATA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const tokensEl = document.getElementById('tokens');
                if (tokensEl) {
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
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class DeleteTokenManager {
    constructor(config = {}, hooks = {})
    {
        this.config = Object.assign({
            controller: '',
            url: '',
        }, config);

        this.hooks = Object.assign({
            onDeleteTokenBefore: null,
            onDeleteTokenAfter: null,
            onDeleteTokenError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {

        /* NUOVO: Impedisce cloni dei listener */
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
            this.deleteToken(formData);
        });
    }

    async deleteToken(formData) {

        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onDeleteTokenBefore === 'function') {
            const stop = this.hooks.onDeleteTokenBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione errori di validazione (CORRETTA) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Gestione fallimento logico generico */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                const deleteToken = document.getElementById('tokens');
                smoothReplace(deleteToken, data.tokensView);

                if (typeof this.hooks.onDeleteTokenAfter === 'function') {
                    this.hooks.onDeleteTokenAfter(data);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onDeleteTokenError === 'function') {
                this.hooks.onDeleteTokenError(error);
            }
            console.error("Errore DeleteToken:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}