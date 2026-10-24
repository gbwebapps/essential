const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

test('Accesso alla dashboard con sessione salvata', async ({ page }) => {
    await page.goto(appUrl('/backend/dashboard'));

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/);

    await expect(page.locator('body')).toBeVisible();
});
