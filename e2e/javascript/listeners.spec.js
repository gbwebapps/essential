const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test('Un listener non deve essere eseguito più volte per un singolo evento', async ({ page }) => {
    await page.goto(appUrl('/backend/auth'));

    const modulePath = appUrl('/assets/js/backend/modules/PasswordVisibility.js');

    await page.evaluate(async path => {
        const { PasswordVisibility } = await import(path);
        PasswordVisibility.instance?.destroy();

        const manager = new PasswordVisibility();
        manager.init();
        manager.init();

        const group = document.createElement('div');
        group.className = 'input-group';
        group.innerHTML = `
            <input id="e2e-listener-password" type="password" value="test">
            <button id="e2e-listener-toggle" type="button" class="toggle-password-visibility">
                <i class="fa-solid fa-eye"></i>
            </button>
        `;
        document.body.appendChild(group);

        window.e2eVisibilityChanges = 0;
        new MutationObserver(records => {
            window.e2eVisibilityChanges += records.length;
        }).observe(group.querySelector('input'), { attributes: true, attributeFilter: ['type'] });
    }, modulePath);

    const input = page.locator('#e2e-listener-password');
    const button = page.locator('#e2e-listener-toggle');

    await button.click();
    await expect(input).toHaveAttribute('type', 'text');
    await expect.poll(() => page.evaluate(() => window.e2eVisibilityChanges)).toBe(1);

    await page.evaluate(async path => {
        const { PasswordVisibility } = await import(path);
        PasswordVisibility.instance.destroy();
    }, modulePath);

    await button.click();
    await page.evaluate(() => new Promise(requestAnimationFrame));
    await expect(input).toHaveAttribute('type', 'text');
    expect(await page.evaluate(() => window.e2eVisibilityChanges)).toBe(1);

    await page.evaluate(async path => {
        const { PasswordVisibility } = await import(path);
        new PasswordVisibility();
    }, modulePath);

    await button.click();
    await expect(input).toHaveAttribute('type', 'password');
    await expect.poll(() => page.evaluate(() => window.e2eVisibilityChanges)).toBe(2);
});
