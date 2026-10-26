const { test, expect } = require('@playwright/test');

const languages = ['it', 'en', 'es', 'de', 'fr', 'zh'];
const themes = ['black', 'white', 'dark', 'grey'];

async function login(page) {
    await page.goto('backend/auth/login');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#hidden-urlbase')).toHaveAttribute('data-urlbase', /^https?:\/\/127\.0\.0\.1:/);
    await page.evaluate(() => import('/assets/js/backend/backend.js'));
    await page.locator('[name=email]').fill(process.env.E2E_ADMIN_EMAIL);
    await page.locator('[name=password]').fill(process.env.E2E_ADMIN_PASSWORD);
    await page.locator('#login_form button[type=submit]').click();
    await page.waitForURL(/backend\/dashboard$/);
    await expect(page.locator('#interface-preferences')).toBeVisible();
}

async function openPreferences(page, preference = 'language') {
    const dropdown = page.locator('#interface-' + preference + '-dropdown');
    if (!await dropdown.locator('.dropdown-menu').isVisible()) await dropdown.locator(':scope > button').click();
}

async function language(page, code) {
    await openPreferences(page);
    await page.locator(`[data-interface-language=${code}]`).click();
    await expect(page.locator('#interface-language-dropdown .dropdown-menu')).not.toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('lang', code);
    await expect.poll(() => page.evaluate(async () => (await import('/assets/js/backend/backend.js')).changeLanguageManager.pending)).toBe(false);
}

async function theme(page, name) {
    await openPreferences(page, 'theme');
    await page.locator(`[data-interface-theme=${name}]`).click();
    await expect(page.locator('#interface-theme-dropdown .dropdown-menu')).not.toBeVisible();
    await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + name + '\\.css$'));
    await expect.poll(() => page.evaluate(async () => (await import('/assets/js/backend/backend.js')).changeThemeManager.pending)).toBe(false);
}

test('sei lingue e quattro temi preservano form, upload e modale aperto', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page);
    await page.goto('backend/categories/add');
    await page.locator('#category').fill('Categoria non salvata');
    await page.locator('#note').fill('Contenuto da mantenere');
    await page.locator('#inputImages').setInputFiles({ name: 'preview.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64') });
    await expect(page.locator('.preview-item')).toHaveCount(1);
    await expect(page.locator('form#categories-add')).toBeVisible();
    await page.evaluate(() => { window.originalForm = document.querySelector('#categories-add'); window.originalPreview = document.querySelector('.preview-item'); });
    await page.locator('#add-reset button').click();
    await expect(page.locator('#globalConfirmModal')).toBeVisible();
    await language(page, await page.locator('html').getAttribute('lang') === 'en' ? 'de' : 'en');

    for (const code of languages) {
        await language(page, code);
        await expect(page.locator('#category')).toHaveValue('Categoria non salvata');
        await expect(page.locator('#note')).toHaveValue('Contenuto da mantenere');
        await expect(page.locator('#globalConfirmModal')).toBeVisible();
        expect(await page.evaluate(() => window.originalForm === document.querySelector('#categories-add') && window.originalPreview === document.querySelector('.preview-item'))).toBe(true);
        await expect(page.locator('#globalConfirmModal .modal-body')).not.toBeEmpty();
        const expected = await page.evaluate(async () => {
            const manager = (await import('/assets/js/backend/backend.js')).changeLanguageManager;
            const binding = JSON.parse(document.querySelector('#globalConfirmModal .modal-body').getAttribute('data-i18n-text'));
            const span = document.createElement('span');
            span.innerHTML = manager.safeHtml(manager.render(binding));
            return span.textContent;
        });
        await expect(page.locator('#globalConfirmModal .modal-body')).toHaveText(expected);
    }
    for (const name of themes) {
        await theme(page, name);
        await page.screenshot({ path: testInfo.outputPath('interface-' + name + '.png'), fullPage: true });
    }
    await expect(page.locator('#globalConfirmModal')).toBeVisible();
    await page.locator('#globalConfirmModal .btn-cancel').click();
    await expect(page.locator('#category')).toHaveValue('Categoria non salvata');
    expect(errors).toEqual([]);
});

test('filtri, paginazione e calendario restano attivi; i nuovi partial seguono la lingua', async ({ page }) => {
    await login(page);
    await page.goto('backend/categories/showAll');
    await expect(page.locator('#categoriesBody')).toBeVisible();
    await page.locator('.changeNumRows').selectOption('10');
    await page.locator('[data-trash-filter=all]').click();
    await page.waitForLoadState('networkidle');
    await page.locator('[data-bs-target="#categories-search-bar"]').click();
    await page.locator('#categories-category').fill('Computer');
    const calendar = page.locator('#categories-created_at-from').locator('..');
    await calendar.locator('input').last().fill('26/10/2026 10:30:00');
    await calendar.locator('input').last().press('Tab');
    const before = await calendar.locator('input').evaluateAll(inputs => inputs.map(input => input.value));
    await language(page, 'fr');
    await expect(page.locator('#categories-category')).toHaveValue('Computer');
    await expect(page.locator('.changeNumRows')).toHaveValue('10');
    expect(await calendar.locator('input').evaluateAll(inputs => inputs.map(input => input.value))).toEqual(before);
    await page.locator('#categories-category').fill('');
    await calendar.evaluate(element => element._flatpickr.clear());
    await page.locator('.changeNumRows').selectOption('20');
    await page.waitForLoadState('networkidle');
    expect(await page.locator('#categoriesBody').innerText()).not.toContain('Contiene');
    await page.goto('backend/products/add');
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr');
    await page.locator('#product').fill('Produit non enregistré');
    const select = page.locator('#brand_uuids');
    const options = await select.locator('option:not([disabled])').evaluateAll(nodes => nodes.map(node => node.value));
    if (options.length) await select.evaluate((element, value) => element.tomselect.addItem(value), options[0]);
    const selected = await select.evaluate(element => element.tomselect.getValue());
    await language(page, 'zh');
    expect(await select.evaluate(element => element.tomselect.getValue())).toEqual(selected);
    await expect(page.locator('#product')).toHaveValue('Produit non enregistré');
});

test('salvataggio General riallinea le preferenze; logout ripristina i valori salvati', async ({ page }) => {
    await login(page);
    await page.goto('backend/settings');
    await page.locator('.btn-trigger-general-settings').click();
    await expect(page.locator('#general-settings')).toBeVisible();
    const original = await page.locator('#general-settings').evaluate(form => Object.fromEntries(new FormData(form)));
    const savedLanguage = original.language === 'en' ? 'it' : 'en';
    const savedTheme = original.theme === 'white' ? 'dark' : 'white';
    try {
        await language(page, 'de');
        await theme(page, 'black');
        await expect(page.locator('#language')).toHaveValue(original.language);
        await expect(page.locator('#theme')).toHaveValue(original.theme);
        await page.locator('#language').selectOption(savedLanguage);
        await page.locator('#theme').selectOption(savedTheme);
        await page.locator('.btn-save-general').click();
        await expect(page.locator('html')).toHaveAttribute('lang', savedLanguage);
        await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + savedTheme + '\\.css$'));
        await language(page, 'es');
        await theme(page, 'grey');
        await page.locator('form[action$="backend/auth/logout"]').evaluate(form => form.submit());
        await page.waitForURL(/backend\/auth$/);
        await expect(page.locator('html')).toHaveAttribute('lang', savedLanguage);
        await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + savedTheme + '\\.css$'));
        await login(page);
        await expect(page.locator('html')).toHaveAttribute('lang', savedLanguage);
    } finally {
        await page.goto('backend/settings');
        await page.locator('.btn-trigger-general-settings').click();
        await expect(page.locator('#general-settings')).toBeVisible();
        await page.locator('#language').selectOption(original.language);
        await page.locator('#theme').selectOption(original.theme);
        await page.locator('.btn-save-general').click();
        await expect(page.locator('html')).toHaveAttribute('lang', original.language);
    }
});

test('errori di validazione si traducono; CSS fallito e richieste invalide non alterano la preferenza', async ({ page }) => {
    await login(page);
    await page.goto('backend/categories/add');
    await page.locator('button[form="categories-add"][type=submit]').click();
    await expect(page.locator('.error_category')).not.toHaveText(/^\s*$/);
    const initialError = await page.locator('.error_category').innerText();
    const nextLanguage = await page.locator('html').getAttribute('lang') === 'en' ? 'it' : 'en';
    await language(page, nextLanguage);
    await expect(page.locator('.error_category')).not.toHaveText(initialError);
    const currentTheme = await page.locator('#interface-preferences').getAttribute('data-active-theme');
    const failedTheme = currentTheme === 'white' ? 'dark' : 'white';
    await page.route('**/assets/css/backend/' + failedTheme + '.css', route => route.abort());
    await openPreferences(page, 'theme');
    await page.locator(`[data-interface-theme=${failedTheme}]`).click();
    await expect(page.locator('#interface-theme-dropdown .dropdown-menu')).not.toBeVisible();
    await expect(page.locator('#alert-container')).not.toBeEmpty();
    await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + currentTheme + '\\.css$'));
    const statuses = await page.evaluate(async () => {
        const csrf = document.getElementById('csrf-meta').content;
        const request = body => fetch('/backend/interface/change', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }, body });
        return [
            (await request('preference=language&value=invalid')).status,
            (await request('preference=language&value%5B%5D=en')).status,
            (await request('preference=timezone&value=en')).status,
            (await fetch('/backend/interface/change', { method: 'POST' })).status,
        ];
    });
    expect(statuses).toEqual([422, 400, 422, 403]);
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('lang', nextLanguage);
    await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + currentTheme + '\\.css$'));
    await page.locator('#category').fill('Bozza da preservare');
    await page.route('**/backend/interface/change', route => route.fulfill({ status: 403, contentType: 'application/json', body: JSON.stringify({ message: 'Forbidden' }) }));
    await openPreferences(page);
    await page.locator(`[data-interface-language=${nextLanguage === 'en' ? 'it' : 'en'}]`).click();
    await expect.poll(() => page.evaluate(async () => (await import('/assets/js/backend/backend.js')).changeLanguageManager.pending)).toBe(false);
    await page.waitForTimeout(2200);
    await expect(page.locator('#category')).toHaveValue('Bozza da preservare');
});


test('le pagine backend e gli attributi accessibili seguono il locale della sessione', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page);
    await language(page, 'de');
    for (const path of ['dashboard', 'categories/showAll', 'brands/showAll', 'products/showAll', 'admins/showAll', 'account/edit', 'account/permissions', 'account/security', 'account/tokens', 'groups', 'tools', 'warehouse', 'logs', 'tokens', 'audits', 'settings']) {
        const response = await page.goto('backend/' + path);
        expect(response.status(), path).toBe(200);
        await expect(page.locator('html')).toHaveAttribute('lang', 'de');
        await expect(page.locator('h1')).not.toBeEmpty();
        await expect(page.locator('#interface-preferences')).toBeVisible();
    }
    await page.goto('backend/account/edit');
    const button = page.locator('.toggle-password-visibility').first();
    await button.click();
    const password = page.locator('#currentPassword');
    await expect(password).toHaveAttribute('type', 'text');
    await language(page, 'en');
    await expect(button).toHaveAttribute('aria-label', 'Hide password');
    await expect(password).toHaveAttribute('type', 'text');
    expect(errors).toEqual([]);
});

test('dropdown mobile, selezioni rapide e logout con Ricordami', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('backend/auth/login');
    await page.waitForLoadState('networkidle');
    await page.locator('[name=email]').fill(process.env.E2E_ADMIN_EMAIL);
    await page.locator('[name=password]').fill(process.env.E2E_ADMIN_PASSWORD);
    await page.locator('#rememberMe').check();
    await page.locator('#login_form button[type=submit]').click();
    await page.waitForURL(/backend\/dashboard$/);
    const originalLanguage = await page.locator('html').getAttribute('lang');
    const originalTheme = await page.locator('#interface-preferences').getAttribute('data-active-theme');
    await page.locator('.navbar-toggler').click();
    await language(page, 'fr');
    const welcome = page.locator('.alert-session .w-100 > span[data-i18n-text]');
    const expectedWelcome = await welcome.evaluate(element => JSON.parse(element.getAttribute('data-i18n-text')).variants.fr);
    await expect(welcome).toHaveText(expectedWelcome);
    await theme(page, 'white');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: testInfo.outputPath('interface-mobile.png'), fullPage: true });
    await page.locator('.navbar-toggler').click();
    await expect(page.locator('#navbarSupportedContent')).not.toHaveClass(/show|collapsing/);
    await page.evaluate(async () => {
        const backend = await import('/assets/js/backend/backend.js');
        window.mobileConfirmation = backend.askConfirm(document.getElementById('lang-definitions').dataset.unsavedChanges);
    });
    await expect(page.locator('#globalConfirmModal')).toBeVisible();
    await page.locator('.navbar-toggler').click();
    await expect(page.locator('#navbarSupportedContent')).toHaveClass(/show/);
    await language(page, 'de');
    await expect(page.locator('#globalConfirmModal')).toBeVisible();
    const expectedConfirmation = await page.locator('#lang-definitions').getAttribute('data-unsaved-changes');
    await expect(page.locator('#globalConfirmModal .modal-body')).toHaveText(expectedConfirmation);
    await page.locator('.navbar-toggler').click();
    await expect(page.locator('#navbarSupportedContent')).not.toHaveClass(/show|collapsing/);
    await page.locator('#globalConfirmModal .btn-cancel').click();
    await page.evaluate(async () => {
        const manager = (await import('/assets/js/backend/backend.js')).changeLanguageManager;
        manager.change('en');
        manager.change('de');
        manager.change('zh');
    });
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh');
    await page.locator('form[action$="backend/auth/logout"]').evaluate(form => form.submit());
    await page.waitForURL(/backend\/auth$/);
    await expect(page.locator('html')).toHaveAttribute('lang', originalLanguage);
    await expect(page.locator('#backend-theme')).toHaveAttribute('href', new RegExp('/' + originalTheme + '\\.css$'));
});

test('la traduzione live coincide con il rendering PHP nella nuova lingua', async ({ page }) => {
    await login(page);
    const snapshot = async () => page.evaluate(() => {
        document.querySelectorAll('.alert-session, #alert-container').forEach(element => element.remove());
        return document.body.innerText.replace(/\s+/g, ' ').replace(/\d[\d.,]*/g, '#').trim();
    });
    for (const path of ['dashboard', 'categories/showAll', 'brands/showAll', 'products/showAll', 'categories/add', 'account/edit', 'account/permissions', 'account/security', 'groups', 'tools', 'warehouse', 'settings']) {
        await language(page, 'it');
        await page.goto('backend/' + path);
        await page.waitForLoadState('networkidle');
        await language(page, 'en');
        const live = await snapshot();
        await page.reload();
        await page.waitForLoadState('networkidle');
        expect(live, path).toBe(await snapshot());
    }
});
