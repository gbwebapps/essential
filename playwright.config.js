const path = require('node:path');
require('dotenv').config({ path: path.resolve(__dirname, '.env.playwright') });

const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './e2e',

    timeout: 30000,

    fullyParallel: false,

    reporter: 'list',

    use: {
        baseURL: 'https://gbwebapps.test/essential/',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] }
        }
    ]
});
