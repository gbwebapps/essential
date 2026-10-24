const { test, expect, adminUuid } = require('../support/admin-fixture');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});


const pageUrl = appUrl(`/backend/admins/edit/${adminUuid}`);
const ajaxPath = '/backend/admins/edit';
const modulePath = appUrl('/assets/js/backend/modules/Crud.js');
const formSelector = '#admins-edit';

async function openEditPage(page) {
	
    const response = await page.goto(pageUrl, {
        waitUntil: 'load'
    });

    console.log('URL richiesto:', pageUrl);
    console.log('URL finale:', page.url());
    console.log('HTTP status:', response?.status());

    await expect(page.locator(formSelector)).toBeAttached({
        timeout: 15000
    });

    await page.waitForFunction(async ({ path, selector }) => {
        const { EditManager } = await import(path);
        const manager = EditManager.instances.get(selector);

        return Boolean(manager?.eventsBound && !manager.isSubmitting);
    }, {
        path: modulePath,
        selector: formSelector
    });
}

function isEditRequest(request) {
    return request.method() === 'POST' &&
        new URL(request.url()).pathname === new URL(appUrl(ajaxPath)).pathname;
}

function mockResponse(result, extra = {}) {
    return { result, ...extra };
}

test('EditManager intercetta il submit ed esegue una richiesta AJAX', async ({ page }) => {
    await openEditPage(page);

    const requests = [];

    await page.route('**/backend/admins/edit', async route => {
        if (!isEditRequest(route.request())) {
            await route.continue();
            return;
        }

        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(mockResponse(false, {
                message: 'Risposta simulata'
            }))
        });
    });

    await page.locator(formSelector).evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => requests.length).toBe(1);

    await page.waitForFunction(async path => {
        const { EditManager } = await import(path);

        return EditManager.instances.get('#admins-edit')?.isSubmitting === false;
    }, modulePath);

    expect(requests[0].method()).toBe('POST');
});

test('EditManager esegue il refresh dopo un salvataggio riuscito', async ({ page }) => {
    await openEditPage(page);

    const actions = [];

    await page.route('**/backend/admins/edit', async route => {
        if (!isEditRequest(route.request())) {
            await route.continue();
            return;
        }

        const body = route.request().postData() || '';

        const isRefresh = /name="action"\r?\n\r?\nrefresh/.test(body);

        actions.push(isRefresh ? 'refresh' : 'edit');

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                isRefresh
                    ? mockResponse(true)
                    : mockResponse(true, { message: 'Salvataggio simulato' })
            )
        });
    });

    await page.locator(formSelector).evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => actions.length).toBe(2);

    expect(actions).toEqual(['edit', 'refresh']);

    await page.waitForFunction(async path => {
        const { EditManager } = await import(path);

        return EditManager.instances.get('#admins-edit')?.isSubmitting === false;
    }, modulePath);
});

test('EditManager evita doppi invii e recupera dopo un errore', async ({ page }) => {
    await openEditPage(page);

    let requests = 0;
    let releaseFirstRequest;

    const blocked = new Promise(resolve => {
        releaseFirstRequest = resolve;
    });

    await page.route('**/backend/admins/edit', async route => {
        if (!isEditRequest(route.request())) {
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

    await page.locator(formSelector).evaluate(form => {
        form.requestSubmit();
        form.requestSubmit();
    });

    await expect.poll(() => requests).toBe(1);

    releaseFirstRequest();

    await page.waitForFunction(async path => {
        const { EditManager } = await import(path);

        return EditManager.instances.get('#admins-edit')?.isSubmitting === false;
    }, modulePath);

    await page.locator(formSelector).evaluate(form => {
        form.requestSubmit();
    });

    await expect.poll(() => requests).toBe(2);

    await page.waitForFunction(async path => {
        const { EditManager } = await import(path);

        return EditManager.instances.get('#admins-edit')?.isSubmitting === false;
    }, modulePath);

    const state = await page.evaluate(async path => {
        const { EditManager } = await import(path);
        const manager = EditManager.instances.get('#admins-edit');

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
