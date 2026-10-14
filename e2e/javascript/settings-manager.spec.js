
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/settings';
const modulePath = '/assets/js/backend/modules/Settings.js';

test('Settings Auth espone la protezione separata del reset password pubblico', async ({ page }) => {
    await page.goto(pageUrl);

    const trigger = page.locator('button[data-env="auth"]');

    await trigger.click();

    await expect(page.locator('#auth_resetAttempts')).toBeVisible();
    await expect(page.locator('#auth_resetAttemptsLimit')).toBeVisible();
    await expect(page.locator('#auth_resetAttemptsInterval')).toBeVisible();
    await expect(page.locator('#auth_twoFactorAttempts')).toBeVisible();
    await expect(page.locator('#auth_twoFactorLimit')).toBeVisible();
    await expect(page.locator('#auth_twoFactorAttemptsInterval')).toBeVisible();
    await expect(page.locator('#auth_resetPasswordLimit')).toHaveCount(0);
    await expect(page.locator('#auth_resetPasswordInterval')).toHaveCount(0);

    expect(await page.locator('#auth_twoFactor').evaluate((first, second) => {
        return Boolean(first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING);
    }, await page.locator('#auth_resetAttempts').elementHandle())).toBe(true);
});

test('SettingsManager carica il pannello prima dell’apertura e lo svuota alla chiusura', async ({ page }) => {
    await page.goto(pageUrl);
    console.log('URL Playwright:', page.url());
    console.log('Titolo:', await page.title());
    console.log('Pulsanti data-env:', await page.locator('button[data-env]').count());

    let requests = 0;
    let receivedPayload = null;
    let releaseRequest;

    const requestGate = new Promise(resolve => {
        releaseRequest = resolve;
    });

    const trigger = page.locator('button[data-env]').first();
    const env = await trigger.getAttribute('data-env');

    expect(env).toBeTruthy();

    const collapse = page.locator(`#main_collapse_${env}`);
    const container = page.locator(`#${env}-settings-container`);

    await page.route('**/backend/settings/openSettings', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;
        receivedPayload = route.request().postData();

        await requestGate;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eSettingsLoaded">Impostazioni caricate</div>'
            })
        });
    });

    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    await trigger.click();

    await expect.poll(() => requests).toBe(1);

    // Il contenuto non è ancora disponibile: l'accordion resta chiuso.
    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    // Simula un secondo click durante la richiesta AJAX.
    await trigger.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true
        }));
    });

    expect(requests).toBe(1);

    releaseRequest();

    await expect(page.locator('#e2eSettingsLoaded')).toHaveText('Impostazioni caricate');
    await expect(collapse).toHaveClass(/\bshow\b/);

    expect(receivedPayload).toContain(`name="env"\r\n\r\n${env}`);

    // Chiusura e distruzione del contenuto dinamico.
    await trigger.click();

    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    await expect(page.locator('#e2eSettingsLoaded')).toHaveCount(0);
    expect(requests).toBe(1);
});

test('SettingsManager gestisce validazione, salvataggio e aggiornamento delle viste', async ({ page }) => {
    await page.goto(pageUrl);

    let requests = 0;
    const receivedPayloads = [];

    await page.route('**/backend/settings/saveSettings', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;
        receivedPayloads.push(route.request().postData());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(requests === 1
                ? {
                    errors: {
                        name: 'Nome obbligatorio'
                    },
                    message: 'Errore di validazione'
                }
                : {
                    result: true,
                    output: '<div id="e2eSettingsSaved">Impostazioni salvate</div>',
                    fragments: {
                        '#e2eSettingsFragment': '<span id="e2eSettingsFragmentUpdated">Frammento aggiornato</span>'
                    },
                    message: 'Salvataggio completato'
                })
        });
    });

    await page.evaluate(async path => {
        const { SettingsManager } = await import(path);

        SettingsManager.instance?.destroy();

        const container = document.createElement('div');
        container.id = 'e2e-settings-container';
        container.className = 'accordion-body';

        container.innerHTML = `
            <form id="e2e-settings">
                <input type="text" name="name" value="Impostazioni E2E">
                <div class="error_name"></div>
                <button type="submit">Salva</button>
            </form>
        `;

        document.body.appendChild(container);

        const fragment = document.createElement('div');
        fragment.id = 'e2eSettingsFragment';
        document.body.appendChild(fragment);

        const manager = new SettingsManager();
        manager.init();
    }, modulePath);

    const form = page.locator('#e2e-settings');

    // Prima richiesta: errori di validazione.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => requests).toBe(1);
    await expect(form.locator('.error_name')).toHaveText('Nome obbligatorio');

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SettingsManager } = await import(path);
            return SettingsManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    // Seconda richiesta: salvataggio riuscito.
    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => requests).toBe(2);

    await expect(page.locator('#e2eSettingsSaved')).toHaveText('Impostazioni salvate');
    await expect(page.locator('#e2eSettingsFragmentUpdated')).toHaveText('Frammento aggiornato');

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SettingsManager } = await import(path);
            return SettingsManager.instance.isSubmitting;
        }, modulePath);
    }).toBe(false);

    expect(requests).toBe(2);

    for (const payload of receivedPayloads) {
        expect(payload).toContain('name="name"\r\n\r\nImpostazioni E2E');
        expect(payload).toContain('name="env"\r\n\r\ne2e');
    }

    await page.evaluate(async path => {
        const { SettingsManager } = await import(path);
        SettingsManager.instance.destroy();
    }, modulePath);
});

test('SettingsManager controlla i vincoli, gestisce la conferma ed evita eliminazioni duplicate', async ({ page }) => {
    await page.goto(pageUrl);

    let checks = 0;
    let deletions = 0;
    let reloads = 0;
    let receivedPayload = null;

    await page.route('**/backend/settings/checkDeleteSettings', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        checks++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: checks === 1 ? false : true,
                message: checks === 1 ? 'Nessuna personalizzazione da eliminare' : ''
            })
        });
    });

    await page.route('**/backend/settings/deleteSettings', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        deletions++;
        receivedPayload = route.request().postData();

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Impostazioni ripristinate'
            })
        });
    });

    await page.route('**/backend/settings/openSettings', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        reloads++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eSettingsRestored">Impostazioni ricaricate</div>'
            })
        });
    });

    await page.evaluate(async path => {
        const { SettingsManager } = await import(path);

        SettingsManager.instance?.destroy();

        const container = document.createElement('div');
        container.id = 'e2e-settings-container';
        container.className = 'accordion-body';

        container.innerHTML = `
            <form id="e2e-settings">
                <button type="button"
                    class="btn-delete-e2e"
                    data-message="Confermi il ripristino delle impostazioni?">
                    Ripristina
                </button>
            </form>
        `;

        document.body.appendChild(container);

        const manager = new SettingsManager();
        manager.init();
    }, modulePath);

    const button = page.locator('.btn-delete-e2e');
    const modal = page.locator('#globalConfirmModal');

    // Primo tentativo: il backend nega l'operazione.
    await button.click();

    await expect.poll(() => checks).toBe(1);

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SettingsManager } = await import(path);
            return SettingsManager.instance.isConfirming;
        }, modulePath);
    }).toBe(false);

    expect(deletions).toBe(0);
    await expect(modal).toBeHidden();

    // Secondo tentativo: conferma aperta, poi annullamento.
    await button.click();

    await expect(modal).toBeVisible();
    await modal.locator('.btn-cancel').click();

    await expect(modal).toBeHidden();

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SettingsManager } = await import(path);
            return SettingsManager.instance.isConfirming;
        }, modulePath);
    }).toBe(false);

    expect(deletions).toBe(0);

    // Terzo tentativo: eventi concorrenti e conferma positiva.
    await button.evaluate(element => {
        element.click();
        element.click();
    });

    await expect(modal).toBeVisible();

    const blockedDuringConfirmation = await page.evaluate(async path => {
        const { SettingsManager } = await import(path);
        return SettingsManager.instance.isConfirming;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);

    await modal.locator('.btn-ok').click();

    await expect.poll(() => deletions).toBe(1);
    await expect(page.locator('#e2eSettingsRestored')).toHaveText('Impostazioni ricaricate');

    await expect.poll(async () => {
        return page.evaluate(async path => {
            const { SettingsManager } = await import(path);

            return {
                isSubmitting: SettingsManager.instance.isSubmitting,
                isConfirming: SettingsManager.instance.isConfirming
            };
        }, modulePath);
    }).toEqual({
        isSubmitting: false,
        isConfirming: false
    });

    expect(checks).toBe(3);
    expect(deletions).toBe(1);
    expect(reloads).toBe(1);
    expect(receivedPayload).toContain('name="env"\r\n\r\ne2e');

    await page.evaluate(async path => {
        const { SettingsManager } = await import(path);
        SettingsManager.instance.destroy();
    }, modulePath);
});
