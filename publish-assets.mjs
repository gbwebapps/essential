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
    // DataTables
    {
        from: 'node_modules/datatables.net/js/dataTables.min.js',
        to: 'public/assets/vendor/datatables/js/dataTables.min.js'
    },
    {
        from: 'node_modules/datatables.net/js/dataTables.mjs',
        to: 'public/assets/vendor/datatables/js/dataTables.mjs'
    },
    /* DataTables Bootstrap 5 Integration */
    {
        from: 'node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
        to: 'public/assets/vendor/datatables/css/dataTables.bootstrap5.min.css'
    },
    {
        from: 'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
        to: 'public/assets/vendor/datatables/js/dataTables.bootstrap5.min.js'
    },
    // jQuery
    {
        from: 'node_modules/jquery/dist/jquery.min.js',
        to: 'public/assets/vendor/jquery/jquery.min.js'
    }
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