/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showAlert, askConfirm, smoothReplace } from '../backend.js';

export class GalleryOneImgManager {
    constructor() {
        this.containerId = '#imagesData';
        /* Rimosso this.container per evitare nodi orfani (fantasma) */
        
        this.onSubmit = this.onSubmit.bind(this);
        this.onClick = this.onClick.bind(this);
        
        this.eventsBound = false;
        this.bindEvents();
    }

    bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        document.addEventListener('submit', this.onSubmit);
        document.addEventListener('click', this.onClick);
    }

    async onClick(e) {
        const btn = e.target.closest('.galleryOneImgAction');
        if (!btn) return;

        e.preventDefault();

        const wrapper = btn.closest('.preview-item');
        if ( ! wrapper) return;

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
            
            /* Peschiamo il nodo VIVO dal DOM un attimo prima di agire */
            const currentContainer = document.querySelector(this.containerId);
            if (currentContainer) {
                smoothReplace(currentContainer, data.output);
            }
        }
    }

    destroy() {
        document.removeEventListener('submit', this.onSubmit);
        document.removeEventListener('click', this.onClick);
    }
}