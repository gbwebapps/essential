/* Import delle costanti e utility da backend.js */
import { urlbase, action} from './backend.js';

/* Import della classe logica */
import { GroupManager } from './components/Groups.js';

const actions = {
    index: function() {

    	const groupManager = new GroupManager({
	        getGroups: urlbase + 'backend/groups/getGroups',
	        getGroup: urlbase + 'backend/groups/getGroup',
	        urlGetExceptions: urlbase + 'backend/groups/getAdmins'
	    });

        /* Listener per il primo macro-accordion (Aggiungi Gruppo) */
        const mainCollapseAdd = document.getElementById('main_collapse_add');
        if (mainCollapseAdd) {
            
            mainCollapseAdd.addEventListener('show.bs.collapse', (e) => {
                /* Assicuriamoci che l'evento sia del macro-panel e non dei figli */
                if (e.target === mainCollapseAdd) {
                    groupManager.loadAddGroupPanel();
                }
            });

            mainCollapseAdd.addEventListener('hidden.bs.collapse', (e) => {
                if (e.target === mainCollapseAdd) {
                    groupManager.resetAddContainer();
                }
            });
        }

	    /* Listener per il secondo macro-accordion (Lista Gruppi) */
        const mainCollapseList = document.getElementById('main_collapse_list');
        if (mainCollapseList) {
            
            mainCollapseList.addEventListener('show.bs.collapse', (e) => {
                /* Assicuriamoci che l'evento sia del macro-panel e non dei figli */
                if (e.target === mainCollapseList) {
                    groupManager.loadGroupsList();
                }
            });

            mainCollapseList.addEventListener('hidden.bs.collapse', (e) => {
                /* Assicuriamoci che l'evento sia del macro-panel e non dei figli */
                if (e.target === mainCollapseList) {
                    groupManager.resetListContainer();
                }
            });
        }

	    /* Listener per il terzo macro-accordion (Eccezioni) */
	    const mainCollapseExceptions = document.getElementById('main_collapse_exceptions');
	    if (mainCollapseExceptions) {
	        mainCollapseExceptions.addEventListener('show.bs.collapse', () => {
	            groupManager.loadExceptionsPanel();
	        });
	    }

    }
};

/* Listener per il link select all nei form add ed edit per selezionare tutti i check box dei permessi */
document.addEventListener('click', function(e) {
    if (e.target.matches('.select-all')) {
        e.preventDefault();
        const controller = e.target.dataset.controller;
        const checkboxes = document.querySelectorAll(`input[type="checkbox"].${controller}`);
        const anyChecked = Array.from(checkboxes).some(el => el.checked);
        const newState = !anyChecked;
        checkboxes.forEach(el => el.checked = newState);
    }
});

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}