
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = appUrl('/backend/tools');
const modulePath = appUrl('/assets/js/backend/modules/Tools.js');

async function createTestManager(page, html) {
    await page.evaluate(async ({ path, markup }) => {
        const { ToolsManager } = await import(path);

        ToolsManager.instance?.destroy();

        document.getElementById('e2e-tools-root')?.remove();

        const root = document.createElement('div');
        root.id = 'e2e-tools-root';
        root.innerHTML = markup;

        document.body.appendChild(root);

        new ToolsManager();
    }, {
        path: modulePath,
        markup: html
    });
}

async function getManagerState(page) {
    return page.evaluate(async path => {
        const { ToolsManager } = await import(path);
        const manager = ToolsManager.instance;

        return {
            isSubmitting: manager.isSubmitting,
            isConfirming: manager.isConfirming,
            destroyed: manager.destroyed
        };
    }, modulePath);
}

test('ToolsManager carica il pannello prima dell’apertura e lo svuota alla chiusura', async ({ page }) => {
    let requests = 0;
    let receivedPayload = null;
    let releaseRequest;

    const requestGate = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/tools/openTools', async route => {
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
                output: '<div id="e2eToolsLoaded">Pannello strumenti caricato</div>'
            })
        });
    });

    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/tools\/?$/);

    // Backups non richiede Flatpickr né inizializzazione dei manager CSV.
    const trigger = page.locator('button[data-env="backups"]');
    const collapse = page.locator('#main_collapse_backups');
    const container = page.locator('#backups-tools-container');

    await expect(trigger).toHaveCount(1);
    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    await trigger.click();

    await expect.poll(() => requests).toBe(1);

    // Il pannello deve restare chiuso durante la richiesta.
    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    // Simula un secondo click durante il caricamento.
    await trigger.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true
        }));
    });

    expect(requests).toBe(1);

    releaseRequest();

    await expect(page.locator('#e2eToolsLoaded')).toHaveText('Pannello strumenti caricato');
    await expect(collapse).toHaveClass(/\bshow\b/);

    expect(receivedPayload).toContain('name="env"\r\n\r\nbackups');

    // Chiusura dell'accordion.
    await trigger.click();

    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();
    await expect(page.locator('#e2eToolsLoaded')).toHaveCount(0);

    // Verifica della pulizia delle istanze Flatpickr gestite dal manager.
    const pickerState = await page.evaluate(async path => {
        const { ToolsManager } = await import(path);
        const manager = ToolsManager.instance;

        let destroyedFrom = 0;
        let destroyedTo = 0;

        manager.datePickers.manageAudits = {
            from: {
                destroy() {
                    destroyedFrom++;
                }
            },
            to: {
                destroy() {
                    destroyedTo++;
                }
            }
        };

        const container = document.createElement('div');
        container.id = 'manageAudits-tools-container';
        document.body.appendChild(container);

        manager.resetContainer('manageAudits-tools-container');

        const result = {
            destroyedFrom,
            destroyedTo,
            hasPickerReference: Object.hasOwn(manager.datePickers, 'manageAudits')
        };

        container.remove();

        return result;
    }, modulePath);

    expect(pickerState).toEqual({
        destroyedFrom: 1,
        destroyedTo: 1,
        hasPickerReference: false
    });

    expect(requests).toBe(1);
});

test('ToolsManager valida gli audit, gestisce conferma e impedisce cancellazioni duplicate', async ({ page }) => {
    let validations = 0;
    let deletions = 0;
    let receivedPayload = null;

    await page.route('**/backend/tools/validateAuditsDateRequest', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        validations++;

        let data;

        if (validations === 1) {
            data = {
                result: false,
                message: 'Intervallo di date non valido'
            };
        } else if (validations === 2) {
            data = {
                result: true,
                count: 0,
                noDataMessage: 'Nessun audit da eliminare'
            };
        } else {
            data = {
                result: true,
                count: 5,
                confirmMessage: 'Confermi la cancellazione degli audit?'
            };
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(data)
        });
    });

    await page.route('**/backend/tools/deleteAudits', async route => {
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
                message: 'Audit eliminati'
            })
        });
    });

    await page.route('**/backend/tools/openTools', async route => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div class="e2e-audits-reset">Interfaccia aggiornata</div>'
            })
        });
    });

    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/tools\/?$/);

    await createTestManager(page, `
        <div id="manageAudits-tools-container" class="accordion-body">
            <form id="manageAudits-tools-form">
                <input type="hidden" name="date_from" value="2026-10-01">
                <input type="hidden" name="date_to" value="2026-10-10">
                <div class="error_date_from"></div>
                <button type="button"
                    class="btn-action-audits"
                    data-action="delete">
                    Elimina audit
                </button>
            </form>
        </div>
    `);

    const button = page.locator('#e2e-tools-root .btn-action-audits');
    const modal = page.locator('#globalConfirmModal');

    // Prima richiesta: validazione respinta.
    await button.click();

    await expect.poll(() => validations).toBe(1);
    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    expect(deletions).toBe(0);

    // Seconda richiesta: nessun record; reset del pannello.
    await button.click();

    await expect.poll(() => validations).toBe(2);

    await expect(page.locator('#manageAudits-tools-container .e2e-audits-reset')).toHaveCount(1);

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    expect(deletions).toBe(0);

    // Ripristina un pulsante di test nel contenitore aggiornato.
    await page.locator('#e2e-tools-root #manageAudits-tools-container').evaluate(container => {
        container.innerHTML = `
            <form id="manageAudits-tools-form">
                <input type="hidden" name="date_from" value="2026-10-01">
                <input type="hidden" name="date_to" value="2026-10-10">
                <button type="button"
                    class="btn-action-audits"
                    data-action="delete">
                    Elimina audit
                </button>
            </form>
        `;
    });

    const restoredButton = page.locator('#manageAudits-tools-container .btn-action-audits');

    // Terza richiesta: conferma aperta, poi annullamento.
    await restoredButton.click();

    await expect(modal).toBeVisible();
    await modal.locator('.btn-cancel').click();

    await expect(modal).toBeHidden();

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    expect(deletions).toBe(0);

    // Quarta richiesta: doppio evento e conferma positiva.
    await restoredButton.evaluate(element => {
        element.click();
        element.click();
    });

    await expect(modal).toBeVisible();

    expect(await getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: true,
        destroyed: false
    });

    await modal.locator('.btn-ok').click();

    await expect.poll(() => deletions).toBe(1);

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    expect(validations).toBe(4);
    expect(deletions).toBe(1);
    expect(receivedPayload).toContain('name="date_from"\r\n\r\n2026-10-01');
    expect(receivedPayload).toContain('name="date_to"\r\n\r\n2026-10-10');
});

test('ToolsManager aggiorna le statistiche delle tabelle e genera backup senza duplicazioni', async ({ page }) => {
    let optimizations = 0;
    let backupGenerations = 0;
    let backupReloads = 0;

    const optimizationPayloads = [];
    const backupPayloads = [];

    await page.route('**/backend/tools/optimizeTable', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        optimizations++;
        optimizationPayloads.push(route.request().postData());

        const tableData = optimizations === 1
            ? {
                name: 'admins',
                rows: 12,
                size: 2.5,
                overhead: 0.1
            }
            : [
                {
                    name: 'admins',
                    rows: 12,
                    size: 2.5,
                    overhead: 0.1
                },
                {
                    name: 'groups',
                    rows: 8,
                    size: 1.5,
                    overhead: 0.2
                }
            ];

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Ottimizzazione completata',
                tableData
            })
        });
    });

    await page.route('**/backend/tools/backups', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        backupGenerations++;
        backupPayloads.push(route.request().postData());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Backup generato'
            })
        });
    });

    await page.route('**/backend/tools/openTools', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        const payload = route.request().postData() || '';

        if (payload.includes('name="env"\r\n\r\nbackups')) {
            backupReloads++;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eBackupsRefreshed">Elenco backup aggiornato</div>'
            })
        });
    });

    await page.goto(pageUrl);

    await expect(page).toHaveURL(/\/backend\/tools\/?$/);

    await createTestManager(page, `
        <div id="dbMaintenance-tools-container" class="accordion-body">
            <form id="dbMaintenance-tools-form"
                data-lang-total-space="Spazio %s"
                data-lang-overhead="Overhead %s"
                data-lang-rows="Righe %d">

                <ul>
                    <li data-table="admins">
                        <div class="app-text-muted d-flex">Stato iniziale admins</div>
                    </li>
                    <li data-table="groups">
                        <div class="app-text-muted d-flex">Stato iniziale groups</div>
                    </li>
                </ul>

                <div class="app-text-muted infoDb">Totali iniziali</div>

                <button type="button"
                    class="btn-optimize-table"
                    data-table="admins">
                    Ottimizza admins
                </button>

                <button type="button"
                    class="btn-optimize-all"
                    data-tables='["admins","groups"]'>
                    Ottimizza tutte
                </button>
            </form>
        </div>

        <div id="backups-tools-container" class="accordion-body">
            <form id="backups-tools-form">
                <button type="button" class="btn-generate-backups">
                    Genera backup
                </button>
            </form>
        </div>
    `);

    // Ottimizzazione singola tabella.
    await page.locator('.btn-optimize-table').click();

    await expect.poll(() => optimizations).toBe(1);

    await expect(page.locator('li[data-table="admins"] .app-text-muted.d-flex')).toContainText('Righe 12');
    await expect(page.locator('li[data-table="admins"] .app-text-muted.d-flex')).toContainText('Spazio 2.50');

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    // Ottimizzazione massiva.
    await page.locator('.btn-optimize-all').click();

    await expect.poll(() => optimizations).toBe(2);

    await expect(page.locator('li[data-table="groups"] .app-text-muted.d-flex')).toContainText('Righe 8');
    await expect(page.locator('.app-text-muted.infoDb')).toContainText('Spazio 4.00');
    await expect(page.locator('.app-text-muted.infoDb')).toContainText('Overhead 0.30');

    // Gli input temporanei devono essere rimossi.
    await expect(page.locator('#dbMaintenance-tools-form input[name="table"]')).toHaveCount(0);
    await expect(page.locator('#dbMaintenance-tools-form input[name="table[]"]')).toHaveCount(0);

    expect(optimizationPayloads[0]).toContain('name="table"\r\n\r\nadmins');
    expect(optimizationPayloads[1]).toContain('name="table[]"\r\n\r\nadmins');
    expect(optimizationPayloads[1]).toContain('name="table[]"\r\n\r\ngroups');

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    // Generazione backup: doppio click simulato.
    await page.locator('.btn-generate-backups').evaluate(element => {
        element.click();
        element.click();
    });

    await expect.poll(() => backupGenerations).toBe(1);

    await expect(page.locator('#e2eBackupsRefreshed')).toHaveText('Elenco backup aggiornato');

    await expect.poll(() => getManagerState(page)).toEqual({
        isSubmitting: false,
        isConfirming: false,
        destroyed: false
    });

    expect(backupGenerations).toBe(1);
    expect(backupReloads).toBe(1);

    expect(backupPayloads[0]).toContain('name="action"\r\n\r\ngenerateBackups');

    // Distruzione del manager e verifica dei listener.
    const destroyedState = await page.evaluate(async path => {
        const { ToolsManager } = await import(path);
        const manager = ToolsManager.instance;

        manager.destroy();

        return {
            destroyed: manager.destroyed,
            singletonCleared: ToolsManager.instance === null
        };
    }, modulePath);

    expect(destroyedState).toEqual({
        destroyed: true,
        singletonCleared: true
    });

    // Un evento successivo a destroy() non deve avviare nuove operazioni.
    await page.locator('#e2e-tools-root').evaluate(root => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-generate-backups';
        root.appendChild(button);

        button.click();
    });

    expect(backupGenerations).toBe(1);
});
