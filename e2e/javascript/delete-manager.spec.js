const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/admins/showAll');
const modulePath = appUrl('/assets/js/backend/modules/Crud.js');

const urls = {
    softDelete: '/backend/admins/delete',
    restoreDelete: '/backend/admins/restore',
    hardDelete: '/backend/admins/hardDelete'
};

async function openPage(page) {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    await page.waitForFunction(async path => {
        const { DeleteManager } = await import(path);

        return Boolean(DeleteManager.instances?.get('admins')?.eventsBound);
    }, modulePath, {
        timeout: 15000
    });
}

async function setupManager(page) {
    await page.evaluate(async ({ path, endpoints }) => {
        const { DeleteManager } = await import(path);

        DeleteManager.instances?.get('admins')?.destroy();

        const manager = new DeleteManager({
            controller: 'admins',
            urls: endpoints,
            listManager: null
        });

        manager.init();
    }, {
        path: modulePath,
        endpoints: urls
    });
}

async function submitAction(page, className) {
    await page.evaluate(className => {
        const form = document.createElement('form');

        form.className = className;
        form.dataset.controller = 'admins';
        form.dataset.message = 'Confermi questa operazione di test?';

        const uuid = document.createElement('input');
        uuid.name = 'uuid';
        uuid.value = 'e2e-test-uuid';

        form.appendChild(uuid);
        document.body.appendChild(form);

        form.requestSubmit();
    }, className);
}

function isDeleteRequest(request) {
    return request.method() === 'POST' &&
        Object.values(urls).includes(new URL(request.url()).pathname);
}

test('DeleteManager instrada correttamente soft delete, restore e hard delete', async ({ page }) => {
    await openPage(page);
    await setupManager(page);

    const requests = [];

    await page.route('**/backend/admins/**', async route => {
        if (!isDeleteRequest(route.request())) {
            await route.continue();
            return;
        }

        requests.push(new URL(route.request().url()).pathname);

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: false })
        });
    });

    for (const [className, expectedUrl] of [
        ['deleteRecord', urls.softDelete],
        ['restoreRecord', urls.restoreDelete],
        ['hardDeleteRecord', urls.hardDelete]
    ]) {
        await submitAction(page, className);
        const modal = page.locator('#globalConfirmModal');
        await expect(modal).toBeVisible();
        await expect.poll(() => modal.evaluate(element => {
            return bootstrap.Modal.getInstance(element)._isTransitioning;
        })).toBe(false);
        await modal.locator('.btn-ok').click();

        await expect.poll(() => requests.length).toBe([
            urls.softDelete, urls.restoreDelete, urls.hardDelete
        ].indexOf(expectedUrl) + 1);

        await page.waitForFunction(async path => {
            const { DeleteManager } = await import(path);
            return DeleteManager.instances.get('admins').isSubmitting === false;
        }, modulePath);
        expect(requests[requests.length - 1]).toBe(expectedUrl);
        await expect(modal).toBeHidden();
    }

    expect(requests).toEqual([
        urls.softDelete,
        urls.restoreDelete,
        urls.hardDelete
    ]);
});

test('DeleteManager blocca richieste duplicate durante un operazione', async ({ page }) => {
    await openPage(page);
    await setupManager(page);

    let requests = 0;
    let releaseFirstRequest;

    const blocked = new Promise(resolve => {
        releaseFirstRequest = resolve;
    });

    await page.route('**/backend/admins/**', async route => {
        if (!isDeleteRequest(route.request())) {
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
            body: JSON.stringify({ result: false })
        });
    });

    const action = page.evaluate(async path => {
        const { DeleteManager } = await import(path);
        const manager = DeleteManager.instances.get('admins');

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        const first = manager.processAction('/backend/admins/delete', formData);
        const second = manager.processAction('/backend/admins/delete', formData);

        await Promise.all([first, second]);
    }, modulePath);

    try {
        await expect.poll(() => requests).toBe(1);

        releaseFirstRequest();
        await action;

        expect(requests).toBe(1);
    } finally {
        releaseFirstRequest();
    }
});


test('DeleteManager aggiorna la lista e ripristina lo stato dopo errori', async ({ page }) => {
    await openPage(page);
    await setupManager(page);

    let attempts = 0;

    await page.route('**/backend/admins/**', async route => {
        if (!isDeleteRequest(route.request())) {
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
                    : { result: true, message: 'Operazione simulata' }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { DeleteManager } = await import(path);
        const manager = DeleteManager.instances.get('admins');

        let refreshes = 0;

        manager.config.listManager = {
            root: document.body,
            storageKey: 'admins',
            state: { page: 2 },
            async showAll() {
                refreshes++;
            }
        };

        const listManager = manager.config.listManager;

        localStorage.setItem('admins_page', '2');

        const existingMarker = listManager.root.querySelector('.lastItemPage');

        if (existingMarker) {
            existingMarker.remove();
        }

        const marker = document.createElement('div');
        marker.className = 'lastItemPage';
        marker.dataset.lastitempage = '1';

        listManager.root.appendChild(marker);

        const firstData = new FormData();
        firstData.append('uuid', 'e2e-test-uuid');

        await manager.processAction('/backend/admins/delete', firstData);

        const unlockedAfterError = manager.isSubmitting === false;

        const secondData = new FormData();
        secondData.append('uuid', 'e2e-test-uuid');

        await manager.processAction('/backend/admins/delete', secondData);

        return {
            unlockedAfterError,
            isSubmitting: manager.isSubmitting,
            page: manager.config.listManager.state.page,
            refreshes
        };
    }, modulePath);

    expect(attempts).toBe(2);

    expect(result).toEqual({
        unlockedAfterError: true,
        isSubmitting: false,
        page: 2,
        refreshes: 1
    });
});

