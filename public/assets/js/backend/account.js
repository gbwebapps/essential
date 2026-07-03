/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { EditManager, GetPermissionsManager, GetTokensManager, DeleteTokenManager, ResetPasswordManager } from './components/Account.js';

const actions = {
    edit: function() {

    	const editManager = new EditManager();
        editManager.init();

	}, 
    permissions: function() {

        const permissionsManager = new GetPermissionsManager();
        permissionsManager.init();
        
    }, 
    tokens: function() {

        const tokensManager = new GetTokensManager();
        tokensManager.init();

        const deleteTokenManager = new DeleteTokenManager();
        deleteTokenManager.init();
        
    }, 
    resetPassword: function() {

        const resetPasswordManager = new ResetPasswordManager();
        resetPasswordManager.init();

    },
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}

