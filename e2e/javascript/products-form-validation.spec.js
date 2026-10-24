const { test, expect } = require('@playwright/test');
const { spawnSync } = require('node:child_process');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://products-validation.test';

/** Usa il form Add e i manager reali; le risposte hanno il contratto verificato dai test PHP. */
async function openForm(page, errors) {
    const rendered = spawnSync('php', ['tests/_support/Fixtures/products-form-preview.php'], { cwd: root, encoding: 'utf8' });
    if (rendered.status !== 0 || rendered.stderr) throw new Error(rendered.stderr || rendered.stdout);
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('console', message => { if (message.type() === 'error') failures.push(message.text()); });

    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;
        if (pathname === '/') {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head><meta charset="UTF-8"></head><body>
                <div id="controller" data-controller="products"></div><div id="action" data-action="add"></div>
                <div id="hidden-urlbase" data-urlbase="${origin}/"></div><div id="lang-definitions"></div>
                <div id="add-products-container">${rendered.stdout}</div>
                <button type="submit" form="products-add">Salva</button>
                <div id="error-img-preview-id"></div>
                </body></html>` });
            return;
        }
        if (pathname === '/backend/products/add') {
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ errors }) });
            return;
        }
        if (pathname.startsWith('/assets/') && pathname.endsWith('.js')) {
            await route.fulfill({ path: path.join(root, 'public', pathname.slice(1)), contentType: 'application/javascript' });
            return;
        }
        await route.abort();
    });

    await page.goto(origin);
    await page.evaluate(async () => {
        const { AddManager } = await import('/assets/js/backend/modules/Crud.js');
        window.productsAdd = new AddManager({
            formSelector: '#products-add', url: '/backend/products/add',
            containerId: 'add-products-container', imagePreviewManager: { files: [] },
        });
        window.productsAdd.init();
    });

    return failures;
}

test('Prodotti Add: mostra tutti gli errori e consente un secondo invio senza eccezioni', async ({ page }) => {
    const errors = { sku: 'SKU obbligatorio', product: 'Prodotto obbligatorio', quantity: 'Quantità obbligatoria', price: 'Prezzo obbligatorio', status: 'Stato obbligatorio', brand_uuids: 'Marca obbligatoria', category_uuids: 'Categoria obbligatoria' };
    const failures = await openForm(page, errors);

    for (let attempt = 0; attempt < 2; attempt++) {
        await page.getByRole('button', { name: 'Salva' }).click();
        for (const [field, message] of Object.entries(errors)) {
            await expect(page.locator('.error_' + field)).toHaveText(message);
        }
        await expect.poll(() => page.evaluate(() => window.productsAdd.isSubmitting)).toBe(false);
    }
    expect(failures).toEqual([]);
});

test('Prodotti Add: gli errori delle immagini conservano il collegamento alla preview', async ({ page }) => {
    const failures = await openForm(page, { brand_uuids: 'UUID marca non valido', category_uuids: 'UUID categoria non valido', 'images.preview-id': 'Immagine non valida' });
    await page.getByRole('button', { name: 'Salva' }).click();
    await expect(page.locator('.error_brand_uuids')).toHaveText('UUID marca non valido');
    await expect(page.locator('.error_category_uuids')).toHaveText('UUID categoria non valido');
    await expect(page.locator('#error-img-preview-id')).toHaveText('Immagine non valida');
    expect(failures).toEqual([]);
});
