const { test, expect } = require('@playwright/test');

test.use({ storageState: 'e2e/.auth/admin.json' });

const modulePath = '/assets/js/backend/modules/Crud.js';

function waitForLogsResponse(page, predicate) {
    return page.waitForResponse(response => {
        const request = response.request();

        if (request.method() !== 'POST' || ! /\/backend\/logs\/?$/.test(request.url())) return false;

        const params = new URLSearchParams(request.postData() || '');

        return predicate(params);
    });
}

async function openLogs(page) {
    await page.addInitScript(() => {
        if (sessionStorage.getItem('logs_e2e_initialized') !== '1') {
            Object.keys(localStorage)
                .filter(key => key.startsWith('logs_'))
                .forEach(key => localStorage.removeItem(key));

            sessionStorage.setItem('logs_e2e_initialized', '1');
        }
    });

    const initialResponse = waitForLogsResponse(page, params => params.get('rows') === '5');

    await page.goto('/backend/logs');
    await expect(page).toHaveURL(/\/backend\/logs\/?$/);
    await initialResponse;

    await page.waitForFunction(async path => {
        const { DeleteManager } = await import(path);

        return Boolean(DeleteManager.instances?.get('logs')?.eventsBound);
    }, modulePath);
}

test('Logs collega filtri, Flatpickr e persistenza localStorage', async ({ page }) => {
    await openLogs(page);

    await expect(page.locator('#index-logs-container')).not.toBeEmpty();

    const flatpickrReady = await page.locator('#wrapper-logs-login-from').evaluate(wrapper => {
        return Boolean(wrapper._flatpickr);
    });

    expect(flatpickrReady).toBe(true);

    await page.locator('#logs-list .link-search').click();
    await expect(page.locator('[data-search-field="username"]')).toBeVisible();

    const usernameResponse = waitForLogsResponse(page, params => params.get('searchFields[username]') === 'admin');
    await page.locator('[data-search-field="username"]').fill('admin');
    await usernameResponse;

    const reasonResponse = waitForLogsResponse(page, params => params.get('searchFields[logout_reason]') === 'manual');
    await page.locator('[data-search-field="logout_reason"]').selectOption('manual');
    await reasonResponse;

    const dateValue = '2026-01-01 10:11:12';
    const dateResponse = waitForLogsResponse(page, params => params.get('searchDates[login-from]') === dateValue);

    await page.locator('#wrapper-logs-login-from').evaluate((wrapper, value) => {
        wrapper._flatpickr.setDate(value, true, 'Y-m-d H:i:S');
    }, dateValue);

    await dateResponse;

    await expect.poll(() => page.evaluate(() => ({
        username: localStorage.getItem('logs_username'),
        reason: localStorage.getItem('logs_logout_reason'),
        from: localStorage.getItem('logs_login-from')
    }))).toEqual({
        username: 'admin',
        reason: 'manual',
        from: dateValue
    });

    await page.reload();

    await expect(page.locator('[data-search-field="username"]')).toHaveValue('admin');
    await expect(page.locator('[data-search-field="logout_reason"]')).toHaveValue('manual');
    await expect(page.locator('[data-search-field="login-from"]')).toHaveValue(dateValue);
});

test('Logs instrada la disconnessione verso l endpoint dedicato', async ({ page }) => {
    await openLogs(page);

    let requestBody = '';

    await page.route('**/backend/logs/disconnectSession', async route => {
        requestBody = route.request().postData() || '';

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: false, message: 'Test E2E' })
        });
    });

    const endpoint = await page.evaluate(async path => {
        const { DeleteManager } = await import(path);
        const manager = DeleteManager.instances.get('logs');
        const formData = new FormData();

        formData.append('tokenId', '123');
        await manager.processAction(manager.config.urls.disconnectSession, formData);

        return manager.config.urls.disconnectSession;
    }, modulePath);

    expect(new URL(endpoint, 'https://essential.test').pathname).toBe('/backend/logs/disconnectSession');
    expect(requestBody).toContain('name="tokenId"');
    expect(requestBody).toContain('123');
});
