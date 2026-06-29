/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { EditManager, GetPermissionsManager } from './components/Account.js';

const actions = {
    edit: function() {

    	const editManager = new EditManager({
    	    formSelector: '#account_edit',
    	    url: urlbase + 'backend/account/edit',
    	    refreshSelector: '#edit_refresh',
    	    containerId: 'edit-account-container', 
    	});
    	editManager.init();

	}, 
    permissions: function() {

        const permissionsManager = new GetPermissionsManager({
            url: urlbase + 'backend/account/permissions', 
            formSelector: '#getPermissions',
            containerId: 'permissions-account-container' /* <--- Passato correttamente */
        });
        permissionsManager.init();
        
    }
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}

