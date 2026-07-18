import fs from 'fs';
import path from 'path';

// Mappa: [Sorgente] -> [Destinazione]
const assetsToCopy = [
    // Bootstrap
    {
        from: 'node_modules/bootstrap/dist/css/bootstrap.min.css',
        to: 'public/assets/vendor/bootstrap/css/bootstrap.min.css'
    },
    {
        from: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
        to: 'public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'
    },
    // Font Awesome (CSS + Cartella Webfonts)
    {
        from: 'node_modules/@fortawesome/fontawesome-free/css/all.min.css',
        to: 'public/assets/vendor/fontawesome/css/all.min.css'
    },
    {
        from: 'node_modules/@fortawesome/fontawesome-free/webfonts',
        to: 'public/assets/vendor/fontawesome/webfonts'
    },
    {
        from: 'node_modules/tom-select/dist/css/tom-select.bootstrap5.min.css',
        to: 'public/assets/vendor/tom-select/css/tom-select.bootstrap5.min.css'
    },
    {
        from: 'node_modules/tom-select/dist/js/tom-select.complete.min.js',
        to: 'public/assets/vendor/tom-select/js/tom-select.complete.min.js'
    },
    {
        from: 'node_modules/flatpickr/dist/flatpickr.min.js',
        to: 'public/assets/vendor/flatpickr/js/flatpickr.min.js'
    },
    {
        from: 'node_modules/flatpickr/dist/l10n/it.js',
        to: 'public/assets/vendor/flatpickr/js/it.js'
    },
    {
        from: 'node_modules/flatpickr/dist/flatpickr.min.css',
        to: 'public/assets/vendor/flatpickr/css/flatpickr.min.css'
    },
];

assetsToCopy.forEach(asset => {
    const destDir = path.extname(asset.to) ? path.dirname(asset.to) : asset.to;

    // Crea la cartella di destinazione se non esiste
    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }

    // Usiamo cpSync (disponibile da Node 16.7+) che gestisce file e cartelle ricorsivamente
    try {
        fs.cpSync(asset.from, asset.to, { recursive: true });
        console.log(`Copiato: ${path.basename(asset.to)}`);
    } catch (err) {
        console.error(`Errore nella copia di ${asset.from}:`, err.message);
    }
});