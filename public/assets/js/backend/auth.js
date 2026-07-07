/* Import delle costanti e utility da backend.js */
import { urlbase, action } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { LoginManager, ResetPasswordManager, SetPasswordManager, VerifyManager } from './components/Auth.js';

const actions = {
    index: function(){},
    login: function() {
        
        const loginManager = new LoginManager();
        loginManager.init();
    }, 

    resetPassword: function() {
        
        const resetPasswordManager = new ResetPasswordManager();
        resetPasswordManager.init();
    }, 

    setPassword: function() {
        
        const setPasswordManager = new SetPasswordManager();
        setPasswordManager.init();
    },

    verify: function() {
        
        const verifyManager = new VerifyManager();
        verifyManager.init();
    },
}

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
