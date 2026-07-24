/* Import delle costanti e utility da backend.js */
import { action } from './backend.js';

/* Import della classe logica */
import { GroupsManager } from './components/Groups.js';

const actions = {
    index: function() {

        /* Istanziazione pulita senza parametri passed dall'esterno */
        const groupManager = new GroupsManager();

        /* --- 1. GESTIONE MANUALE E FLUIDA: Aggiungi Gruppo --- */
        const triggerAddBtn = document.querySelector('.btn-trigger-add-group');
        const mainCollapseAdd = document.getElementById('main_collapse_add');

        if (triggerAddBtn && mainCollapseAdd) {
            const bsCollapseAdd = new bootstrap.Collapse(mainCollapseAdd, { toggle: false });

            triggerAddBtn.addEventListener('click', async (e) => {
                e.preventDefault();

                if (mainCollapseAdd.classList.contains('show') || mainCollapseAdd.classList.contains('collapsing')) {
                    bsCollapseAdd.hide();
                    return;
                }

                const container = document.getElementById('add-groups-container');
                if (container && container.innerHTML.trim() !== '') {
                    bsCollapseAdd.show();
                    return;
                }

                /* Interrompe l'apertura del collapsable se il metodo ritorna false (utente non loggato) */
                const success = await groupManager.loadAddGroupPanel();
                if (success === false) return;
                
                triggerAddBtn.disabled = false;
                triggerAddBtn.classList.remove('disabled');
                
                bsCollapseAdd.show();
            });

            mainCollapseAdd.addEventListener('hidden.bs.collapse', (e) => {
                if (e.target === mainCollapseAdd) {
                    groupManager.resetAddContainer();
                }
            });
        }

        /* --- 2. GESTIONE MANUALE E FLUIDA: Lista Gruppi --- */
        const triggerListBtn = document.querySelector('[data-bs-target="#main_collapse_list"]');
        const mainCollapseList = document.getElementById('main_collapse_list');

        if (triggerListBtn && mainCollapseList) {
            triggerListBtn.removeAttribute('data-bs-toggle');
            
            const bsCollapseList = new bootstrap.Collapse(mainCollapseList, { toggle: false });

            triggerListBtn.addEventListener('click', async (e) => {
                e.preventDefault();

                if (mainCollapseList.classList.contains('show') || mainCollapseList.classList.contains('collapsing')) {
                    bsCollapseList.hide();
                    return;
                }

                const container = document.getElementById('showAll-groups-container');
                if (container && container.innerHTML.trim() !== '') {
                    bsCollapseList.show();
                    return;
                }

                /* Interrompe l'apertura del collapsable se il metodo ritorna false (utente non loggato) */
                const success = await groupManager.loadGroupsList();
                if (success === false) return;
                
                triggerListBtn.disabled = false;
                triggerListBtn.classList.remove('disabled');
                
                bsCollapseList.show();
            });

            mainCollapseList.addEventListener('hidden.bs.collapse', (e) => {
                if (e.target === mainCollapseList) {
                    groupManager.resetListContainer();
                }
            });
        }

        /* --- 3. GESTIONE MANUALE E FLUIDA: Sotto-gruppi (Elementi del foreach) --- */
        document.addEventListener('click', async (e) => {
            const subTriggerBtn = e.target.closest('.group-toggle-btn');
            if ( ! subTriggerBtn) return;

            const targetId = subTriggerBtn.getAttribute('data-bs-target');
            const subCollapseEl = document.querySelector(targetId);
            if ( ! subCollapseEl || ! subCollapseEl.closest('#groupsAccordion')) return;

            /* Gestione del toggle manuale */
            let bsSubCollapse = bootstrap.Collapse.getInstance(subCollapseEl);
            if ( ! bsSubCollapse) {
                bsSubCollapse = new bootstrap.Collapse(subCollapseEl, { toggle: false });
            }

            if (subCollapseEl.classList.contains('show') || subCollapseEl.classList.contains('collapsing')) {
                bsSubCollapse.hide();
                return;
            }

            const bodyContainer = subCollapseEl.querySelector('.template-container');
            if (bodyContainer && bodyContainer.innerHTML.trim() !== '') {
                bsSubCollapse.show();
                return;
            }

            /* Chiamata asincrona con controllo sbarramento (interrompe se ritorna false) */
            const success = await groupManager.loadSingleGroupData(subTriggerBtn.dataset.id, bodyContainer);
            if (success === false) return;

            /* Ripristino dello sblocco del loader ed esecuzione animazione */
            subTriggerBtn.disabled = false;
            subTriggerBtn.classList.remove('disabled');
            
            bsSubCollapse.show();
        });

        /* --- 4. GESTIONE MANUALE E FLUIDA: Apri pannello eccezioni --- */
        const triggerExceptionsBtn = document.querySelector('.btn-trigger-exceptions-group');
        const mainCollapseExceptions = document.getElementById('main_collapse_exceptions');

        if (triggerExceptionsBtn && mainCollapseExceptions) {
            const bsCollapseExceptions = new bootstrap.Collapse(mainCollapseExceptions, { toggle: false });

            triggerExceptionsBtn.addEventListener('click', async (e) => {
                e.preventDefault();

                if (mainCollapseExceptions.classList.contains('show') || mainCollapseExceptions.classList.contains('collapsing')) {
                    bsCollapseExceptions.hide();
                    return;
                }

                const container = document.getElementById('exceptions-groups-container');
                if (container && container.innerHTML.trim() !== '') {
                    bsCollapseExceptions.show();
                    return;
                }

                /* Interrompe l'apertura del collapsable se il metodo ritorna false (utente non loggato) */
                const success = await groupManager.loadExceptionsPanel();
                if (success === false) return;
                
                triggerExceptionsBtn.disabled = false;
                triggerExceptionsBtn.classList.remove('disabled');
                
                bsCollapseExceptions.show();
            });

            mainCollapseExceptions.addEventListener('hidden.bs.collapse', (e) => {
                if (e.target === mainCollapseExceptions) {
                    groupManager.resetExceptionsContainer();
                }
            });
        }
    }
};

/* Listener per il link select all nei form add ed edit */
document.addEventListener('click', function(e) {
    if (e.target.matches('.select-all')) {
        e.preventDefault();
        const controller = e.target.dataset.controller;
        const checkboxes = document.querySelectorAll(`input[type="checkbox"].${controller}`);
        const anyChecked = Array.from(checkboxes).some(el => el.checked);
        const newState = !anyChecked;
        checkboxes.forEach(el => el.checked = newState);
    }
});

if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}