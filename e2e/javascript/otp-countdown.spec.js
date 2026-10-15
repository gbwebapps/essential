const { test, expect } = require('@playwright/test');

const pageUrl = '/backend/auth';
const modulePath = '/assets/js/backend/components/OtpCountdown.js';

test('Il countdown OTP usa scadenze e periodi reali del server', async ({ page }) => {
    await page.goto(pageUrl);

    const result = await page.evaluate(async path => {
        const {
            calculateOtpCountdown,
            initOtpCountdown
        } = await import(path);

        const expiryValues = [
            calculateOtpCountdown('expiry', 100, 160),
            calculateOtpCountdown('expiry', 159.2, 160),
            calculateOtpCountdown('expiry', 160, 160)
        ];

        const periodValues = [
            calculateOtpCountdown('period', 120, 30),
            calculateOtpCountdown('period', 121, 30),
            calculateOtpCountdown('period', 149, 30),
            calculateOtpCountdown('period', 150, 30)
        ];

        const element = document.createElement('span');
        element.dataset.otpCountdown = '';
        element.dataset.otpCountdownMode = 'expiry';
        element.dataset.serverTime = '100';
        element.dataset.expiresAt = '160';
        document.body.appendChild(element);

        const countdown = initOtpCountdown(document);
        const renderedValue = element.textContent;

        countdown.stop();
        element.remove();

        return {
            expiryValues,
            periodValues,
            renderedValue
        };
    }, modulePath);

    expect(result).toEqual({
        expiryValues: [60, 1, 0],
        periodValues: [30, 29, 1, 30],
        renderedValue: '60s'
    });
});
