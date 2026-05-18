/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, showToast, handleValidationErrors, handleValidationImages, handleValidationDocuments } from '../backend.js';

export class UploadPreviewImgManager {
    constructor(inputSelector, previewSelector, triggerSelector, galleryOneImgManager = null) {
        this.inputEl = document.querySelector(inputSelector);
        this.previewContainer = document.querySelector(previewSelector);
        this.triggerBtn = document.querySelector(triggerSelector);
        this.galleryOneImgManager = galleryOneImgManager;
        this.files = []; // sempre [{id, file}]

        this._onSubmit = this._onSubmit.bind(this);
        this._bindEvents();
    }

    /* animazione entrata thumbnail */
    _smoothAdd(container, element) {
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

    /* animazione uscita thumbnail */
    _smoothRemove(element) {
        if ( ! element) return;
        element.style.height = element.offsetHeight + 'px';
        element.style.overflow = 'hidden';
        element.style.transition = 'opacity 0.2s ease, transform 0.2s ease, height 0.2s ease, margin 0.2s ease, padding 0.2s ease';
        element.offsetHeight; // forza reflow
        requestAnimationFrame(() => {
            element.style.opacity = 0;
            element.style.transform = 'scale(0.95)';
            element.style.height = 0;
            element.style.margin = 0;
            element.style.padding = 0;
        });
        element.addEventListener('transitionend', () => {
            if (element.parentNode) element.parentNode.removeChild(element);
            if (this.files.length === 0 && !this.previewContainer.querySelector('.preview-item')) {
                const emptyText = this.previewContainer.dataset.emptyText;
                this.previewContainer.innerHTML = `
                    <div class="col-12 d-flex justify-content-center align-items-center placeholder-preview">
                        <i class="fa-solid fa-image"></i> ${emptyText}
                    </div>
                `;
            }
        }, { once: true });
    }

    /* bind eventi principali */
    _bindEvents() {
        if (this.triggerBtn) {
            this.triggerBtn.addEventListener('click', () => this.inputEl.click());
        }
        this.inputEl.addEventListener('change', () => {
            const newFiles = Array.from(this.inputEl.files);
            newFiles.forEach(fileBlob => {
                if (fileBlob.type.startsWith('image/')) {
                    this._addPreview(fileBlob);
                }
            });
            this.inputEl.value = '';
        });
        this.previewContainer.addEventListener('click', e => {
            if (e.target.classList.contains('remove-preview')) {
                const id = e.target.dataset.id;
                this.files = this.files.filter(f => f.id !== id);
                this._removePreview(id);
            }
        });
        document.addEventListener('submit', this._onSubmit);
    }

    /* gestione submit form */
    _onSubmit(e) {
        if (e.target.matches('#save_images')) {
            e.preventDefault();
            this.save(e.target);
        }
    }

    /* aggiunta di una anteprima */
    _addPreview(fileBlob, existingId = null) {
        const id = existingId || (Date.now().toString(36) + Math.random().toString(36).slice(2, 7));
        if (!existingId) {
            this.files.push({ id, file: fileBlob });
        }
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3 mb-3'; // responsive
            col.dataset.id = id;
            col.innerHTML = `
              <div class="position-relative preview-item" data-id="${id}">
                <img src="${e.target.result}" class="img-fluid img-thumbnail rounded border" alt="">
                <button type="button"
                        class="btn btn-danger btn-sm position-absolute top-50 start-50 translate-middle remove-preview"
                        data-id="${id}">
                  Elimina
                </button>
                <div class="error-msg text-danger small fw-bold pt-1">&nbsp;</div>
              </div>
            `;

            this._smoothAdd(this._getRow(), col);
        };
        reader.readAsDataURL(fileBlob);
    }

    /* rimozione di una anteprima */
    _removePreview(id) {
        const el = this.previewContainer.querySelector(`[data-id="${id}"]`);
        if (el) {
            const col = el.closest('.col-6, .col-md-4, .col-lg-3');
            if (col) this._smoothRemove(col);
        }
    }

    /* recupera o crea il contenitore row */
    _getRow() {
        let row = this.previewContainer.querySelector('.row');
        if (!row) {
            row = document.createElement('div');
            row.className = 'row';
            this.previewContainer.innerHTML = '';
            this.previewContainer.appendChild(row);
        }
        return row;
    }

    /* rigenera anteprime mancanti (es. refresh) */
    _refreshPreviews() {
        if (this.files.length === 0) {
            const emptyText = this.previewContainer.dataset.emptyText;
            this.previewContainer.innerHTML = `
                <div class="d-flex justify-content-center align-items-center">
                    <i class="fa-solid fa-image"></i> ${emptyText}
                </div>
            `;
            return;
        }
        this.files.forEach(item => {
            if ( ! this.previewContainer.querySelector(`[data-id="${item.id}"]`)) {
                this._addPreview(item.file, item.id);
            }
        });
    }

    /* ritorna solo i File (Blob) */
    getFiles() {
        return this.files.map(item => item.file);
    }

    /* resetta l'input e rimette il placeholder */
    reset() {
        this.files = [];
        if (this.inputEl) this.inputEl.value = '';
        this._refreshPreviews();
    }

    /* distrugge istanza e listener */
    destroy() {
        document.removeEventListener('submit', this._onSubmit);
        if (this.previewContainer && this.previewContainer.parentNode) {
            const clone = this.previewContainer.cloneNode(true);
            this.previewContainer.parentNode.replaceChild(clone, this.previewContainer);
            this.previewContainer = clone;
        }
        if (this.inputEl) this.inputEl.value = '';
        this.files = [];
    }

    /* salva le immagini via AJAX */
    save(formEl) {
        const form_data = new FormData(formEl);
        const files = this.getFiles();
        if (files.length === 0) {
            const msg = this.previewContainer.dataset.requiredText;
            showToast('danger', msg);
            return;
        }
        files.forEach(file => {
            form_data.append('images[]', file);
        });
        apiFetch(urlbase + 'admin/uploadPreviewImg/saveImages', {
            method: 'POST',
            headers: { 'X-CSRF-Token': form_data.get('csrf_token') },
            body: form_data
        })
            .then(response => response.json())
            .then(data => {
                if (data.result === 'no_current_user_logged') {
                    window.location.href = urlbase + 'admin/auth/login';
                } else if (data.result === '404') {
                    window.location.href = urlbase + 'admin/404';
                } else {
                    if (data.errors) {
                        handleValidationErrors(data.errors);
                        handleValidationImages(data.errors);
                        showToast('danger', data.message);
                    } else if (data.result === false) {
                        showToast('danger', data.message);
                    } else if (data.result === true) {
                        if (this.galleryOneImgManager) {
                            this.galleryOneImgManager.refresh(form_data);
                        }
                        this.reset();
                        showToast('success', data.message);
                    }
                }
            })
            .catch(error => console.error(error));
    }
}
