
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/admins/showAll');
const modulePath = appUrl('/assets/js/backend/modules/Crud.js');
const statusUrl = appUrl('/backend/admins/changeStatus');

async function openPage(page) {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    await page.evaluate(async ({ path, url }) => {
        const { ChangeStatusManager } = await import(path);

        ChangeStatusManager.instances.get('admins')?.destroy();

        const manager = new ChangeStatusManager({
            controller: 'admins',
            url
        });

        manager.init();
    }, {
        path: modulePath,
        url: statusUrl
    });
}

function isStatusRequest(request) {
    return request.method() === 'POST' &&
        new URL(request.url()).pathname === new URL(appUrl(statusUrl)).pathname;
}

async function runStatusChange(page) {
    return page.evaluate(async path => {
        const { ChangeStatusManager } = await import(path);
        const manager = ChangeStatusManager.instances.get('admins');

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        await manager.changeStatus(formData);
    }, modulePath);
}

test('ChangeStatusManager esegue una richiesta AJAX', async ({ page }) => {
    await openPage(page);

    const requests = [];

    await page.route('**/backend/admins/**', async route => {
        if (!isStatusRequest(route.request())) {
            await route.continue();
            return;
        }

        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Stato aggiornato'
            })
        });
    });

    await runStatusChange(page);

    expect(requests).toHaveLength(1);
    expect(requests[0].method()).toBe('POST');
});

test('ChangeStatusManager impedisce richieste duplicate', async ({ page }) => {
    await openPage(page);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/admins/**', async route => {
        if (!isStatusRequest(route.request())) {
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
            body: JSON.stringify({ result: true })
        });
    });

    const action = page.evaluate(async path => {
        const { ChangeStatusManager } = await import(path);
        const manager = ChangeStatusManager.instances.get('admins');

        const firstData = new FormData();
        firstData.append('uuid', 'e2e-test-uuid');

        const secondData = new FormData();
        secondData.append('uuid', 'e2e-test-uuid');

        await Promise.all([
            manager.changeStatus(firstData),
            manager.changeStatus(secondData)
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

test('ChangeStatusManager recupera dopo un errore', async ({ page }) => {
    await openPage(page);

    let attempts = 0;

    await page.route('**/backend/admins/**', async route => {
        if (!isStatusRequest(route.request())) {
            await route.continue();
            return;
        }

        attempts++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                attempts === 1
                    ? { result: false, message: 'Errore simulato' }
                    : { result: true, message: 'Operazione riuscita' }
            )
        });
    });

    await runStatusChange(page);

    const unlockedAfterError = await page.evaluate(async path => {
        const { ChangeStatusManager } = await import(path);
        return ChangeStatusManager.instances.get('admins').isSubmitting === false;
    }, modulePath);

    await runStatusChange(page);

    const finalState = await page.evaluate(async path => {
        const { ChangeStatusManager } = await import(path);
        const manager = ChangeStatusManager.instances.get('admins');

        return {
            isSubmitting: manager.isSubmitting,
            destroyed: manager.destroyed
        };
    }, modulePath);

    expect(attempts).toBe(2);
    expect(unlockedAfterError).toBe(true);
    expect(finalState).toEqual({
        isSubmitting: false,
        destroyed: false
    });
});
