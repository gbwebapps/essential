const { test, expect } = require('@playwright/test');
const { spawnSync } = require('node:child_process');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://dashboard-preview.test';
const expectedLabels = {
    it: ['Dentro il magazzino', 'Valore del magazzino', 'Valore prodotti per categoria'],
    en: ['Inside the warehouse', 'Inventory value', 'Product value by category'],
    de: ['Lagerübersicht', 'Lagerwert', 'Produktwert nach Kategorie'],
    es: ['Dentro del almacén', 'Valor del almacén', 'Valor de productos por categoría'],
    fr: ['Vue de l’entrepôt', 'Valeur du stock', 'Valeur des produits par catégorie'],
    zh: ['仓库概览', '库存总值', '按类别统计产品价值']
};

/** Usa le viste, il presenter e gli asset reali; i dati sono fixture e non provengono dal database. */
async function openDashboard(page, theme, locale = 'it', scenario = 'normal') {
    const rendered = spawnSync('php', ['tests/_support/Fixtures/dashboard-preview.php', locale, scenario], { cwd: root, encoding: 'utf8' });
    if (rendered.status !== 0 || rendered.stderr) throw new Error(rendered.stderr || rendered.stdout);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

        if (pathname === '/') {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="${locale}"><head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
                <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
                <link rel="stylesheet" href="/assets/css/backend/${theme}.css">
                <link rel="stylesheet" href="/assets/css/backend/backend.css">
                <link rel="stylesheet" href="/assets/css/backend/dashboard.css">
                </head><body><main class="container-fluid"><div id="index-dashboard-container" data-dashboard-theme="${theme}">${rendered.stdout}</div></main></body></html>` });
            return;
        }

        if (pathname.startsWith('/assets/') && /\.(css|woff2?|png)$/.test(pathname)) {
            await route.fulfill({ path: path.join(root, 'public', pathname.slice(1)) });
            return;
        }

        await route.abort();
    });

    await page.goto(origin);
    return errors;
}

for (const theme of ['white', 'black', 'grey', 'dark']) {
    test('Dashboard: riepiloghi e grafici nel tema ' + theme, async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 1000 });
        const errors = await openDashboard(page, theme);
        await expect(page.locator('[data-dashboard-summary]')).toHaveCount(4);
        await expect(page.locator('[data-dashboard-ranking]')).toHaveCount(4);
        await expect(page.locator('.dashboard-inventory-amount')).toHaveText('1.234.567,89');
        await expect(page.locator('[data-dashboard-ranking="brandsValue"] .dashboard-bar-value').first()).toHaveText('987.654,32');
        await expect(page.locator('[data-dashboard-ranking="categoriesValue"] h3')).toHaveText('Valore prodotti per categoria');
        await expect(page.locator('[data-dashboard-summary="products"] .dashboard-total')).toHaveText('1.248');
        await expect(page.locator('[data-dashboard-summary="admins"]')).toBeVisible();
        await expect(page.locator('.dashboard-donut').first()).toHaveCSS('background-image', /conic-gradient/);
        if (theme === 'dark') await expect(page.locator('.dashboard-card').first()).toHaveCSS('background-color', 'rgb(68, 68, 68)');
        const columns = await page.locator('.dashboard-summaries').evaluate(node => getComputedStyle(node).gridTemplateColumns.split(' ').length);
        expect(columns).toBe(4);
        await expect(page.locator('[data-dashboard-summary="brands"] a')).toHaveAttribute('href', origin + '/backend/brands/showAll');
        expect(errors).toEqual([]);
        await page.screenshot({ path: test.info().outputPath('dashboard-' + theme + '.png'), fullPage: true });
    });
}

for (const locale of ['it', 'en', 'de', 'es', 'fr', 'zh']) {
    test('Dashboard: testi e layout mobile in lingua ' + locale, async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 1000 });
        const errors = await openDashboard(page, 'grey', locale);
        await expect(page.locator('.dashboard-analysis-title')).toHaveText(expectedLabels[locale][0]);
        await expect(page.locator('#dashboard-inventory-value-title')).toHaveText(expectedLabels[locale][1]);
        await expect(page.locator('[data-dashboard-ranking="categoriesValue"] h3')).toHaveText(expectedLabels[locale][2]);
        await expect(page.locator('[data-dashboard-summary]')).toHaveCount(4);
        await expect(page.locator('main')).not.toContainText('backend/dashboard.');
        const overflow = await page.locator('#index-dashboard-container').evaluate(root => ({
            root: root.scrollWidth > root.clientWidth,
            sections: Array.from(root.querySelectorAll('.dashboard-card-header, .dashboard-summary-body, .dashboard-states, .dashboard-bars li')).some(node => node.scrollWidth > node.clientWidth),
        }));
        expect(overflow).toEqual({ root: false, sections: false });
        expect(errors).toEqual([]);
    });
}

test('Dashboard: grafici vuoti non rappresentano dati inesistenti', async ({ page }) => {
    await openDashboard(page, 'dark', 'it', 'empty');
    await expect(page.locator('.dashboard-donut-empty')).toHaveCount(4);
    await expect(page.locator('.dashboard-total').first()).toHaveText('0');
    await expect(page.locator('.dashboard-donut-empty').first()).toHaveCSS('background-image', 'none');
    await expect(page.locator('.dashboard-bars')).toHaveCount(0);
    await expect(page.locator('.dashboard-empty')).toHaveCount(4);
    await expect(page.locator('.dashboard-inventory-amount')).toHaveText('0,00');
});

test('Dashboard: nasconde amministratori e analisi non autorizzate', async ({ page }) => {
    await openDashboard(page, 'grey', 'it', 'restricted');
    await expect(page.locator('[data-dashboard-summary]')).toHaveCount(1);
    await expect(page.locator('[data-dashboard-summary="admins"]')).toHaveCount(0);
    await expect(page.locator('[data-dashboard-ranking]')).toHaveCount(0);
    await expect(page.locator('[data-dashboard-inventory-value]')).toHaveCount(0);
});

test('Dashboard: distingue errori e assenza di associazioni', async ({ page }) => {
    await openDashboard(page, 'grey', 'it', 'error');
    await expect(page.locator('[data-dashboard-ranking="brands"]')).toContainText('Statistiche temporaneamente non disponibili.');
    await expect(page.locator('[data-dashboard-ranking="categories"]')).toContainText('Nessuna associazione disponibile.');
    await expect(page.locator('[data-dashboard-inventory-value]')).toContainText('Statistiche temporaneamente non disponibili.');
    await expect(page.locator('.dashboard-inventory-amount')).toHaveCount(0);
});

test('Dashboard: nomi delle entità visualizzati come testo, non come HTML', async ({ page }) => {
    await openDashboard(page, 'white', 'it', 'unsafe');
    await expect(page.locator('.dashboard-bar-label').first()).toContainText('<img');
    await expect(page.locator('.dashboard-ranking img')).toHaveCount(0);
    expect(await page.evaluate(() => window.fixtureXss)).toBeUndefined();
});

test('Dashboard: importi elevati mantengono i centesimi senza overflow mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 1000 });
    await openDashboard(page, 'grey', 'it', 'large');
    await expect(page.locator('.dashboard-inventory-amount')).toHaveText('42.949.672.949.957.050.327,05');
    await expect(page.locator('[data-dashboard-ranking="brandsValue"] .dashboard-bar-value').first()).toHaveText('42.949.672.949.957.050.327,05');
    const overflow = await page.locator('#index-dashboard-container').evaluate(root => root.scrollWidth > root.clientWidth);
    expect(overflow).toBe(false);
});

test('Dashboard: associazioni con valore zero non disegnano barre positive', async ({ page }) => {
    await openDashboard(page, 'dark', 'it', 'zeroValues');
    await expect(page.locator('[data-dashboard-ranking="brandsValue"] .dashboard-bar-value')).toHaveText('0,00');
    await expect(page.locator('[data-dashboard-ranking="brandsValue"] .dashboard-bar-fill')).toHaveAttribute('style', 'width: 0.0000%;');
});

test('Dashboard: il totale rimane visibile con il solo accesso ai prodotti', async ({ page }) => {
    await openDashboard(page, 'grey', 'it', 'productsOnly');
    await expect(page.locator('[data-dashboard-ranking]')).toHaveCount(0);
    await expect(page.locator('.dashboard-inventory-amount')).toHaveText('1.234.567,89');
});
