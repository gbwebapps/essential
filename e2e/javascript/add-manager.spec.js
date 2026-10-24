const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/admins/add');
const ajaxPath = '/backend/admins/add';
const modulePath = appUrl('/assets/js/backend/modules/Crud.js');

async function openAddPage(page) {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/admins\/add\/?$/);

    await expect(page.locator('#admins-add')).toBeAttached({
        timeout: 15000
    });

    await page.waitForFunction(async path => {
        const { AddManager } = await import(path);
        const manager = AddManager.instances.get('#admins-add');

        return Boolean(manager?.eventsBound && !manager.isSubmitting);
    }, modulePath);
}

function isAddRequest(request) {
    return request.method() === 'POST' &&
        new URL(request.url()).pathname === new URL(appUrl(ajaxPath)).pathname;
}

function mockResponse(result, extra = {}) {
    return {
        result,
        ...extra
    };
}

test('AddManager intercetta il submit ed esegue una richiesta AJAX', async ({ page }) => {
    await openAddPage(page);

    const requests = [];

    await page.route('**/backend/admins/add', async route => {
        if (!isAddRequest(route.request())) {
            await route.continue();
            return;
        }

        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(mockResponse(false, {
                message: 'Risposta simulata dal test'
            }))
        });
    });

    await page.locator('#admins-add').evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => requests.length).toBe(1);

    await page.waitForFunction(async path => {
        const { AddManager } = await import(path);

        return AddManager.instances.get('#admins-add')?.isSubmitting === false;
    }, modulePath);

    expect(requests[0].method()).toBe('POST');
});

test('AddManager esegue il reset dopo un aggiunta riuscita', async ({ page }) => {
    await openAddPage(page);

    await expect(page.locator('#add-reset')).toBeAttached();

    const actions = [];

    await page.route('**/backend/admins/add', async route => {
        if (!isAddRequest(route.request())) {
            await route.continue();
            return;
        }

        const body = route.request().postData() || '';
        const isReset = /name="action"\r?\n\r?\nreset/.test(body);

        actions.push(isReset ? 'reset' : 'add');

        const response = isReset
            ? mockResponse(true)
            : mockResponse(true, {
                message: 'Aggiunta simulata'
            });

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(response)
        });
    });

    await page.locator('#admins-add').evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => actions.length).toBe(2);

    expect(actions).toEqual(['add', 'reset']);

    await page.waitForFunction(async path => {
        const { AddManager } = await import(path);

        return AddManager.instances.get('#admins-add')?.isSubmitting === false;
    }, modulePath);
});

test('AddManager evita doppi invii e recupera dopo un errore', async ({ page }) => {
    await openAddPage(page);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/admins/add', async route => {
        if (!isAddRequest(route.request())) {
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
            body: JSON.stringify(mockResponse(false, {
                message: 'Errore simulato'
            }))
        });
    });

    // Due invii ravvicinati: il secondo deve essere ignorato.
    await page.locator('#admins-add').evaluate(form => {
        form.requestSubmit();
        form.requestSubmit();
    });

    await expect.poll(() => requests).toBe(1);

    releaseRequest();

    await page.waitForFunction(async path => {
        const { AddManager } = await import(path);

        return AddManager.instances.get('#admins-add')?.isSubmitting === false;
    }, modulePath);

    // Dopo la risposta di errore il manager deve accettare un nuovo invio.
    await page.locator('#admins-add').evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => requests).toBe(2);

    const state = await page.evaluate(async path => {
        const { AddManager } = await import(path);
        const manager = AddManager.instances.get('#admins-add');

        return {
            eventsBound: manager.eventsBound,
            destroyed: manager.destroyed,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(state).toEqual({
        eventsBound: true,
        destroyed: false,
        isSubmitting: false
    });
});
