const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

test('Accesso alla dashboard con sessione salvata', async ({ page }) => {
    const response = await page.goto(appUrl('/backend/dashboard'));

    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/);

    await expect(page.locator('#index-dashboard-container')).toBeVisible();
    await expect(page.locator('[data-dashboard-summary]').first()).toBeVisible();
});
