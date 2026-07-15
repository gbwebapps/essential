/* Import delle utility risalendo di un livello */
import { apiFetch, urlbase, handleValidationImages, showAlert } from '../backend.js';

export class UploadPreviewImgManager {
    constructor(galleryOneImgManager = null) {

        /* Salviamo i selettori stringa, NON i nodi del DOM, così rimangono validi per sempre */
        this.inputSelector = '#inputImages';
        this.previewSelector = '#previewImages';
        this.triggerSelector = '#buttonImages';
        this.galleryOneImgManager = galleryOneImgManager;
        this.files = []; 

        /* Variabili di stato coerenti con l'architettura dei tuoi Manager */
        this.eventsBound = false;
        this.isSubmitting = false; 

        this.bindEvents();
    }

    /* Animazione ingresso miniatura */
    smoothAdd(container, element) {
        if (!container || !element) return;
        element.style.opacity = 0;
        element.style.transform = 'scale(0.95)';
        element.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        container.appendChild(element);
        requestAnimationFrame(() => {
            element.style.opacity = 1;
            element.style.transform = 'scale(1)';
        });
    }

    /* Animazione uscita miniatura */
    smoothRemove(element) {
        if (!element) return;
        element.style.height = element.offsetHeight + 'px';
        element.style.overflow = 'hidden';
        element.style.transition = 'opacity 0.2s ease, transform 0.2s ease, height 0.2s ease, margin 0.2s ease, padding 0.2s ease';
        element.offsetHeight;
        
        requestAnimationFrame(() => {
            element.style.opacity = 0;
            element.style.transform = 'scale(0.95)';
            element.style.height = 0;
            element.style.margin = 0;
            element.style.padding = 0;
        });

        element.addEventListener('transitionend', () => {
            if (element.parentNode) element.parentNode.removeChild(element);
            
            /* Aggiornato: Se la coda è vuota, puliamo del tutto il contenitore principale */
            const previewContainer = document.querySelector(this.previewSelector);
            if (previewContainer && this.files.length === 0) {
                previewContainer.innerHTML = '';
            }
        }, { once: true });
    }

    /* DELEGAZIONE DEGLI EVENTI: Listener unici sul document */
    bindEvents() {
        /* 1. Click sul pulsante Scegli Immagini */
        document.addEventListener('click', e => {
            const btn = e.target.closest(this.triggerSelector);
            if (!btn) return;
            
            const input = document.querySelector(this.inputSelector);
            if (input) input.click();
        });

        /* 2. Cambio stato dell'input file (Selezione immagini) */
        document.addEventListener('change', e => {
            if (!e.target.matches(this.inputSelector)) return;

            const newFiles = Array.from(e.target.files);
            newFiles.forEach(fileBlob => {
                if (fileBlob.type.startsWith('image/')) {
                    this.addPreview(fileBlob);
                }
            });
            e.target.value = ''; /* Reset immediato del valore nativo */
        });

        /* 3. Rimozione della miniatura */
        document.addEventListener('click', e => {
            const removeBtn = e.target.closest('.remove-preview');
            if (!removeBtn) return;

            /* Sicurezza: verifichiamo che il pulsante appartenga al nostro contenitore attivo */
            const previewContainer = document.querySelector(this.previewSelector);
            if (!previewContainer || !previewContainer.contains(removeBtn)) return;

            const id = removeBtn.dataset.id;
            this.files = this.files.filter(f => f.id !== id);
            this.removePreview(id);
        });

        /* 4. Submit del Form "Salva Immagini" autonomo */
        document.addEventListener('submit', async e => {
            if (!e.target.matches('#saveImages')) return;

            e.preventDefault();

            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                await this.saveImages(e.target);
            } catch (err) {
                console.error(err);
            } finally {
                this.isSubmitting = false;
            }
        });
    }

    /* Generazione anteprima locale */
    addPreview(fileBlob, existingId = null) {
        const id = existingId || (Date.now().toString(36) + Math.random().toString(36).slice(2, 7));
        if (!existingId) {
            this.files.push({ id, file: fileBlob });
        }

        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3 mb-3';
            col.dataset.id = id;
            col.innerHTML = `
                <div class="position-relative preview-item rounded border overflow-hidden bg-white p-1 shadow-sm" data-id="${id}">
                    <img src="${e.target.result}" class="img-fluid w-100 d-block" alt="">
                    <div class="preview-overlay d-flex align-items-center justify-content-center">
                        <button type="button" class="btn btn-danger btn-sm remove-preview" data-id="${id}">
                            <i class="fa-solid fa-trash me-1"></i> Rimuovi
                        </button>
                    </div>
                </div>
                <div class="error-msg text-danger text-center small fw-bold px-1 pt-1" id="error-img-${id}">&nbsp;</div>
            `;

            this.smoothAdd(this.getRow(), col);
        };
        reader.readAsDataURL(fileBlob);
    }

    removePreview(id) {
        const previewContainer = document.querySelector(this.previewSelector);
        if (!previewContainer) return;

        const el = previewContainer.querySelector(`[data-id="${id}"]`);
        if (el) {
            const col = el.closest('.col-6, .col-md-4, .col-lg-3');
            if (col) this.smoothRemove(col);
        }
    }

    getRow() {
        const previewContainer = document.querySelector(this.previewSelector);
        if (!previewContainer) return null;

        let row = previewContainer.querySelector('.row');
        if (!row) {
            row = document.createElement('div');
            row.className = 'row';
            previewContainer.innerHTML = '';
            previewContainer.appendChild(row);
        }
        return row;
    }

    /* Svuota lo stato: richiamato direttamente da AddManager ed EditManager */
    reset() {
        this.files = [];
        const previewContainer = document.querySelector(this.previewSelector);
        if (previewContainer) {
            previewContainer.innerHTML = ''; /* Collassa istantaneamente lo spazio a zero */
        }
    }

    /* Invio asincrono dei file accumulati al Controller */
    async saveImages(formElement) {
        try {

            /* Costruiamo il FormData qui dentro */
            const formData = new FormData(formElement);

            /* Iniettiamo i file con i loro ID reali prima dell'invio */
            this.files.forEach(({ id, file }) => {
                formData.append(`images[${id}]`, file);
            });
                    
            const response = await apiFetch(urlbase + 'backend/uploadPreviewImg/saveImages', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            /* Controllo sessione scaduta */
            if (data.result === 'no_current_user_logged') {
                window.location.href = urlbase + 'backend/auth/login';
                return;
            }

            /* Gestione Errori di Validazione */
            if (data.imagesErrors) {

                if (typeof handleValidationImages === 'function') {
                    handleValidationImages(data.imagesErrors);
                }
                
                if (data.message && typeof showAlert === 'function') {
                    showAlert('danger', data.message);
                }
                
                return;
            }

            /* Gestione errore di validazione server-side */
            if (data.result === false) {
                showAlert('danger', data.message);
                return;
            }

            /* Caso di successo totale */
            if (data.result === true) {
                showAlert('success', data.message);
                
                /* Svuota l'anteprima locale e azzera l'array dei file */
                this.reset();

                /* Aggiornamento automatico della galleria esistente (Decoupling) */
                if (this.galleryOneImgManager) {
                    /* Recuperiamo il form di reload nascosto della galleria (#getImages) */
                    const galleryForm = document.querySelector('#getImages');
                    if (galleryForm) {
                        const reloadData = new FormData(galleryForm);
                        await this.galleryOneImgManager.refresh(reloadData);
                    }
                }
            }
        } catch (err) {
            console.error('Errore durante il salvataggio delle immagini:', err);
        }
    }
}