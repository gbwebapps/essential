
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/account/edit');
const modulePath = appUrl('/assets/js/backend/modules/Account.js');
const editUrl = '/backend/account/edit';

test('Account EditManager gestisce la validazione e aggiorna account e navbar', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    const requests = [];

    await page.route('**/backend/account/edit', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                requests.length === 1
                    ? {
                        result: false,
                        errors: { firstname: 'Nome obbligatorio' },
                        message: 'Errore di validazione'
                    }
                    : {
                        result: true,
                        output: '<div id="e2eAccountResult">Account aggiornato</div>',
                        navBarTop: '<div id="e2eNavbarResult">Navbar aggiornata</div>',
                        message: 'Modifiche salvate'
                    }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { EditManager } = await import(path);

        EditManager.instance?.destroy();

        let container = document.getElementById('edit-account-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'edit-account-container';
            document.body.appendChild(container);
        }

        let navbar = document.getElementById('navbar-top-view');

        if (!navbar) {
            navbar = document.createElement('div');
            navbar.id = 'navbar-top-view';
            document.body.appendChild(navbar);
        }

        const manager = new EditManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('firstname', '');

        await manager.edit(firstData);

        const unlockedAfterValidation = !manager.isSubmitting;
        const validationField = document.querySelector('.error_firstname').textContent;
        const validationFieldVisible = document.querySelector('.error_firstname').checkVisibility();
        const validationAlert = document.querySelector('#alert-container [role="alert"]').checkVisibility() && document.querySelector('#alert-container [role="alert"]').textContent.includes('Errore di validazione');

        const secondData = new FormData();
        secondData.append('firstname', 'Utente E2E');

        await manager.edit(secondData);

        return {
            unlockedAfterValidation,
            validationField,
            validationFieldVisible,
            validationAlert,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(requests).toHaveLength(2);
    expect(requests.every(request => request.method() === 'POST')).toBe(true);

    expect(result).toEqual({
        unlockedAfterValidation: true,
        validationField: 'Nome obbligatorio',
        validationFieldVisible: true,
        validationAlert: true,
        isSubmitting: false
    });

    await expect(page.locator('#e2eAccountResult')).toHaveText('Account aggiornato');
    await expect(page.locator('#e2eNavbarResult')).toHaveText('Navbar aggiornata');
});


test('Account EditManager mostra solo l alert se cambia email senza password corrente', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/edit', async route => {
        if (route.request().method() === 'POST') {
            requests++;
        }

        await route.continue();
    });

    await page.evaluate(async path => {
        const { EditManager } = await import(path);

        EditManager.instance?.destroy();

        const manager = new EditManager();
        manager.init();

        const form = document.getElementById('account-edit');
        const emailInput = form.querySelector('[name="email"]');
        const passwordInput = form.querySelector('[name="currentPassword"]');

        emailInput.value = 'nuova-email-e2e@example.com';
        passwordInput.value = '';

        form.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    }, modulePath);

    expect(requests).toBe(0);
    await expect(page.locator('#account-edit .error_currentPassword')).toHaveText('\u00A0');
    await expect(page.locator('#alert-container [role="alert"]')).toContainText('Inserisci la password corrente.');
});


test('Account EditManager blocca operazioni duplicate durante il refresh', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/account/edit', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eRefreshResult">Form ripristinato</div>'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { EditManager } = await import(path);

        EditManager.instance?.destroy();

        const manager = new EditManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('uuid', 'e2e-test-uuid');

        const secondData = new FormData();
        secondData.append('uuid', 'e2e-test-uuid');

        await Promise.all([
            manager.refresh(firstData),
            manager.refresh(secondData)
        ]);
    }, modulePath);

    try {
        await expect.poll(() => requests).toBe(1);

        releaseRequest();
        await action;

        expect(requests).toBe(1);

        await expect(page.locator('#e2eRefreshResult')).toHaveText('Form ripristinato');
    } finally {
        releaseRequest();
    }
});


test('Account EditManager gestisce gli hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/account\/edit\/?$/);

    let requests = 0;

    await page.route('**/backend/account/edit', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eAccountHookResult">Account aggiornato</div>'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { EditManager } = await import(path);

        EditManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new EditManager({
            async onEditBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onEditAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onEditError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('firstname', 'Utente E2E');

        // Primo tentativo: errore nell'hook, nessuna richiesta HTTP.
        await manager.edit(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: richiesta AJAX e hook di successo.
        await manager.edit(formData);

        return {
            unlockedAfterError,
            isSubmitting: manager.isSubmitting,
            beforeCalls,
            afterCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        unlockedAfterError: true,
        isSubmitting: false,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1
    });

    await expect(page.locator('#e2eAccountHookResult')).toHaveText('Account aggiornato');
});
