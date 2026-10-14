const { test, expect } = require('@playwright/test');

test('La pagina di autenticazione non genera errori JavaScript', async ({ page }) => {
    const errors = [];

    page.on('pageerror', error => {
        errors.push(error.message);
    });

    await page.goto('/backend/auth');

    await expect(page.locator('body')).toBeVisible();

    expect(errors).toEqual([]);
});
