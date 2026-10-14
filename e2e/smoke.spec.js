const { test, expect } = require('@playwright/test');

test('La pagina di autenticazione è raggiungibile', async ({ page }) => {
    const response = await page.goto('/backend/auth');

    expect(response).not.toBeNull();
    expect(response.status()).toBeLessThan(400);

    await expect(page.locator('body')).toBeVisible();
});
