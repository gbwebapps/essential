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
            try {
                /* Chiamata all'endpoint usando la tua apiFetch */
                const response = await apiFetch(urlbase + 'backend/auth/login', {
                    method: 'POST',
                    body: form_data
                });

                /* Estrazione dei dati JSON */
                const data = await response.json();

                /* 1. Reset immediato degli errori visivi */
                document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

                /* 2. Errori di Validazione (422 o simili) */
                if (data.errors) {
                    handleValidationErrors(data.errors);
                    showToast('danger', data.message);
                    return;
                }

                /* 3. Fallimento Logico (Credenziali errate, Account disabilitato) */
                if (data.result === false) {
                    showToast('danger', data.message);
                    form.reset();
                    return;
                }

                /* 5. Successo Finale */
                if (data.result === true) {
                    showToast('success', 'Accesso autorizzato. Reindirizzamento...');

                    /* Reindirizzamento alla login dopo un breve delay per far leggere il toast? 
                       Oppure immediato come nel tuo vecchio codice */
                    setTimeout(() => {
                        window.location.href = urlbase + 'backend/dashboard';
                    }, 1000);
                }

            } catch (error) {
                /* L'errore è già parzialmente gestito da handleAjaxError in apiFetch */
                console.error("Login Error:", error);
            }
        }
    }, 

    resetPassword: function() {
        const form = document.getElementById('reset_password_form');
        if (!form) return; /* Early return se il form non esiste */

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const form_data = new FormData(form);
            performReset(form_data);
        });

        async function performReset(form_data) {
            try {
                /* Chiamata fetch tramite la tua utility apiFetch */
                const response = await apiFetch(urlbase + 'backend/auth/resetPassword', {
                    method: 'POST',
                    body: form_data
                });

                const data = await response.json();

                /* 1. Pulizia errori precedenti */
                document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

                /* 2. Gestione Errori di Validazione */
                if (data.errors) {
                    handleValidationErrors(data.errors);
                    showToast('danger', data.message);
                    return;
                }

                /* 3. Fallimento Logico (es. email non trovata o errore sistema) */
                if (data.result === false) {
                    showToast('danger', data.message);
                    return;
                }

                /* 4. Successo */
                if (data.result === true) {
                    form.reset();
                    showToast('success', data.message || 'Istruzioni inviate via email.');
                    
                    /* Reindirizzamento alla login dopo un breve delay per far leggere il toast? 
                       Oppure immediato come nel tuo vecchio codice */
                    setTimeout(() => {
                        window.location.href = urlbase + 'backend/auth/login';
                    }, 1000);
                }

            } catch (error) {
                console.error("Errore durante il reset password:", error);
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
            try {
                const response = await apiFetch(urlbase + 'backend/auth/setPassword', {
                    method: 'POST',
                    body: form_data
                });

                const data = await response.json();

                /* 1. Pulizia errori precedenti */
                document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '&nbsp;');

                /* 2. Gestione Errori di Validazione */
                if (data.errors) {
                    handleValidationErrors(data.errors);
                    showToast('danger', data.message);
                    return;
                }

                /* 3. Fallimento Logico (es. token scaduto o non valido) */
                if (data.result === false) {
                    showToast('danger', data.message);
                    return;
                }

                /* 4. Successo */
                if (data.result === true) {
                    form.reset();
                    showToast('success', data.message || 'Password aggiornata con successo.');
                    
                    /* Reindirizzamento alla login */
                    setTimeout(() => {
                        window.location.href = urlbase + 'backend/auth/login';
                    }, 1000);
                }

            } catch (error) {
                console.error("Errore durante l'impostazione della password:", error);
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
