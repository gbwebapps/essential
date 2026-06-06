/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class LoginManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            formId: 'login_form',
            url: ''
        }, config);

        this.hooks = Object.assign({
            onLoginBefore: null,
            onLoginAfter: null,
            onLoginError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        const form = document.getElementById(this.config.formId);
        if ( ! form) return;

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Aggiunto 'async' per attendere la chiamata */
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);
            /* Aggiunto 'await' */
            await this.login(formData, form);
        });
    }
    
    async login(formData, form) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onLoginBefore === 'function') {
            const stop = this.hooks.onLoginBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Reset immediato degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            /* 1. Controllo per gli errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 2. Controllo per fallimento generico (es. Credenziali errate) */
            if (data.result === false) {
                if (form) form.reset();
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Login avvenuto con successo */
            if (data.result === true) {
                if (typeof this.hooks.onLoginAfter === 'function') {
                    this.hooks.onLoginAfter(data);
                }
                
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }

        } catch (error) {
            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onLoginError === 'function') {
                this.hooks.onLoginError(error);
            }
            console.error("Errore LoginManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class ResetPasswordManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            formId: '',           /* Usato per il modulo Auth pubblico (es: 'reset_password_form') */
            formSelector: '',     /* Usato per il pannello Admins (es: '.reset_admin') */
            url: '',
            redirectUrl: '',      /* Se valorizzato, fa il redirect dopo il successo */
            listManager: null,      /* Se valorizzato, ricarica la tabella dopo il successo */
            showSuccessToast: true /* Nuovo flag di default a true */
        }, config);

        this.hooks = Object.assign({
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Caso 1: Form singolo diretto */
        if (this.config.formId) {
            const form = document.getElementById(this.config.formId);
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    /* Aggiunto 'await' */
                    await this.resetPassword(formData);
                });
            }
        }

        /* Caso 2: Event Delegation */
        if (this.config.formSelector) {
            document.addEventListener('submit', async (e) => {
                const formEl = e.target.closest(this.config.formSelector);
                if ( ! formEl) return;

                e.preventDefault();

                const message = formEl.dataset.message;
                if (message) {
                    const ok = await askConfirm(message);
                    if ( ! ok) return;
                }

                const formData = new FormData(formEl);
                /* Aggiunto 'await' */
                await this.resetPassword(formData);
            });
        }
    }
    
    async resetPassword(formData) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onResetBefore === 'function') {
            const stop = this.hooks.onResetBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per utente non loggato (attivo nel backend) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth/login`;
                return;
            }

            /* 2. Errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Fallimento logico (es. email non trovata) */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 4. Successo */
            if (data.result === true) {
                if (this.config.showSuccessToast && data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                if (typeof this.hooks.onResetAfter === 'function') {
                    this.hooks.onResetAfter(data);
                }

                /* Se è configurato il listManager (Admins), aggiorna la tabella */
                if (this.config.listManager && typeof this.config.listManager.showAll === 'function') {
                    this.config.listManager.showAll();
                }

                /* Se è configurato un redirect (Auth), cambia pagina */
                if (this.config.redirectUrl) {
                    window.location.href = this.config.redirectUrl;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }

        } catch (error) {
            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onResetError === 'function') {
                this.hooks.onResetError(error);
            }
            console.error("Errore ResetPasswordManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class SetPasswordManager {
    constructor(config = {}, hooks = {}) {
        this.config = Object.assign({
            formId: 'set_password_form',
            url: '',
            redirectUrl: ''
        }, config);

        this.hooks = Object.assign({
            onSetBefore: null,
            onSetAfter: null,
            onSetError: null
        }, hooks);

        /* NUOVO: Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        const form = document.getElementById(this.config.formId);
        if ( ! form) return;

        /* NUOVO: Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Aggiunto 'async' per attendere la chiamata */
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            /* Aggiunto 'await' */
            await this.savePassword(formData);
        });
    }
    
    async savePassword(formData) {
        
        /* NUOVO: Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onSetBefore === 'function') {
            const stop = this.hooks.onSetBefore(formData);
            if (stop === false) {
                this.isSubmitting = false; /* <--- NUOVO RILASCIO */
                return;
            }
        }

        /* Pulizia immediata degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.config.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* 1. Controllo per gli errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 2. Controllo per fallimento logico (es. token scaduto o invalido) */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') showToast('danger', data.message);
                return;
            }

            /* 3. Successo Finale */
            if (data.result === true) {
                if (typeof this.hooks.onSetAfter === 'function') {
                    this.hooks.onSetAfter(data);
                }

                /* Redirect alla pagina di login */
                if (this.config.redirectUrl) {
                    window.location.href = this.config.redirectUrl;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }

        } catch (error) {
            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onSetError === 'function') {
                this.hooks.onSetError(error);
            }
            console.error("Errore SetPasswordManager:", error);
        } finally {
            /* NUOVO: Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}