const { test, expect } = require('@playwright/test');

test('Login amministratore tramite AJAX', async ({ page }) => {
    const email = process.env.E2E_ADMIN_EMAIL;
    const password = process.env.E2E_ADMIN_PASSWORD;

    expect(email).toBeTruthy();
    expect(password).toBeTruthy();

    await page.goto('/backend/auth/login');

    await page.locator('[name="email"]').fill(email);
    await page.locator('[name="password"]').fill(password);

    const loginRequest = page.waitForRequest(request =>
        request.url().includes('/backend/auth/login') &&
        request.method() === 'POST'
    );

    await page.locator('#login_form button[type="submit"]').click();

    await loginRequest;

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/, {
        timeout: 15000
    });

    await page.context().storageState({
        path: 'e2e/.auth/admin.json'
    });
});
