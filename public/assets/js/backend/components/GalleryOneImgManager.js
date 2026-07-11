/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace } from '../backend.js';

export class GalleryOneImgManager {
    constructor() {
        this.containerId = '#imagesData';
        this.container = document.querySelector(this.containerId);
        this.onSubmit = this.onSubmit.bind(this);
        this.onClick = this.onClick.bind(this);
        
        /* Variabile di stato per evitare listener multipli sul document */
        this.eventsBound = false;
        
        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        /* Listener globale per il submit del form di reload */
        document.addEventListener('submit', this.onSubmit);
        
        /* SOLUZIONE 2: Spostiamo il listener del click sul document globale */
        document.addEventListener('click', this.onClick);
    }

    async onClick(e) {
        const btn = e.target.closest('.galleryOneImgAction');
        if (!btn) return;

        e.preventDefault();

        const wrapper = btn.closest('.preview-item');
        if (!wrapper) return;

        const formData = new FormData();
        ['id', 'uuid', 'entity', 'context', 'filename'].forEach(key => {
            formData.append(key, wrapper.dataset[key]);
        });

        const ok = await askConfirm(btn.dataset.message);
        if (ok) {
            this.handleAction(btn.dataset.action, formData);
        }
    }

    onSubmit(e) {
        if (e.target.matches('#getImages')) {
            e.preventDefault();
            const formData = new FormData(e.target);
            this.refresh(formData);
        }
    }

    async refresh(formData) {
        try {
            const response = await apiFetch(urlbase + 'backend/galleryOneImg/showGallery', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            this.handleResponse(data, false);
        } catch (err) {
            console.error(err);
        }
    }

    async handleAction(action, formData) {
        try {
            const response = await apiFetch(urlbase + 'backend/galleryOneImg/' + action, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            this.handleResponse(data, true);
        } catch (err) {
            console.error(err);
        }
    }

    handleResponse(data, showSuccess) {
        if (data.result === 'no_current_user_logged') {
            window.location.href = urlbase + 'backend/auth/login';
            return;
        }

        if (data.result === false) {
            showAlert('danger', data.message);
            return;
        }

        if (data.result === true) {
            if (showSuccess) {
                showAlert('success', data.message);
            }
            
            /* Aggiornato: esegue solo il rimpiazzo HTML, i listener sul document non muoiono */
            if (this.container) {
                smoothReplace(this.container, data.output);
                
                /* Mantiene aggiornato il riferimento al nuovo elemento nel DOM */
                this.container = document.querySelector(this.containerId);
            }
        }
    }

    destroy() {
        document.removeEventListener('submit', this.onSubmit);
        document.removeEventListener('click', this.onClick);
    }
}