/* Inizializzazioni dei componenti del pacchetto Warehouse. */
import { action } from './backend.js';
import { WarehouseImportManager, WarehouseExportManager } from './modules/Warehouse.js';

/* Mappa delle inizializzazioni specifiche per ogni pagina del modulo. */
const actions = {
    index: function() {
        new WarehouseExportManager().init();
        new WarehouseImportManager().init();
    },
};

if (actions[action]) {
    actions[action]();
}
