const { test, expect } = require('@playwright/test');

const pageUrl = '/backend/auth/login';
const modulePath = '/assets/js/backend/backend.js';

test.beforeEach(async ({ page }) => {
    await page.goto(pageUrl);

    await page.evaluate(() => {
        document.getElementById('e2e-alert-errors')?.remove();

        const fixture = document.createElement('div');
        fixture.id = 'e2e-alert-errors';
        fixture.innerHTML = `
            <div class="small error_e2eCurrent">&nbsp;</div>
            <div class="small error_e2eOther">Errore indipendente</div>
        `;

        document.body.appendChild(fixture);
    });
});

test('La chiusura dell alert pulisce solo gli errori di validazione associati', async ({ page }) => {
    await page.evaluate(async path => {
        const { handleValidationErrors, showAlert } = await import(path);

        handleValidationErrors({ e2eCurrent: 'Errore corrente' });
        showAlert('danger', 'Errore di validazione');
    }, modulePath);

    await expect(page.locator('.error_e2eCurrent')).toHaveText('Errore corrente');
    await page.locator('#alert-container [data-bs-dismiss="alert"]').click();
    await expect(page.locator('#alert-container [role="alert"]')).toHaveCount(0);

    await expect.poll(() => page.locator('.error_e2eCurrent').evaluate(element => {
        return element.textContent === '\u00A0';
    })).toBe(true);
    await expect(page.locator('.error_e2eOther')).toHaveText('Errore indipendente');
});

test('Un alert precedente non cancella errori di validazione piu recenti', async ({ page }) => {
    await page.evaluate(async path => {
        const { handleValidationErrors, showAlert } = await import(path);

        handleValidationErrors({ e2eCurrent: 'Primo errore' });
        showAlert('danger', 'Primo alert');

        const previousAlert = document.querySelector('#alert-container [role="alert"]');

        handleValidationErrors({ e2eCurrent: 'Errore aggiornato' });
        showAlert('danger', 'Nuovo alert');

        previousAlert.dispatchEvent(new Event('closed.bs.alert'));
    }, modulePath);

    await expect(page.locator('.error_e2eCurrent')).toHaveText('Errore aggiornato');

    await page.locator('#alert-container [data-bs-dismiss="alert"]').click();
    await expect.poll(() => page.locator('.error_e2eCurrent').evaluate(element => {
        return element.textContent === '\u00A0';
    })).toBe(true);
});
