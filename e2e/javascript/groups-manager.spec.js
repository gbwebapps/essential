
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/groups';
const modulePath = '/assets/js/backend/modules/Groups.js';

test('GroupsManager carica le viste prima di aprire gli accordion e le svuota alla chiusura', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/groups\/?$/);

    let requests = 0;
    let releaseRequest;

    const requestGate = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/groups/getGroups', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await requestGate;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <div id="e2eGroupsList">
                        <div id="groupsAccordion">
                            <div class="accordion-item">
                                <button class="group-toggle-btn" data-id="7" data-bs-target="#collapse_group_7">
                                    Gruppo E2E
                                </button>
                                <div id="collapse_group_7" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="template-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `
            })
        });
    });

    await page.route('**/backend/groups/getGroup', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: '<div id="e2eGroupDetails">Dettagli gruppo caricati</div>'
            })
        });
    });

    const listCollapse = page.locator('#main_collapse_list');
    const listContainer = page.locator('#showAll-groups-container');
    const listButton = page.locator('[data-bs-target="#main_collapse_list"]');

    await expect(listCollapse).toBeHidden();
    await expect(listContainer).toBeEmpty();

    // Avviamo il caricamento senza rilasciare ancora la risposta.
    await listButton.click();

    await expect.poll(() => requests).toBe(1);

    // Durante la richiesta l'accordion deve rimanere chiuso.
    await expect(listCollapse).not.toHaveClass(/\bshow\b/);
    await expect(listContainer).toBeEmpty();

    // Una seconda richiesta non deve partire durante il caricamento.
    await listButton.evaluate(element => {
        element.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true
        }));
    });

    expect(requests).toBe(1);

    releaseRequest();

    await expect(page.locator('#e2eGroupsList')).toBeAttached();
    await expect(listCollapse).toHaveClass(/\bshow\b/);

    // Apertura di un singolo gruppo.
    await page.locator('.group-toggle-btn[data-id="7"]').click();

    await expect(page.locator('#e2eGroupDetails')).toHaveText('Dettagli gruppo caricati');
    await expect(page.locator('#collapse_group_7')).toHaveClass(/\bshow\b/);

    // Chiusura: la vista dinamica del gruppo viene distrutta.
    await page.locator('.group-toggle-btn[data-id="7"]').click();

    await expect(page.locator('#collapse_group_7')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#e2eGroupDetails')).toHaveCount(0);

    // Chiusura della lista principale.
    await listButton.click();

    await expect(listCollapse).not.toHaveClass(/\bshow\b/);
    await expect(listContainer).toBeEmpty();

    const state = await page.evaluate(async path => {
        const { GroupsManager } = await import(path);

        return {
            isListLoaded: GroupsManager.instance.isListLoaded,
            isSubmitting: GroupsManager.instance.isSubmitting
        };
    }, modulePath);

    expect(state).toEqual({
        isListLoaded: false,
        isSubmitting: false
    });

    expect(requests).toBe(1);
});

test('GroupsManager verifica i vincoli prima di eliminare e blocca richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/groups\/?$/);

    let checks = 0;
    let deletions = 0;
    let receivedPayload = null;

    await page.route('**/backend/groups/checkDeleteConstraints', async route => {
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
                message: checks === 1 ? 'Gruppo con amministratori associati' : ''
            })
        });
    });

    await page.route('**/backend/groups/del', async route => {
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
                message: 'Gruppo eliminato'
            })
        });
    });

    await page.evaluate(async path => {
        const { GroupsManager } = await import(path);

        GroupsManager.instance?.destroy();

        const manager = new GroupsManager();

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-delete-group';
        button.dataset.id = '7';
        button.dataset.message = 'Confermi eliminazione gruppo?';
        button.textContent = 'Elimina gruppo E2E';

        document.body.appendChild(button);
    }, modulePath);

    const button = page.locator('.btn-delete-group[data-id="7"]');
    const modal = page.locator('#globalConfirmModal');

    // Primo tentativo: vincoli non soddisfatti.
    await button.click();

    await expect.poll(() => checks).toBe(1);
    await expect.poll(async () => page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        return GroupsManager.instance.isDeleting;
    }, modulePath)).toBe(false);

    expect(deletions).toBe(0);
    await expect(modal).toBeHidden();

    // Secondo tentativo: vincoli soddisfatti, ma annullamento.
    await button.click();

    await expect(modal).toBeVisible();
    await modal.locator('.btn-cancel').click();

    await expect(modal).toBeHidden();

    await expect.poll(async () => page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        return GroupsManager.instance.isDeleting;
    }, modulePath)).toBe(false);

    expect(deletions).toBe(0);

    // Terzo tentativo: doppio click e conferma.
    await button.evaluate(element => {
        element.click();
        element.click();
    });

    await expect(modal).toBeVisible();

    const blockedDuringConfirmation = await page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        return GroupsManager.instance.isDeleting;
    }, modulePath);

    expect(blockedDuringConfirmation).toBe(true);

    await modal.locator('.btn-ok').click();

    await expect.poll(() => deletions).toBe(1);

    await expect.poll(async () => page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        return GroupsManager.instance.isDeleting;
    }, modulePath)).toBe(false);

    expect(checks).toBe(3);
    expect(deletions).toBe(1);
    expect(receivedPayload).toContain('name="id"\r\n\r\n7');

    await page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        GroupsManager.instance.destroy();
    }, modulePath);
});

test('GroupsManager gestisce ricerca amministratori, selezione e salvataggio eccezioni', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/groups\/?$/);

    const adminUuid = 'bc5f9e1b-5d9c-43a6-8f42-758b9e1c7d5a';

    let searches = 0;
    let permissionsRequests = 0;
    let saveRequests = 0;
    let receivedPayload = null;

    await page.route('**/backend/groups/getDropdownAdmins', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        searches++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <button type="button"
                        class="btn-select-admin"
                        data-id="${adminUuid}"
                        data-identity="Admin E2E">
                        Admin E2E
                    </button>
                `
            })
        });
    });

    await page.route('**/backend/groups/getAdminPermissions', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        permissionsRequests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <form id="edit-admin-exceptions-form">
                        <input type="hidden" name="uuid" value="${adminUuid}">
                        <input type="checkbox" name="permissions[]" value="e2e_permission" checked>
                        <button type="submit">Salva eccezioni</button>
                    </form>
                `
            })
        });
    });

    await page.route('**/backend/groups/saveExceptions', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        saveRequests++;
        receivedPayload = route.request().postData();

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Eccezioni salvate'
            })
        });
    });

    await page.route('**/backend/groups/openExceptions', async route => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <div class="input-group">
                        <input id="search-admin" type="text">
                        <button type="button" class="reset-search-field">X</button>
                    </div>
                    <div id="dropdownAdmins"></div>
                    <div id="admin-permissions-container"></div>
                `
            })
        });
    });

    const exceptionsButton = page.locator('.btn-trigger-exceptions-group');

    await exceptionsButton.click();

    await expect(page.locator('#main_collapse_exceptions')).toHaveClass(/\bshow\b/);

    const searchInput = page.locator('#search-admin');

    // La ricerca parte solo dopo almeno 3 caratteri.
    await searchInput.fill('Ad');

    await page.waitForTimeout(600);
    expect(searches).toBe(0);

    await searchInput.fill('Admin');

    await expect.poll(() => searches).toBe(1);

    await expect(page.locator('.btn-select-admin')).toBeVisible();

    // Selezione dell'amministratore.
    await page.locator('.btn-select-admin').click();

    await expect.poll(() => permissionsRequests).toBe(1);
    await expect(page.locator('#edit-admin-exceptions-form')).toBeVisible();

    await expect(searchInput).toHaveValue('Admin E2E');
    await expect(page.locator('.btn-select-admin')).toHaveCount(0);

    // Salvataggio delle eccezioni.
    await page.locator('#edit-admin-exceptions-form').evaluate(form => {
        form.dispatchEvent(new Event('submit', {
            bubbles: true,
            cancelable: true
        }));
    });

    await expect.poll(() => saveRequests).toBe(1);

    await expect.poll(async () => page.evaluate(async path => {
        const { GroupsManager } = await import(path);
        return GroupsManager.instance.isSubmitting;
    }, modulePath)).toBe(false);

    expect(receivedPayload).toContain(`name="uuid"\r\n\r\n${adminUuid}`);
    expect(receivedPayload).toContain('name="permissions[]"\r\n\r\ne2e_permission');

    await exceptionsButton.click();

    await expect(page.locator('#main_collapse_exceptions')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#exceptions-groups-container')).toBeEmpty();

    const state = await page.evaluate(async path => {
        const { GroupsManager } = await import(path);

        return {
            isExceptionsLoaded: GroupsManager.instance.isExceptionsLoaded,
            isSubmitting: GroupsManager.instance.isSubmitting
        };
    }, modulePath);

    expect(state).toEqual({
        isExceptionsLoaded: false,
        isSubmitting: false
    });
});
