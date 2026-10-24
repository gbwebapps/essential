
const { test, expect, adminUuid } = require('../support/admin-fixture');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl(`/backend/admins/edit/${adminUuid}`);
const modulePath = appUrl('/assets/js/backend/modules/Admins.js');

test('ChangeGroupManager gestisce fallimento, successo e aggiornamento permessi', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/changeGroup', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(requests === 1
                ? { result: false, message: 'Cambio gruppo non riuscito' }
                : {
                    result: true,
                    output: '<div id="e2eGroupPermissions">Permessi aggiornati</div>'
                })
        });
    });

    const result = await page.evaluate(async path => {
        const { ChangeGroupManager } = await import(path);

        ChangeGroupManager.instance?.destroy();

        let container = document.getElementById('permissions');

        if (!container) {
            container = document.createElement('div');
            container.id = 'permissions';
            document.body.appendChild(container);
        }

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new ChangeGroupManager({
            async onGroupBefore() {
                beforeCalls++;
                await Promise.resolve();
            },
            async onGroupAfter() {
                afterCalls++;
                await Promise.resolve();
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('group_id', '2');
        formData.append('uuid', 'e2e-test-admin');

        const firstResult = await manager.changeGroup(formData);
        const unlockedAfterFailure = !manager.isSubmitting;

        const secondResult = await manager.changeGroup(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        const output = {
            firstResult,
            secondResult,
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
        firstResult: false,
        secondResult: true,
        unlockedAfterFailure: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1
    });

    await expect(page.locator('#e2eGroupPermissions')).toHaveText('Permessi aggiornati');
});

test('ChangeGroupManager gestisce conferma, annullamento e cambi concorrenti', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;
    let receivedPayload = null;

    await page.route('**/backend/admins/changeGroup', async route => {
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
                message: 'Gruppo modificato'
            })
        });
    });

    await page.evaluate(async path => {
        const { ChangeGroupManager } = await import(path);

        ChangeGroupManager.instance?.destroy();

        document.getElementById('group_id')?.remove();
        document.getElementById('uuid')?.remove();

        const uuid = document.createElement('input');
        uuid.id = 'uuid';
        uuid.value = 'e2e-test-admin';
        document.body.appendChild(uuid);

        const select = document.createElement('select');
        select.id = 'group_id';
        select.dataset.message = 'Confermi il cambio gruppo?';
        select.innerHTML = `
            <option value="1">Gruppo 1</option>
            <option value="2">Gruppo 2</option>
        `;
        select.value = '1';
        document.body.appendChild(select);

        const manager = new ChangeGroupManager();
        manager.init();
    }, modulePath);

    const select = page.locator('#group_id');
    const modal = page.locator('#globalConfirmModal');

    // Prima modifica: annullamento.
    await select.selectOption('2');
    await expect(modal).toBeVisible();

    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    await expect(select).toHaveValue('1');
    expect(requests).toBe(0);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { ChangeGroupManager } = await import(path);
            return ChangeGroupManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    // Seconda modifica: due eventi consecutivi.
    await select.evaluate(element => {
        element.value = '2';

        element.dispatchEvent(new Event('change', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    });

    await expect(modal).toBeVisible();

    const blockedDuringConfirmation = await page.evaluate(async path => {
        const { ChangeGroupManager } = await import(path);
        return ChangeGroupManager.instance.isSubmitting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);

    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { ChangeGroupManager } = await import(path);
            return ChangeGroupManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    await expect(select).toHaveValue('2');

    expect(requests).toBe(1);
    expect(receivedPayload).toContain('name="group_id"\r\n\r\n2');
    expect(receivedPayload).toContain('name="uuid"\r\n\r\ne2e-test-admin');

    await page.evaluate(async path => {
        const { ChangeGroupManager } = await import(path);
        ChangeGroupManager.instance.destroy();
    }, modulePath);
});

test('ChangeGroupManager recupera da errori degli hook e gestisce destroy', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(new RegExp(`/backend/admins/edit/${adminUuid}/?$`));

    let requests = 0;

    await page.route('**/backend/admins/changeGroup', async route => {
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
                message: 'Gruppo modificato'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { ChangeGroupManager } = await import(path);

        ChangeGroupManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new ChangeGroupManager({
            async onGroupBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }

                await Promise.resolve();
            },

            async onGroupAfter() {
                await Promise.resolve();
                afterCalls++;
            },

            async onGroupError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('group_id', '2');
        formData.append('uuid', 'e2e-test-admin');

        const firstResult = await manager.changeGroup(formData);
        const unlockedAfterError = !manager.isSubmitting;

        const secondResult = await manager.changeGroup(formData);
        const unlockedAfterSuccess = !manager.isSubmitting;

        manager.destroy();

        const afterDestroyResult = await manager.changeGroup(formData);

        return {
            firstResult,
            secondResult,
            afterDestroyResult,
            unlockedAfterError,
            unlockedAfterSuccess,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: ChangeGroupManager.instance === null
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        firstResult: false,
        secondResult: true,
        afterDestroyResult: false,
        unlockedAfterError: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1,
        destroyed: true,
        singletonReleased: true
    });
});
