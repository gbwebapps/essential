const { test, expect } = require('@playwright/test');
const { spawnSync } = require('node:child_process');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://products-selects.test';

/** Carica viste, modulo Products e Tom Select reali, con salvataggi HTTP isolati. */
async function openProductForm(page, context) {
    const rendered = spawnSync('php', ['tests/_support/Fixtures/products-form-preview.php', context], { cwd: root, encoding: 'utf8' });
    if (rendered.status !== 0 || rendered.stderr) throw new Error(rendered.stderr || rendered.stdout);
    const form = context === 'edit'
        ? `<form id="products-edit" data-unsaved-check><input type="hidden" id="uuid" name="uuid" value="00000000-0000-4000-8000-000000000005"><div id="generalData">${rendered.stdout}</div></form>`
        : rendered.stdout;
    const failures = [];
    const requests = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('console', message => { if (message.type() === 'error') failures.push(message.text()); });

    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;
        if (pathname === '/') {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="UTF-8">
                <link rel="stylesheet" href="/assets/vendor/tom-select/css/tom-select.bootstrap5.min.css"></head><body>
                <div id="controller" data-controller="products"></div><div id="action" data-action="${context}"></div>
                <div id="hidden-urlbase" data-urlbase="${origin}/"></div><div id="lang-definitions" data-select="Seleziona" data-remove="Rimuovi"></div>
                <div id="${context}-products-container">${form}</div>
                <form id="add-reset"></form><form id="edit-refresh"><input type="hidden" name="uuid" value="00000000-0000-4000-8000-000000000005"></form>
                <button type="submit" form="products-${context}">Salva</button></body></html>` });
            return;
        }
        if (pathname === '/backend/products/' + context) {
            requests.push(route.request().postData());
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ result: true, output: form }) });
            return;
        }
        if (pathname.startsWith('/assets/') && /\.(js|css)$/.test(pathname)) {
            await route.fulfill({ path: path.join(root, 'public', pathname.slice(1)), contentType: pathname.endsWith('.js') ? 'application/javascript' : 'text/css' });
            return;
        }
        await route.abort();
    });

    await page.goto(origin);
    await page.addScriptTag({ path: path.join(root, 'public/assets/vendor/tom-select/js/tom-select.complete.min.js') });
    await page.evaluate(async context => {
        await import('/assets/js/backend/products.js');
        const { AddManager, EditManager } = await import('/assets/js/backend/modules/Crud.js');
        window.productsManager = context === 'add' ? AddManager.instances.get('#products-add') : EditManager.instances.get('#products-edit');
    }, context);

    return { failures, requests };
}

async function expectWorkingSelects(page) {
    await expect(page.locator('.ts-wrapper')).toHaveCount(2);
    await expect(page.locator('#brand_uuids option[disabled]')).toContainText('Sospesa');
    await expect(page.locator('#category_uuids option[disabled]')).toContainText('Sospesa');
    expect(await page.locator('#brand_uuids').evaluate(select => select.tomselect.options['00000000-0000-4000-8000-000000000002'].disabled)).toBe(true);
    await page.locator('.ts-control').first().click();
    await page.locator('.ts-dropdown-content [data-selectable]').filter({ hasText: 'Active brand' }).click();
    expect(await page.locator('#brand_uuids').evaluate(select => select.tomselect.getValue())).toEqual(['00000000-0000-4000-8000-000000000001']);
}

for (const context of ['add', 'edit']) {
    test('Prodotti ' + context + ': select operative dopo salvataggi ripetuti e ricaricamento', async ({ page }) => {
        const fixture = await openProductForm(page, context);
        await expectWorkingSelects(page);
        for (let save = 0; save < 2; save++) {
            await page.getByRole('button', { name: 'Salva' }).click();
            await expect.poll(() => fixture.requests.length).toBe((save + 1) * 2);
            await expect.poll(() => page.evaluate(() => window.productsManager.isSubmitting)).toBe(false);
            await expectWorkingSelects(page);
        }
        await page.evaluate(async context => {
            if (context === 'add') await window.productsManager.reset();
            else await window.productsManager.refresh(new FormData(document.getElementById('edit-refresh')));
            const { initTomSelects } = await import('/assets/js/backend/backend.js');
            initTomSelects();
        }, context);
        await expectWorkingSelects(page);
        expect(fixture.failures).toEqual([]);
    });
}
