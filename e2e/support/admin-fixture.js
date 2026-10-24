const { randomUUID } = require('node:crypto');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { test: base, expect } = require('@playwright/test');

const adminUuid = randomUUID();

// Crea e rimuove esclusivamente i record posseduti da questo worker.
function manageAdmin(action) {
    const output = execFileSync('php', [
        path.join(__dirname, 'admin-record.php'), action, adminUuid
    ], {
        cwd: path.resolve(__dirname, '../..'),
        encoding: 'utf8',
        timeout: 15000,
        windowsHide: true
    });

    if (JSON.parse(output).result !== true) {
        throw new Error('Preparazione della fixture amministratore non riuscita.');
    }
}

const test = base.extend({
    _adminRecord: [async ({}, use) => {
        manageAdmin('create');

        try {
            await use();
        } finally {
            manageAdmin('delete');
        }
    }, { scope: 'worker', auto: true }]
});

module.exports = { test, expect, adminUuid };
