const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://warehouse-counts.test';
const fields = { brands: 'brand', categories: 'category', products: 'product' };

/** Legge i link reali del modulo; le espressioni di lingua sono sostituite solo nella fixture. */
function getLinks(entity) {
    const moduleName = { brands: 'Brands', categories: 'Categories', products: 'Products' }[entity];
    const source = fs.readFileSync(path.join(root, 'modules/Backend', moduleName, 'Views/showAllView.php'), 'utf8');
    const labels = { activeRecords: 'Attivi', suspendedRecords: 'Sospesi', trashedRecords: 'Eliminati', allRecords: 'Tutti' };

    return source.match(/<a\b[^>]*data-trash-filter="(?:active|suspended|trashed|all)"[^>]*>[\s\S]*?<\/a>/g).join('\n')
        .replace(/<\?= lang\('backend\/\w+\.links\.(\w+)'\); \?>/g, (_, key) => labels[key]);
}

/** Carica vista e JavaScript reali, senza richieste all'applicazione o al database. */
async function openList(page, entity, replies) {
    const links = getLinks(entity);
    let requests = 0;
    const payloads = [];
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

        if (pathname === '/') {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
                <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
                <link rel="stylesheet" href="/assets/css/backend/grey.css">
                </head><body>
                <div id="controller" data-controller="${entity}"></div>
                <div id="action" data-action="showAll"></div>
                <div id="hidden-urlbase" data-urlbase="${origin}/"></div>
                <div id="lang-definitions"></div>
                <div id="${entity}-list">${links}
                    <a href="#" class="refresh-list">Ricarica</a>
                    <input data-search-field="${fields[entity]}">
                    <div id="showAll-${entity}-container"></div>
                </div>
                <div id="another-list"><span data-trash-count="active">99</span></div>
                </body></html>` });
            return;
        }

        if (pathname === '/backend/' + entity + '/showAll') {
            payloads.push(new URLSearchParams(route.request().postData()));
            const reply = replies[Math.min(requests++, replies.length - 1)];
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify(reply) });
            return;
        }

        if (pathname.startsWith('/assets/') && /\.(js|css)$/.test(pathname)) {
            await route.fulfill({ path: path.join(root, 'public', pathname.slice(1)), contentType: pathname.endsWith('.js') ? 'application/javascript' : 'text/css' });
            return;
        }

        await route.abort();
    });

    await page.goto(origin);
    await page.evaluate(async entity => {
        await import('/assets/js/backend/' + entity + '.js');
        const { ListManager } = await import('/assets/js/backend/modules/Crud.js');
        window.moduleList = ListManager.instances['showAll-' + entity + '-container'];
        await window.moduleList.loadingPromise;
    }, entity);

    return { payloads, errors };
}

function success(active, trashed, suspended = 0) {
    return { result: true, output: '<p>Lista aggiornata</p>', trashCounts: { active, suspended, trashed, all: active + suspended + trashed } };
}

async function expectCounts(page, entity, active, trashed, suspended = 0) {
    for (const [filter, value] of Object.entries({ active, suspended, trashed, all: active + suspended + trashed })) {
        const badge = page.locator('#' + entity + '-list [data-trash-count="' + filter + '"]');
        await expect(badge).toBeVisible();
        await expect(badge).toHaveText(String(value));
    }
    await expect(page.locator('#another-list [data-trash-count]')).toHaveText('99');
}

for (const entity of ['brands', 'categories', 'products']) {
    test(entity + ': primo caricamento e refresh aggiornano tutti i badge', async ({ page }) => {
        const fixture = await openList(page, entity, [success(4, 1), success(5, 2)]);
        await expectCounts(page, entity, 4, 1);

        await page.locator('.refresh-list').click();
        await expectCounts(page, entity, 5, 2);
        expect(fixture.payloads).toHaveLength(2);
        expect(fixture.errors).toEqual([]);
    });

    test(entity + ': cestino e ricerca mantengono i totali globali', async ({ page }) => {
        const fixture = await openList(page, entity, [success(4, 1)]);
        await page.locator('[data-trash-filter="trashed"] [data-trash-count]').click();
        await expect.poll(() => fixture.payloads.length).toBe(2);
        expect(fixture.payloads[1].get('trash_filter')).toBe('trashed');

        await page.locator('[data-search-field]').fill('Mario');
        await expect.poll(() => fixture.payloads.length).toBe(3);
        expect(fixture.payloads[2].get('searchFields[' + fields[entity] + ']')).toBe('Mario');
        await expectCounts(page, entity, 4, 1);
        expect(fixture.errors).toEqual([]);
    });

    test(entity + ': visualizza anche i conteggi a zero', async ({ page }) => {
        const fixture = await openList(page, entity, [success(0, 0)]);
        await expectCounts(page, entity, 0, 0);
        expect(fixture.errors).toEqual([]);
    });

    test(entity + ': esiti falliti e conteggi non validi non sovrascrivono i badge', async ({ page }) => {
        const fixture = await openList(page, entity, [success(4, 1), { result: false }, success(-1, 2), { result: true, output: '<p>Nessun conteggio</p>' }]);

        for (let i = 0; i < 3; i++) {
            await page.evaluate(() => window.moduleList.showAll());
            await expectCounts(page, entity, 4, 1);
        }

        expect(fixture.payloads).toHaveLength(4);
        expect(fixture.errors).toEqual([]);
    });


    test(entity + ': Sospesi ha filtro e conteggio propri', async ({ page }) => {
        const fixture = await openList(page, entity, [success(1, 1, 2)]);
        await expectCounts(page, entity, 1, 1, 2);
        expect(await page.locator('#' + entity + '-list [data-trash-filter]').evaluateAll(links => links.map(link => link.dataset.trashFilter))).toEqual(['active', 'suspended', 'trashed', 'all']);

        await page.locator('[data-trash-filter="suspended"] [data-trash-count]').click();
        await expect.poll(() => fixture.payloads.length).toBe(2);
        expect(fixture.payloads[1].get('trash_filter')).toBe('suspended');
        await expect(page.locator('[data-trash-filter="suspended"]')).toHaveClass(/active/);
        expect(fixture.errors).toEqual([]);
    });

    test(entity + ': asset sorgente e pubblico sincronizzati', () => {
        const moduleName = { brands: 'Brands', categories: 'Categories', products: 'Products' }[entity];
        const source = fs.readFileSync(path.join(root, 'modules/Backend', moduleName, 'Assets/js', entity + '.js'), 'utf8');
        const published = fs.readFileSync(path.join(root, 'public/assets/js/backend', entity + '.js'), 'utf8');
        expect(published).toBe(source);
    });
}

