
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/account/edit';
const modulePath = '/assets/js/backend/modules/Account.js';
const deleteUrl = '/backend/account/deleteToken';

test('DeleteTokenManager gestisce errori e aggiorna il contenitore', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/deleteToken', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const response = requests === 1
            ? {
                result: false,
                errors: { id: 'Token non valido' },
                message: 'Errore di validazione'
            }
            : requests === 2
                ? {
                    result: false,
                    message: 'Impossibile eliminare il token'
                }
                : {
                    result: true,
                    output: '<div id="e2eDeleteTokenResult">Token eliminato</div>',
                    message: 'Token revocato'
                };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    const result = await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

        let container = document.getElementById('tokens-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'tokens-account-container';
            document.body.appendChild(container);
        }

        const manager = new DeleteTokenManager();
        manager.init();

        const formData = new FormData();
        formData.append('id', 'e2e-test-token');

        await manager.deleteToken(formData);
        const unlockedAfterValidation = !manager.isSubmitting;

        await manager.deleteToken(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.deleteToken(formData);

        return {
            unlockedAfterValidation,
            unlockedAfterFailure,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(requests).toBe(3);

    expect(result).toEqual({
        unlockedAfterValidation: true,
        unlockedAfterFailure: true,
        isSubmitting: false
    });

    await expect(page.locator('#e2eDeleteTokenResult')).toHaveText('Token eliminato');
});


test('DeleteTokenManager gestisce conferma e invii duplicati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/deleteToken', async route => {
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
                message: 'Token revocato'
            })
        });
    });

    await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

        const manager = new DeleteTokenManager();
        manager.init();

        const form = document.createElement('form');
        form.id = 'e2eDeleteTokenForm';
        form.className = 'deleteToken';
        form.dataset.message = 'Confermi la revoca?';
        form.innerHTML = '<input type="hidden" name="id" value="e2e-test-token">';

        document.body.appendChild(form);
    }, modulePath);

    const modal = page.locator('#globalConfirmModal');
    const form = page.locator('#e2eDeleteTokenForm');

    // Prima conferma: due submit ravvicinati.
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
        const { DeleteTokenManager } = await import(path);
        return DeleteTokenManager.instance.isSubmitting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);

    // Annullamento: non deve partire alcuna richiesta.
    await modal.locator('.btn-cancel').click();

    await expect(modal).toBeHidden();

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { DeleteTokenManager } = await import(path);
            return DeleteTokenManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(0);

    // Seconda conferma: accettazione.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(modal).toBeVisible();
    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { DeleteTokenManager } = await import(path);
            return DeleteTokenManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
});


test('DeleteTokenManager gestisce hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/deleteToken', async route => {
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
                output: '<div id="e2eDeleteTokenHookResult">Token revocato</div>'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

        let container = document.getElementById('tokens-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'tokens-account-container';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new DeleteTokenManager({
            async onDeleteTokenBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }

                await Promise.resolve();
            },

            async onDeleteTokenAfter() {
                await Promise.resolve();
                afterCalls++;
            },

            async onDeleteTokenError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('id', 'e2e-test-token');

        // Primo tentativo: errore nell'hook Before.
        await manager.deleteToken(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: operazione completata.
        await manager.deleteToken(formData);

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

    await expect(page.locator('#e2eDeleteTokenHookResult')).toHaveText('Token revocato');
});
