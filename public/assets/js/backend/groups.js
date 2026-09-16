/* Import delle costanti e utility da backend.js */
import { action } from './backend.js';

/* Import della classe logica */
import { GroupsManager } from './modules/Groups.js';

const actions = {
    index: function() {

        /* Istanziazione pulita senza parametri passed dall'esterno */
        const groupManager = new GroupsManager();

        /* --- 1. GESTIONE MANUALE E FLUIDA: Aggiungi Gruppo --- */
        document.addEventListener('click', async (e) => {
            const triggerAddBtn = e.target.closest('.btn-trigger-add-group');
            if (!triggerAddBtn) return;
            
            e.preventDefault();

            const mainCollapseAdd = document.getElementById('main_collapse_add');
            if (!mainCollapseAdd) return;

            const bsCollapseAdd = bootstrap.Collapse.getOrCreateInstance(mainCollapseAdd, { toggle: false });

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

        document.addEventListener('hidden.bs.collapse', (e) => {
            if (e.target && e.target.id === 'main_collapse_add') {
                groupManager.resetAddContainer();
            }
        });


        /* --- 2. GESTIONE MANUALE E FLUIDA: Lista Gruppi --- */
        
        /* FIX: Rimuoviamo il data-bs-toggle a monte per evitare il conflitto (lo scattino) con Bootstrap */
        document.querySelectorAll('[data-bs-target="#main_collapse_list"]').forEach(btn => {
            btn.removeAttribute('data-bs-toggle');
        });

        document.addEventListener('click', async (e) => {
            const triggerListBtn = e.target.closest('[data-bs-target="#main_collapse_list"]');
            if (!triggerListBtn) return;
            
            e.preventDefault();
            
            const mainCollapseList = document.getElementById('main_collapse_list');
            if (!mainCollapseList) return;

            const bsCollapseList = bootstrap.Collapse.getOrCreateInstance(mainCollapseList, { toggle: false });

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

        document.addEventListener('hidden.bs.collapse', (e) => {
            if (e.target && e.target.id === 'main_collapse_list') {
                groupManager.resetListContainer();
            }
        });


        /* --- 3. GESTIONE MANUALE E FLUIDA: Sotto-gruppi (Elementi del foreach) --- */
        /* Questo blocco era già delegato correttamente nel tuo codice, l'ho lasciato identico */
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
        document.addEventListener('click', async (e) => {
            const triggerExceptionsBtn = e.target.closest('.btn-trigger-exceptions-group');
            if (!triggerExceptionsBtn) return;
            
            e.preventDefault();

            const mainCollapseExceptions = document.getElementById('main_collapse_exceptions');
            if (!mainCollapseExceptions) return;

            const bsCollapseExceptions = bootstrap.Collapse.getOrCreateInstance(mainCollapseExceptions, { toggle: false });

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

        document.addEventListener('hidden.bs.collapse', (e) => {
            if (e.target && e.target.id === 'main_collapse_exceptions') {
                groupManager.resetExceptionsContainer();
            }
        });
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