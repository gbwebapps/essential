
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/account/edit';
const modulePath = '/assets/js/backend/modules/Account.js';
const permissionsUrl = '/backend/account/permissions';

test('GetPermissionsManager gestisce errori e aggiorna i permessi', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/permissions', async route => {
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
                    message: 'Impossibile recuperare i permessi'
                }
                : {
                    result: true,
                    output: '<div id="e2ePermissionsResult">Permessi caricati</div>'
                };

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    const result = await page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);

        GetPermissionsManager.instance?.destroy();

        let container = document.getElementById('permissions-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'permissions-account-container';
            document.body.appendChild(container);
        }

        const manager = new GetPermissionsManager();
        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        await manager.getPermissions(formData);
        const unlockedAfterValidation = !manager.isSubmitting;

        await manager.getPermissions(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.getPermissions(formData);

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

    await expect(page.locator('#e2ePermissionsResult')).toHaveText('Permessi caricati');
});


test('GetPermissionsManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/account/permissions', async route => {
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
                output: '<div>Permessi caricati</div>'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);

        GetPermissionsManager.instance?.destroy();

        const manager = new GetPermissionsManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('uuid', 'e2e-test-uuid');

        const secondData = new FormData();
        secondData.append('uuid', 'e2e-test-uuid');

        await Promise.all([
            manager.getPermissions(firstData),
            manager.getPermissions(secondData)
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


test('GetPermissionsManager gestisce gli hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/permissions', async route => {
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
                output: '<div id="e2ePermissionsHookResult">Permessi caricati</div>'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);

        GetPermissionsManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new GetPermissionsManager({
            async onPermissionsBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onPermissionsAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onPermissionsError(error) {
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

        // Primo tentativo: eccezione nell'hook, nessuna richiesta HTTP.
        await manager.getPermissions(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: richiesta AJAX e hook di successo.
        await manager.getPermissions(formData);

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

    await expect(page.locator('#e2ePermissionsHookResult')).toHaveText('Permessi caricati');
});
