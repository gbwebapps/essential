
const { test, expect } = require('@playwright/test');

// Guest Mode: non utilizziamo admin.json.
const pageUrl = '/backend/auth';
const modulePath = '/assets/js/backend/modules/Auth.js';
const verifyUrl = '/backend/auth/verify';

test('VerifyManager gestisce validazione OTP e codici errati', async ({ page }) => {
    await page.goto(pageUrl);

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'verify_form';

        form.innerHTML = `
            <input type="text" name="code">
            <button type="submit">Verifica</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#verify_form')).toBeVisible();

    const requests = [];

    await page.route('**/backend/auth/verify', async route => {
        requests.push(route.request());

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(
                requests.length === 1
                    ? {
                        result: false,
                        errors: {
                            code: 'Codice OTP non valido'
                        },
                        message: 'Errore di validazione'
                    }
                    : {
                        result: false,
                        message: 'Codice di verifica errato'
                    }
            )
        });
    });

    const result = await page.evaluate(async path => {
        const { VerifyManager } = await import(path);

        VerifyManager.instance?.destroy();

        const manager = new VerifyManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('code', '');

        await manager.verify(firstData);

        const unlockedAfterValidation = !manager.isSubmitting;

        const secondData = new FormData();
        secondData.append('code', '000000');

        await manager.verify(secondData);

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


test('VerifyManager impedisce richieste duplicate', async ({ page }) => {
    await page.goto(pageUrl);

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'verify_form';

        form.innerHTML = `
            <input type="text" name="code">
            <button type="submit">Verifica</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#verify_form')).toBeVisible();

    let requests = 0;
    let releaseRequest;

    const blocked = new Promise(resolve => {
        releaseRequest = resolve;
    });

    await page.route('**/backend/auth/verify', async route => {
        requests++;

        if (requests === 1) {
            await blocked;
        }

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                result: false,
                message: 'Codice OTP errato'
            })
        });
    });

    const action = page.evaluate(async path => {
        const { VerifyManager } = await import(path);

        VerifyManager.instance?.destroy();

        const manager = new VerifyManager();
        manager.init();

        const firstData = new FormData();
        firstData.append('code', '123456');

        const secondData = new FormData();
        secondData.append('code', '123456');

        await Promise.all([
            manager.verify(firstData),
            manager.verify(secondData)
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


test('VerifyManager recupera dopo errori e reindirizza dopo verifica positiva', async ({ page }) => {
    await page.goto(pageUrl);

    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'verify_form';

        form.innerHTML = `
            <input type="text" name="code">
            <button type="submit">Verifica</button>
        `;

        document.body.appendChild(form);
    });

    await expect(page.locator('#verify_form')).toBeVisible();

    let requests = 0;

    await page.route('**/backend/auth/verify', async route => {
        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true })
        });
    });

    await page.route('**/backend/dashboard', async route => {
        await route.fulfill({
            status: 200,
            contentType: 'text/html',
            body: '<html><body>Dashboard E2E</body></html>'
        });
    });

    const result = await page.evaluate(async path => {
        const { VerifyManager } = await import(path);

        VerifyManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;
        let errorCalls = 0;

        const manager = new VerifyManager({
            async onVerifyBefore() {
                beforeCalls++;

                if (beforeCalls === 1) {
                    throw new Error('Errore hook simulato');
                }
            },
            async onVerifyAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onVerifyError(error) {
                await Promise.resolve();

                if (error.message === 'Errore hook simulato') {
                    errorCalls++;
                }
            }
        });

        manager.init();

        const formData = new FormData();
        formData.append('code', '123456');

        // Primo tentativo: eccezione nell'hook.
        await manager.verify(formData);

        const unlockedAfterError = manager.isSubmitting === false;

        // Secondo tentativo: risposta positiva e redirect.
        // La navigazione viene avviata, ma non attesa qui.
        await manager.verify(formData);

        return {
            unlockedAfterError,
            beforeCalls,
            afterCalls,
            errorCalls,
            isSubmitting: manager.isSubmitting
        };
    }, modulePath);

    expect(result).toEqual({
        unlockedAfterError: true,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1,
        isSubmitting: false
    });

    expect(requests).toBe(1);

    await expect(page).toHaveURL(/\/backend\/dashboard\/?$/);
});


