const { test, expect } = require('@playwright/test');

test.use({ storageState: 'e2e/.auth/admin.json' });

const modulePath = '/assets/js/backend/modules/Crud.js';

function waitForTokensResponse(page, predicate) {
    return page.waitForResponse(response => {
        const request = response.request();

        if (request.method() !== 'POST' || ! /\/backend\/tokens\/?$/.test(request.url())) return false;

        const params = new URLSearchParams(request.postData() || '');

        return predicate(params);
    });
}

async function openTokens(page) {
    await page.addInitScript(() => {
        if (sessionStorage.getItem('tokens_e2e_initialized') !== '1') {
            Object.keys(localStorage)
                .filter(key => key.startsWith('tokens_'))
                .forEach(key => localStorage.removeItem(key));

            sessionStorage.setItem('tokens_e2e_initialized', '1');
        }
    });

    const initialResponse = waitForTokensResponse(page, params => params.get('rows') === '5');

    await page.goto('/backend/tokens');
    await expect(page).toHaveURL(/\/backend\/tokens\/?$/);
    await initialResponse;

    await page.waitForFunction(async path => {
        const { DeleteManager } = await import(path);

        return Boolean(DeleteManager.instances?.get('tokens')?.eventsBound);
    }, modulePath);
}

test('Tokens collega filtri, Flatpickr e persistenza localStorage', async ({ page }) => {
    await openTokens(page);

    await expect(page.locator('#index-tokens-container')).not.toBeEmpty();
    await expect(page.locator('#tokens-list .changeNumRows')).toHaveValue('5');

    const flatpickrReady = await page.locator('#wrapper-tokens-token_create-from').evaluate(wrapper => {
        return Boolean(wrapper._flatpickr);
    });

    expect(flatpickrReady).toBe(true);

    await page.locator('#tokens-list .link-search').click();
    await expect(page.locator('[data-search-field="email"]')).toBeVisible();

    const emailResponsePromise = waitForTokensResponse(
        page,
        params => params.get('searchFields[email]') === 'admin1@'
    );
    await page.locator('[data-search-field="email"]').fill('admin1@');
    const emailResponse = await emailResponsePromise;

    expect((await emailResponse.json()).result).toBe(true);

    const typeResponse = waitForTokensResponse(
        page,
        params => params.get('searchFields[token_type]') === 'session'
    );
    await page.locator('[data-search-field="token_type"]').selectOption('session');
    await typeResponse;

    await expect.poll(() => page.evaluate(() => ({
        email: localStorage.getItem('tokens_email'),
        type: localStorage.getItem('tokens_token_type')
    }))).toEqual({
        email: 'admin1@',
        type: 'session'
    });

    await page.reload();

    await expect(page.locator('[data-search-field="email"]')).toHaveValue('admin1@');
    await expect(page.locator('[data-search-field="token_type"]')).toHaveValue('session');
});

test('Tokens instrada la revoca verso hardDelete', async ({ page }) => {
    await openTokens(page);

    let requestBody = '';

    await page.route('**/backend/tokens/hardDelete', async route => {
        requestBody = route.request().postData() || '';

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: false, message: 'Test E2E' })
        });
    });

    const endpoint = await page.evaluate(async path => {
        const { DeleteManager } = await import(path);
        const manager = DeleteManager.instances.get('tokens');
        const formData = new FormData();

        formData.append('id', '123');
        formData.append('uuid', '00000000-0000-4000-8000-000000000001');
        await manager.processAction(manager.config.urls.hardDelete, formData);

        return manager.config.urls.hardDelete;
    }, modulePath);

    expect(new URL(endpoint, 'https://essential.test').pathname).toBe('/backend/tokens/hardDelete');
    expect(requestBody).toContain('name="id"');
    expect(requestBody).toContain('123');
    expect(requestBody).toContain('name="uuid"');
});
