const { execFileSync } = require('node:child_process');
const path = require('node:path');

function checkEnvironment() {
    const output = execFileSync('php', [path.join(__dirname, 'environment.php')], {
        cwd: path.resolve(__dirname, '../..'),
        encoding: 'utf8',
        timeout: 15000,
        windowsHide: true
    });

    return JSON.parse(output);
}

module.exports = { checkEnvironment };
