
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/admins/showAll');
const modulePath = appUrl('/assets/js/backend/modules/Crud.js');
const dataUrl = '/backend/admins/e2e-general-data';

async function openPage(page) {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    await page.evaluate(async ({ path, url }) => {
        const { GeneralDataManager } = await import(path);

        GeneralDataManager.instances.get('#e2eGeneralForm')?.destroy();

        const manager = new GeneralDataManager({
            url,
            formSelector: '#e2eGeneralForm',
            containerId: 'e2eGeneralData'
        });

        manager.init();

        const container = document.createElement('div');
        container.id = 'e2eGeneralData';
        document.body.appendChild(container);
    }, { path: modulePath, url: dataUrl });
}

async function submitData(page) {
    return page.evaluate(async path => {
        const { GeneralDataManager } = await import(path);
        const manager = GeneralDataManager.instances.get('#e2eGeneralForm');

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        await manager.getGeneralData(formData);
    }, modulePath);
}

test('GeneralDataManager aggiorna il contenitore dopo la risposta AJAX', async ({ page }) => {
    await openPage(page);

    let requests = 0;

    await page.route('**/backend/admins/e2e-general-data', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eGeneralResult">Dati generali aggiornati</div>'
            })
        });
    });

    await submitData(page);

    expect(requests).toBe(1);
    await expect(page.locator('#e2eGeneralResult')).toHaveText('Dati generali aggiornati');
});

test('GeneralDataManager blocca richieste duplicate', async ({ page }) => {
    await openPage(page);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/admins/e2e-general-data', async route => {
        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    const action = page.evaluate(async path => {
        const { GeneralDataManager } = await import(path);
        const manager = GeneralDataManager.instances.get('#e2eGeneralForm');

        await Promise.all([
            manager.getGeneralData(new FormData()),
            manager.getGeneralData(new FormData())
        ]);
    }, modulePath);

    try {
        await expect.poll(() => requests).toBe(1);
        releaseRequest();
        await action;

        expect(requests).toBe(1);
    } finally {
        releaseRequest();
    }
});

test('GeneralDataManager recupera dopo un errore', async ({ page }) => {
    await openPage(page);

    let attempts = 0;

    await page.route('**/backend/admins/e2e-general-data', async route => {
        attempts++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                attempts === 1
                    ? { result: false, message: 'Errore simulato' }
                    : { result: true }
            )
        });
    });

    await submitData(page);

    const unlockedAfterError = await page.evaluate(async path => {
        const { GeneralDataManager } = await import(path);

        return GeneralDataManager.instances.get('#e2eGeneralForm').isSubmitting === false;
    }, modulePath);

    await submitData(page);

    const finalState = await page.evaluate(async path => {
        const { GeneralDataManager } = await import(path);

        return GeneralDataManager.instances.get('#e2eGeneralForm').isSubmitting;
    }, modulePath);

    expect(attempts).toBe(2);
    expect(unlockedAfterError).toBe(true);
    expect(finalState).toBe(false);
});
