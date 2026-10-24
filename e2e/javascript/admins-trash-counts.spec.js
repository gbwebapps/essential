const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://admins-counts.test';
const source = fs.readFileSync(path.join(root, 'app/Views/backend/admins/showAllView.php'), 'utf8');
const labels = { activeRecords: 'Attivi', suspendedRecords: 'Sospesi', trashedRecords: 'Eliminati', allRecords: 'Tutti' };
const links = source.split('<!-- Selezione stato record -->')[1]
    .split('<!-- End Selezione stato record -->')[0]
    .replace(/<\?= lang\('backend\/admins\.links\.(\w+)'\); \?>/g, (_, key) => labels[key]);

/** Carica vista e JavaScript reali, senza richieste all'applicazione o al database. */
async function openList(page, replies) {
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
                <div id="controller" data-controller="admins"></div>
                <div id="action" data-action="showAll"></div>
                <div id="hidden-urlbase" data-urlbase="${origin}/"></div>
                <div id="lang-definitions"></div>
                <div id="admins-list">${links}
                    <a href="#" class="refresh-list">Ricarica</a>
                    <input data-search-field="firstname">
                    <div id="showAll-admins-container"></div>
                </div>
                <div id="another-list"><span data-trash-count="active">99</span></div>
                </body></html>` });
            return;
        }

        if (pathname === '/backend/admins/showAll') {
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
    await page.evaluate(async () => {
        await import('/assets/js/backend/admins.js');
        const { ListManager } = await import('/assets/js/backend/modules/Crud.js');
        window.adminsList = ListManager.instances['showAll-admins-container'];
        await window.adminsList.loadingPromise;
    });

    return { payloads, errors };
}

function success(active, trashed, suspended = 0) {
    return { result: true, output: '<p>Lista aggiornata</p>', trashCounts: { active, suspended, trashed, all: active + suspended + trashed } };
}

async function expectCounts(page, active, trashed, suspended = 0) {
    for (const [filter, value] of Object.entries({ active, suspended, trashed, all: active + suspended + trashed })) {
        const badge = page.locator('#admins-list [data-trash-count="' + filter + '"]');
        await expect(badge).toBeVisible();
        await expect(badge).toHaveText(String(value));
    }
    await expect(page.locator('#another-list [data-trash-count]')).toHaveText('99');
}

test('Admins: primo caricamento e refresh aggiornano tutti i badge', async ({ page }) => {
    const fixture = await openList(page, [success(4, 1), success(5, 2)]);
    await expectCounts(page, 4, 1);

    await page.locator('.refresh-list').click();
    await expectCounts(page, 5, 2);
    expect(fixture.payloads).toHaveLength(2);
    expect(fixture.errors).toEqual([]);
});

test('Admins: cestino e ricerca mantengono i totali globali', async ({ page }) => {
    const fixture = await openList(page, [success(4, 1)]);
    await page.locator('[data-trash-filter="trashed"] [data-trash-count]').click();
    await expect.poll(() => fixture.payloads.length).toBe(2);
    expect(fixture.payloads[1].get('trash_filter')).toBe('trashed');

    await page.locator('[data-search-field="firstname"]').fill('Mario');
    await expect.poll(() => fixture.payloads.length).toBe(3);
    expect(fixture.payloads[2].get('searchFields[firstname]')).toBe('Mario');
    await expectCounts(page, 4, 1);
    expect(fixture.errors).toEqual([]);
});

test('Admins: visualizza anche i conteggi a zero', async ({ page }) => {
    const fixture = await openList(page, [success(0, 0)]);
    await expectCounts(page, 0, 0);
    expect(fixture.errors).toEqual([]);
});

test('Admins: Sospesi ha filtro e conteggio propri', async ({ page }) => {
    const fixture = await openList(page, [success(1, 1, 2)]);
    await expectCounts(page, 1, 1, 2);
    expect(await page.locator('#admins-list [data-trash-filter]').evaluateAll(links => links.map(link => link.dataset.trashFilter))).toEqual(['active', 'suspended', 'trashed', 'all']);

    await page.locator('[data-trash-filter="suspended"] [data-trash-count]').click();
    await expect.poll(() => fixture.payloads.length).toBe(2);
    expect(fixture.payloads[1].get('trash_filter')).toBe('suspended');
    await expect(page.locator('[data-trash-filter="suspended"]')).toHaveClass(/active/);
    expect(fixture.errors).toEqual([]);
});

test('Admins: esiti falliti e conteggi non validi non sovrascrivono i badge', async ({ page }) => {
    const fixture = await openList(page, [success(4, 1), { result: false }, success(-1, 2), { result: true, output: '<p>Nessun conteggio</p>' }]);

    for (let i = 0; i < 3; i++) {
        await page.evaluate(() => window.adminsList.showAll());
        await expectCounts(page, 4, 1);
    }

    expect(fixture.payloads).toHaveLength(4);
    expect(fixture.errors).toEqual([]);
});
