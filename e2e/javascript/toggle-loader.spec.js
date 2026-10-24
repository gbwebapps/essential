const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test('toggleLoader ripristina correttamente lo stato dei controlli', async ({ page }) => {
    await page.goto(appUrl('/backend/auth/login'));

    await page.evaluate(async () => {
        const { toggleLoader } = await import(new URL('assets/js/backend/backend.js', document.getElementById('hidden-urlbase').dataset.urlbase).href);

        document.getElementById('show-loader')?.remove();

        const container = document.createElement('div');
        container.id = 'e2e-controls';

        container.innerHTML = `
            <button id="e2e-enabled">Abilitato</button>
            <button id="e2e-disabled" disabled>Disabilitato</button>
            <button id="e2e-excluded" class="no-general-disabled">Escluso</button>
        `;

        document.body.appendChild(container);

        toggleLoader(true);
    });

    await expect(page.locator('#e2e-enabled')).toBeDisabled();
    await expect(page.locator('#e2e-disabled')).toBeDisabled();
    await expect(page.locator('#e2e-excluded')).toBeEnabled();

    await page.evaluate(async () => {
        const { toggleLoader } = await import(new URL('assets/js/backend/backend.js', document.getElementById('hidden-urlbase').dataset.urlbase).href);

        toggleLoader(false);
    });

    await expect(page.locator('#e2e-enabled')).toBeEnabled();
    await expect(page.locator('#e2e-disabled')).toBeDisabled();
    await expect(page.locator('#e2e-excluded')).toBeEnabled();
});
