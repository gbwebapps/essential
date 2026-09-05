/* Import delle costanti e utility da backend.js */
import { urlbase, action, initRangeDatePicker, initOffcanvasAutoClose } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';

const actions = {
    index: function(){

        /* 1. Inizializziamo il manager separando i campi di testo dalle date */
        const auditsManager = new ListManager({
            controller: 'audits',
            url: urlbase + 'backend/audits',
            containerId: 'index-audits-container',
            searchFields: ['username', 'action', 'section', 'details'],
            searchDates: ['created_at']
        });
        auditsManager.init();

        /* 2. Attiviamo Flatpickr sui wrapper (leggerà i valori appena inseriti dal manager) */
        const { pickerFrom, pickerTo } = initRangeDatePicker('#wrapper-audits-created_at-from', '#wrapper-audits-created_at-to');

        const exportManager = new ExportCsvManager({ controller: 'audits' });
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
