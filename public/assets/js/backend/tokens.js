/* Import delle costanti e utility da backend.js */
import { urlbase, action, initRangeDatePicker } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, DeleteManager } from './components/Crud.js';

const actions = {
    index: function(){

        /* 1. Inizializziamo il manager separando i campi di testo dalle date */
        const tokensManager = new ListManager({
            controller: 'tokens',
            url: urlbase + 'backend/tokens',
            containerId: 'index-tokens-container',
            searchFields: ['email', 'token_type'],
            searchDates: ['token_create']
        });
        tokensManager.init();

        /* 2. Attiviamo Flatpickr sui wrapper (leggerà i valori appena inseriti dal manager) */
        const { pickerFrom, pickerTo } = initRangeDatePicker('#wrapper-tokens-token_create-from', '#wrapper-tokens-token_create-to');

        const deleteManager = new DeleteManager({
            controller: 'tokens',
            urls: {
                hardDelete: urlbase + 'backend/tokens/hardDelete'
            },
            listManager: tokensManager
        });
        deleteManager.init();

    },
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
