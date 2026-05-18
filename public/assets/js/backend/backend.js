/* HTML references */
export const controller = document.getElementById('controller').dataset.controller;
export const action = document.getElementById('action').dataset.action;
export const urlbase = document.getElementById('hidden-urlbase').dataset.urlbase;

/* Scrollup */
window.addEventListener('scroll', function() {
    const scrollupElements = document.querySelectorAll('.scrollup');
    if (window.scrollY > 120) {
        scrollupElements.forEach(el => {
            if (getComputedStyle(el).display === 'none') {
                el.style.display = 'block';
            }
        });
    } else {
        scrollupElements.forEach(el => {
            if (getComputedStyle(el).display !== 'none') {
                el.style.display = 'none';
            }
        });
    }
});

document.addEventListener('click', function(e) {
    const el = e.target.closest('.scrollup');
    if ( ! el) return;
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

/* --- Gestione globale Loader e Controlli --- */
export function toggleLoader(show) {
    const loader = document.getElementById('show_loader');
    if (!loader) return;

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

/* --- Chiamata fetch generica aggiornata --- */
export async function apiFetch(input, init = {}) {
    toggleLoader(true); /* Attiva loader */

    const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
    init.headers = Object.assign({}, defaultHeaders, init.headers || {});

    try {
        const response = await fetch(input, init);
        if (!response.ok) handleAjaxError(response, response.statusText, null);
        return response;
    } catch (error) {
        handleAjaxError({ status: 0, statusText: error.message }, error.message, error);
        throw error;
    } finally {
        toggleLoader(false); /* Disattiva loader */
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
            el.classList.add('error-show'); // fade-in
        });
    });
}

/* Funzione per gestire gli errori per le immagini */
export function handleValidationImages(errors) {
    if (!errors.images) return;

    if (errors.images.required) {
        const preview = document.querySelector('#preview_images');
        if (preview) {
            preview.innerHTML = `<div class="text-danger text-center fw-bold error-show">${errors.images.required}</div>`;
        }
        return;
    }

    Object.entries(errors.images).forEach(([id, message]) => {
        const item = document.querySelector(`.preview-item[data-id="${id}"]`);
        if (item) {
            const errorBox = item.querySelector('.error-msg');
            if (errorBox) {
                errorBox.textContent = Array.isArray(message) ? message.join(', ') : String(message);
                errorBox.classList.add('error-show'); // fade-in
            }
        }
    });
}

/* Funzione per gestire gli errori per i documenti */
export function handleValidationDocuments(errors) {
    if (!errors.documents) return;

    if (errors.documents.required) {
        const preview = document.querySelector('#preview_documents');
        if (preview) {
            preview.innerHTML = `<div class="text-danger text-center fw-bold error-show">${errors.documents.required}</div>`;
        }
        return;
    }

    Object.entries(errors.documents).forEach(([id, message]) => {
        const item = document.querySelector(`.preview-doc[data-id="${id}"]`);
        if (item) {
            const errorBox = item.querySelector('.error-msg');
            if (errorBox) {
                errorBox.textContent = Array.isArray(message) ? message.join(', ') : String(message);
                errorBox.classList.add('error-show'); // fade-in
            }
        }
    });
}

/* Funzione per gestire il .fail delle chiamate ajax */
export function handleAjaxError(jqXHR, textStatus, errorThrown) {

    /* Messaggio descrittivo */
    let message = `Errore AJAX:
    - Status Code: ${jqXHR.status}
    - Status Text: ${textStatus}
    - Error Thrown: ${errorThrown}`;

    /* Aggiungi il contenuto della risposta se disponibile */
    if (jqXHR.responseText) {
        message += `\n- Response Text: ${jqXHR.responseText}`;
    }

    /* Mostra il messaggio in un toast */
    showToast('danger', message);

    /* Logga il messaggio in console per debugging */
    console.error('Dettagli errore AJAX:', {
        status: jqXHR.status,
        textStatus: textStatus,
        errorThrown: errorThrown,
        responseText: jqXHR.responseText
    });
}

export function showToast(type, message)
{
    /* Determina la classe di stile in base al tipo */
    let bgClass;
    switch (type) {
        case 'success':
            bgClass = 'bg-success text-white';
            break;
        case 'danger':
            bgClass = 'bg-danger text-white';
            break;
        case 'info':
            bgClass = 'bg-info text-white';
            break;
        case 'warning':
            bgClass = 'bg-warning text-dark';
            break;
        case 'primary':
            bgClass = 'bg-primary text-white';
            break;
        default:
            bgClass = 'bg-secondary text-white';
    }

    /* Crea il markup del toast */
    const toastHTML = `
        <div class="toast ${bgClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true">
            <div class="toast-header">
                <strong class="me-auto">Essential</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>`;

    /* Aggiungi il toast al container */
    const toastContainer = document.getElementById('toast-container');
    const toastElement = document.createElement('div');
    toastElement.innerHTML = toastHTML.trim();
    toastContainer.appendChild(toastElement.firstChild);

    /* Inizializza e mostra il toast */
    const toast = new bootstrap.Toast(toastContainer.lastChild);
    toast.show();

    /* Rimuovi il toast dal DOM dopo la chiusura */
    toastContainer.lastChild.addEventListener('hidden.bs.toast', function () {
        this.remove();
        /*document.querySelectorAll('[class^="error_"], .error-msg, .error_status').forEach(el => {
            el.classList.remove('error-show'); // fade-out
            setTimeout(() => {
                el.innerHTML = '\u00A0'; // mantieni spazio e padding
            }, 150); // stessa durata della transizione CSS
        });*/
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
    container.innerHTML = newHtml;
}
