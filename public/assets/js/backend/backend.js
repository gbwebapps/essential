/* HTML references */
export const controller = document.getElementById('controller').dataset.controller;
export const action = document.getElementById('action').dataset.action;
export const urlbase = document.getElementById('hidden-urlbase').dataset.urlbase;

import { ExportPdfManager } from './modules/ExportPdf.js';

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

/* Avvio del gestore PDF al caricamento della pagina */
new ExportPdfManager();

/* Listener globale per il pulsante di stampa */
document.addEventListener('click', (e) => {
    const printBtn = e.target.closest('#show-print-button');
    
    if (printBtn) {
        e.preventDefault();
        window.print();
    }
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

/* Coda di esecuzione per evitare Race Conditions sulle chiamate simultanee */
let fetchQueue = Promise.resolve();

/* --- Chiamata fetch generica ottimizzata (Interfaccia Response preservata) --- */
export async function apiFetch(input, init = {}) {

    /* Concateniamo la chiamata per eseguirle in sequenza e mantenere coerente il CSRF */
    fetchQueue = fetchQueue.then(() => executeFetch(input, init));
    return fetchQueue;
}

async function executeFetch(input, init = {}) {
    toggleLoader(true);

    const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
    const method = (init.method || 'GET').toUpperCase();

    /* Selezione dinamica del meta tag CSRF presente nella pagina */
    const csrfMeta = document.getElementById('csrf-meta');

    /* Iniezione dell'header CSRF su tutte le chiamate non-GET */
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method) && csrfMeta) {
        defaultHeaders['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
    }

    init.headers = Object.assign({}, defaultHeaders, init.headers || {});

    try {
        const response = await fetch(input, init);
        
        /* 1. Gestione dei casi con Status 200 OK */
        if (response.ok) {
            const data = await response.json();

            /* Sbarramento Sessione Scaduta */
            if (data && data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return new Promise(() => {});
            }

            /* Aggiornamento automatico del token CSRF leggendo csrfHash o csrf */
            const newHash = data?.csrfHash || data?.csrf;
            if (newHash && csrfMeta) {
                csrfMeta.setAttribute('content', newHash);
            }

            /* Ricreiamo e restituiamo un oggetto Response fresco per non rompere il codice a valle */
            return Response.json(data);
        }

        /* 2. Gestione degli errori del server (es. 403, 500, 404) */
        const errorJson = await response.json().catch(() => ({}));
        
        /* Aggiornamento del token CSRF anche in caso di risposta d'errore HTTP */
        const errorHash = errorJson?.csrfHash || errorJson?.csrf;
        if (errorHash && csrfMeta) {
            csrfMeta.setAttribute('content', errorHash);
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
    controls.forEach(el => {
        if (!el.classList.contains('no-general-disabled')) {
            el.disabled = true;
        }
    });
    disableLinks();
}

/* Funzione per abilitare tutti gli elementi di un form durante la chiamata ajax */
function enableAllControls() {
    const controls = document.querySelectorAll('input, select, textarea, button');
    controls.forEach(el => {
        if (!el.classList.contains('no-general-disabled')) {
            el.disabled = false;
        }
    });
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
            alertClass = 'light text-success fw-bold';
            defaultIcon = '<i class="fa-solid fa-circle-check"></i>';
            break;
        case 'danger':
            alertClass = 'light text-danger fw-bold';
            defaultIcon = '<i class="fa-solid fa-triangle-exclamation"></i>';
            break;
        case 'info':
            alertClass = 'light text-info fw-bold';
            defaultIcon = '<i class="fa-solid fa-circle-info"></i>';
            break;
        default:
            alertClass = 'light text-secondary fw-bold';
            defaultIcon = '';
    }

    /* Usa l'icona custom se passata, altrimenti quella di default */
    const finalIcon = customIcon || defaultIcon;
    const iconHTML = finalIcon ? `${finalIcon} ` : '';

    const alertHTML = `
        <div class="alert alert-${alertClass} alert-dismissible fade show border-0 d-flex align-items-center p-3" role="alert">
            <div class="w-100 text-center p-0 ms-4">
                ${iconHTML}${message}
            </div>
            <button type="button" class="badge bg-danger p-2 border-0 rounded-1 ms-auto" data-bs-dismiss="alert" style="cursor: pointer;">
                <i class="fa-solid fa-xmark"></i> Rimuovi
            </button>
        </div>`;

    const alertContainer = document.getElementById('alert-container');
    if ( ! alertContainer) return;

    /* Cerca l'alert di sessione PHP e lo rimuove dal DOM se presente */
    const sessionAlert = document.querySelector('.alert-session');
    if (sessionAlert) {
        sessionAlert.remove();
    }

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

let confirmModalInitialized = false;

export async function askConfirm(message) {
    return new Promise(resolve => {
        
        const modalEl = document.getElementById('globalConfirmModal');

        /* 1. Fallback di sicurezza */
        if ( ! modalEl) {
            return resolve(window.confirm(message));
        }

        /* 2. Inizializzazione eventi backdrop una sola volta */
        if ( ! confirmModalInitialized) {
            const backdropEl = document.getElementById('customBackdrop');
            if (backdropEl) {
                modalEl.addEventListener('show.bs.modal', () => backdropEl.classList.add('active'));
                modalEl.addEventListener('hidden.bs.modal', () => backdropEl.classList.remove('active'));
            }
            confirmModalInitialized = true;
        }

        /* Recupero o creazione istanza Bootstrap */
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: false, 
            keyboard: false  
        });

        /* 3. Aggiornamento esclusivo del messaggio */
        modalEl.querySelector('.modal-body').textContent = message;

        /* 4. Prevenzione Multi-Esecuzione: controlli di esistenza prima della clonazione */
        let oldOkBtn = modalEl.querySelector('.btn-ok');
        let oldCancelBtn = modalEl.querySelector('.btn-cancel');

        if ( ! oldOkBtn || ! oldCancelBtn) {
            console.error("askConfirm: Pulsanti '.btn-ok' o '.btn-cancel' mancanti nel DOM.");
            return resolve(false); 
        }

        const okBtn = oldOkBtn.cloneNode(true);
        const cancelBtn = oldCancelBtn.cloneNode(true);

        oldOkBtn.replaceWith(okBtn);
        oldCancelBtn.replaceWith(cancelBtn);

        /* 5. Gestione sicura della scelta tramite semaforo */
        let isAnswered = false;

        const handleChoice = (choice) => {
            if (isAnswered) return;
            isAnswered = true;

            modalInstance.hide();
            resolve(choice);
        };

        okBtn.addEventListener('click', () => handleChoice(true));
        cancelBtn.addEventListener('click', () => handleChoice(false));

        modalInstance.show();
    });
}

export function smoothReplace(container, newHtml) {
    if ( ! container) return;

    /* Se l'HTML è vuoto, facciamo un fade-out prima di svuotare */
    if (newHtml === '') {
        container.style.transition = 'opacity 0.25s ease-in-out';
        container.style.opacity = '0';
        
        setTimeout(() => {
            container.innerHTML = '';
            container.style.transition = '';
            container.style.opacity = '';
        }, 250);
        return;
    }

    /* Comportamento normale: fade-in del nuovo contenuto */
    container.style.transition = 'none';
    container.style.opacity = '0';
    
    container.innerHTML = newHtml;
    
    container.offsetHeight; /* Forza il reflow */
    
    container.style.transition = 'opacity 0.25s ease-in-out';
    container.style.opacity = '1';
}

// export function smoothReplace(container, newHtml) {
//     if ( ! container) return;

//     // 1. Applichiamo subito l'opacità zero (nascosto) senza transizione
//     container.style.transition = 'none';
//     container.style.opacity = '0';

//     // 2. Sostituiamo immediatamente il contenuto HTML
//     container.innerHTML = newHtml;

//     // 3. Forziamo il reflow del browser (obbliga a registrare lo stato opacità = 0)
//     container.offsetHeight;

//     // 4. Ripristiniamo la transizione CSS e portiamo l'opacità a 1 per avviare il fade-in
//     container.style.transition = 'opacity 0.25s ease-in-out';
//     container.style.opacity = '1';
// }

/* Funzione globale per inizializzare tutte le Tom Select attive nella pagina */
export function initTomSelects() {
    document.querySelectorAll('.tom-select:not(.tomselected)').forEach(select => {
        new TomSelect(select, {
            plugins: {
                remove_button: {
                    title: 'Rimuovi'
                }
            },
            persist: false,
            create: false,
            placeholder: select.getAttribute('placeholder') || 'Seleziona...'
        });
    });
}

/**
 * Inizializza un singolo campo data/ora senza vincoli di intervallo
 * @param {string} containerSelector - Il selettore del contenitore (es. '.input-group' o '#id-parent')
 */
export function initSingleDatePicker(containerSelector) {
    if (typeof flatpickr === 'undefined') return;

    return flatpickr(containerSelector, {
        locale: flatpickr.l10ns.it,
        enableTime: true,
        time_24hr: true,
        dateFormat: "Y-m-d H:i:s",
        altInput: true,
        altFormat: "d/m/Y H:i:s",
        allowInput: true,
        wrap: true
    });
}

/**
 * Inizializza una coppia di campi data/ora relazionati tra loro (Range)
 * @param {string} fromContainerSelector - Il selettore del contenitore "Da"
 * @param {string} toContainerSelector - Il selettore del contenitore "A"
 */
export function initRangeDatePicker(fromContainerSelector, toContainerSelector) {
    if (typeof flatpickr === 'undefined') return { pickerFrom: null, pickerTo: null };

    const baseConfig = {
        locale: flatpickr.l10ns.it,
        enableTime: true,
        enableSeconds: true,
        time_24hr: true,
        dateFormat: "Y-m-d H:i:ss",
        altInput: true,
        altFormat: "d/m/Y H:i:ss",
        allowInput: true,
        wrap: true
    };

    const pickerFrom = flatpickr(fromContainerSelector, {
        ...baseConfig,
        onChange: function(selectedDates, dateStr, instance) {
            if (pickerTo) pickerTo.set("minDate", dateStr);
            const input = instance.element.querySelector('input') || instance.element;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    const pickerTo = flatpickr(toContainerSelector, {
        ...baseConfig,
        onChange: function(selectedDates, dateStr, instance) {
            if (pickerFrom) pickerFrom.set("maxDate", dateStr);
            const input = instance.element.querySelector('input') || instance.element;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    return { pickerFrom, pickerTo };
}