const { test, expect } = require('@playwright/test');
const { appUrl } = require('../support/app-url');

test('Un listener non deve essere eseguito più volte per un singolo evento', async ({ page }) => {
    await page.goto(appUrl('/backend/auth'));

    const result = await page.evaluate(() => {
        const element = document.createElement('button');
        let executions = 0;

        const handler = () => {
            executions++;
        };

        element.addEventListener('click', handler);
        element.addEventListener('click', handler);

        element.click();

        element.removeEventListener('click', handler);

        return executions;
    });

    expect(result).toBe(1);
});
