/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class GroupManager {
    constructor(config = {}) {
        this.config = Object.assign({
            urlShowAll: '',
            urlGetEditForm: '',
            urlGetExceptions: ''
        }, config);

        /* Variabili di stato per evitare ricaricamenti inutili */
        this.isListLoaded = false;
        this.isExceptionsLoaded = false;
        
        this.init();
    }

    init() {
        this.bindDynamicEvents();
    }

    /* Carica l'elenco dei gruppi (Primo livello AJAX) */
    async loadGroupsList() {
        if (this.isListLoaded) return;

        const container = document.getElementById('showAll-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(this.config.urlShowAll, { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isListLoaded = true;
            }
        } catch (error) {
            console.error("Errore caricamento lista gruppi:", error);
        }
    }

    /* Gestione dei listener sugli elementi generati dinamicamente via AJAX */
    bindDynamicEvents() {
        /* 1. Intercettiamo l'apertura dei sotto-accordion dei singoli gruppi */
        document.addEventListener('show.bs.collapse', async e => {
            /* Se l'evento non arriva dal sotto-accordion dei gruppi, ignoralo */
            const subCollapse = e.target.closest('#groupsAccordion .accordion-collapse');
            if ( ! subCollapse) return;

            /* Blocchiamo il bubbling per evitare che risalga al macro-accordion */
            e.stopPropagation();

            const bodyContainer = subCollapse.querySelector('.template-container');
            /* Se ha già dell'HTML dentro, non rifacciamo la chiamata */
            if ( ! bodyContainer || bodyContainer.innerHTML.trim() !== '') return;

            /* Recuperiamo il bottone corretto associato a QUESTO pannello */
            const toggleBtn = document.querySelector(`[data-bs-target="#${subCollapse.id}"]`);
            if ( ! toggleBtn) return;

            const groupId = toggleBtn.dataset.id;

            try {
                const formData = new FormData();
                formData.append('id', groupId);

                const response = await apiFetch(this.config.urlGetEditForm, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.result === true) {
                    smoothReplace(bodyContainer, data.output);
                }
            } catch (error) {
                console.error("Errore caricamento form edit gruppo:", error);
            }
        });

        /* 2. Intercettiamo la CHIUSURA del singolo gruppo per svuotarlo */
        document.addEventListener('hidden.bs.collapse', e => {
            const subCollapse = e.target.closest('#groupsAccordion .accordion-collapse');
            if ( ! subCollapse) return;

            /* Blocchiamo il bubbling anche in chiusura */
            e.stopPropagation();

            const bodyContainer = subCollapse.querySelector('.template-container');
            if (bodyContainer) {
                bodyContainer.innerHTML = ''; 
            }
        });
    }

    /* Metodo pubblico per resettare lo stato della lista principale quando viene chiusa */
    resetListContainer() {
        const container = document.getElementById('showAll-groups-container');
        if (container) {
            container.innerHTML = ''; 
            this.isListLoaded = false; 
        }
    }

    /* Carica lo scheletro iniziale del pannello eccezioni (Terzo livello) */
    async loadExceptionsPanel() {
        if (this.isExceptionsLoaded) return;
        
        /* Qui gestiremo la logica della select con autocompletamento */
        this.isExceptionsLoaded = true;
    }
}