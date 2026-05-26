/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, apiFetch, handleValidationErrors, showToast, askConfirm, smoothReplace } from './backend.js';

const actions = {
    index: function(){},
    login: function() {
        const form = document.getElementById('login_form');
        if (!form) return; /* Early return se il form non esiste */

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const form_data = new FormData(form);
            
            /* 
               Usiamo async/await per una lettura lineare del codice.
               È molto più pulito rispetto ai .then() concatenati.
            */
            performLogin(form_data);
        });

        async function performLogin(form_data) {
            /* 1. Reset immediato degli errori visivi */
            document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

            try {
                const response = await apiFetch(urlbase + 'backend/auth/login', {
                    method: 'POST',
                    body: form_data
                });

                /* Se il codice arriva qui, è matematicamente un 200 OK (Successo) */
                const data = await response.json();
                
                if (data.result === true) {
                    window.location.href = urlbase + 'backend/dashboard';
                }

            } catch (error) {
                /* 2. Gestione centralizzata dei fallimenti HTTP */
                if (error.status === 422) {
                    /* Errore di Validazione (Campi vuoti, ecc.) */
                    handleValidationErrors(error.data.errors);
                    showToast('danger', error.data.message);
                } else if (error.status === 401) {
                    /* Credenziali Errate (loginFailed dal Model) */
                    showToast('danger', error.data.message);
                    document.getElementById('login_form').reset();
                } else {
                    /* Errori di rete o imprevisti */
                    console.error("Login Error:", error);
                }
            }
        }
    }, 

    resetPassword: function() {
        const form = document.getElementById('reset_password_form');
        if (!form) return; /* Early return se il form non esiste */

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const form_data = new FormData(form);
            performResetPassword(form_data);
        });

        async function performResetPassword(form_data) {
            /* 1. Pulizia immediata degli errori visivi */
            document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

            try {
                /* 2. Chiamata fetch all'endpoint */
                const response = await apiFetch(urlbase + 'backend/auth/resetPassword', {
                    method: 'POST',
                    body: form_data
                });

                /* 3. Estrazione dati JSON (qui arriva solo se lo status è 200 OK) */
                const data = await response.json();

                /* 4. Successo Finale */
                document.getElementById('reset_password_form').reset();
                showToast('success', data.message);
                
            } catch (error) {
                /* 5. Gestione centralizzata dei fallimenti HTTP */
                if (error.status === 422) {
                    /* Errore di Validazione (es. campo email vuoto o malformato) */
                    handleValidationErrors(error.data.errors);
                    showToast('danger', error.data.message);
                } else if (error.status === 401) {
                    /* Fallimento Logico (es. email non trovata nel database) */
                    showToast('danger', error.data.message);
                } else {
                    /* Altri errori loggati in console */
                    console.error("Errore durante il reset password:", error);
                }
            }
        }
    }, 

    setPassword: function() {
        const form = document.getElementById('set_password_form');
        if (!form) return; /* Early return se il form non esiste */

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const form_data = new FormData(form);
            performSetPassword(form_data);
        });

        async function performSetPassword(form_data) {
            /* 1. Pulizia immediata degli errori visivi */
            document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

            try {
                /* 2. Chiamata fetch all'endpoint */
                const response = await apiFetch(urlbase + 'backend/auth/setPassword', {
                    method: 'POST',
                    body: form_data
                });

                /* 3. Estrazione dati JSON (qui arriva solo se lo status è 200 OK) */
                const data = await response.json();

                /* 4. Successo Finale */
                document.getElementById('set_password_form').reset(); /* Assicurati che l'ID del form sia corretto */
                showToast('success', data.message);
                
                /* Reindirizzamento alla login */
                window.location.href = urlbase + 'backend/auth/login';

            } catch (error) {
                /* 5. Gestione centralizzata dei fallimenti HTTP */
                if (error.status === 422) {
                    /* Errore di Validazione (es. password troppo corta o non coincidente) */
                    handleValidationErrors(error.data.errors);
                    showToast('danger', error.data.message);
                } else if (error.status === 401) {
                    /* Fallimento Logico (es. token di reset scaduto o invalido) */
                    showToast('danger', error.data.message);
                } else {
                    /* Altri errori loggati in console */
                    console.error("Errore durante l'impostazione della password:", error);
                }
            }
        }
    }
}

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
