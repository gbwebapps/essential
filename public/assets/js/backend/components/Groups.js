/* Import delle utility risalendo di un livello */
import { urlbase, apiFetch, toggleLoader, showToast, askConfirm, smoothReplace, handleValidationErrors } from '../backend.js';

export class GroupManager {
    constructor(config = {}) {
        this.config = Object.assign({
            getGroups: '',
            getGroup: '',
            urlGetExceptions: ''
        }, config);

        /* Variabili di stato per evitare ricaricamenti inutili */
        this.isListLoaded = false;
        this.isExceptionsLoaded = false; 
        this.isAddLoaded = false;
        
        this.init();
    }

    init() {
        this.bindDynamicEvents();
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

                const response = await apiFetch(this.config.getGroup, {
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

        /* 1. Intercettiamo il submit del form di aggiunta nuovo gruppo */
        document.addEventListener('submit', e => {
            if (e.target && e.target.id === 'new-group') {
                this.handleAddGroup(e);
            }
        });

        /* 2. Intercettiamo il submit dei form di edit generati dinamicamente */
        document.addEventListener('submit', e => {
            const editForm = e.target.closest('.edit-group-form');
            if (editForm) {
                this.handleEditGroup(e);
            }
        });

        /* 4. Intercettiamo il click sul pulsante elimina del gruppo */
        document.addEventListener('click', e => {
            const deleteBtn = e.target.closest('.btn-delete-group');
            if (deleteBtn) {
                e.preventDefault();
                this.handleDeleteGroup(deleteBtn);
            }
        });

        /* 5. Intercettiamo il reset del form di aggiunta per pulire gli errori visivi */
        document.addEventListener('reset', e => {
            if (e.target && e.target.id === 'new-group') {
                document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');
            }
        });
    }

    /* Gestisce l'invio asincrono del form per la creazione di un nuovo gruppo */
    async handleAddGroup(e) {
        e.preventDefault();

        /* Blocco esecuzione se c'è già una chiamata in corso */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        /* Pulizia immediata di tutti gli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(urlbase + 'backend/groups/add', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione Errori di Validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 3. Errore generico gestito dal backend */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }
                
                formEl.reset();

                /* Forza il ricaricamento asincrono della lista se la sezione è aperta, mostrando il record reale */
                this.isListLoaded = false;
                const mainCollapseList = document.getElementById('main_collapse_list');
                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }
        } catch (error) {
            console.error("Errore durante l'invio del form nuovo gruppo:", error);
        } finally {
            /* Rilascia sempre il blocco anti-doppio click al termine del ciclo */
            this.isSubmitting = false;
        }
    }

    /* Gestisce l'invio asincrono del form per la modifica di un gruppo esistente */
    async handleEditGroup(e) {
        e.preventDefault();

        /* Blocco esecuzione se c'è già una chiamata in corso */
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        const formEl = e.target;
        const formData = new FormData(formEl);

        /* Pulizia immediata di tutti gli errori visivi prima dell'invio */
        document.querySelectorAll('[class^="error_"]').forEach(el => el.innerHTML = '\u00A0');

        try {
            const response = await apiFetch(urlbase + 'backend/groups/edit', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione Errori di Validazione */
            if (data.errors) {
                if (typeof handleValidationErrors === 'function') {
                    handleValidationErrors(data.errors);
                }
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 3. Errore generico gestito dal backend */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                /* Aggiorna dinamicamente il titolo dell'accordion con il nuovo nome inserito */
                const groupId = formEl.dataset.id;
                const toggleBtnSpan = document.querySelector(`[data-bs-target="#collapse_group_${groupId}"] span`);
                const nameInput = formEl.querySelector('input[name="name"]');
                if (toggleBtnSpan && nameInput) {
                    toggleBtnSpan.textContent = nameInput.value;
                }
            }
        } catch (error) {
            console.error("Errore durante l'invio del form modifica gruppo:", error);
        } finally {
            /* Rilascia sempre il blocco anti-doppio click al termine del ciclo */
            this.isSubmitting = false;
        }
    }

    /* Gestisce l'eliminazione asincrona di un gruppo previa conferma dell'utente */
    async handleDeleteGroup(btnEl) {
        /* Blocco esecuzione se c'è già una chiamata in corso */
        if (this.isSubmitting) return;

        const message = btnEl.dataset.message || "Sei sicuro di voler eliminare questo gruppo?";
        const ok = await askConfirm(message);
        if ( ! ok) return;

        this.isSubmitting = true;
        const groupId = btnEl.dataset.id;

        try {
            const formData = new FormData();
            formData.append('id', groupId);

            const response = await apiFetch(urlbase + 'backend/groups/del', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            /* 1. Controllo per utente non loggato (filtro MasterFilter) */
            if (data.result === 'no_current_user_logged') {
                window.location.href = `${urlbase}backend/auth`;
                return;
            }

            /* 2. Gestione Errori di Validazione */
            if (data.errors) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 3. Errore generico gestito dal backend */
            if (data.result === false) {
                if (data.message && typeof showToast === 'function') {
                    showToast('danger', data.message);
                }
                return;
            }

            /* 4. Caso successo */
            if (data.result === true) {
                if (data.message && typeof showToast === 'function') {
                    showToast('success', data.message);
                }

                /* Forza il ricaricamento asincrono dell'intero blocco per aggiornare il DOM */
                this.isListLoaded = false;
                const mainCollapseList = document.getElementById('main_collapse_list');
                if (mainCollapseList && mainCollapseList.classList.contains('show')) {
                    this.loadGroupsList();
                }
            }
        } catch (error) {
            console.error("Errore durante l'eliminazione del gruppo:", error);
        } finally {
            /* Rilascia sempre il blocco anti-doppio click al termine del ciclo */
            this.isSubmitting = false;
        }
    }

    /* Carica lo scheletro iniziale del pannello di aggiunta nuovo gruppo (Primo livello) */
    async loadAddGroupPanel() {
        if (this.isAddLoaded) return;

        const container = document.getElementById('add-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(urlbase + 'backend/groups/openAdd', { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isAddLoaded = true;
            }
        } catch (error) {
            console.error("Errore durante il caricamento del form aggiunta gruppo:", error);
        }
    }

    /* Svuota il container alla chiusura del pannello per distruggere il form dal DOM */
    resetAddContainer() {
        const container = document.getElementById('add-groups-container');
        if (container) {
            container.innerHTML = '';
            this.isAddLoaded = false; 
        }
    }

    /* Carica l'elenco dei gruppi (Primo livello AJAX) */
    async loadGroupsList() {
        if (this.isListLoaded) return;

        const container = document.getElementById('showAll-groups-container');
        if ( ! container) return;

        try {
            const response = await apiFetch(this.config.getGroups, { method: 'POST' });
            const data = await response.json();

            if (data.result === true) {
                smoothReplace(container, data.output);
                this.isListLoaded = true;
            }
        } catch (error) {
            console.error("Errore caricamento lista gruppi:", error);
        }
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