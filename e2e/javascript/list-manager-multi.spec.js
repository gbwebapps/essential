const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({ storageState: 'e2e/.auth/admin.json' });

test('Due ListManager mantengono filtri, paginazione e risultati indipendenti', async ({ page }) => {
    await page.goto(appUrl('/backend/admins/showAll'));
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    const payloads = { alpha: [], beta: [] };
    for (const controller of ['alpha', 'beta']) {
        await page.route(`**/backend/${controller}/showAll`, async route => {
            const params = new URLSearchParams(route.request().postData() || '');
            payloads[controller].push({
                page: params.get('page'),
                firstname: params.get('searchFields[firstname]')
            });
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    result: true,
                    output: `<div class="result-${controller}">${controller}</div>`
                })
            });
        });
    }

    await page.evaluate(async () => {
        const { ListManager } = await import(new URL('assets/js/backend/modules/Crud.js', document.getElementById('hidden-urlbase').dataset.urlbase).href);
        for (const controller of ['alpha', 'beta']) {
            const root = document.createElement('section');
            root.id = `e2e-${controller}-root`;
            root.innerHTML = `
                <select class="changeNumRows"><option value="5">5</option><option value="10">10</option></select>
                <a href="#" class="refresh-list">Refresh</a>
                <a href="#" class="link-search">Search</a>
                <div class="search-bar"><input data-search-field="firstname"></div>
                <div id="e2e-${controller}-results"></div>
            `;
            document.body.appendChild(root);
            localStorage.removeItem(`e2e_${controller}_page`);
            localStorage.removeItem(`e2e_${controller}_firstname`);
            const manager = new ListManager({
                controller,
                url: `/backend/${controller}/showAll`,
                rootId: root.id,
                containerId: `e2e-${controller}-results`,
                storageKey: `e2e_${controller}`,
                searchFields: ['firstname'],
                debounceTime: 0
            });
            await manager.init();
        }
    });

    await expect(page.locator('.result-alpha')).toHaveText('alpha');
    await expect(page.locator('.result-beta')).toHaveText('beta');

    await page.locator('#e2e-alpha-root [data-search-field="firstname"]').fill('Mario');
    await expect.poll(() => payloads.alpha.some(p => p.firstname === 'Mario')).toBe(true);
    expect(payloads.beta.every(p => p.firstname !== 'Mario')).toBe(true);

    await page.locator('#e2e-beta-root .changeNumRows').selectOption('10');
    await expect.poll(() => payloads.beta.length).toBeGreaterThan(1);
    const state = await page.evaluate(async () => {
        const { ListManager } = await import(new URL('assets/js/backend/modules/Crud.js', document.getElementById('hidden-urlbase').dataset.urlbase).href);
        const alpha = ListManager.instances['e2e-alpha-results'];
        const beta = ListManager.instances['e2e-beta-results'];
        const result = {
            alphaSearch: alpha.state.searchFields.firstname,
            betaSearch: beta.state.searchFields.firstname,
            alphaRows: alpha.state.rows,
            betaRows: beta.state.rows
        };
        alpha.destroy();
        beta.destroy();
        document.getElementById('e2e-alpha-root').remove();
        document.getElementById('e2e-beta-root').remove();
        return result;
    });
    expect(state).toEqual({ alphaSearch: 'Mario', betaSearch: '', alphaRows: '5', betaRows: '10' });
});
