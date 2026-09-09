/* Import delle costanti e utility da backend.js */
import { urlbase, action, initRangeDatePicker, initOffcanvasAutoClose } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, DeleteManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';

const actions = {
    index: function(){

        /* 1. Inizializziamo il manager separando i campi di testo dalle date */
        const logsManager = new ListManager({
            controller: 'logs',
            url: urlbase + 'backend/logs',
            containerId: 'index-logs-container',
            searchFields: ['email', 'log_type'],
            searchDates: ['log_create']
        });
        logsManager.init();

        /* 2. Attiviamo Flatpickr sui wrapper (leggerà i valori appena inseriti dal manager) */
        const { pickerFrom, pickerTo } = initRangeDatePicker('#wrapper-logs-log_create-from', '#wrapper-logs-log_create-to');

        const exportManager = new ExportCsvManager({ controller: 'logs' });
        exportManager.init();

        initOffcanvasAutoClose('actionsOffcanvas');

    },
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
