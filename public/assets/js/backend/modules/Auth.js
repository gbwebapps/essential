/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, handleValidationErrors } from '../backend.js';

export class LoginManager {
    constructor(hooks = {}) {

        if (LoginManager.instance) {
            return LoginManager.instance;
        }

        LoginManager.instance = this;

        this.formId = 'login_form';
        this.url = urlbase + 'backend/auth/login';

        this.hooks = Object.assign({
            onLoginBefore: null,
            onLoginAfter: null,
            onLoginError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed || this.eventsBound) return;

        const form = document.getElementById(this.formId);
        if (!form) return;

        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            const formData = new FormData(form);

            await this.login(formData);
        }, {
            signal: this.eventsController.signal
        });
    }

    async login(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onLoginBefore === 'function') {
                const stop = await this.hooks.onLoginBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            document.querySelectorAll('[class^="error_"]').forEach(el => { el.innerHTML = '\u00A0'; });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === '2fa_required') {
                window.location.href = urlbase + 'backend/auth/verify';
                return;
            }

            if (data.result === true) {
                if (typeof this.hooks.onLoginAfter === 'function') {
                    await this.hooks.onLoginAfter(data);
                }

                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }

        } catch (error) {
            if (typeof this.hooks.onLoginError === 'function') {
                try {
                    await this.hooks.onLoginError(error);
                } catch (hookError) {
                    console.error('Errore hook LoginManager:', hookError);
                }
            }

            console.error('Errore LoginManager:', error);

        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (LoginManager.instance === this) {
            LoginManager.instance = null;
        }
    }
}

export class ResetPasswordManager {
    constructor(hooks = {}) {
        if (ResetPasswordManager.instance) {
            return ResetPasswordManager.instance;
        }

        ResetPasswordManager.instance = this;

        this.formId = 'reset_password_form';
        this.url = urlbase + 'backend/auth/resetPassword';

        this.hooks = Object.assign({
            onResetBefore: null,
            onResetAfter: null,
            onResetError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed || this.eventsBound) return;

        const form = document.getElementById(this.formId);
        if (!form) return;

        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            const formData = new FormData(form);

            await this.resetPassword(formData);
        }, {
            signal: this.eventsController.signal
        });
    }

    async resetPassword(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onResetBefore === 'function') {
                const stop = await this.hooks.onResetBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            document.querySelectorAll('[class^="error_"]').forEach(el => { el.innerHTML = '\u00A0'; });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                if (typeof this.hooks.onResetAfter === 'function') {
                    await this.hooks.onResetAfter(data);
                }

                if (this.destroyed) return;

                document.getElementById(this.formId)?.reset();

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onResetError === 'function') {
                try {
                    await this.hooks.onResetError(error);
                } catch (hookError) {
                    console.error('Errore hook ResetPasswordManager:', hookError);
                }
            }

            console.error('Errore ResetPasswordManager:', error);

        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (ResetPasswordManager.instance === this) {
            ResetPasswordManager.instance = null;
        }
    }
}

export class SetPasswordManager {
    constructor(hooks = {}) {
        if (SetPasswordManager.instance) {
            return SetPasswordManager.instance;
        }

        SetPasswordManager.instance = this;

        this.formId = 'set_password_form';
        this.url = urlbase + 'backend/auth/setPassword';

        this.hooks = Object.assign({
            onSetBefore: null,
            onSetAfter: null,
            onSetError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed || this.eventsBound) return;

        const form = document.getElementById(this.formId);
        if (!form) return;

        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            const formData = new FormData(form);

            await this.savePassword(formData);
        }, {
            signal: this.eventsController.signal
        });
    }

    async savePassword(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onSetBefore === 'function') {
                const stop = await this.hooks.onSetBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            document.querySelectorAll('[class^="error_"]').forEach(el => { el.innerHTML = '\u00A0'; });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                if (typeof this.hooks.onSetAfter === 'function') {
                    await this.hooks.onSetAfter(data);
                }

                if (this.destroyed) return;

                document.getElementById(this.formId)?.reset();

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            if (typeof this.hooks.onSetError === 'function') {
                try {
                    await this.hooks.onSetError(error);
                } catch (hookError) {
                    console.error('Errore hook SetPasswordManager:', hookError);
                }
            }

            console.error('Errore SetPasswordManager:', error);

        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (SetPasswordManager.instance === this) {
            SetPasswordManager.instance = null;
        }
    }
}

export class VerifyManager {
    constructor(hooks = {}) {
        if (VerifyManager.instance) {
            return VerifyManager.instance;
        }

        VerifyManager.instance = this;

        this.formId = 'verify_form';
        this.url = urlbase + 'backend/auth/verify';

        this.hooks = Object.assign({
            onVerifyBefore: null,
            onVerifyAfter: null,
            onVerifyError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed || this.eventsBound) return;

        const form = document.getElementById(this.formId);
        if (!form) return;

        this.eventsBound = true;

        form.addEventListener('submit', async e => {
            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            const formData = new FormData(form);

            await this.verify(formData);
        }, {
            signal: this.eventsController.signal
        });
    }

    async verify(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onVerifyBefore === 'function') {
                const stop = await this.hooks.onVerifyBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            document.querySelectorAll('[class^="error_"]').forEach(el => {
                el.innerHTML = '\u00A0';
            });

            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                if (typeof this.hooks.onVerifyAfter === 'function') {
                    await this.hooks.onVerifyAfter(data);
                }

                if (this.destroyed) return;

                window.location.href = data.redirect || urlbase + 'backend/dashboard';
            }

        } catch (error) {
            if (typeof this.hooks.onVerifyError === 'function') {
                try {
                    await this.hooks.onVerifyError(error);
                } catch (hookError) {
                    console.error('Errore hook VerifyManager:', hookError);
                }
            }

            console.error('Errore VerifyManager:', error);

        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (VerifyManager.instance === this) {
            VerifyManager.instance = null;
        }
    }
}
