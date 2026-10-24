
const { test, expect, adminUuid } = require('../support/admin-fixture');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl(`/backend/admins/edit/${adminUuid}`);
const modulePath = appUrl('/assets/js/backend/modules/Admins.js');

test('GetTokensManager gestisce fallimento, successo e aggiornamento token', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/getTokens', async route => {
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
                    message: 'Impossibile recuperare i token'
                }
                : {
                    result: true,
                    output: '<div id="e2eTokensResult">Token caricati</div>'
                })
        });
    });

    const result = await page.evaluate(async path => {
        const { GetTokensManager } = await import(path);

        GetTokensManager.instance?.destroy();

        let container = document.getElementById('tokens');

        if (!container) {
            container = document.createElement('div');
            container.id = 'tokens';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new GetTokensManager({
            async onTokensBefore() {
                beforeCalls++;
                await Promise.resolve();
            },

            async onTokensAfter() {
                await Promise.resolve();
                afterCalls++;
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.getTokens(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.getTokens(formData);
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

    await expect(page.locator('#e2eTokensResult')).toHaveText('Token caricati');
});

test('GetTokensManager blocca submit duplicati e verifica il payload AJAX', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;
    let receivedPayload = null;

    await page.route('**/backend/admins/getTokens', async route => {
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
                message: 'Token recuperati'
            })
        });
    });

    await page.evaluate(async path => {
        const { GetTokensManager } = await import(path);

        GetTokensManager.instance?.destroy();

        const manager = new GetTokensManager({
            async onTokensBefore() {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        });

        manager.init();

        document.getElementById('getTokens')?.remove();

        const form = document.createElement('form');
        form.id = 'getTokens';
        form.innerHTML = '<input type="hidden" name="uuid" value="e2e-test-admin">';
        document.body.appendChild(form);
    }, modulePath);

    const form = page.locator('#getTokens');

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

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { GetTokensManager } = await import(path);
            return GetTokensManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
    expect(receivedPayload).toContain('name="uuid"\r\n\r\ne2e-test-admin');

    await page.evaluate(async path => {
        const { GetTokensManager } = await import(path);
        GetTokensManager.instance.destroy();
    }, modulePath);
});

test('GetTokensManager recupera da errori degli hook e gestisce destroy', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/getTokens', async route => {
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
                message: 'Token recuperati'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { GetTokensManager } = await import(path);

        GetTokensManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new GetTokensManager({
            async onTokensBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }

                await Promise.resolve();
            },

            async onTokensAfter() {
                await Promise.resolve();
                afterCalls++;
            },

            async onTokensError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.getTokens(formData);
        const unlockedAfterError = !manager.isSubmitting;

        await manager.getTokens(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        manager.destroy();

        const afterDestroyResult = await manager.getTokens(formData);

        return {
            afterDestroyResult,
            unlockedAfterError,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: GetTokensManager.instance === null
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
