import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process'; /* Importiamo la funzione per eseguire comandi di terminale */

// 1. Eseguiamo l'aggiornamento dei pacchetti in node_modules prima di copiare
try {
    console.log('Controllo e aggiornamento dei pacchetti npm in corso...');
    /* Esegue npm update mostrando l'output direttamente nella tua console */
    execSync('npm update', { stdio: 'inherit' });
    console.log('Aggiornamento completato con successo.\n');
} catch (error) {
    console.error('Nota: Impossibile aggiornare i pacchetti (nessuna connessione o errore npm):', error.message);
    console.log('Procedo comunque con la copia dei file locali disponibili...\n');
}

const assetsToCopy = [
    {
        from: 'node_modules/bootstrap/dist/css/bootstrap.min.css',
        to: 'public/assets/vendor/bootstrap/css/bootstrap.min.css'
    },
    {
        from: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
        to: 'public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'
    },
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

    if ( ! fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }

    try {
        fs.cpSync(asset.from, asset.to, { recursive: true });
        console.log(`Copiato: ${path.basename(asset.to)}`);
    } catch (err) {
        console.error(`Errore nella copia di ${asset.from}:`, err.message);
    }
});