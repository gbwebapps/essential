/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

// Recupera la password corrente e gestisce localmente il campo obbligatorio.
function readCurrentPassword(input) {
    if (!(input instanceof HTMLInputElement)) return null;

    if (input.value === '') {
        const message = input.dataset.requiredMessage || '';

        if (message && typeof handleValidationErrors === 'function') {
            handleValidationErrors({ currentPassword: message });
        }

        if (message && typeof showAlert === 'function') {
            showAlert('danger', message);
        }

        input.focus();
        return null;
    }

    return input.value;
}

// Esegue la verifica preliminare senza autorizzare direttamente l'operazione finale.
async function requestCurrentPasswordVerification(url, currentPassword) {
    const formData = new FormData();
    formData.append('currentPassword', currentPassword);

    const response = await apiFetch(url, {
        method: 'POST',
        body: formData
    });

    return response.json();
}

// Mostra in modo uniforme gli errori restituiti dalla verifica preliminare.
function handleCurrentPasswordVerification(data) {
    if (data.result === true) return true;

    if (data.errors && typeof handleValidationErrors === 'function') {
        handleValidationErrors(data.errors);
    }

    if (data.message && typeof showAlert === 'function') {
        showAlert('danger', data.message);
    }

    return false;
}

export class EditManager {
    constructor(hooks = {}) {
        if (EditManager.instance) {
            return EditManager.instance;
        }

        EditManager.instance = this;

        this.formSelector = '#account-edit';
        this.url = urlbase + 'backend/account/edit';
        this.refreshSelector = '#edit-refresh';
        this.containerId = 'edit-account-container';

        this.hooks = Object.assign({
            onEditBefore: null,
            onEditAfter: null,
            onEditError: null,
            onRefreshBefore: null,
            onRefreshAfter: null,
            onRefreshError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        const signal = this.eventsController.signal;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.edit(new FormData(formEl));
        }, { signal });

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.refreshSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            // Il blocco comprende anche la conferma.
            this.isSubmitting = true;

            try {
                const ok = await askConfirm(formEl.dataset.message);

                if (!ok || this.destroyed) return;

                await this.performRefresh(new FormData(formEl));
            } catch (error) {
                await this.handleError(error, 'refresh');
            } finally {
                this.isSubmitting = false;
            }
        }, { signal });
    }

    async edit(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onEditBefore === 'function') {
                const stop = await this.hooks.onEditBefore(formData);

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
                if (data.output) {
                    const container = document.getElementById(this.containerId);

                    if (container) {
                        await smoothReplace(container, data.output);
                    }
                }

                if (data.navBarTop) {
                    const navbar = document.getElementById('navbar-top-view');

                    if (navbar) {
                        await smoothReplace(navbar, data.navBarTop);
                    }
                }

                if (typeof this.hooks.onEditAfter === 'function') {
                    await this.hooks.onEditAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            await this.handleError(error, 'edit');
        } finally {
            this.isSubmitting = false;
        }
    }

    async refresh(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.performRefresh(formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async performRefresh(formData) {
        try {
            formData.set('action', 'refresh');

            if (typeof this.hooks.onRefreshBefore === 'function') {
                const stop = await this.hooks.onRefreshBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

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
                const container = document.getElementById(this.containerId);

                if (container && data.output) {
                    await smoothReplace(container, data.output);
                }

                if (typeof this.hooks.onRefreshAfter === 'function') {
                    await this.hooks.onRefreshAfter(data);
                }
            }
        } catch (error) {
            await this.handleError(error, 'refresh');
        }
    }

    async handleError(error, operation) {
        const hook = operation === 'refresh'
            ? this.hooks.onRefreshError
            : this.hooks.onEditError;

        if (typeof hook === 'function') {
            try {
                await hook(error);
            } catch (hookError) {
                console.error('Errore hook EditManager Account:', hookError);
            }
        }

        console.error(`Errore EditManager Account (${operation}):`, error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (EditManager.instance === this) {
            EditManager.instance = null;
        }
    }
}

export class GetPermissionsManager {

    constructor(hooks = {}) {
        if (GetPermissionsManager.instance) {
            return GetPermissionsManager.instance;
        }

        GetPermissionsManager.instance = this;

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
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.getPermissions(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    async getPermissions(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onPermissionsBefore === 'function') {
                const stop = await this.hooks.onPermissionsBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

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
                const permissionsEl = document.getElementById(
                    this.containerId
                );

                if (permissionsEl && data.output) {
                    await smoothReplace(permissionsEl, data.output);
                }

                if (typeof this.hooks.onPermissionsAfter === 'function') {
                    await this.hooks.onPermissionsAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onPermissionsError === 'function') {
                try {
                    await this.hooks.onPermissionsError(error);
                } catch (hookError) {
                    console.error(
                        'Errore hook GetPermissionsManager:',
                        hookError
                    );
                }
            }

            console.error('Errore GetPermissionsManager:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (GetPermissionsManager.instance === this) {
            GetPermissionsManager.instance = null;
        }
    }
}

export class GetTokensManager {

    constructor(hooks = {}) {
        if (GetTokensManager.instance) {
            return GetTokensManager.instance;
        }

        GetTokensManager.instance = this;

        this.url = urlbase + 'backend/account/tokens';
        this.formSelector = '#getTokens';
        this.containerId = 'tokens-account-container';

        this.hooks = Object.assign({
            onTokensBefore: null,
            onTokensAfter: null,
            onTokensError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.getTokens(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    async getTokens(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            if (typeof this.hooks.onTokensBefore === 'function') {
                const stop = await this.hooks.onTokensBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

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
                const tokensEl = document.getElementById(this.containerId);

                if (tokensEl && data.output) {
                    await smoothReplace(tokensEl, data.output);
                }

                if (typeof this.hooks.onTokensAfter === 'function') {
                    await this.hooks.onTokensAfter(data);
                }
            }
        } catch (error) {
            if (typeof this.hooks.onTokensError === 'function') {
                try {
                    await this.hooks.onTokensError(error);
                } catch (hookError) {
                    console.error(
                        'Errore hook GetTokensManager:',
                        hookError
                    );
                }
            }

            console.error('Errore GetTokensManager:', error);
        } finally {
            this.isSubmitting = false;
        }
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (GetTokensManager.instance === this) {
            GetTokensManager.instance = null;
        }
    }
}

export class DeleteTokenManager {
    constructor(hooks = {}) {
        if (DeleteTokenManager.instance) {
            return DeleteTokenManager.instance;
        }

        DeleteTokenManager.instance = this;

        this.url = urlbase + 'backend/account/deleteToken';
        this.containerId = 'tokens-account-container';

        this.hooks = Object.assign({
            onDeleteTokenBefore: null,
            onDeleteTokenAfter: null,
            onDeleteTokenError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.destroyed = false;

        this.eventsController = new AbortController();
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches('.deleteToken')) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            // Protegge anche la fase di conferma.
            this.isSubmitting = true;

            try {
                const ok = await askConfirm(formEl.dataset.message);

                if (!ok || this.destroyed) return;

                const formData = new FormData(formEl);

                await this.performDelete(formData);
            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async deleteToken(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.performDelete(formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async performDelete(formData) {
        try {
            if (typeof this.hooks.onDeleteTokenBefore === 'function') {
                const stop = await this.hooks.onDeleteTokenBefore(formData);

                if (stop === false) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

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
                const container = document.getElementById(
                    this.containerId
                );

                if (container && data.output) {
                    await smoothReplace(container, data.output);
                }

                if (typeof this.hooks.onDeleteTokenAfter === 'function') {
                    await this.hooks.onDeleteTokenAfter(data);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            await this.handleError(error);
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onDeleteTokenError === 'function') {
            try {
                await this.hooks.onDeleteTokenError(error);
            } catch (hookError) {
                console.error(
                    'Errore hook DeleteTokenManager:',
                    hookError
                );
            }
        }

        console.error('Errore DeleteTokenManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (DeleteTokenManager.instance === this) {
            DeleteTokenManager.instance = null;
        }
    }
}

export class ResetPasswordManager {
    constructor(hooks = {}) {

        if (ResetPasswordManager.instance) {
            return ResetPasswordManager.instance;
        }

        ResetPasswordManager.instance = this;

        this.url = urlbase + 'backend/account/resetPassword';
        this.verifyPasswordUrl = urlbase + 'backend/account/verifyCurrentPassword';
        this.formSelector = '#getResetPassword';
        this.containerId = 'reset-account-container';

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
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches(this.formSelector)) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            // Protegge anche l'eventuale fase di conferma.
            this.isSubmitting = true;

            try {
                const formData = new FormData(formEl);
                const passwordInput = formEl.querySelector('[name="currentPassword"]');
                const currentPassword = readCurrentPassword(passwordInput);

                if (currentPassword === null) return;

                const verification = await requestCurrentPasswordVerification(
                    this.verifyPasswordUrl,
                    currentPassword
                );

                if (!handleCurrentPasswordVerification(verification) || this.destroyed) {
                    return;
                }

                const message = formEl.dataset.message;

                if (message) {
                    const ok = await askConfirm(message);

                    if (!ok || this.destroyed) return;
                }

                await this.performReset(formData);
            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async resetPassword(formData) {
        if (this.destroyed || this.isSubmitting) return;

        this.isSubmitting = true;

        try {
            await this.performReset(formData);
        } finally {
            this.isSubmitting = false;
        }
    }

    async performReset(formData) {
        try {
            if (typeof this.hooks.onResetBefore === 'function') {
                const stop = await this.hooks.onResetBefore(formData);

                if (stop === false || this.destroyed) return;
            }

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            // Errori di validazione.
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            // Fallimento logico.
            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            // Operazione registrata, ma email non inviata.
            if (data.result === 'db_committed_no_email') {
                const container = document.getElementById(
                    this.containerId
                );

                if (container && data.output) {
                    await smoothReplace(container, data.output);
                }

                if (this.destroyed) return;

                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            // Operazione completata correttamente.
            if (data.result === true) {
                const container = document.getElementById(
                    this.containerId
                );

                if (container && data.output) {
                    await smoothReplace(container, data.output);
                }

                if (this.destroyed) return;

                if (typeof this.hooks.onResetAfter === 'function') {
                    await this.hooks.onResetAfter(data);
                }

                if (this.destroyed) return;

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }
        } catch (error) {
            await this.handleError(error);
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onResetError === 'function') {
            try {
                await this.hooks.onResetError(error);
            } catch (hookError) {
                console.error(
                    'Errore hook ResetPasswordManager:',
                    hookError
                );
            }
        }

        console.error('Errore ResetPasswordManager:', error);
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

export class SecurityManager {
    constructor(hooks = {}) {

        if (SecurityManager.instance) {
            return SecurityManager.instance;
        }

        SecurityManager.instance = this;

        this.wrapperId = 'totp-setup-wrapper';

        this.saveBasicUrl = urlbase + 'backend/account/saveBasicMethod';
        this.setupTotpUrl = urlbase + 'backend/account/setupTotp';
        this.confirmTotpUrl = urlbase + 'backend/account/confirmTotp';
        this.cancelTotpUrl = urlbase + 'backend/account/cancelTotpSetup';
        this.verifyPasswordUrl = urlbase + 'backend/account/verifyCurrentPassword';

        this.hooks = Object.assign({
            onSecurityBefore: null,
            onSecurityAfter: null,
            onSecurityError: null
        }, hooks);

        this.eventsBound = false;
        this.isSubmitting = false;
        this.setupPending = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        const activeRadio = document.querySelector(
            '.twofactor-method-trigger:checked'
        );

        this.currentActiveMethod = activeRadio
            ? activeRadio.value
            : 'none';
    }

    init() {
        if (this.destroyed) return;

        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound || this.destroyed) return;

        this.eventsBound = true;

        // 1. Selezione del metodo 2FA.
        document.addEventListener('click', async e => {
            const radio = e.target;

            if (!(radio instanceof HTMLInputElement)) return;
            if (!radio.matches('.twofactor-method-trigger')) return;
            if (radio.disabled) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            const method = radio.value;

            // Metodo già attivo.
            if (method === this.currentActiveMethod) {
                if (!this.setupPending) {
                    const wrapper = document.getElementById(this.wrapperId);

                    if (wrapper) {
                        await smoothReplace(wrapper, '');
                    }

                    this.restoreActiveMethod();
                }

                return;
            }

            await this.runExclusive(async () => {
                const currentPassword = readCurrentPassword(
                    document.getElementById('securityCurrentPassword')
                );

                if (currentPassword === null) {
                    this.restoreActiveMethod();
                    return false;
                }

                const verification = await requestCurrentPasswordVerification(
                    this.verifyPasswordUrl,
                    currentPassword
                );

                if (!handleCurrentPasswordVerification(verification) || this.destroyed) {
                    this.restoreActiveMethod();
                    return false;
                }

                const message = radio.dataset.message;

                if (message) {
                    const confirmed = await askConfirm(message);

                    if (!confirmed || this.destroyed) {
                        this.restoreActiveMethod();
                        return false;
                    }
                }

                if (radio.dataset.requiresSetup === 'true') {
                    return this.performTotpSetupRequest(currentPassword);
                }

                return this.performSaveBasicMethod(method, currentPassword);
            });
        }, {
            signal: this.eventsController.signal
        });

        // 2. Annullamento del setup TOTP.
        document.addEventListener('click', async e => {
            if (!(e.target instanceof Element)) return;
            if (!e.target.closest('#cancel-setup-btn')) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.runExclusive(async () => {
                const response = await apiFetch(this.cancelTotpUrl, {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.result !== true) {
                    this.handleResponseFailure(data);
                    return false;
                }

                this.setupPending = false;
                this.clearCurrentPassword();

                try {
                    const wrapper = document.getElementById(this.wrapperId);

                    if (wrapper) {
                        await smoothReplace(wrapper, '');
                    }
                } finally {
                    this.unlockMethods();
                    this.restoreActiveMethod();
                }

                return true;
            });
        }, {
            signal: this.eventsController.signal
        });

        // 3. Conferma del codice TOTP.
        document.addEventListener('submit', async e => {
            const formEl = e.target;

            if (!(formEl instanceof HTMLFormElement)) return;
            if (!formEl.matches('#confirm-totp-form')) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            await this.initTotpConfirmation(new FormData(formEl));
        }, {
            signal: this.eventsController.signal
        });
    }

    // Esegue una sola operazione alla volta.
    async runExclusive(callback) {
        if (this.destroyed || this.isSubmitting) return false;

        this.isSubmitting = true;

        try {
            return await callback();
        } catch (error) {
            await this.handleError(error);
            return false;
        } finally {
            this.isSubmitting = false;
        }
    }

    // Hook eseguito prima delle richieste.
    async runBeforeHook(formData) {
        if (typeof this.hooks.onSecurityBefore !== 'function') {
            return true;
        }

        const result = await this.hooks.onSecurityBefore(formData);

        return result !== false && !this.destroyed;
    }

    // Hook eseguito dopo un'operazione riuscita.
    async runAfterHook(data) {
        if (typeof this.hooks.onSecurityAfter === 'function') {
            await this.hooks.onSecurityAfter(data);
        }
    }

    // Errori di validazione e fallimenti logici.
    handleResponseFailure(data) {
        if (data.errors && typeof handleValidationErrors === 'function') {
            handleValidationErrors(data.errors);
        }

        if (data.message && typeof showAlert === 'function') {
            showAlert('danger', data.message);
        }
    }

    // Salvataggio pubblico dei metodi diretti.
    async saveBasicMethod(method) {
        return this.runExclusive(
            () => this.performSaveBasicMethod(method)
        );
    }

    async performSaveBasicMethod(method, currentPassword = this.getCurrentPassword()) {
        const formData = new FormData();
        formData.append('twoFactorMethod', method);
        formData.append('currentPassword', currentPassword);

        const proceed = await this.runBeforeHook(formData);

        if (!proceed || this.destroyed) return false;

        const response = await apiFetch(this.saveBasicUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (this.destroyed) return false;

        if (data.errors || data.result === false) {
            this.handleResponseFailure(data);
            return false;
        }

        if (data.result !== true) return false;

        // Il server ha confermato il nuovo metodo.
        this.currentActiveMethod = method;
        this.setupPending = false;
        this.clearCurrentPassword();

        // Aggiornamento immediato dello stato grafico.
        this.unlockMethods();
        this.restoreActiveMethod();

        const wrapper = document.getElementById(this.wrapperId);

        if (wrapper) {
            await smoothReplace(wrapper, '');
        }

        if (this.destroyed) return true;

        if (data.message && typeof showAlert === 'function') {
            showAlert('success', data.message);
        }

        await this.runAfterHook(data);

        return true;
    }

    // Avvio pubblico del setup TOTP.
    async requestTotpSetup() {
        return this.runExclusive(
            () => this.performTotpSetupRequest(this.getCurrentPassword())
        );
    }

    // Esegue il setup TOTP mantenendo coerente lo stato grafico durante la richiesta.
    async performTotpSetupRequest(currentPassword) {
        this.setupPending = false;
        this.lockMethods();

        try {
            const wrapper = document.getElementById(this.wrapperId);

            if (wrapper) {
                await smoothReplace(wrapper, '');
            }

            if (this.destroyed) return false;

            return await this.performTotpSetup(currentPassword);
        } finally {
            // Il blocco rimane soltanto se il setup è stato caricato.
            if (!this.setupPending && !this.destroyed) {
                this.unlockMethods();
                this.restoreActiveMethod();
            }
        }
    }

    async performTotpSetup(currentPassword = this.getCurrentPassword()) {
        const formData = new FormData();
        formData.append('currentPassword', currentPassword);

        const proceed = await this.runBeforeHook(formData);

        if (!proceed || this.destroyed) return false;

        const response = await apiFetch(this.setupTotpUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (this.destroyed) return false;

        if (data.errors || data.result === false) {
            this.handleResponseFailure(data);
            return false;
        }

        if (data.result !== true) return false;

        const wrapper = document.getElementById(this.wrapperId);

        // Un setup senza contenuto non è utilizzabile.
        if (!wrapper || !data.output) {
            throw new Error(
                'SecurityManager: contenitore o output TOTP mancante.'
            );
        }

        await smoothReplace(wrapper, data.output);

        if (this.destroyed) return false;

        // Ora il setup è disponibile per conferma o annullamento.
        this.setupPending = true;
        this.clearCurrentPassword();

        await this.runAfterHook(data);

        return true;
    }

    // Recupera la password corrente richiesta per le modifiche sensibili.
    getCurrentPassword() {
        const input = document.getElementById('securityCurrentPassword');

        return input instanceof HTMLInputElement ? input.value : '';
    }

    // Rimuove la password dal DOM dopo una modifica completata.
    clearCurrentPassword() {
        const input = document.getElementById('securityCurrentPassword');

        if (input instanceof HTMLInputElement) {
            input.value = '';
        }
    }

    // Conferma pubblica del TOTP.
    async initTotpConfirmation(formData) {
        return this.runExclusive(
            () => this.performTotpConfirmation(formData)
        );
    }

    async performTotpConfirmation(formData) {
        const proceed = await this.runBeforeHook(formData);

        if (!proceed || this.destroyed) return false;

        const response = await apiFetch(this.confirmTotpUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (this.destroyed) return false;

        if (data.errors || data.result === false) {
            this.handleResponseFailure(data);

            // Il setup resta aperto per correggere il codice.
            return false;
        }

        if (data.result !== true) return false;

        // Il server ha confermato l'attivazione.
        this.currentActiveMethod = 'totp';
        this.setupPending = false;

        this.unlockMethods();
        this.restoreActiveMethod();

        const wrapper = document.getElementById(this.wrapperId);

        if (wrapper) {
            await smoothReplace(wrapper, '');
        }

        if (this.destroyed) return true;

        if (data.message && typeof showAlert === 'function') {
            showAlert('success', data.message);
        }

        await this.runAfterHook(data);

        return true;
    }

    // Mantiene la selezione coerente con il metodo salvato.
    restoreActiveMethod() {
        if (this.destroyed) return;

        document.querySelectorAll('.twofactor-method-trigger').forEach(radio => {
            radio.checked = radio.value === this.currentActiveMethod;
        });

        this.updateVisualBorder(this.currentActiveMethod);
    }

    lockMethods() {
        if (this.destroyed) return;

        document.querySelectorAll('.twofactor-method-trigger').forEach(radio => {
            radio.disabled = true;

            const card = radio.closest('.card');

            if (card) {
                card.classList.add('opacity-50', 'pe-none');
            }
        });
    }

    unlockMethods() {
        document.querySelectorAll('.twofactor-method-trigger').forEach(radio => {
            radio.disabled = false;

            const card = radio.closest('.card');

            if (card) {
                card.classList.remove('opacity-50', 'pe-none');
            }
        });
    }

    updateVisualBorder(newMethod) {
        document.querySelectorAll('.twofactor-method-trigger').forEach(radio => {
            const card = radio.closest('.card');

            if (card) {
                card.classList.remove('border-primary', 'bg-light');
            }
        });

        const activeRadio = Array.from(
            document.querySelectorAll('.twofactor-method-trigger')
        ).find(radio => radio.value === newMethod);

        if (activeRadio) {
            const card = activeRadio.closest('.card');

            if (card) {
                card.classList.add('border-primary', 'bg-light');
            }
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onSecurityError === 'function') {
            try {
                await this.hooks.onSecurityError(error);
            } catch (hookError) {
                console.error(
                    'Errore hook SecurityManager:',
                    hookError
                );
            }
        }

        console.error('Errore SecurityManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();

        this.setupPending = false;
        this.eventsBound = false;

        this.unlockMethods();

        if (SecurityManager.instance === this) {
            SecurityManager.instance = null;
        }
    }
}
