const { use: { baseURL } } = require('../../playwright.config');

// Mantiene il percorso dell'applicazione anche nelle installazioni in sottocartella.
function appUrl(path) {
    return new URL(path.replace(/^\/+/, ''), baseURL).href;
}

module.exports = { appUrl };
