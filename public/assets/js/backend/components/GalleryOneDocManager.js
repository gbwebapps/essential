/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showToast, handleValidationErrors, handleValidationImages, handleValidationDocuments } from '../backend.js';

export class GalleryOneDocManager {
    constructor(containerId = '#documents_data') {
        this.container = document.querySelector(containerId);
        this._onSubmit = this._onSubmit.bind(this); // 🔧 salva riferimento per add/remove
        this.bindEvents();
    }

    bindEvents() {
        if ( ! this.container) return;

        // 🔧 listener globale con riferimento a _onSubmit
        document.addEventListener('submit', this._onSubmit);

        this.container.addEventListener('click', async e => {
            const btn = e.target.closest('.galleryOneDocAction');
            if ( ! btn) return;

            e.preventDefault();

            const wrapper = btn.closest('.gallery-documents-container-document');
            if ( ! wrapper) return;

            const form_data = new FormData();
            form_data.append(wrapper.dataset.csrfName, wrapper.dataset.csrfValue);

            ['id', 'uuid', 'entity', 'context'].forEach(key => {
                form_data.append(key, wrapper.dataset[key]);
            });

            const ok = await askConfirm(btn.dataset.message);
            if (ok) {
                this.handleAction(btn.dataset.action, form_data);
            }
        });
    }

    // 🔧 nuovo metodo interno usato da add/removeEventListener
    _onSubmit(e) {
        if (e.target.matches('#get_documents')) {
            e.preventDefault();
            const form_data = new FormData(e.target);
            this.refresh(form_data);
        }
    }

    refresh(form_data) {
        apiFetch(urlbase + 'admin/galleryOneDoc/show', {
            method: 'POST',
            headers: { 'X-CSRF-Token': form_data.get('csrf_token') },
            body: form_data
        })
            .then(r => r.json())
            .then(data => this.handleResponse(data, false))
            .catch(err => {
                console.error(err);
                showToast('danger', 'Errore di rete');
            });
    }

    handleAction(action, form_data) {
        apiFetch(urlbase + 'admin/galleryOneDoc/' + action, {
            method: 'POST',
            headers: { 'X-CSRF-Token': form_data.get('csrf_token') },
            body: form_data
        })
            .then(r => r.json())
            .then(data => this.handleResponse(data, true))
            .catch(err => {
                console.error(err);
                showToast('danger', 'Errore di rete');
            });
    }

    handleResponse(data, showSuccess) {
        if (data.result === 'no_current_user_logged') {
            window.location.href = urlbase + 'admin/auth/login';
        } else if (data.result === '404') {
            window.location.href = urlbase + 'admin/404';
        } else if (data.result === false) {
            showToast('danger', data.message);
        } else if (data.errors) {
            showToast('danger', data.errors);
        } else if (data.result === true) {
            if (showSuccess) {
                showToast('success', data.message);
            }
            if (this.container) {
                smoothReplace(this.container, data.output);
            }
        }
    }

    destroy() {
        // 🔧 rimuove il listener globale quando distruggi l'istanza
        document.removeEventListener('submit', this._onSubmit);

        if (this.container && this.container.parentNode) {
            const clone = this.container.cloneNode(true);
            this.container.parentNode.replaceChild(clone, this.container);
            this.container = clone;
        }
    }
}
