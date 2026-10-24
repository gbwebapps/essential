
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const modulePath = appUrl('/assets/js/backend/modules/PasswordVisibility.js');

async function getManager(page) {
    return page.evaluate(async path => {
        const { PasswordVisibility } = await import(path);
        const manager = PasswordVisibility.instance;

        return {
            exists: Boolean(manager),
            eventsBound: manager?.eventsBound,
            destroyed: manager?.destroyed
        };
    }, modulePath);
}

test('PasswordVisibility mostra e nasconde la chiave TOTP in Account Security', async ({ page }) => {

    const currentPassword = 'E2E-current-password';
    const requests = [];

    /* Simula la verifica, senza inviare credenziali o modificare il vero account. */
    await page.route('**/backend/account/verifyCurrentPassword', async route => {
        expect(route.request().method()).toBe('POST');
        expect(route.request().postData()).toContain(currentPassword);
        requests.push('verify');

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    /* Simula la risposta AJAX che carica la vista TOTP. */
    await page.route('**/backend/account/setupTotp', async route => {
        expect(route.request().method()).toBe('POST');
        expect(route.request().postData()).toContain(currentPassword);
        requests.push('setup');

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                output: `
                    <div class="input-group input-group-sm">
                        <button type="button"
                                class="btn btn-secondary toggle-password-visibility"
                                aria-label="Mostra password"
                                aria-pressed="false">
                            <i class="fa-solid fa-eye text-muted"></i>
                        </button>
                        <input type="password"
                               id="totp-secret-field"
                               value="E2E-TEST-SECRET"
                               readonly>
                    </div>
                `
            })
        });
    });

    await page.goto(appUrl('/backend/account/security'));

    await expect(page).toHaveURL(/\/backend\/account\/security\/?$/);

    /* Controlla solo lo stato iniziale nel browser, non le impostazioni salvate. */
    await page.evaluate(async path => {
        const { SecurityManager } = await import(path);
        SecurityManager.instance.currentActiveMethod = 'none';
        SecurityManager.instance.restoreActiveMethod();
    }, appUrl('/assets/js/backend/modules/Account.js'));

    /* Il campo TOTP è contenuto in una vista caricata dinamicamente */
    const totpMethod = page.locator('#method-totp');

    await expect(totpMethod).toHaveCount(1);

    await page.locator('#securityCurrentPassword').fill(currentPassword);
    await totpMethod.click();

    await expect(page.locator('#globalConfirmModal')).toBeVisible();
    expect(requests).toEqual(['verify']);
    await page.locator('#globalConfirmModal .btn-ok').click();

    const input = page.locator('#totp-secret-field');
    const button = input.locator('xpath=..').locator('.toggle-password-visibility');
    const icon = button.locator('i');

    await expect(input).toBeAttached();
    expect(requests).toEqual(['verify', 'setup']);
    await expect(button).toHaveCount(1);

    await expect(input).toHaveAttribute('type', 'password');
    await expect(icon).toHaveClass(/\bfa-eye\b/);
    await expect(button).toHaveAttribute('aria-pressed', 'false');

    const initialValue = await input.inputValue();

    /* Prima pressione: mostra la chiave */
    await button.click();

    await expect(input).toHaveAttribute('type', 'text');
    await expect(icon).toHaveClass(/\bfa-eye-slash\b/);
    await expect(button).toHaveAttribute('aria-pressed', 'true');
    await expect(button).toHaveAttribute('aria-label', 'Nascondi password');

    expect(await input.inputValue()).toBe(initialValue);

    /* Seconda pressione: nasconde la chiave */
    await button.click();

    await expect(input).toHaveAttribute('type', 'password');
    await expect(icon).toHaveClass(/\bfa-eye\b/);
    await expect(button).toHaveAttribute('aria-pressed', 'false');
    await expect(button).toHaveAttribute('aria-label', 'Mostra password');

    expect(await input.inputValue()).toBe(initialValue);

    expect(await getManager(page)).toEqual({
        exists: true,
        eventsBound: true,
        destroyed: false
    });
});

test('PasswordVisibility funziona in Settings Email anche dopo il ricaricamento AJAX', async ({ page }) => {
    await page.goto(appUrl('/backend/settings'));

    await expect(page).toHaveURL(/\/backend\/settings\/?$/);

    const trigger = page.locator('button[data-env="email"]');
    const collapse = page.locator('#main_collapse_email');
    const container = page.locator('#email-settings-container');

    await trigger.click();

    await expect(collapse).toHaveClass(/\bshow\b/);

    const input = page.locator('#email_SMTPPass');
    const button = input.locator('xpath=..').locator('.toggle-password-visibility');

    await expect(input).toHaveAttribute('type', 'password');

    /* Primo caricamento */
    await button.click();

    await expect(input).toHaveAttribute('type', 'text');
    await expect(button).toHaveAttribute('aria-pressed', 'true');

    /* Chiusura dell'accordion: il contenuto viene distrutto */
    await trigger.click();

    await expect(collapse).not.toHaveClass(/\bshow\b/);
    await expect(container).toBeEmpty();

    /* Riapertura: il componente funziona senza reinizializzazione */
    await trigger.click();

    await expect(collapse).toHaveClass(/\bshow\b/);

    const reloadedInput = page.locator('#email_SMTPPass');
    const reloadedButton = reloadedInput.locator('xpath=..').locator('.toggle-password-visibility');

    await expect(reloadedInput).toHaveAttribute('type', 'password');

    await reloadedButton.click();

    await expect(reloadedInput).toHaveAttribute('type', 'text');
    await expect(reloadedButton).toHaveAttribute('aria-pressed', 'true');
});

test('PasswordVisibility gestisce campi indipendenti e rimuove i listener con destroy', async ({ page }) => {
    await page.goto(appUrl('/backend/auth'));

    const initialState = await getManager(page);

    expect(initialState).toEqual({
        exists: true,
        eventsBound: true,
        destroyed: false
    });

    /* Aggiunge due campi di test indipendenti */
    await page.evaluate(() => {
        const root = document.createElement('div');
        root.id = 'e2e-password-fields';

        root.innerHTML = `
            <div class="input-group">
                <input type="password" id="e2e-password-one" value="secret-one">
                <button type="button"
                        class="toggle-password-visibility"
                        aria-label="Mostra password"
                        aria-pressed="false">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <div class="input-group">
                <input type="password" id="e2e-password-two" value="secret-two">
                <button type="button"
                        class="toggle-password-visibility"
                        aria-label="Mostra password"
                        aria-pressed="false">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        `;

        document.body.appendChild(root);
    });

    const root = page.locator('#e2e-password-fields');
    const firstInput = root.locator('#e2e-password-one');
    const secondInput = root.locator('#e2e-password-two');

    const firstButton = firstInput.locator('xpath=..').locator('button');
    const secondButton = secondInput.locator('xpath=..').locator('button');

    /* Mostra soltanto il primo campo */
    await firstButton.click();

    await expect(firstInput).toHaveAttribute('type', 'text');
    await expect(secondInput).toHaveAttribute('type', 'password');

    /* Mostra anche il secondo */
    await secondButton.click();

    await expect(firstInput).toHaveAttribute('type', 'text');
    await expect(secondInput).toHaveAttribute('type', 'text');

    /* Nasconde il primo senza modificare il secondo */
    await firstButton.click();

    await expect(firstInput).toHaveAttribute('type', 'password');
    await expect(secondInput).toHaveAttribute('type', 'text');

    /* Distrugge il componente globale */
    const destroyedState = await page.evaluate(async path => {
        const { PasswordVisibility } = await import(path);
        const manager = PasswordVisibility.instance;

        manager.destroy();

        return {
            destroyed: manager.destroyed,
            eventsBound: manager.eventsBound,
            singletonCleared: PasswordVisibility.instance === null
        };
    }, modulePath);

    expect(destroyedState).toEqual({
        destroyed: true,
        eventsBound: false,
        singletonCleared: true
    });

    /* Dopo destroy() il listener non deve più modificare il campo */
    await firstButton.click();

    await expect(firstInput).toHaveAttribute('type', 'password');
    await expect(secondInput).toHaveAttribute('type', 'text');
});
