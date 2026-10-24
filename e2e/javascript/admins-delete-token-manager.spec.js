
const { test, expect, adminUuid } = require('../support/admin-fixture');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl(`/backend/admins/edit/${adminUuid}`);
const modulePath = appUrl('/assets/js/backend/modules/Admins.js');

test('DeleteTokenManager gestisce fallimento, successo e aggiornamento token', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/deleteToken', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(requests === 1
                ? {
                    result: false,
                    message: 'Eliminazione token non riuscita'
                }
                : {
                    result: true,
                    tokensView: '<div id="e2eTokensUpdated">Token aggiornati</div>',
                    message: 'Token eliminato'
                })
        });
    });

    const result = await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

        let container = document.getElementById('tokens');

        if (!container) {
            container = document.createElement('div');
            container.id = 'tokens';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new DeleteTokenManager({
            async onDeleteTokenBefore() {
                beforeCalls++;
                await Promise.resolve();
            },

            async onDeleteTokenAfter() {
                await Promise.resolve();
                afterCalls++;
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');
        formData.append('token_id', '7');

        await manager.deleteToken(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.deleteToken(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        const output = {
            unlockedAfterFailure,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls
        };

        manager.destroy();

        return output;
    }, modulePath);

    expect(requests).toBe(2);

    expect(result).toEqual({
        unlockedAfterFailure: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1
    });

    await expect(page.locator('#e2eTokensUpdated')).toHaveText('Token aggiornati');
});

test('DeleteTokenManager gestisce conferma, annullamento e submit duplicati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;
    let receivedPayload = null;

    await page.route('**/backend/admins/deleteToken', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;
        receivedPayload = route.request().postData();

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Token eliminato'
            })
        });
    });

    await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

        document.querySelectorAll('form.deleteToken').forEach(form => form.remove());

        const form = document.createElement('form');
        form.className = 'deleteToken';
        form.dataset.message = 'Confermi eliminazione token?';
        form.innerHTML = `
            <input type="hidden" name="uuid" value="e2e-test-admin">
            <input type="hidden" name="token_id" value="7">
        `;

        document.body.appendChild(form);

        const manager = new DeleteTokenManager();
        manager.init();
    }, modulePath);

    const form = page.locator('form.deleteToken');
    const modal = page.locator('#globalConfirmModal');

    // Primo submit: annullamento.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect(modal).toBeVisible();

    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { DeleteTokenManager } = await import(path);
            return DeleteTokenManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(0);

    // Secondo submit: due eventi consecutivi.
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

    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { DeleteTokenManager } = await import(path);
            return DeleteTokenManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
    expect(receivedPayload).toContain('name="uuid"\r\n\r\ne2e-test-admin');
    expect(receivedPayload).toContain('name="token_id"\r\n\r\n7');

    await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);
        DeleteTokenManager.instance.destroy();
    }, modulePath);
});

test('DeleteTokenManager recupera da errori degli hook e gestisce destroy', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/deleteToken', async route => {
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
                message: 'Token eliminato'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { DeleteTokenManager } = await import(path);

        DeleteTokenManager.instance?.destroy();

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
        formData.append('uuid', 'e2e-test-admin');
        formData.append('token_id', '7');

        await manager.deleteToken(formData);
        const unlockedAfterError = !manager.isSubmitting;

        await manager.deleteToken(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        manager.destroy();

        const afterDestroyResult = await manager.deleteToken(formData);

        return {
            afterDestroyResult,
            unlockedAfterError,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: DeleteTokenManager.instance === null
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        afterDestroyResult: undefined,
        unlockedAfterError: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1,
        destroyed: true,
        singletonReleased: true
    });
});
