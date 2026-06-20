/* Import delle costanti e utility da backend.js */
import { urlbase, action} from './backend.js';

/* Import della classe logica */
import { GroupManager } from './components/Groups.js';

const actions = {
    index: function() {

    	const groupManager = new GroupManager({
	        urlShowAll: urlbase + 'backend/groups/getGroups',
	        urlGetEditForm: urlbase + 'backend/groups/getGroup',
	        urlGetExceptions: urlbase + 'backend/groups/getAdmins'
	    });

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

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}