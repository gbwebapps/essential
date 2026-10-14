/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace } from '../backend.js';

export class ResetPasswordManager {

    constructor(hooks = {}) {
        if (ResetPasswordManager.instance) {
            return ResetPasswordManager.instance;
        }

        ResetPasswordManager.instance = this;

        this.formSelector = '.resetAdmin';
        this.url = `${urlbase}backend/admins/resetPassword`;

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

            this.isSubmitting = true;

            try {
                const message = formEl.dataset.message;

                if (message) {
                    const ok = await askConfirm(message);

                    if (!ok || this.destroyed) return;
                }

                await this.resetPassword(new FormData(formEl), formEl, true);

            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async resetPassword(formData, formEl, alreadySubmitting = false) {
        if (this.destroyed || (this.isSubmitting && !alreadySubmitting)) return;

        if (!alreadySubmitting) {
            this.isSubmitting = true;
        }

        try {
            if (typeof this.hooks.onResetBefore === 'function') {
                const stop = await this.hooks.onResetBefore(formData);

                if (stop === false) return;
            }

            if (this.destroyed) return;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

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

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            await this.handleError(error);
        } finally {
            if (!alreadySubmitting) {
                this.isSubmitting = false;
            }
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

export class ChangeGroupManager {

    constructor(hooks = {}) {
        if (ChangeGroupManager.instance) {
            return ChangeGroupManager.instance;
        }

        ChangeGroupManager.instance = this;

        this.url = urlbase + 'backend/admins/changeGroup';

        this.hooks = Object.assign({
            onGroupBefore: null,
            onGroupAfter: null,
            onGroupError: null
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

        /* Memorizzo il valore iniziale al caricamento della pagina, se presente */
        const initialGroup = document.getElementById('group_id');
        let previousGroupId = initialGroup ? initialGroup.value : '';

        /* Event delegation per le viste dinamiche */
        document.addEventListener('change', async e => {
            const selectEl = e.target;

            if (!(selectEl instanceof HTMLSelectElement)) return;
            if (!selectEl.matches('#group_id')) return;

            if (this.isSubmitting || this.destroyed) return;

            this.isSubmitting = true;

            try {
                const message = selectEl.dataset.message;
                const ok = await askConfirm(message);

                if (this.destroyed) return;

                if (!ok) {
                    selectEl.value = previousGroupId;
                    return;
                }

                const groupId = selectEl.value;
                const uuidEl = document.getElementById('uuid');
                const uuid = uuidEl ? uuidEl.value : '';

                if (!groupId || !uuid) return;

                const formData = new FormData();
                formData.append('group_id', groupId);
                formData.append('uuid', uuid);

                const changed = await this.changeGroup(formData, true);

                if (this.destroyed) return;

                if (changed === true) {
                    previousGroupId = groupId;
                } else {
                    selectEl.value = previousGroupId;
                }

            } catch (error) {
                selectEl.value = previousGroupId;
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async changeGroup(formData, alreadySubmitting = false) {
        if (this.destroyed || (this.isSubmitting && !alreadySubmitting)) {
            return false;
        }

        if (!alreadySubmitting) {
            this.isSubmitting = true;
        }

        try {
            if (typeof this.hooks.onGroupBefore === 'function') {
                const stop = await this.hooks.onGroupBefore(formData);

                if (stop === false) return false;
            }

            if (this.destroyed) return false;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return false;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return false;
            }

            if (data.result === true) {
                /* Aggiorno solo il contenitore dei permessi parziali */
                const permissionsEl = document.getElementById('permissions');

                if (permissionsEl && data.output) {
                    smoothReplace(permissionsEl, data.output);
                }

                if (typeof this.hooks.onGroupAfter === 'function') {
                    await this.hooks.onGroupAfter(data);
                }

                return true;
            }

            return false;

        } catch (error) {
            await this.handleError(error);
            return false;
        } finally {
            if (!alreadySubmitting) {
                this.isSubmitting = false;
            }
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onGroupError === 'function') {
            try {
                await this.hooks.onGroupError(error);
            } catch (hookError) {
                console.error(
                    'Errore hook ChangeGroupManager:',
                    hookError
                );
            }
        }

        console.error('Errore ChangeGroupManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (ChangeGroupManager.instance === this) {
            ChangeGroupManager.instance = null;
        }
    }
}

export class GetPermissionsManager {

    constructor(hooks = {}) {
        if (GetPermissionsManager.instance) {
            return GetPermissionsManager.instance;
        }

        GetPermissionsManager.instance = this;

        this.url = urlbase + 'backend/admins/getPermissions';
        this.formSelector = '#getPermissions';

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

            if (this.destroyed) return;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                const permissionsEl = document.getElementById('permissions');

                if (permissionsEl && data.output) {
                    smoothReplace(permissionsEl, data.output);
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

export class ChangePermissionManager {

    constructor(hooks = {}) {
        if (ChangePermissionManager.instance) {
            return ChangePermissionManager.instance;
        }

        ChangePermissionManager.instance = this;

        this.url = urlbase + 'backend/admins/changePermission';

        this.hooks = Object.assign({
            onPermissionBefore: null,
            onPermissionAfter: null,
            onPermissionError: null
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
            if (!formEl.matches('.changePermission')) return;

            e.preventDefault();

            if (this.isSubmitting || this.destroyed) return;

            this.isSubmitting = true;

            try {
                const message = formEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.destroyed) return;

                await this.changePermission(new FormData(formEl), true);

            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async changePermission(formData, alreadySubmitting = false) {
        if (this.destroyed || (this.isSubmitting && !alreadySubmitting)) return;

        if (!alreadySubmitting) {
            this.isSubmitting = true;
        }

        try {
            if (typeof this.hooks.onPermissionBefore === 'function') {
                const stop = await this.hooks.onPermissionBefore(formData);

                if (stop === false) return;
            }

            if (this.destroyed) return;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

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
                    await this.hooks.onPermissionAfter(data);
                }

                if (this.destroyed) return;

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            await this.handleError(error);
        } finally {
            if (!alreadySubmitting) {
                this.isSubmitting = false;
            }
        }
    }

    async handleError(error) {
        if (typeof this.hooks.onPermissionError === 'function') {
            try {
                await this.hooks.onPermissionError(error);
            } catch (hookError) {
                console.error(
                    'Errore hook ChangePermissionManager:',
                    hookError
                );
            }
        }

        console.error('Errore ChangePermissionManager:', error);
    }

    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;
        this.eventsController.abort();
        this.eventsBound = false;

        if (ChangePermissionManager.instance === this) {
            ChangePermissionManager.instance = null;
        }
    }
}

export class GetTokensManager {

    constructor(hooks = {}) {
        if (GetTokensManager.instance) {
            return GetTokensManager.instance;
        }

        GetTokensManager.instance = this;

        this.url = urlbase + 'backend/admins/getTokens';
        this.formSelector = '#getTokens';

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

            if (this.destroyed) return;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                const tokensEl = document.getElementById('tokens');

                if (tokensEl && data.output) {
                    smoothReplace(tokensEl, data.output);
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

        this.url = urlbase + 'backend/admins/deleteToken';

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

            this.isSubmitting = true;

            try {
                const message = formEl.dataset.message;
                const ok = await askConfirm(message);

                if (!ok || this.destroyed) return;

                await this.deleteToken(new FormData(formEl), true);

            } catch (error) {
                await this.handleError(error);
            } finally {
                this.isSubmitting = false;
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    async deleteToken(formData, alreadySubmitting = false) {
        if (this.destroyed || (this.isSubmitting && !alreadySubmitting)) return;

        if (!alreadySubmitting) {
            this.isSubmitting = true;
        }

        try {
            if (typeof this.hooks.onDeleteTokenBefore === 'function') {
                const stop = await this.hooks.onDeleteTokenBefore(formData);

                if (stop === false) return;
            }

            if (this.destroyed) return;

            const response = await apiFetch(this.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (this.destroyed) return;

            if (data.result === false) {
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }

                return;
            }

            if (data.result === true) {
                const tokensEl = document.getElementById('tokens');

                if (tokensEl && data.tokensView) {
                    smoothReplace(tokensEl, data.tokensView);
                }

                if (typeof this.hooks.onDeleteTokenAfter === 'function') {
                    await this.hooks.onDeleteTokenAfter(data);
                }

                if (this.destroyed) return;

                if (data.message && typeof showAlert === 'function') {
                    showAlert('success', data.message);
                }
            }

        } catch (error) {
            await this.handleError(error);
        } finally {
            if (!alreadySubmitting) {
                this.isSubmitting = false;
            }
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