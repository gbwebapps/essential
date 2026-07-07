/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showAlert, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class LoginManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded (Guest Mode) */
        this.formId = 'login_form';
        this.url = urlbase + 'backend/auth/login';

        this.hooks = Object.assign({
            onLoginBefore: null,
            onLoginAfter: null,
            onLoginError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        const form = document.getElementById(this.formId);
        if ( ! form) return;

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);
            await this.login(formData, form);
        });
    }
    
    async login(formData, form) {
        
        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onLoginBefore === 'function') {
            const stop = this.hooks.onLoginBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        /* Reset immediato degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            /* Controllo per gli errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Controllo per fallimento generico (es. Credenziali errate) */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* NUOVO: Controllo per Secondo Fattore Richiesto (2FA) */
            if (data.result === '2fa_required') {
                /* Effettua il reindirizzamento pulito alla pagina di verifica.
                   Il server saprà già quale utente validare leggendolo dalla sessione. */
                window.location.href = urlbase + 'backend/auth/verify';
                return;
            }

            /* Login avvenuto con successo */
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
            this.isSubmitting = false;
        }
    }
}

export class ResetPasswordManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded (Guest Mode) */
        this.formId = 'reset_password_form';
        this.url = urlbase + 'backend/auth/resetPassword';

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
        const form = document.getElementById(this.formId);
        if ( ! form) return;

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);
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

        /* Pulizia immediata degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

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

                document.getElementById(this.formId)?.reset();

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

export class SetPasswordManager {
    constructor(hooks = {}) {

        /* Parametri di configurazione della sezione dedicati e hardcoded (Guest Mode) */
        this.formId = 'set_password_form';
        this.url = urlbase + 'backend/auth/setPassword';

        this.hooks = Object.assign({
            onSetBefore: null,
            onSetAfter: null,
            onSetError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        const form = document.getElementById(this.formId);
        if ( ! form) return;

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            await this.savePassword(formData);
        });
    }
    
    async savePassword(formData) {
        
        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onSetBefore === 'function') {
            const stop = this.hooks.onSetBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        /* Pulizia immediata degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Controllo per gli errori di input (Validazione) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Controllo per fallimento logico (es. token scaduto o invalido) */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Successo Finale */
            if (data.result === true) {
                
                if (typeof this.hooks.onSetAfter === 'function') {
                    this.hooks.onSetAfter(data);
                }

                document.getElementById(this.formId)?.reset();

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {

            /* Qui finiscono solo gli errori di rete o i crash del server */
            if (typeof this.hooks.onSetError === 'function') {
                this.hooks.onSetError(error);
            }
            console.error("Errore SetPasswordManager:", error);
        } finally {
            
            /* Rilascia sempre il blocco */
            this.isSubmitting = false;
        }
    }
}

export class VerifyManager {
    constructor(hooks = {}) {

        /* Configurazione dedicata alla sezione di verifica OTP */
        this.formId = 'verify_form';
        this.url = urlbase + 'backend/auth/verify';

        this.hooks = Object.assign({
            onVerifyBefore: null,
            onVerifyAfter: null,
            onVerifyError: null
        }, hooks);

        /* Variabili di stato per la sicurezza */
        this.eventsBound = false;
        this.isSubmitting = false;
    }
    
    init() {
        const form = document.getElementById(this.formId);
        if ( ! form) return;

        /* Impedisce cloni dei listener */
        if (this.eventsBound) return;
        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);
            await this.verify(formData, form);
        });
    }
    
    async verify(formData, form) {
        
        /* Se c'è già una richiesta in corso, blocca */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (typeof this.hooks.onVerifyBefore === 'function') {
            const stop = this.hooks.onVerifyBefore(formData);
            if (stop === false) {
                this.isSubmitting = false;
                return;
            }
        }

        /* Reset immediato degli errori visivi */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            /* Controllo per gli errori di input (Validazione formale del codice) */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') handleValidationErrors(data.errors);
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Controllo per codice errato o tentativi falliti */
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') showAlert('danger', data.message);
                return;
            }

            /* Verifica superata con successo */
            if (data.result === true) {
                if (typeof this.hooks.onVerifyAfter === 'function') {
                    this.hooks.onVerifyAfter(data);
                }
                
                window.location.href = urlbase + 'backend/dashboard';
            }

        } catch (error) {

            /* Errori di rete o crash del server */
            if (typeof this.hooks.onVerifyError === 'function') {
                this.hooks.onVerifyError(error);
            }
            console.error("Errore VerifyManager:", error);
        } finally {
            this.isSubmitting = false;
        }
    }
}