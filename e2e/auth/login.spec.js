const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test('Login amministratore tramite AJAX', async ({ page }) => {
    const email = process.env.E2E_ADMIN_EMAIL;
    const password = process.env.E2E_ADMIN_PASSWORD;

    expect(email).toBeTruthy();
    expect(password).toBeTruthy();

    await page.goto(appUrl('/backend/auth/login'));

    await expect(page.locator('[name="email"]')).toHaveValue('');
    await expect(page.locator('[name="password"]')).toHaveValue('');

    await page.locator('[name="email"]').fill(email);
    await page.locator('[name="password"]').fill(password);

    let loginResult;

    await page.exposeFunction('e2eRecordLogin', data => {
        loginResult = data;
    });

    // Osserva una copia del JSON prima che il client avvii il redirect.
    await page.evaluate(loginUrl => {
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const response = await originalFetch(...args);
            if (response.url === loginUrl && args[1]?.method === 'POST') {
                await window.e2eRecordLogin(await response.clone().json());
            }
            return response;
        };
    }, appUrl('/backend/auth/login'));

    const loginResponse = page.waitForResponse(response =>
        response.url() === appUrl('/backend/auth/login') &&
        response.request().method() === 'POST'
    );
    const dashboardResponse = page.waitForResponse(response =>
        response.request().isNavigationRequest() &&
        response.url().replace(/\/$/, '') === appUrl('/backend/dashboard')
    );

    await page.locator('#login_form button[type="submit"]').click();

    const response = await loginResponse;
    expect(response.status()).toBe(200);
    expect(response.request().headers()['x-requested-with']).toBe('XMLHttpRequest');
    expect((await dashboardResponse).status()).toBe(200);
    expect(loginResult.result).toBe(true);

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/, {
        timeout: 15000
    });

    await expect(page.locator('#index-dashboard-container')).toBeVisible();
    await expect(page.locator('[data-dashboard-summary]').first()).toBeVisible();

    await page.context().storageState({
        path: 'e2e/.auth/admin.json'
    });
});
