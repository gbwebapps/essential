const { test, expect, adminUuid } = require('./support/admin-fixture');
const { appUrl } = require('./support/app-url');
const { checkEnvironment } = require('./support/environment');

test.use({ storageState: 'e2e/.auth/admin.json' });

test('L’ambiente E2E è configurato correttamente', async ({ page }) => {
    expect(checkEnvironment()).toEqual({
        environment: 'e2e',
        group: 'tests',
        database: 'essential_test'
    });

    // La lettura HTTP deve trovare il record casuale creato solo nel database di test.
    const response = await page.goto(appUrl(`/backend/admins/edit/${adminUuid}`));

    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);

    await expect(page).toHaveURL(appUrl(`/backend/admins/edit/${adminUuid}`));
    await expect(page.locator('#admins-edit')).toBeVisible();
    await expect(page.locator('#admins-edit [name="email"]')).toHaveValue(`e2e-admin-${adminUuid}@example.invalid`);
    await expect(page.locator('#admins-edit [name="note"]')).toHaveValue(`Playwright fixture ${adminUuid}`);
});
