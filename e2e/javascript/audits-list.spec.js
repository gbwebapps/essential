const { test, expect } = require('@playwright/test');

test.use({ storageState: 'e2e/.auth/admin.json' });

function waitForAuditsResponse(page, predicate) {
    return page.waitForResponse(response => {
        const request = response.request();

        if (request.method() !== 'POST' || ! /\/backend\/audits\/?$/.test(request.url())) return false;

        const params = new URLSearchParams(request.postData() || '');

        return predicate(params);
    });
}

test('Audits collega vista e ListManager per righe, filtri e ordinamento', async ({ page }) => {
    const initialResponse = waitForAuditsResponse(page, params => params.get('rows') === '5');

    await page.goto('/backend/audits');
    await expect(page).toHaveURL(/\/backend\/audits\/?$/);
    await initialResponse;

    await expect(page.locator('#index-audits-container table')).toBeVisible();

    const rowsResponse = waitForAuditsResponse(page, params => params.get('rows') === '10');
    await page.locator('#audits-list .changeNumRows').selectOption('10');
    await rowsResponse;

    await page.locator('#audits-list .link-search').click();
    await expect(page.locator('[data-search-field="action"]')).toBeVisible();

    const filterResponse = waitForAuditsResponse(page, params => params.get('searchFields[action]') === 'LOGIN_SUCCESS');
    await page.locator('[data-search-field="action"]').fill('LOGIN_SUCCESS');
    await filterResponse;

    const sortResponse = waitForAuditsResponse(page, params => params.get('column') === 'created_at');
    await page.locator('a.sort[data-column="created_at"]').click();
    await sortResponse;
});
