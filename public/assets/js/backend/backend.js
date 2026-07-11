/* HTML references */
export const controller = document.getElementById('controller').dataset.controller;
export const action = document.getElementById('action').dataset.action;
export const urlbase = document.getElementById('hidden-urlbase').dataset.urlbase;

/* Scrollup */
window.addEventListener('scroll', function() {
    const scrollupElements = document.querySelectorAll('.scrollup');
    scrollupElements.forEach(el => {
        if (window.scrollY > 120) {
            el.classList.remove('fade-out');
            el.classList.add('fade-in');
        } else {
            el.classList.remove('fade-in');
            el.classList.add('fade-out');
        }
    });
});

document.addEventListener('click', function(e) {
    const el = e.target.closest('.scrollup');
    if ( ! el) return;
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

/* --- Gestione globale Loader e Controlli --- */
export function toggleLoader(show) {
    const loader = document.getElementById('show-loader');
    if ( ! loader) return;

    if (show) {
        loader.style.display = 'block';
        loader.innerHTML = `<img src="${urlbase}assets/img/squares_wave.gif" alt="">`;
        disableAllControls();
    } else {
        loader.style.display = 'none';
        loader.innerHTML = '';
        enableAllControls();
    }
}

/* --- Chiamata fetch generica ottimizzata (Interfaccia Response preservata) --- */
export async function apiFetch(input, init = {}) {
    toggleLoader(true);

    const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
    const method = (init.method || 'GET').toUpperCase();

    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            defaultHeaders['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
        }
    }

    init.headers = Object.assign({}, defaultHeaders, init.headers || {});

    try {
        const response = await fetch(input, init);
        
        /* 1. Gestione dei casi con Status 200 OK */
        if (response.ok) {
            /* Consumiamo lo stream una volta sola qui dentro */
            const data = await response.json();

            /* Sbarramento Sessione Scaduta */
            if (data && data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return new Promise(() => {});
            }

            /* Aggiornamento automatico del token CSRF se presente nel payload */
            if (data && data.csrf) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) csrfMeta.setAttribute('content', data.csrf);
            }

            /* Ricreiamo e restituiamo un oggetto Response fresco per non rompere il codice a valle */
            return Response.json(data);
        }

        /* 2. Gestione degli errori del server (es. 403, 500, 404) */
        const errorJson = await response.json().catch(() => ({}));
        
        if (response.status === 403 && errorJson.csrf) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) csrfMeta.setAttribute('content', errorJson.csrf);
        }

        const serverMessage = errorJson.message || `Errore server (${response.status})`;
        handleAjaxError({ status: response.status }, serverMessage, null);
        
        throw response;

    } catch (error) {
        if ( ! (error instanceof Response)) {
            /* Errori di rete puri (es. assenza di connessione) */
            handleAjaxError({ status: 0 }, error.message, error);
        }
        throw error;
    } finally {
        toggleLoader(false);
    }
}

/* Funzione per disabilitare i links durante la chiamata ajax */
function disableLinks() {
    document.querySelectorAll('a').forEach(link => {
        link.dataset.originalHref = link.getAttribute('href') || '';
        link.removeAttribute('href');
        link.setAttribute('aria-disabled', 'true');
        link.setAttribute('tabindex', '-1');
        link.classList.add('disabled-link');
    });
}

/* Funzione per abilitare i links durante la chiamata ajax */
function enableLinks() {
    document.querySelectorAll('a').forEach(link => {
        const href = link.dataset.originalHref;
        if (href) link.setAttribute('href', href);
        delete link.dataset.originalHref;
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        link.classList.remove('disabled-link');
    });
}

/* Funzione per disabilitare tutti gli elementi di un form durante lka chiamata ajax */
function disableAllControls() {
    const controls = document.querySelectorAll('input, select, textarea, button');
    controls.forEach(el => el.disabled = true);
    disableLinks();
}

/* Funzione per abilitare tutti gli elementi di un form durante la chiamata ajax */
function enableAllControls() {
    const controls = document.querySelectorAll('input, select, textarea, button');
    controls.forEach(el => el.disabled = false);
    enableLinks();
}

/* Funzione per gestire gli errori di validazione */
export function handleValidationErrors(errors) {
    Object.entries(errors).forEach(([key, value]) => {
        document.querySelectorAll('.error_' + key).forEach(el => {
            el.textContent = value;
            // el.classList.add('error-show'); // fade-in
        });
    });
}

/* Funzione per gestire gli errori per le immagini adattata a CI4 */
export function handleValidationImages(errors) {
    Object.entries(errors).forEach(([key, message]) => {
        
        /* Intercettiamo le chiavi che iniziano con "images." */
        if (key.startsWith('images.')) {

            /* Estraiamo l'ID JS dopo il punto (es. "mrcesmbk5w52f") */
            const id = key.split('.')[1];
            
            /* Cerchiamo direttamente l'ID del div di errore che è fuori dal preview-item */
            const errorBox = document.getElementById(`error-img-${id}`);
            
            if (errorBox) {
                errorBox.textContent = Array.isArray(message) ? message.join(', ') : String(message);
            }
        }
    });
}

/* Funzione per gestire il .fail delle chiamate ajax */
export function handleAjaxError(jqXHR, textStatus, errorThrown) {

    /* Messaggio descrittivo di base */
    let message = '';

    /* Se è un errore 403, prendiamo SOLO lo statusText pulito senza debug */
    if (jqXHR.status === 403) {
        message = textStatus;
    } else {
        /* Per tutti gli altri errori (es. 500, 404), mantiene il report completo */
        message = `Errore AJAX:
        - Status Code: ${jqXHR.status}
        - Status Text: ${textStatus}
        - Error Thrown: ${errorThrown}`;
    }

    /* Mostra il messaggio in un toast */
    showAlert('danger', message);

    /* Logga comunque il messaggio completo in console per il debug dello sviluppatore */
    console.error('Dettagli errore AJAX:', {
        status: jqXHR.status,
        textStatus: textStatus,
        errorThrown: errorThrown
    });
}

export function showAlert(type, message, customIcon = '')
{
    let alertClass;
    let defaultIcon;

    /* Determina classe e icona di default in base al tipo */
    switch (type) {
        case 'success':
            alertClass = 'alert-success';
            defaultIcon = '<i class="fa-solid fa-circle-check"></i>';
            break;
        case 'danger':
            alertClass = 'alert-danger';
            defaultIcon = '<i class="fa-solid fa-triangle-exclamation"></i>';
            break;
        case 'info':
            alertClass = 'alert-info';
            defaultIcon = '<i class="fa-solid fa-circle-info"></i>';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            defaultIcon = '<i class="fa-solid fa-triangle-exclamation"></i>';
            break;
        case 'primary':
            alertClass = 'alert-primary';
            defaultIcon = '<i class="fa-solid fa-circle-info"></i>';
            break;
        default:
            alertClass = 'alert-secondary';
            defaultIcon = '';
    }

    /* Usa l'icona custom se passata, altrimenti quella di default */
    const finalIcon = customIcon || defaultIcon;
    const iconHTML = finalIcon ? `${finalIcon} ` : '';

    const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show border-0 d-flex align-items-center p-3" role="alert">
            <div class="w-100 text-center p-0 ms-4">
                ${iconHTML}${message}
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    /* Svuota il contenitore rimuovendo l'alert precedente prima di inserire il nuovo */
    alertContainer.innerHTML = '';

    const alertElement = document.createElement('div');
    alertElement.innerHTML = alertHTML.trim();
    const currentAlert = alertElement.firstChild;
    
    alertContainer.appendChild(currentAlert);

    currentAlert.addEventListener('closed.bs.alert', function () {
        this.remove();
    });
}

/* Funzioni per generare alert di conferma si/no */
let globalConfirmModalEl = null;
let globalConfirmModal = null;
let customBackdropEl = null;

export async function askConfirm(message, options = {}) {
    return new Promise(resolve => {

        /* se il backdrop non esiste, crealo una volta sola */
        if ( ! customBackdropEl) {
            customBackdropEl = document.createElement('div');
            customBackdropEl.id = 'customBackdrop';
            document.body.appendChild(customBackdropEl);
        }

        /* se il modale non esiste, crealo una volta sola */
        if ( ! globalConfirmModalEl) {
            globalConfirmModalEl = document.createElement('div');
            globalConfirmModalEl.id = 'globalConfirmModal';
            globalConfirmModalEl.className = 'modal fade';
            globalConfirmModalEl.tabIndex = -1;
            globalConfirmModalEl.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content shadow">
                    <div class="modal-header border-0">
                      <h5 class="modal-title"></h5>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer border-0">
                      <button type="button" class="btn btn-danger btn-cancel">No</button>
                      <button type="button" class="btn btn-success btn-ok">Sì</button>
                    </div>
                  </div>
                </div>`;
            document.body.appendChild(globalConfirmModalEl);
            globalConfirmModal = new bootstrap.Modal(globalConfirmModalEl, {
                backdrop: false,   /* nessun overlay Bootstrap */
                keyboard: false    /* ESC disabilitato */
            });

            // mostra/nasconde il backdrop custom sugli eventi bootstrap
            globalConfirmModalEl.addEventListener('show.bs.modal', () => {
                customBackdropEl.classList.add('active');
            });
            globalConfirmModalEl.addEventListener('hidden.bs.modal', () => {
                customBackdropEl.classList.remove('active');
            });
        }

        /* aggiorna testi */
        globalConfirmModalEl.querySelector('.modal-title').textContent = options.title || 'Conferma';
        globalConfirmModalEl.querySelector('.modal-body').textContent = message;
        globalConfirmModalEl.querySelector('.btn-ok').textContent = options.okText || 'Sì';
        globalConfirmModalEl.querySelector('.btn-cancel').textContent = options.cancelText || 'No';

        const okBtn = globalConfirmModalEl.querySelector('.btn-ok');
        const cancelBtn = globalConfirmModalEl.querySelector('.btn-cancel');

        const cleanUp = () => {
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
        };

        const onOk = () => {
            cleanUp();
            resolve(true);
            globalConfirmModal.hide();
        };

        const onCancel = () => {
            cleanUp();
            resolve(false);
            globalConfirmModal.hide();
        };

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);

        globalConfirmModal.show();
    });
}

export function smoothReplace(container, newHtml) {
    if (!container) return;

    // 1. Applichiamo subito l'opacità zero (nascosto) senza transizione
    container.style.transition = 'none';
    container.style.opacity = '0';

    // 2. Sostituiamo immediatamente il contenuto HTML
    container.innerHTML = newHtml;

    // 3. Forziamo il reflow del browser (obbliga a registrare lo stato opacità = 0)
    container.offsetHeight;

    // 4. Ripristiniamo la transizione CSS e portiamo l'opacità a 1 per avviare il fade-in
    container.style.transition = 'opacity 0.25s ease-in-out';
    container.style.opacity = '1';
}
