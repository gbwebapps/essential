const path = require('node:path');
require('dotenv').config({ path: path.resolve(__dirname, '.env.playwright') });

const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './e2e',

    globalSetup: require.resolve('./e2e/support/global-setup'),

    timeout: 30000,

    fullyParallel: false,

    // I test autenticati condividono la stessa sessione amministrativa.
    workers: 1,

    reporter: 'list',

    use: {
        baseURL: 'https://gbwebapps.test/essential/',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    },

    projects: [
        {
            name: 'auth',
            testMatch: /auth[\\/]login\.spec\.js/,
            use: { ...devices['Desktop Chrome'] }
        },
        {
            name: 'isolation',
            testMatch: /database-isolation\.spec\.js/,
            dependencies: ['auth'],
            use: { ...devices['Desktop Chrome'] }
        },
        {
            name: 'chromium',
            testIgnore: [/auth[\\/]login\.spec\.js/, /database-isolation\.spec\.js/],
            dependencies: ['isolation'],
            use: { ...devices['Desktop Chrome'] }
        }
    ]
});
