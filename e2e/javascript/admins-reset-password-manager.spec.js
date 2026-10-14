
const { test, expect } = require('@playwright/test');

test.use({
    storageState: 'e2e/.auth/admin.json'
});

const pageUrl = '/backend/admins/showAll';
const modulePath = '/assets/js/backend/modules/Admins.js';

test('ResetPasswordManager gestisce fallimento e successo AJAX', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    let requests = 0;

    await page.route('**/backend/admins/resetPassword', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(requests === 1
                ? { result: false, message: 'Reset non riuscito' }
                : { result: true, message: 'Password reimpostata' })
        });
    });

    const result = await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        ResetPasswordManager.instance?.destroy();

        let beforeCalls = 0;
        let afterCalls = 0;

        const manager = new ResetPasswordManager({
            onResetBefore() { beforeCalls++; },
            onResetAfter() { afterCalls++; }
        });

        manager.init();

        const data = new FormData();
        data.append('id', 'e2e-test-admin');

        await manager.resetPassword(data);
        const unlockedAfterFailure = !manager.isSubmitting;

        await manager.resetPassword(data);

        const output = {
            unlockedAfterFailure,
            unlockedAfterSuccess: !manager.isSubmitting,
            beforeCalls,
            afterCalls
        };

        manager.destroy();
        return output;
    }, modulePath);

    expect(requests).toBe(2);
    expect(result).toEqual({
        unlockedAfterFailure: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1
    });
});

test('ResetPasswordManager gestisce conferma e submit duplicati', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    let requests = 0;

    await page.route('**/backend/admins/resetPassword', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true, message: 'Reset completato' })
        });
    });

    await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        ResetPasswordManager.instance?.destroy();

        const manager = new ResetPasswordManager();
        manager.init();

        const form = document.createElement('form');
        form.className = 'resetAdmin';
        form.dataset.message = 'Confermi il reset della password?';
        form.innerHTML = '<input type="hidden" name="id" value="e2e-test-admin">';
        document.body.appendChild(form);
    }, modulePath);

    const modal = page.locator('#globalConfirmModal');
    const form = page.locator('form.resetAdmin').last();

    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        element.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });

    await expect(modal).toBeVisible();

    expect(await page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        return ResetPasswordManager.instance.isSubmitting;
    }, modulePath)).toBe(true);

    await modal.locator('.btn-cancel').click();
    await expect(modal).toBeHidden();

    await expect.poll(async () => page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        return ResetPasswordManager.instance.isSubmitting;
    }, modulePath)).toBe(false);

    expect(requests).toBe(0);

    await form.evaluate(element => {
        element.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });

    await expect(modal).toBeVisible();
    await modal.locator('.btn-ok').click();

    await expect.poll(() => requests).toBe(1);

    await expect.poll(async () => page.evaluate(async path => {
        const { ResetPasswordManager } = await import(path);
        return ResetPasswordManager.instance.isSubmitting;
    }, modulePath)).toBe(false);

    expect(requests).toBe(1);
});

test('ResetPasswordManager recupera da errori degli hook e rimuove i listener', async ({ page }) => {
    await page.goto(pageUrl);
    await expect(page).toHaveURL(/\/backend\/admins\/showAll\/?$/);

    let requests = 0;

    await page.route('**/backend/admins/resetPassword', async route => {
        if (route.request().method() !== 'POST') {
            await route.continue();
            return;
        }

        requests++;

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ result: true, message: 'Reset completato' })
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
                if (beforeCalls === 1) throw new Error('Errore hook simulato');
                await Promise.resolve();
            },
            async onResetAfter() {
                await Promise.resolve();
                afterCalls++;
            },
            async onResetError(error) {
                await Promise.resolve();
                if (error.message === 'Errore hook simulato') errorCalls++;
            }
        });

        manager.init();

        const data = new FormData();
        data.append('id', 'e2e-test-admin');

        await manager.resetPassword(data);
        const unlockedAfterError = !manager.isSubmitting;

        await manager.resetPassword(data);

        const form = document.createElement('form');
        form.className = 'resetAdmin';
        form.innerHTML = '<input type="hidden" name="id" value="e2e-test-admin">';
        document.body.appendChild(form);

        manager.destroy();
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

        return {
            unlockedAfterError,
            unlockedAfterSuccess: !manager.isSubmitting,
            beforeCalls,
            afterCalls,
            errorCalls,
            destroyed: manager.destroyed,
            singletonReleased: ResetPasswordManager.instance === null
        };
    }, modulePath);

    expect(requests).toBe(1);
    expect(result).toEqual({
        unlockedAfterError: true,
        unlockedAfterSuccess: true,
        beforeCalls: 2,
        afterCalls: 1,
        errorCalls: 1,
        destroyed: true,
        singletonReleased: true
    });
});
