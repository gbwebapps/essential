
const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

// Non utilizziamo admin.json: LoginManager opera in Guest Mode.
const pageUrl = appUrl('/backend/auth/login');
const modulePath = appUrl('/assets/js/backend/modules/Auth.js');
const loginUrl = '/backend/auth/login';

test('LoginManager gestisce validazione e credenziali errate', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page.locator('#login_form')).toBeVisible();

    const requests = [];

    await page.route('**/backend/auth/login', async route => {
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
                        message: 'Credenziali errate'
                    }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { LoginManager } = await import(path);

        LoginManager.instance?.destroy();

        const manager = new LoginManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('email', '');

        await manager.login(firstData);

        const unlockedAfterValidation = !manager.isSubmitting;

        const secondData = new FormData();
        secondData.append('email', 'test@example.com');
        secondData.append('password', 'password-errata');

        await manager.login(secondData);

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


test('LoginManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page.locator('#login_form')).toBeVisible();

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/auth/login', async route => {
        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: false,
                message: 'Credenziali errate'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { LoginManager } = await import(path);

        LoginManager.instance?.destroy();

        const manager = new LoginManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('email', 'test@example.com');
        firstData.append('password', 'password-errata');

        const secondData = new FormData();
        secondData.append('email', 'test@example.com');
        secondData.append('password', 'password-errata');

        await Promise.all([
            manager.login(firstData),
            manager.login(secondData)
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


test('LoginManager gestisce gli hook asincroni e recupera dopo errori', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page.locator('#login_form')).toBeVisible();

    let requests = 0;

    await page.route('**/backend/auth/login', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: false,
                message: 'Credenziali errate'
            })
        });
    });

    const result = await page.evaluate(async path => {
        const { LoginManager } = await import(path);

        LoginManager.instance?.destroy();

        let beforeCalls = 0;
        let errorCalls = 0;

        const manager = new LoginManager({
            async onLoginBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onLoginError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('email', 'test@example.com');
        formData.append('password', 'password-errata');

        await manager.login(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        await manager.login(formData);

        return {
            unlockedAfterError,
            isSubmitting: manager.isSubmitting,
            beforeCalls,
            errorCalls
        };
    }, modulePath);

    expect(requests).toBe(1);

    expect(result).toEqual({
        unlockedAfterError: true,
        isSubmitting: false,
        beforeCalls: 2,
        errorCalls: 1
    });
});
