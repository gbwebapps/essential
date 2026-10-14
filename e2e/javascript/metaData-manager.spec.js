
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/admins/showAll';
const modulePath = '/assets/js/backend/modules/Crud.js';
const dataUrl = '/backend/admins/e2e-meta-data';

async function openPage(page) {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    await page.evaluate(async ({ path, url }) => {
        const { MetaDataManager } = await import(path);

        MetaDataManager.instances.get('#e2eMetaForm')?.destroy();

        const manager = new MetaDataManager({
            url,
            formSelector: '#e2eMetaForm',
            containerId: 'e2eMetaData'
        });

        manager.init();

        const container = document.createElement('div');
        container.id = 'e2eMetaData';
        document.body.appendChild(container);
    }, { path: modulePath, url: dataUrl });
}

async function submitData(page) {
    await page.evaluate(async path => {
        const { MetaDataManager } = await import(path);
        const manager = MetaDataManager.instances.get('#e2eMetaForm');

        const formData = new FormData();
        formData.append('uuid', 'e2e-test-uuid');

        await manager.getMetaData(formData);
    }, modulePath);
}

test('MetaDataManager aggiorna il contenitore dopo la risposta AJAX', async ({ page }) => {
    await openPage(page);

    let requests = 0;

    await page.route('**/backend/admins/e2e-meta-data', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eMetaResult">Metadati aggiornati</div>'
            })
        });
    });

    await submitData(page);

    expect(requests).toBe(1);
    await expect(page.locator('#e2eMetaResult')).toHaveText('Metadati aggiornati');
});

test('MetaDataManager blocca richieste duplicate', async ({ page }) => {
    await openPage(page);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/admins/e2e-meta-data', async route => {
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
        const { MetaDataManager } = await import(path);
        const manager = MetaDataManager.instances.get('#e2eMetaForm');

        await Promise.all([
            manager.getMetaData(new FormData()),
            manager.getMetaData(new FormData())
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

test('MetaDataManager recupera dopo un errore', async ({ page }) => {
    await openPage(page);

    let attempts = 0;

    await page.route('**/backend/admins/e2e-meta-data', async route => {
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
        const { MetaDataManager } = await import(path);

        return MetaDataManager.instances.get('#e2eMetaForm').isSubmitting === false;
    }, modulePath);

    await submitData(page);

    const finalState = await page.evaluate(async path => {
        const { MetaDataManager } = await import(path);

        return MetaDataManager.instances.get('#e2eMetaForm').isSubmitting;
    }, modulePath);

    expect(attempts).toBe(2);
    expect(unlockedAfterError).toBe(true);
    expect(finalState).toBe(false);
});
