
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/account/edit';
const modulePath = '/assets/js/backend/modules/Account.js';

test('ResetPasswordManager gestisce errori e risultati del reset', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/resetPassword', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const responses = [
            {
                result: false,
                errors: { password: 'Password non valida' },
                message: 'Errore di validazione'
            },
            {
                result: false,
                message: 'Impossibile reimpostare la password'
            },
            {
                result: 'db_committed_no_email',
                output: '<div id="e2eResetNoEmail">Email non inviata</div>',
                message: 'Operazione registrata, email non inviata'
            },
            {
                result: true,
                output: '<div id="e2eResetSuccess">Password reimpostata</div>',
                message: 'Operazione completata'
            }
        ];

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(responses[requests - 1])
        });
    });

    const result = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        let container = document.getElementById('reset-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'reset-account-container';
            document.body.appendChild(container);
        }

        let afterCalls = 0;

        const manager = new ResetPasswordManager({
            async onResetAfter() {
                afterCalls++;
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('id', 'e2e-test-account');

        await manager.resetPassword(formData);
        const unlockedAfterValidation = !manager.isSubmitting;

        await manager.resetPassword(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.resetPassword(formData);

        const unlockedAfterNoEmail = !manager.isSubmitting;
        const noEmailRendered = !!document.getElementById('e2eResetNoEmail');
        const afterCallsBeforeSuccess = afterCalls;

        await manager.resetPassword(formData);

        return {
            unlockedAfterValidation,
            unlockedAfterFailure,
            unlockedAfterNoEmail,
            noEmailRendered,
            afterCallsBeforeSuccess,
            afterCalls,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(requests).toBe(4);

    expect(result).toEqual({
        unlockedAfterValidation: true,
        unlockedAfterFailure: true,
        unlockedAfterNoEmail: true,
        noEmailRendered: true,
        afterCallsBeforeSuccess: 0,
        afterCalls: 1,
        isSubmitting: false
    });

    await expect(page.locator('#e2eResetSuccess')).toHaveText('Password reimpostata');
    await expect(page.locator('#e2eResetNoEmail')).toHaveCount(0);
});


test('ResetPasswordManager gestisce conferma e invii duplicati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let verificationRequests = 0;

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

    await page.route('**/backend/account/resetPassword', async route => {
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
                message: 'Password reimpostata'
            })
        });
    });

    await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        const manager = new ResetPasswordManager();
        manager.init();

        const form = document.createElement('form');
        form.id = 'e2eResetPasswordForm';
        form.dataset.message = 'Confermi il reset della password?';
        form.innerHTML = `
            <input type="hidden" name="id" value="e2e-test-account">
            <input
                type="password"
                name="currentPassword"
                data-required-message="Inserisci la password corrente."
            >
            <div class="error_currentPassword"></div>
        `;

        // Il manager intercetta questo selettore.
        form.setAttribute('id', 'getResetPassword');

        document.body.appendChild(form);
    }, modulePath);

    const modal = page.locator('#globalConfirmModal');
    const form = page.locator('#getResetPassword');
    const passwordInput = form.locator('[name="currentPassword"]');

    // Campo vuoto: nessuna chiamata server e nessun modale.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(modal).toBeHidden();
    expect(verificationRequests).toBe(0);
    expect(requests).toBe(0);
    await expect(form.locator('.error_currentPassword')).toHaveText('Inserisci la password corrente.');
    await expect(page.locator('#alert-container [role="alert"]')).toContainText('Inserisci la password corrente.');

    // Password errata: verifica server, alert immediato e nessun modale.
    await passwordInput.fill('PasswordErrata!');
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => verificationRequests).toBe(1);
    await expect(modal).toBeHidden();
    expect(requests).toBe(0);
    await expect(form.locator('.error_currentPassword')).toHaveText('Password corrente non valida');
    await expect(page.locator('#alert-container [role="alert"]')).toContainText('Password corrente non valida');

    await passwordInput.fill('PasswordCorretta!');

    // Due submit consecutivi mentre la conferma è aperta.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));

        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(modal).toBeVisible();

    const blockedDuringConfirmation = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        return ResetPasswordManager.instance.isSubmitting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);
    expect(verificationRequests).toBe(2);

    // Annullamento: nessuna richiesta AJAX.
    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { ResetPasswordManager } = await import(path);
            return ResetPasswordManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(0);

    // Nuovo submit con conferma positiva.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(modal).toBeVisible();
    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);
    expect(verificationRequests).toBe(3);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { ResetPasswordManager } = await import(path);
            return ResetPasswordManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
});


test('ResetPasswordManager gestisce hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/resetPassword', async route => {
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
                output: '<div id="e2eResetHookResult">Reset completato</div>'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        let container = document.getElementById('reset-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'reset-account-container';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new ResetPasswordManager({
            async onResetBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }

                await Promise.resolve();
            },

            async onResetAfter() {
                await Promise.resolve();
                afterCalls++;
            },

            async onResetError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('id', 'e2e-test-account');

        // Primo tentativo: eccezione nell'hook Before.
        await manager.resetPassword(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: operazione completata.
        await manager.resetPassword(formData);

        return {
            unlockedAfterError,
            isSubmitting: manager.isSubmitting,
            beforeCalls,
            afterCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        unlockedAfterError: true,
        isSubmitting: false,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1
    });

    await expect(page.locator('#e2eResetHookResult')).toHaveText('Reset completato');
});
