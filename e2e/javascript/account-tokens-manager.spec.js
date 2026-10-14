
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/account/edit';
const modulePath = '/assets/js/backend/modules/Account.js';
const tokensUrl = '/backend/account/tokens';

test('GetTokensManager gestisce errori e aggiorna il contenitore dei token', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/tokens', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        const response = requests === 1
            ? {
                result: false,
                errors: { uuid: 'Identificativo non valido' },
                message: 'Errore di validazione'
            }
            : requests === 2
                ? {
                    result: false,
                    message: 'Impossibile recuperare i token'
                }
                : {
                    result: true,
                    output: '<div id="e2eTokensResult">Token caricati</div>'
                };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    const result = await page.evaluate(async path => {
        const { GetTokensManager } = await import(path);

        GetTokensManager.instance?.destroy();

        let container = document.getElementById('tokens-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'tokens-account-container';
            document.body.appendChild(container);
        }

        const manager = new GetTokensManager();
        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        await manager.getTokens(formData);
        const unlockedAfterValidation = !manager.isSubmitting;

        await manager.getTokens(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.getTokens(formData);

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

    await expect(page.locator('#e2eTokensResult')).toHaveText('Token caricati');
});


test('GetTokensManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/account/tokens', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div>Token caricati</div>'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { GetTokensManager } = await import(path);

        GetTokensManager.instance?.destroy();

        const manager = new GetTokensManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('uuid', 'e2e-test-uuid');

        const secondData = new FormData();
        secondData.append('uuid', 'e2e-test-uuid');

        await Promise.all([
            manager.getTokens(firstData),
            manager.getTokens(secondData)
        ]);
    }, modulePath);

    try {
        await expect.poll(() => requests).toBe(1);

        releaseRequest();
        await action;

        expect(requests).toBe(1);
    } finally {
        releaseRequest();
    }
});


test('GetTokensManager gestisce gli hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/tokens', async route => {
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
                output: '<div id="e2eTokensHookResult">Token caricati</div>'
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

        let container = document.getElementById(manager.containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = manager.containerId;
            document.body.appendChild(container);
        }

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        // Primo tentativo: errore nell'hook, nessuna richiesta HTTP.
        await manager.getTokens(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: risposta AJAX positiva.
        await manager.getTokens(formData);

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

    await expect(page.locator('#e2eTokensHookResult')).toHaveText('Token caricati');
});
