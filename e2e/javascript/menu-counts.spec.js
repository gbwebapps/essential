const { test, expect } = require('@playwright/test');
const { spawnSync } = require('node:child_process');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://menu-counts.test';

/** Renderizza le viste PHP reali con dati isolati, senza avviare framework o database. */
function renderMenus(restricted) {
    const php = `
        function esc($value, $context = null) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
        function lang($key) { $labels = ['active'=>'Attivi', 'trashed'=>'Eliminati', 'suspended'=>'Sospesi']; $parts = explode('.', $key); return $labels[end($parts)] ?? end($parts); }
        function base_url($path) { return '${origin}/' . $path; }
        function csrf_field() { return ''; }
        function view_cell($name, $data) { return ''; }
        function view($name, $data) { extract($data); ob_start(); include 'app/Views/' . $name . '.php'; return ob_get_clean(); }
        $currentAdmin = (object) ['firstname'=>'Test', 'lastname'=>'Admin', 'superadmin'=>${restricted ? 0 : 1}];
        $controller = 'admins'; $action = 'showAll';
        $menuTopRight = [['controller'=>'admins', 'route'=>'backend/admins/showAll', 'label'=>'Amministratori'], ['controller'=>'account', 'route'=>'backend/account', 'label'=>'Profilo']];
        $menuBottomLeft = [['controller'=>'brands', 'route'=>'backend/brands/showAll', 'label'=>'Marche'], ['controller'=>'categories', 'route'=>'backend/categories/showAll', 'label'=>'Categorie'], ['controller'=>'products', 'route'=>'backend/products/showAll', 'label'=>'Prodotti']];
        $menuBottomRight = [];
        $menuCounts = [];
        foreach (${restricted ? "['brands']" : "['admins', 'brands', 'categories', 'products']"} as $entity) { $menuCounts[$entity] = ['active'=>4, 'trashed'=>2, 'suspended'=>1]; }
        include 'app/Views/backend/template/navbarTopView.php';
        include 'app/Views/backend/template/navbarBottomView.php';
    `;
    const result = spawnSync('php', ['-r', php], { cwd: root, encoding: 'utf8' });
    if (result.status !== 0 || result.stderr) throw new Error(result.stderr || 'Rendering PHP fallito');
    return result.stdout;
}

async function openMenus(page, theme = 'grey', restricted = false, replies = []) {
    const menus = renderMenus(restricted);
    let requests = 0;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

        if (pathname === '/') {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
                <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
                <link rel="stylesheet" href="/assets/css/backend/${theme}.css">
                <link rel="stylesheet" href="/assets/css/backend/backend.css">
                </head><body>${menus}
                <div id="controller" data-controller="fixture"></div><div id="action" data-action="index"></div>
                <div id="hidden-urlbase" data-urlbase="${origin}/"></div><div id="lang-definitions"></div>
                </body></html>` });
            return;
        }

        if (pathname === '/fixture') {
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify(replies[Math.min(requests++, replies.length - 1)]) });
            return;
        }

        if (pathname.startsWith('/assets/') && /\.(css|js)$/.test(pathname)) {
            await route.fulfill({ path: path.join(root, 'public', pathname.slice(1)), contentType: pathname.endsWith('.js') ? 'application/javascript' : 'text/css' });
            return;
        }

        await route.abort();
    });

    await page.goto(origin);
    await page.evaluate(async () => {
        window.ajax = await import('/assets/js/backend/backend.js');
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('show'));
        document.getElementById('navbarSupportedContent').classList.add('show');
    });

    return { errors, requests: () => requests };
}

for (const theme of ['white', 'black', 'grey', 'dark']) {
    test('Menu: tre badge leggibili per ogni CRUD nel tema ' + theme, async ({ page }) => {
        const fixture = await openMenus(page, theme);
        await expect(page.locator('[data-menu-counts]')).toHaveCount(4);

        for (const entity of ['admins', 'brands', 'categories', 'products']) {
            const container = page.locator('[data-menu-counts="' + entity + '"]');
            const order = await container.locator('[data-menu-count]').evaluateAll(badges => badges.map(badge => badge.dataset.menuCount));
            expect(order).toEqual(['active', 'suspended', 'trashed']);
            await expect(container.locator('[data-menu-count="active"]')).toHaveText('4');
            await expect(container.locator('[data-menu-count="trashed"]')).toHaveText('2');
            await expect(container.locator('[data-menu-count="suspended"]')).toHaveText('1');
            await expect(container.locator('[data-menu-count="suspended"]')).toHaveAttribute('title', 'Sospesi: 1');
            await expect(container).toBeVisible();
            if (theme === 'dark') {
                await expect(container.locator('[data-menu-count="active"]')).toHaveCSS('background-color', 'rgb(25, 135, 84)');
                await expect(container.locator('[data-menu-count="suspended"]')).toHaveCSS('background-color', 'rgb(255, 102, 0)');
                await expect(container.locator('[data-menu-count="trashed"]')).toHaveCSS('background-color', 'rgb(220, 53, 69)');
                await expect(container.locator('[data-menu-count="suspended"]')).toHaveCSS('color', 'rgb(0, 0, 0)');
            }
            const alignment = await container.evaluate(element => {
                const link = element.closest('a');
                return Math.abs(element.getBoundingClientRect().right - (link.getBoundingClientRect().right - parseFloat(getComputedStyle(link).paddingRight)));
            });
            expect(alignment).toBeLessThan(1);
        }

        expect(fixture.errors).toEqual([]);
    });
}

test('Menu: le risposte AJAX aggiornano numeri e descrizioni senza chiamate aggiuntive', async ({ page }) => {
    const fixture = await openMenus(page, 'grey', false, [{ result: true, menuCounts: { brands: { active: 0, trashed: 6, suspended: 0 } } }]);
    await page.evaluate(() => window.ajax.apiFetch('/fixture', { method: 'POST' }));

    await expect(page.locator('[data-menu-counts="brands"] [data-menu-count="active"]')).toHaveText('0');
    await expect(page.locator('[data-menu-counts="brands"] [data-menu-count="trashed"]')).toHaveText('6');
    await expect(page.locator('[data-menu-counts="brands"] [data-menu-count="trashed"]')).toHaveAttribute('aria-label', 'Eliminati: 6');
    await expect(page.locator('[data-menu-counts="categories"] [data-menu-count="active"]')).toHaveText('4');
    expect(fixture.requests()).toBe(1);
    expect(fixture.errors).toEqual([]);
});

test('Menu: errori e conteggi incoerenti non sovrascrivono i valori', async ({ page }) => {
    const fixture = await openMenus(page, 'dark', false, [
        { result: false, menuCounts: { brands: { active: 0, trashed: 0, suspended: 0 } } },
        { result: true, menuCounts: { brands: { active: -1, trashed: 0, suspended: 2 } } },
    ]);

    await page.evaluate(async () => {
        await window.ajax.apiFetch('/fixture', { method: 'POST' });
        await window.ajax.apiFetch('/fixture', { method: 'POST' });
    });

    await expect(page.locator('[data-menu-counts="brands"] [data-menu-count="active"]')).toHaveText('4');
    expect(fixture.errors).toEqual([]);
});

test('Menu: nessun badge senza autorizzazione alla relativa lista', async ({ page }) => {
    const fixture = await openMenus(page, 'grey', true);
    await expect(page.locator('[data-menu-counts]')).toHaveCount(1);
    await expect(page.locator('[data-menu-counts="brands"]')).toBeVisible();
    await expect(page.locator('[data-menu-counts="admins"]')).toHaveCount(0);
    expect(fixture.errors).toEqual([]);
});

test('Menu: badge allineati a destra anche su mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const fixture = await openMenus(page, 'grey');
    const geometry = await page.locator('[data-menu-counts]').evaluateAll(elements => elements.map(element => {
        const link = element.closest('a');
        const badge = element.getBoundingClientRect();
        const anchor = link.getBoundingClientRect();
        return { right: badge.right, offset: Math.abs(badge.right - (anchor.right - parseFloat(getComputedStyle(link).paddingRight))) };
    }));

    for (const box of geometry) {
        expect(box.offset).toBeLessThan(1);
        expect(box.right).toBeLessThanOrEqual(390);
    }

    expect(fixture.errors).toEqual([]);
});
