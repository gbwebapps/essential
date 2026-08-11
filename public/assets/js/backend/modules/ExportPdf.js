export class ExportPdfManager {

    constructor(config = {}) {
        
        /* 1. Ripristinata la configurazione dinamica */
        this.config = Object.assign({
            buttonId: 'show-export-button',
            modalId: 'exportPdfModal',
            formId: 'exportPdfForm',
            wrapperId: 'show-wrapper',
            qualitySliderId: 'pdf_quality',
            qualityLabelId: 'pdf_quality_label'
        }, config);

        /* Cache degli elementi DOM usati frequentemente */
        this.elements = {};
        this.modalInstance = null;

        this.init();
    }

    init() {
        this.elements.form = document.getElementById(this.config.formId);
        this.elements.modal = document.getElementById(this.config.modalId);
        this.elements.qualitySlider = document.getElementById(this.config.qualitySliderId);
        this.elements.qualityLabel = document.getElementById(this.config.qualityLabelId);

        /* Listener per l'apertura del modale */
        document.addEventListener('click', (e) => {
            const btn = e.target.closest(`#${this.config.buttonId}`);
            if ( ! btn) return;

            e.preventDefault();
            this.showModal();
        });

        /* Listener per il form */
        if (this.elements.form) {
            this.elements.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.generatePdf();
            });
        }

        /* Listener per lo slider */
        if (this.elements.qualitySlider && this.elements.qualityLabel) {
            this.elements.qualitySlider.addEventListener('input', (e) => {
                this.elements.qualityLabel.textContent = e.target.value + '%';
            });
        }
    }

    showModal() {
        if ( ! this.elements.modal) return;

        /* Reset dati */
        if (this.elements.form) {
            this.elements.form.reset();
            if (this.elements.qualityLabel) {
                this.elements.qualityLabel.textContent = '80%';
            }
        }

        /* Inizializza l'istanza solo se non esiste */
        if ( ! this.modalInstance) {
            this.modalInstance = new bootstrap.Modal(this.elements.modal, {
                backdrop: false,
                keyboard: false
            });

            /* Intercetta il backdrop statico dal DOM */
            const backdropEl = document.getElementById('customBackdrop');
            
            if (backdropEl) {
                this.elements.modal.addEventListener('show.bs.modal', () => backdropEl.classList.add('active'));
                this.elements.modal.addEventListener('hidden.bs.modal', () => backdropEl.classList.remove('active'));
            }
        }

        this.modalInstance.show();
    }

    generatePdf() {
        const wrapper = document.getElementById(this.config.wrapperId);
        if ( ! wrapper || ! this.elements.form) return;

        const form = this.elements.form;
        const orientation = form.querySelector('input[name="pdf_orientation"]:checked').value;
        const format = form.querySelector('#pdf_format').value;
        
        const parseMargin = (id) => {
            const val = parseInt(form.querySelector(id).value, 10);
            return isNaN(val) ? 10 : val;
        };

        const margin = [
            parseMargin('#pdf_margin_top'),
            parseMargin('#pdf_margin_right'),
            parseMargin('#pdf_margin_bottom'),
            parseMargin('#pdf_margin_left')
        ];
        
        const qualityValue = parseInt(this.elements.qualitySlider.value, 10) / 100;

        const options = {
            margin: margin,
            filename: 'export_document.pdf',
            image: { type: 'jpeg', quality: qualityValue },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: format, orientation: orientation }
        };

        html2pdf().set(options).from(wrapper).save().then(() => {
            if (this.modalInstance) {
                this.modalInstance.hide();
            }
        });
    }
}