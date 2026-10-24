
const { test, expect, adminUuid } = require('../support/admin-fixture');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl(`/backend/admins/edit/${adminUuid}`);
const modulePath = appUrl('/assets/js/backend/modules/Admins.js');

test('ChangePermissionManager gestisce fallimento, successo e aggiornamento viste', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/changePermission', async route => {
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
                    message: 'Modifica permesso non riuscita'
                }
                : {
                    result: true,
                    permissionsView: '<div id="e2ePermissionsUpdated">Permessi aggiornati</div>',
                    metaView: '<div id="e2eMetaUpdated">Metadati aggiornati</div>',
                    message: 'Permesso modificato'
                })
        });
    });

    const result = await page.evaluate(async path => {
        const { ChangePermissionManager } = await import(path);

        ChangePermissionManager.instance?.destroy();

        for (const id of ['permissions', 'metaData']) {
            if (!document.getElementById(id)) {
                const container = document.createElement('div');
                container.id = id;
                document.body.appendChild(container);
            }
        }

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new ChangePermissionManager({
            async onPermissionBefore() {
                beforeCalls++;
                await Promise.resolve();
            },

            async onPermissionAfter() {
                await Promise.resolve();
                afterCalls++;
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.changePermission(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.changePermission(formData);
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

    await expect(page.locator('#e2ePermissionsUpdated')).toHaveText('Permessi aggiornati');
    await expect(page.locator('#e2eMetaUpdated')).toHaveText('Metadati aggiornati');
});

test('ChangePermissionManager gestisce conferma, annullamento e submit duplicati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;
    let receivedPayload = null;

    await page.route('**/backend/admins/changePermission', async route => {
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
                message: 'Permesso modificato'
            })
        });
    });

    await page.evaluate(async path => {
        const { ChangePermissionManager } = await import(path);

        ChangePermissionManager.instance?.destroy();

        document.querySelectorAll('form.changePermission').forEach(form => form.remove());

        const form = document.createElement('form');
        form.className = 'changePermission';
        form.dataset.message = 'Confermi la modifica del permesso?';
        form.innerHTML = `
            <input type="hidden" name="uuid" value="e2e-test-admin">
            <input type="hidden" name="permission_id" value="7">
        `;

        document.body.appendChild(form);

        const manager = new ChangePermissionManager();
        manager.init();
    }, modulePath);

    const form = page.locator('form.changePermission');
    const modal = page.locator('#globalConfirmModal');

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
            const { ChangePermissionManager } = await import(path);
            return ChangePermissionManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(0);

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
        const { ChangePermissionManager } = await import(path);
        return ChangePermissionManager.instance.isSubmitting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);

    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { ChangePermissionManager } = await import(path);
            return ChangePermissionManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(1);
    expect(receivedPayload).toContain('name="uuid"\r\n\r\ne2e-test-admin');
    expect(receivedPayload).toContain('name="permission_id"\r\n\r\n7');

    await page.evaluate(async path => {
        const { ChangePermissionManager } = await import(path);
        ChangePermissionManager.instance.destroy();
    }, modulePath);
});

test('ChangePermissionManager recupera da errori degli hook e gestisce destroy', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/changePermission', async route => {
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
                message: 'Permesso modificato'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { ChangePermissionManager } = await import(path);

        ChangePermissionManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new ChangePermissionManager({
            async onPermissionBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }

                await Promise.resolve();
            },

            async onPermissionAfter() {
                await Promise.resolve();
                afterCalls++;
            },

            async onPermissionError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-admin');

        await manager.changePermission(formData);
        const unlockedAfterError = !manager.isSubmitting;

        await manager.changePermission(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        manager.destroy();

        const afterDestroyResult = await manager.changePermission(formData);

        return {
            afterDestroyResult,
            unlockedAfterError,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: ChangePermissionManager.instance === null
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
