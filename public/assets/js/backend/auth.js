/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, apiFetch, handleValidationErrors, showToast, askConfirm, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { LoginManager, ResetPasswordManager, SetPasswordManager } from './components/Auth.js';

const actions = {
    index: function(){},
    login: function() {
        
        /* Istanzia il manager passando le configurazioni necessarie */
        const loginManager = new LoginManager({
            formId: 'login_form',
            url: `${urlbase}backend/auth/login`
        });

        loginManager.init();
    }, 

    resetPassword: function() {
        
        /* Istanzia il manager passando le configurazioni necessarie */
        const resetPasswordManager = new ResetPasswordManager({
            formId: 'reset_password_form',                  /* ID del form nella pagina pubblica */
            url: `${urlbase}backend/auth/resetPassword`,    /* Endpoint del controller Auth */
            redirectUrl: `${urlbase}backend/auth`,          /* Redirect al login dopo il successo */ 
            showSuccessToast: false
        });

        resetPasswordManager.init();
    }, 

    setPassword: function() {
        
        /* Istanzia il manager passando le configurazioni necessarie */
        const setPasswordManager = new SetPasswordManager({
            formId: 'set_password_form',                  /* ID del form */
            url: `${urlbase}backend/auth/setPassword`,    /* Endpoint del controller */
            redirectUrl: `${urlbase}backend/auth`         /* Redirect al login dopo il successo */
        });

        setPasswordManager.init();
    },
}

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
