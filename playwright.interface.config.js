const path = require('node:path');
require('dotenv').config({ path: path.resolve(__dirname, '.env.playwright'), quiet: true });
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './e2e/javascript',
    testMatch: 'interface-preferences.spec.js',
    globalSetup: require.resolve('./e2e/support/global-setup'),
    workers: 1,
    timeout: 90000,
    reporter: 'list',
    outputDir: 'output/interface-preferences',
    use: {
        baseURL: process.env.INTERFACE_TEST_BASE_URL || 'https://127.0.0.1:8770/',
        ignoreHTTPSErrors: true,
        viewport: { width: 1440, height: 1000 },
        screenshot: 'only-on-failure',
        launchOptions: { args: ['--disable-logging'] },
    },
});
