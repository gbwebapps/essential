
export class ExportPdfManager {

    constructor(config = {}) {

        if (ExportPdfManager.instance) {
            return ExportPdfManager.instance;
        }
        ExportPdfManager.instance = this;

        /* Configurazione dinamica */
        this.config = Object.assign({
            buttonId: 'show-export-button',
            modalId: 'exportPdfModal',
            formId: 'exportPdfForm',
            wrapperId: 'show-wrapper',
            qualitySliderId: 'pdf_quality',
            qualityLabelId: 'pdf_quality_label'
        }, config);

        this.modalInstance = null;
        this.modalElement = null;

        this.isGenerating = false;
        this.eventsBound = false;
        this.destroyed = false;

        this.eventsController = new AbortController();

        this.init();
    }

    init() {
        if (this.destroyed || this.eventsBound) return;

        this.eventsBound = true;

        /* Apertura della modale */
        document.addEventListener('click', e => {
            const btn = e.target.closest(`#${this.config.buttonId}`);
            if (!btn) return;

            e.preventDefault();

            if (this.isGenerating) return;

            this.showModal();
        }, {
            signal: this.eventsController.signal
        });

        /* Submit del form PDF */
        document.addEventListener('submit', e => {
            if (e.target.id !== this.config.formId) return;

            e.preventDefault();

            if (this.isGenerating) return;

            this.generatePdf();
        }, {
            signal: this.eventsController.signal
        });

        /* Aggiornamento dell'etichetta qualità */
        document.addEventListener('input', e => {
            if (e.target.id !== this.config.qualitySliderId) return;

            const label = document.getElementById(this.config.qualityLabelId);

            if (label) {
                label.textContent = e.target.value + '%';
            }
        }, {
            signal: this.eventsController.signal
        });

        /* Gestione del backdrop personalizzato */
        document.addEventListener('show.bs.modal', e => {
            if (e.target.id !== this.config.modalId) return;

            const backdropEl = document.getElementById('customBackdrop');

            if (backdropEl) {
                backdropEl.classList.add('active');
            }
        }, {
            signal: this.eventsController.signal
        });

        document.addEventListener('hidden.bs.modal', e => {
            if (e.target.id !== this.config.modalId) return;

            const backdropEl = document.getElementById('customBackdrop');

            if (backdropEl) {
                backdropEl.classList.remove('active');
            }
        }, {
            signal: this.eventsController.signal
        });
    }

    /* Recupera o crea l'istanza Bootstrap della modale corrente */
    getModalInstance() {
        if (this.destroyed) return null;

        const modalEl = document.getElementById(this.config.modalId);
        if (!modalEl) return null;

        /* La modale potrebbe essere stata sostituita dinamicamente */
        if (this.modalElement && this.modalElement !== modalEl) {
            if (this.modalInstance) {
                this.modalInstance.dispose();
            }

            this.modalInstance = null;
        }

        this.modalElement = modalEl;

        if (!this.modalInstance) {
            this.modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: false,
                keyboard: false
            });
        }

        return this.modalInstance;
    }

    showModal() {
        if (this.destroyed || this.isGenerating) return;

        const form = document.getElementById(this.config.formId);
        const modalInstance = this.getModalInstance();

        if (!modalInstance) return;

        /* Ripristina le opzioni iniziali del form */
        if (form) {
            form.reset();

            const qualitySlider = document.getElementById(this.config.qualitySliderId);
            const qualityLabel = document.getElementById(this.config.qualityLabelId);

            if (qualitySlider && qualityLabel) {
                qualityLabel.textContent = qualitySlider.value + '%';
            }
        }

        modalInstance.show();
    }

    /* Genera e scarica il PDF */
    async generatePdf() {
        if (this.destroyed || this.isGenerating) return;

        const wrapper = document.getElementById(this.config.wrapperId);
        const form = document.getElementById(this.config.formId);
        const qualitySlider = document.getElementById(this.config.qualitySliderId);

        if (!wrapper || !form || !qualitySlider) return;

        const orientationInput = form.querySelector('input[name="pdf_orientation"]:checked');
        const formatInput = form.querySelector('#pdf_format');

        if (!orientationInput || !formatInput) return;

        const parseMargin = id => {
            const input = form.querySelector(id);
            const value = parseInt(input?.value, 10);

            return Number.isNaN(value) ? 10 : value;
        };

        const margin = [
            parseMargin('#pdf_margin_top'),
            parseMargin('#pdf_margin_right'),
            parseMargin('#pdf_margin_bottom'),
            parseMargin('#pdf_margin_left')
        ];

        const qualityValue = parseInt(qualitySlider.value, 10) / 100;

        const options = {
            margin: margin,
            filename: 'export_document.pdf',
            image: {
                type: 'jpeg',
                quality: qualityValue
            },
            html2canvas: {
                scale: 2
            },
            jsPDF: {
                unit: 'mm',
                format: formatInput.value,
                orientation: orientationInput.value
            }
        };

        this.isGenerating = true;

        try {
            await html2pdf()
                .set(options)
                .from(wrapper)
                .save();

            /* Chiude la modale soltanto dopo il download */
            if (!this.destroyed) {
                const modalInstance = this.getModalInstance();

                if (modalInstance) {
                    modalInstance.hide();
                }
            }

        } catch (error) {
            console.error('Errore durante la generazione del PDF:', error);

            if (!this.destroyed && typeof showAlert === 'function') {
                showAlert('danger', 'Errore durante la generazione del PDF.');
            }

        } finally {
            this.isGenerating = false;
        }
    }

    /* Libera listener, riferimenti DOM e istanza Bootstrap */
    destroy() {
        if (this.destroyed) return;

        this.destroyed = true;

        this.eventsController.abort();
        this.eventsBound = false;

        if (this.modalInstance) {
            this.modalInstance.dispose();
            this.modalInstance = null;
        }

        this.modalElement = null;

        const backdropEl = document.getElementById('customBackdrop');

        if (backdropEl) {
            backdropEl.classList.remove('active');
        }

        if (ExportPdfManager.instance === this) {
            ExportPdfManager.instance = null;
        }
    }
}
