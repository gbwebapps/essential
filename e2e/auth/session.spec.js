const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

test('Accesso alla dashboard con sessione salvata', async ({ page }) => {
    await page.goto('/backend/dashboard');

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/);

    await expect(page.locator('body')).toBeVisible();
});
