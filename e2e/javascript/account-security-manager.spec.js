
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/account/edit');
const modulePath = appUrl('/assets/js/backend/modules/Account.js');

test('SecurityManager aggiorna il metodo 2FA solo dopo il successo del server', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    page.on('console', message => {
        if (message.type() === 'error') {
            console.log('[BROWSER ERROR]', message.text());
        }
    });

    page.on('pageerror', error => {
        console.log('[PAGE ERROR]', error.message);
    });

    const requests = [];
    let releaseRequest;
    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/account/saveBasicMethod', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        const body = route.request().postData() || '';

        const method = body.includes('twoFactorMethod')
            ? /name="twoFactorMethod"\r?\n\r?\n([^\r\n]+)/.exec(body)?.[1]
            : undefined;

        requests.push(method);

        if (requests.length === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true
            })
        });
    });

    const action = page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        // Ambiente controllato per non dipendere dal metodo
        // 2FA realmente attivo sull'account.
        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <div class="card">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        const manager = new SecurityManager();
        manager.init();

        const initialMethod = manager.currentActiveMethod;

        const firstResult = await manager.saveBasicMethod('email');

        const emailSelected = document.getElementById('method-email').checked;
        const emailBorder = document.getElementById('method-email')
            .closest('.card')
            .classList.contains('app-border-accent');

        const secondResult = await manager.saveBasicMethod('none');

        const noneSelected = document.getElementById('method-none').checked;
        const noneBorder = document.getElementById('method-none')
            .closest('.card')
            .classList.contains('app-border-accent');

        return {
            initialMethod,
            firstResult,
            emailSelected,
            emailBorder,
            secondResult,
            noneSelected,
            noneBorder,
            currentActiveMethod: manager.currentActiveMethod,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    let result;

    try {
        await expect.poll(() => requests.length).toBe(1);
        await expect(page.locator('#method-none')).toBeChecked();
        await expect(page.locator('#method-email')).not.toBeChecked();
        expect(await page.evaluate(async path => {
            const { SecurityManager } = await import(path);
            return {
                activeMethod: SecurityManager.instance.currentActiveMethod,
                isSubmitting: SecurityManager.instance.isSubmitting
            };
        }, modulePath)).toEqual({ activeMethod: 'none', isSubmitting: true });

        releaseRequest();
        result = await action;
    } finally {
        releaseRequest();
    }

    expect(requests).toEqual(['email', 'none']);

    expect(result).toEqual({
        initialMethod: 'none',
        firstResult: true,
        emailSelected: true,
        emailBorder: true,
        secondResult: true,
        noneSelected: true,
        noneBorder: true,
        currentActiveMethod: 'none',
        isSubmitting: false
    });
});


test('SecurityManager gestisce conferma e operazioni concorrenti', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let verificationRequests = 0;
    let releaseRequest;

    const blockedRequest = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/account/verifyCurrentPassword', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        verificationRequests++;

        const valid = verificationRequests > 1;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(valid
                ? { result: true }
                : {
                    result: false,
                    errors: { currentPassword: 'Password corrente non valida' },
                    message: 'Password corrente non valida'
                }
            )
        });
    });

    await page.route('**/backend/account/saveBasicMethod', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        // Manteniamo la prima richiesta in sospeso.
        await blockedRequest;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true
            })
        });
    });

    await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        const oldWrapper = document.getElementById('totp-setup-wrapper');
        oldWrapper?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <input
                type="password"
                id="securityCurrentPassword"
                data-required-message="Inserisci la password corrente."
            >
            <div class="error_currentPassword"></div>

            <div class="card">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                    data-message="Confermi il cambio del metodo 2FA?"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        const manager = new SecurityManager();
        manager.init();
    }, modulePath);

    const modal = page.locator('#globalConfirmModal');
    const emailRadio = page.locator('#method-email');
    const passwordInput = page.locator('#securityCurrentPassword');

    // Campo vuoto: nessuna chiamata server e nessun modale.
    await emailRadio.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    });

    await expect(modal).toBeHidden();
    expect(verificationRequests).toBe(0);
    expect(requests).toBe(0);
    await expect(page.locator('#e2eSecurityFixture .error_currentPassword')).toHaveText('\u00A0');
    await expect(page.locator('#alert-container [role="alert"]')).toContainText('Inserisci la password corrente.');
    await expect(page.locator('#method-none')).toBeChecked();

    // Password errata: alert immediato e nessun modale.
    await passwordInput.fill('PasswordErrata!');
    await emailRadio.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    });

    await expect.poll(() => verificationRequests).toBe(1);
    await expect(modal).toBeHidden();
    expect(requests).toBe(0);
    await expect(page.locator('#e2eSecurityFixture .error_currentPassword')).toHaveText('Password corrente non valida');
    await expect(page.locator('#alert-container [role="alert"]')).toContainText('Password corrente non valida');
    await expect(page.locator('#method-none')).toBeChecked();

    await passwordInput.fill('PasswordCorretta!');

    // Prima apertura: due click consecutivi.
    await emailRadio.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    });

    await expect(modal).toBeVisible();

    const blockedDuringConfirmation = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);
        return SecurityManager.instance.isSubmitting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);
    expect(verificationRequests).toBe(2);

    // Annullamento: nessun salvataggio.
    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SecurityManager } = await import(path);
            return SecurityManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(0);
    await expect(page.locator('#method-none')).toBeChecked();

    // Conferma positiva.
    await emailRadio.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    });

    await expect(modal).toBeVisible();
    await modal.locator('.btn-ok').click();
    expect(verificationRequests).toBe(3);

    try {
        await expect.poll(() => requests).toBe(1);

        // Durante il POST, ulteriori click non devono produrre richieste.
        await emailRadio.evaluate(element => {
            element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        });

        expect(requests).toBe(1);

        releaseRequest();

        await expect.poll(async () => {
            return page.evaluate(async path => {
                const { SecurityManager } = await import(path);
                return SecurityManager.instance.isSubmitting;
            }, modulePath);
        }).toBe(false);

        await expect(page.locator('#method-email')).toBeChecked();
        expect(requests).toBe(1);
    } finally {
        releaseRequest();
    }
});


test('SecurityManager preserva il metodo attivo dopo errori di salvataggio', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    const submittedMethods = [];

    await page.route('**/backend/account/saveBasicMethod', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const body = route.request().postData() || '';
        const method = /name="twoFactorMethod"\r?\n\r?\n([^\r\n]+)/.exec(body)?.[1];

        submittedMethods.push(method);

        if (requests === 3) {
            await route.abort('failed');
            return;
        }

        const response = requests === 1
            ? {
                result: false,
                errors: { twoFactorMethod: 'Metodo non valido' },
                message: 'Errore di validazione'
            }
            : requests === 2
                ? {
                    result: false,
                    message: 'Impossibile modificare il metodo 2FA'
                }
                : {
                    result: true
                };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    const result = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        let errors = 0;
        let afterCalls = 0;

        const manager = new SecurityManager({
            async onSecurityError(error) {
                if (error) errors++;
            },

            async onSecurityAfter() {
                afterCalls++;
            }
        });

        manager.init();

        const attempts = [];

        for (let i = 0; i < 4; i++) {
            const success = await manager.saveBasicMethod('email');

            attempts.push({
                success,
                activeMethod: manager.currentActiveMethod,
                noneChecked: document.getElementById('method-none').checked,
                emailChecked: document.getElementById('method-email').checked,
                isSubmitting: manager.isSubmitting
            });
        }

        return {
            attempts,
            errors,
            afterCalls
        };
    }, modulePath);

    expect(requests).toBe(4);
    expect(submittedMethods).toEqual(['email', 'email', 'email', 'email']);

    for (let i = 0; i < 3; i++) {
        expect(result.attempts[i]).toEqual({
            success: false,
            activeMethod: 'none',
            noneChecked: true,
            emailChecked: false,
            isSubmitting: false
        });
    }

    expect(result.attempts[3]).toEqual({
        success: true,
        activeMethod: 'email',
        noneChecked: false,
        emailChecked: true,
        isSubmitting: false
    });

    expect(result.errors).toBe(1);
    expect(result.afterCalls).toBe(1);
});


test('SecurityManager inizializza il setup TOTP e blocca gli altri metodi', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/setupTotp', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <form id="confirm-totp-form">
                        <input type="text" name="code">
                        <button type="submit">Conferma</button>
                        <button type="button" id="cancel-setup-btn">Annulla</button>
                    </form>
                `
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-totp"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="totp"
                    data-requires-setup="true"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new SecurityManager({
            async onSecurityBefore() {
                await Promise.resolve();
                beforeCalls++;
            },

            async onSecurityAfter() {
                await Promise.resolve();
                afterCalls++;
            }
        });

        manager.init();

        const success = await manager.requestTotpSetup();

        const radios = [...document.querySelectorAll(
            '.twofactor-method-trigger'
        )];

        return {
            success,
            currentActiveMethod: manager.currentActiveMethod,
            setupPending: manager.setupPending,
            isSubmitting: manager.isSubmitting,
            noneChecked: document.getElementById('method-none').checked,
            totpChecked: document.getElementById('method-totp').checked,
            allDisabled: radios.every(radio => radio.disabled),
            allLocked: radios.every(radio =>
                radio.closest('.card').classList.contains('pe-none')
            ),
            beforeCalls,
            afterCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        success: true,
        currentActiveMethod: 'none',
        setupPending: true,
        isSubmitting: false,
        noneChecked: true,
        totpChecked: false,
        allDisabled: true,
        allLocked: true,
        beforeCalls: 1,
        afterCalls: 1
    });

    await expect(page.locator('#confirm-totp-form')).toBeVisible();
    await expect(page.locator('#cancel-setup-btn')).toBeVisible();
});


test('SecurityManager ripristina i metodi dopo annullamento ed errore TOTP', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let cancellations = 0;

    await page.route('**/backend/account/cancelTotpSetup', async route => {
        expect(route.request().method()).toBe('POST');
        cancellations++;
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    await page.route('**/backend/account/verifyCurrentPassword', async route => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    await page.route('**/backend/account/setupTotp', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const response = requests === 1
            ? {
                result: true,
                output: `
                    <form id="confirm-totp-form">
                        <input type="text" name="code">
                        <button type="submit">Conferma</button>
                        <button type="button" id="cancel-setup-btn">Annulla</button>
                    </form>
                `
            }
            : {
                result: false,
                message: 'Impossibile inizializzare il TOTP'
            };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <input
                type="password"
                id="securityCurrentPassword"
                value="PasswordCorretta!"
                data-required-message="Inserisci la password corrente."
            >

            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-totp"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="totp"
                    data-requires-setup="true"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        const manager = new SecurityManager();
        manager.init();
    }, modulePath);

    const readState = async () => {
        return page.evaluate(async path => {
            const { SecurityManager } = await import(path);
            const manager = SecurityManager.instance;

            const radios = [...document.querySelectorAll(
                '.twofactor-method-trigger'
            )];

            return {
                currentActiveMethod: manager.currentActiveMethod,
                setupPending: manager.setupPending,
                isSubmitting: manager.isSubmitting,
                noneChecked: document.getElementById('method-none').checked,
                totpChecked: document.getElementById('method-totp').checked,
                allEnabled: radios.every(radio => !radio.disabled),
                allUnlocked: radios.every(radio =>
                    !radio.closest('.card').classList.contains('pe-none')
                )
            };
        }, modulePath);
    };

    // Primo scenario: setup caricato e annullato.
    await page.locator('#method-totp').evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(page.locator('#cancel-setup-btn')).toBeVisible();

    await expect.poll(async () => {
        const state = await readState();
        return state.setupPending;
    }).toBe(true);

    await page.locator('#cancel-setup-btn').click();

    await expect.poll(async () => {
        const state = await readState();
        return state.setupPending === false &&
            state.isSubmitting === false &&
            state.allEnabled &&
            state.allUnlocked;
    }).toBe(true);

    expect(await readState()).toEqual({
        currentActiveMethod: 'none',
        setupPending: false,
        isSubmitting: false,
        noneChecked: true,
        totpChecked: false,
        allEnabled: true,
        allUnlocked: true
    });

    await expect(page.locator('#confirm-totp-form')).toHaveCount(0);

    expect(cancellations).toBe(1);

    // Secondo scenario: il server rifiuta il setup.
    await page.locator('#securityCurrentPassword').fill('PasswordCorretta!');

    await page.locator('#method-totp').evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => requests).toBe(2);

    await expect.poll(async () => {
        const state = await readState();

        return state.setupPending === false &&
            state.isSubmitting === false &&
            state.allEnabled &&
            state.allUnlocked;
    }).toBe(true);

    expect(await readState()).toEqual({
        currentActiveMethod: 'none',
        setupPending: false,
        isSubmitting: false,
        noneChecked: true,
        totpChecked: false,
        allEnabled: true,
        allUnlocked: true
    });

    expect(requests).toBe(2);
});


test('SecurityManager conferma il TOTP solo dopo una verifica riuscita', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let setupRequests = 0;
    let confirmRequests = 0;
    const submittedCodes = [];

    await page.route('**/backend/account/setupTotp', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        setupRequests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <form id="confirm-totp-form">
                        <input type="text" name="code">
                        <button type="submit">Conferma</button>
                        <button type="button" id="cancel-setup-btn">Annulla</button>
                    </form>
                `
            })
        });
    });

    await page.route('**/backend/account/confirmTotp', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        confirmRequests++;

        const body = route.request().postData() || '';
        const code = /name="code"\r?\n\r?\n([^\r\n]+)/.exec(body)?.[1];

        submittedCodes.push(code);

        const response = confirmRequests === 1
            ? {
                result: false,
                message: 'Codice TOTP non valido'
            }
            : {
                result: true,
                message: 'Autenticazione TOTP attivata'
            };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-totp"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="totp"
                    data-requires-setup="true"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        window.__e2eSecurityHooks = {
            before: 0,
            after: 0
        };

        const manager = new SecurityManager({
            async onSecurityBefore() {
                await Promise.resolve();
                window.__e2eSecurityHooks.before++;
            },

            async onSecurityAfter() {
                await Promise.resolve();
                window.__e2eSecurityHooks.after++;
            }
        });

        manager.init();
    }, modulePath);

    const readState = () => page.evaluate(async path => {
        const { SecurityManager } = await import(path);
        const manager = SecurityManager.instance;

        const radios = [...document.querySelectorAll(
            '.twofactor-method-trigger'
        )];

        return {
            activeMethod: manager.currentActiveMethod,
            setupPending: manager.setupPending,
            isSubmitting: manager.isSubmitting,
            noneChecked: document.getElementById('method-none').checked,
            totpChecked: document.getElementById('method-totp').checked,
            radiosDisabled: radios.every(radio => radio.disabled),
            beforeCalls: window.__e2eSecurityHooks.before,
            afterCalls: window.__e2eSecurityHooks.after
        };
    }, modulePath);

    // Prima fase: inizializzazione del setup TOTP.
    const setupResult = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);
        return SecurityManager.instance.requestTotpSetup();
    }, modulePath);

    expect(setupResult).toBe(true);
    expect(setupRequests).toBe(1);

    await expect(page.locator('#confirm-totp-form')).toBeVisible();

    // Prima verifica: codice errato.
    await page.locator('#confirm-totp-form [name="code"]').fill('000000');

    const firstResult = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        const form = document.getElementById('confirm-totp-form');

        return SecurityManager.instance.initTotpConfirmation(
            new FormData(form)
        );
    }, modulePath);

    expect(firstResult).toBe(false);
    expect(confirmRequests).toBe(1);

    expect(await readState()).toEqual({
        activeMethod: 'none',
        setupPending: true,
        isSubmitting: false,
        noneChecked: true,
        totpChecked: false,
        radiosDisabled: true,
        beforeCalls: 2,
        afterCalls: 1
    });

    await expect(page.locator('#confirm-totp-form')).toBeVisible();

    // Seconda verifica: codice accettato dal server.
    await page.locator('#confirm-totp-form [name="code"]').fill('123456');

    const secondResult = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        const form = document.getElementById('confirm-totp-form');

        return SecurityManager.instance.initTotpConfirmation(
            new FormData(form)
        );
    }, modulePath);

    expect(secondResult).toBe(true);
    expect(confirmRequests).toBe(2);

    expect(await readState()).toEqual({
        activeMethod: 'totp',
        setupPending: false,
        isSubmitting: false,
        noneChecked: false,
        totpChecked: true,
        radiosDisabled: false,
        beforeCalls: 3,
        afterCalls: 2
    });

    await expect(page.locator('#confirm-totp-form')).toHaveCount(0);

    expect(submittedCodes).toEqual(['000000', '123456']);
});


test('SecurityManager gestisce hook asincroni ed eccezioni senza perdere lo stato', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    const submittedMethods = [];

    await page.route('**/backend/account/saveBasicMethod', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const body = route.request().postData() || '';
        const method = /name="twoFactorMethod"\r?\n\r?\n([^\r\n]+)/.exec(body)?.[1];

        submittedMethods.push(method);

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-email"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="email"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new SecurityManager({
            async onSecurityBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore Before simulato');
                }

                await Promise.resolve();
            },

            async onSecurityAfter() {
                afterCalls++;

                if (afterCalls === 1) {
                    throw new Error('Errore After simulato');
                }

                await Promise.resolve();
            },

            async onSecurityError(error) {
                await Promise.resolve();

                if (
                    error.message === 'Errore Before simulato' ||
                    error.message === 'Errore After simulato'
                ) {
                    errorCalls++;
                }
            }
        });

        manager.init();

        // 1. L'hook Before fallisce: nessuna richiesta AJAX.
        const firstResult = await manager.saveBasicMethod('email');

        const stateAfterBeforeError = {
            result: firstResult,
            activeMethod: manager.currentActiveMethod,
            noneChecked: document.getElementById('method-none').checked,
            emailChecked: document.getElementById('method-email').checked,
            isSubmitting: manager.isSubmitting
        };

        // 2. Il server conferma il salvataggio, ma l'hook After fallisce.
        const secondResult = await manager.saveBasicMethod('email');

        const stateAfterAfterError = {
            result: secondResult,
            activeMethod: manager.currentActiveMethod,
            noneChecked: document.getElementById('method-none').checked,
            emailChecked: document.getElementById('method-email').checked,
            isSubmitting: manager.isSubmitting
        };

        // 3. Un'ulteriore operazione deve funzionare regolarmente.
        const thirdResult = await manager.saveBasicMethod('none');

        const finalState = {
            result: thirdResult,
            activeMethod: manager.currentActiveMethod,
            noneChecked: document.getElementById('method-none').checked,
            emailChecked: document.getElementById('method-email').checked,
            isSubmitting: manager.isSubmitting
        };

        return {
            stateAfterBeforeError,
            stateAfterAfterError,
            finalState,
            beforeCalls,
            afterCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(2);
    expect(submittedMethods).toEqual(['email', 'none']);

    expect(result.stateAfterBeforeError).toEqual({
        result: false,
        activeMethod: 'none',
        noneChecked: true,
        emailChecked: false,
        isSubmitting: false
    });

    expect(result.stateAfterAfterError).toEqual({
        result: false,
        activeMethod: 'email',
        noneChecked: false,
        emailChecked: true,
        isSubmitting: false
    });

    expect(result.finalState).toEqual({
        result: true,
        activeMethod: 'none',
        noneChecked: true,
        emailChecked: false,
        isSubmitting: false
    });

    expect(result.beforeCalls).toBe(3);
    expect(result.afterCalls).toBe(2);
    expect(result.errorCalls).toBe(2);
});


test('SecurityManager gestisce destroy e reinizializzazione del setup TOTP', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/verifyCurrentPassword', async route => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    await page.route('**/backend/account/setupTotp', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: false,
                message: 'Setup TOTP simulato'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { SecurityManager } = await import(path);

        SecurityManager.instance?.destroy();

        document.querySelectorAll('.twofactor-method-trigger').forEach(el => {
            el.closest('.card')?.remove();
        });

        document.getElementById('totp-setup-wrapper')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2eSecurityFixture';

        fixture.innerHTML = `
            <input
                type="password"
                id="securityCurrentPassword"
                value="PasswordCorretta!"
                data-required-message="Inserisci la password corrente."
            >

            <div class="card app-border-accent app-bg-surface">
                <input
                    type="radio"
                    id="method-none"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="none"
                    checked
                >
            </div>

            <div class="card">
                <input
                    type="radio"
                    id="method-totp"
                    name="twoFactorMethod"
                    class="twofactor-method-trigger"
                    value="totp"
                    data-requires-setup="true"
                >
            </div>

            <div id="totp-setup-wrapper"></div>
        `;

        document.body.appendChild(fixture);

        const first = new SecurityManager();
        first.init();

        const sameInstance = new SecurityManager() === first;

        first.init();

        // Destroy deve rimuovere i listener.
        first.destroy();

        const instanceReleased = SecurityManager.instance === null;

        // L'istanza distrutta non deve più eseguire operazioni.
        const destroyedResult = await first.requestTotpSetup();

        // Nuova istanza, con nuovi listener.
        const second = new SecurityManager();
        second.init();

        const newInstance = second !== first;

        // Un solo click sul radio TOTP deve produrre un solo POST.
        document.getElementById('method-totp').dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
                cancelable: true
            })
        );

        // Aspettiamo il completamento della richiesta AJAX.
        for (let i = 0; i < 100 && !second.isSubmitting; i++) {
            await new Promise(resolve => setTimeout(resolve, 10));
        }

        for (let i = 0; i < 100 && second.isSubmitting; i++) {
            await new Promise(resolve => setTimeout(resolve, 10));
        }

        return {
            sameInstance,
            instanceReleased,
            destroyedResult,
            newInstance,
            activeMethod: second.currentActiveMethod,
            setupPending: second.setupPending,
            isSubmitting: second.isSubmitting
        };
    }, modulePath);

    expect(result).toEqual({
        sameInstance: true,
        instanceReleased: true,
        destroyedResult: false,
        newInstance: true,
        activeMethod: 'none',
        setupPending: false,
        isSubmitting: false
    });

    expect(requests).toBe(1);
});

