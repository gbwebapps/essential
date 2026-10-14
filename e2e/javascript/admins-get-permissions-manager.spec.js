
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const adminUuid = 'aef9b10c-ebe7-4e55-af1f-19aef22c53c8';
const pageUrl = `/backend/admins/edit/${adminUuid}`;
const modulePath = '/assets/js/backend/modules/Admins.js';

test('GetPermissionsManager gestisce fallimento, successo e aggiornamento permessi', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/getPermissions', async route => {
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
                    message: 'Impossibile recuperare i permessi'
                }
                : {
                    result: true,
                    output: '<div id="e2ePermissionsResult">Permessi caricati</div>'
                })
        });
    });

    const result = await page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);

        GetPermissionsManager.instance?.destroy();

        let container = document.getElementById('permissions');

        if (!container) {
            container = document.createElement('div');
            container.id = 'permissions';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new GetPermissionsManager({
            async onPermissionsBefore() {
                beforeCalls++;
                await Promise.resolve();
            },

            async onPermissionsAfter() {
                await Promise.resolve();
                afterCalls++;
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.getPermissions(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.getPermissions(formData);
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

    await expect(page.locator('#e2ePermissionsResult')).toHaveText('Permessi caricati');
});

test('GetPermissionsManager blocca richieste duplicate e gestisce submit delegati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;
    let receivedPayload = null;

    await page.route('**/backend/admins/getPermissions', async route => {
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
                message: 'Permessi recuperati'
            })
        });
    });

    await page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);

        GetPermissionsManager.instance?.destroy();

        const manager = new GetPermissionsManager({
            async onPermissionsBefore() {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        });

        manager.init();

        document.getElementById('getPermissions')?.remove();

        const form = document.createElement('form');
        form.id = 'getPermissions';
        form.innerHTML = '<input type="hidden" name="uuid" value="e2e-test-admin">';
        document.body.appendChild(form);
    }, modulePath);

    const form = page.locator('#getPermissions');

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
            const { GetPermissionsManager } = await import(path);
            return GetPermissionsManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
    expect(receivedPayload).toContain('name="uuid"\r\n\r\ne2e-test-admin');

    await page.evaluate(async path => {
        const { GetPermissionsManager } = await import(path);
        GetPermissionsManager.instance.destroy();
    }, modulePath);
});

test('GetPermissionsManager recupera da errori degli hook e gestisce destroy', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/getPermissions', async route => {
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
                message: 'Permessi recuperati'
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

                await Promise.resolve();
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

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.getPermissions(formData);
        const unlockedAfterError = !manager.isSubmitting;

        await manager.getPermissions(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        manager.destroy();

        const afterDestroyResult = await manager.getPermissions(formData);

        return {
            afterDestroyResult,
            unlockedAfterError,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: GetPermissionsManager.instance === null
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
