
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

// Guest Mode: non utilizziamo admin.json.
const pageUrl = appUrl('/backend/auth/resetPassword');
const modulePath = appUrl('/assets/js/backend/modules/Auth.js');
const resetUrl = '/backend/auth/resetPassword';

test('ResetPasswordManager gestisce validazione e fallimenti logici', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page.locator('#reset_password_form')).toBeVisible();

    const requests = [];

    await page.route('**/backend/auth/resetPassword', async route => {
        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                requests.length === 1
                    ? {
                        result: false,
                        errors: {
                            email: 'Email obbligatoria'
                        },
                        message: 'Errore di validazione'
                    }
                    : {
                        result: false,
                        message: 'Operazione non riuscita'
                    }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        const manager = new ResetPasswordManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('email', '');

        await manager.resetPassword(firstData);

        const unlockedAfterValidation = !manager.isSubmitting;

        const secondData = new FormData();
        secondData.append('email', 'test@example.com');

        await manager.resetPassword(secondData);

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


test('ResetPasswordManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page.locator('#reset_password_form')).toBeVisible();

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/auth/resetPassword', async route => {
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
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        const manager = new ResetPasswordManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('email', 'test@example.com');

        const secondData = new FormData();
        secondData.append('email', 'test@example.com');

        await Promise.all([
            manager.resetPassword(firstData),
            manager.resetPassword(secondData)
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


test('ResetPasswordManager recupera dopo errori e resetta il form al successo', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page.locator('#reset_password_form')).toBeVisible();

    let requests = 0;

    await page.route('**/backend/auth/resetPassword', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: true,
                message: 'Richiesta completata'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);

        ResetPasswordManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new ResetPasswordManager({
            async onResetBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onResetAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onResetError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const form = document.getElementById('reset_password_form');
        const email = form.querySelector('[name="email"]');

        if (!email) {
            throw new Error('Campo email non trovato nel form');
        }

        email.value = 'test@example.com';

        const formData = new FormData(form);

        await manager.resetPassword(formData);

        const unlockedAfterError = manager.isSubmitting === false;
        const emailAfterError = email.value;

        await manager.resetPassword(formData);

        return {
            unlockedAfterError,
            emailPreservedAfterError: emailAfterError === 'test@example.com',
            emailClearedAfterSuccess: email.value === '',
            isSubmitting: manager.isSubmitting,
            beforeCalls,
            afterCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        unlockedAfterError: true,
        emailPreservedAfterError: true,
        emailClearedAfterSuccess: true,
        isSubmitting: false,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1
    });
});
