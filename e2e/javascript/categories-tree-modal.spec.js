const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const origin = 'https://categories-modal.test';
const source = fs.readFileSync(path.join(root, 'modules/Backend/Categories/Views/partials/common/categoryTreePartial.php'), 'utf8');
const modal = source.split("<?php if($categoryTreeDisplay === 'modal'): ?>")[1]
    .split('<?php elseif')[0]
    .replace(/<\?=[\s\S]*?\?>/g, expression => expression.includes('buttons.close') ? 'Chiudi' : 'Albero categorie');

/** Usa il markup e gli asset reali senza collegarsi all'applicazione o al database. */
async function openModal(page, theme) {
    await page.route(origin + '/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

        if (pathname === '/') {
            await route.fulfill({
                contentType: 'text/html',
                body: `<!doctype html><html><head>
                    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
                    <link rel="stylesheet" href="/assets/css/backend/${theme}.css">
                    <link rel="stylesheet" href="/assets/css/backend/backend.css">
                    </head><body>${modal}
                    <button id="reference" class="btn btn-sm app-btn-secondary">Riferimento</button>
                    <div id="globalConfirmModal"><div class="modal-content">Conferma</div></div>
                    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
                    </body></html>`,
            });
            return;
        }

        const allowed = [
            '/assets/vendor/bootstrap/css/bootstrap.min.css',
            '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
            '/assets/css/backend/backend.css',
            '/assets/css/backend/' + theme + '.css',
        ];

        if ( ! allowed.includes(pathname)) {
            await route.abort();
            return;
        }

        await route.fulfill({
            path: path.join(root, 'public', pathname.slice(1)),
            contentType: pathname.endsWith('.js') ? 'application/javascript' : 'text/css',
        });
    });

    await page.goto(origin);
    await page.evaluate(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('categoriesTreeModal')).show());
    await expect(page.locator('#categoriesTreeModal')).toBeVisible();
    await expect.poll(() => page.evaluate(() => document.getElementById('categoriesTreeModal').classList.contains('show'))).toBe(true);
}

for (const theme of ['grey', 'dark']) {
    test('Albero categorie: chiusura rossa, hover e bordi nel tema ' + theme, async ({ page }) => {
        await openModal(page, theme);

        const button = page.locator('#categoriesTreeModal [data-bs-dismiss="modal"]');
        const content = page.locator('#categoriesTreeModal .modal-content');
        const border = theme === 'grey' ? 'rgb(108, 117, 125)' : 'rgb(34, 34, 34)';

        await expect(button).toHaveCSS('color', 'rgb(255, 255, 255)');
        await expect(button).toHaveCSS('border-top-color', border);
        await expect(button).toHaveCSS('background-image', 'linear-gradient(rgb(220, 53, 69), rgb(187, 45, 59))');

        if (theme === 'dark') {
            await expect(content).toHaveCSS('background-image', 'none');
            await expect(content).toHaveCSS('background-color', 'rgb(68, 68, 68)');
            await expect(content).toHaveCSS('border-top-color', border);
            await expect(page.locator('#globalConfirmModal .modal-content')).toHaveCSS('background-image', 'linear-gradient(rgb(34, 34, 34), rgb(51, 51, 51))');
        } else {
            await expect(content).toHaveCSS('background-image', 'linear-gradient(rgb(194, 200, 206), rgb(213, 218, 224))');
            await expect(content).toHaveCSS('border-top-color', border);
        }

        await button.hover();
        await expect(button).toHaveCSS('background-image', 'linear-gradient(rgb(187, 45, 59), rgb(220, 53, 69))');
        await expect(button).toHaveCSS('border-top-color', border);

        await page.keyboard.press('Escape');
        await expect(page.locator('#categoriesTreeModal')).toBeVisible();

        await page.screenshot({ path: test.info().outputPath('categories-tree-' + theme + '.png') });
        await button.click();
        await expect(page.locator('#categoriesTreeModal')).toBeHidden();
    });
}

for (const theme of ['white', 'black']) {
    test('Albero categorie: stile del pulsante invariato nel tema ' + theme, async ({ page }) => {
        await openModal(page, theme);

        const styles = await page.evaluate(() => {
            const read = selector => {
                const style = getComputedStyle(document.querySelector(selector));
                return [style.backgroundImage, style.color, style.borderTopColor];
            };

            return {
                close: read('#categoriesTreeModal [data-bs-dismiss="modal"]'),
                reference: read('#reference'),
            };
        });

        expect(styles.close).toEqual(styles.reference);
    });
}
