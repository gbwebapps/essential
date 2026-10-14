const { test, expect } = require('@playwright/test');

// Guest Mode: non utilizziamo admin.json.
const pageUrl = '/backend/auth/setPassword';
const modulePath = '/assets/js/backend/modules/Auth.js';
const setUrl = '/backend/auth/setPassword';

test('SetPasswordManager gestisce validazione e fallimenti logici', async ({ page }) => {
    
    await page.goto('/backend/auth');

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'set_password_form';

        form.innerHTML = `
            <input type="password" name="password">
            <input type="password" name="password_confirm">
            <button type="submit">Salva password</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#set_password_form')).toBeVisible();

    const requests = [];

    await page.route('**/backend/auth/setPassword', async route => {
        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                requests.length === 1
                    ? {
                        result: false,
                        errors: {
                            password: 'Password non valida'
                        },
                        message: 'Errore di validazione'
                    }
                    : {
                        result: false,
                        message: 'Token non valido o scaduto'
                    }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { SetPasswordManager } = await import(path);

        SetPasswordManager.instance?.destroy();

        const manager = new SetPasswordManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('password', '123');

        await manager.savePassword(firstData);

        const unlockedAfterValidation = !manager.isSubmitting;

        const secondData = new FormData();
        secondData.append('password', 'PasswordSicura123!');
        secondData.append('password_confirm', 'PasswordSicura123!');

        await manager.savePassword(secondData);

        return {
            unlockedAfterValidation,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(requests).toHaveLength(2);
    expect(requests.every(request => request.method() === 'POST')).toBe(true);

    expect(result).toEqual({
        unlockedAfterValidation: true,
        isSubmitting: false
    });
});


test('SetPasswordManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto('/backend/auth');

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'set_password_form';

        form.innerHTML = `
            <input type="password" name="password">
            <input type="password" name="password_confirm">
            <button type="submit">Salva password</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#set_password_form')).toBeVisible();

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/auth/setPassword', async route => {
        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: false,
                message: 'Operazione non riuscita'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { SetPasswordManager } = await import(path);

        SetPasswordManager.instance?.destroy();

        const manager = new SetPasswordManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('password', 'PasswordSicura123!');

        const secondData = new FormData();
        secondData.append('password', 'PasswordSicura123!');

        await Promise.all([
            manager.savePassword(firstData),
            manager.savePassword(secondData)
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


test('SetPasswordManager recupera dopo errori e resetta il form al successo', async ({ page }) => {
    await page.goto('/backend/auth');

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'set_password_form';

        form.innerHTML = `
            <input type="password" name="password">
            <input type="password" name="password_confirm">
            <button type="submit">Salva password</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#set_password_form')).toBeVisible();

    let requests = 0;

    await page.route('**/backend/auth/setPassword', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Password impostata'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { SetPasswordManager } = await import(path);

        SetPasswordManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new SetPasswordManager({
            async onSetBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onSetAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onSetError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const form = document.getElementById('set_password_form');
        const password = form.querySelector('[name="password"]');
        const confirm = form.querySelector('[name="password_confirm"]');

        password.value = 'PasswordSicura123!';
        confirm.value = 'PasswordSicura123!';

        const formData = new FormData(form);

        await manager.savePassword(formData);

        const unlockedAfterError = manager.isSubmitting === false;
        const passwordPreservedAfterError = password.value === 'PasswordSicura123!';

        await manager.savePassword(formData);

        return {
            unlockedAfterError,
            passwordPreservedAfterError,
            passwordClearedAfterSuccess: password.value === '',
            confirmClearedAfterSuccess: confirm.value === '',
            isSubmitting: manager.isSubmitting,
            beforeCalls,
            afterCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        unlockedAfterError: true,
        passwordPreservedAfterError: true,
        passwordClearedAfterSuccess: true,
        confirmClearedAfterSuccess: true,
        isSubmitting: false,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1
    });
});

