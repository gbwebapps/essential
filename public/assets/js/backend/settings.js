/* Import delle costanti e utility da backend.js */
import { action } from './backend.js';

/* Import della classe logica polivalente */
import { SettingsManager } from './modules/Settings.js';

const actions = {
    index: function() {
        
        /* Istanziamo il manager UNA SOLA VOLTA globalmente */
        const manager = new SettingsManager();

        /* 1. DELEGAZIONE EVENTO CLICK: Alte performance, un solo listener in memoria */
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.accordion-header button[data-env]');
            if (!btn) return;

            e.preventDefault();

            const env = btn.dataset.env;
            const mainCollapse = document.getElementById(`main_collapse_${env}`);
            if (!mainCollapse) return;

            /* Gestione nativa Bootstrap */
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(mainCollapse, { toggle: false });
            const isOpen = mainCollapse.classList.contains('show') || mainCollapse.classList.contains('collapsing');

            if (isOpen) {
                bsCollapse.hide();
                return;
            }

            const container = document.getElementById(`${env}-settings-container`);
            
            /* Se il contenitore ha già i dati, lo apre e basta */
            if (container && container.innerHTML.trim() !== '') {
                bsCollapse.show();
                return;
            }

            /* Se è vuoto, scarica il contenuto */
            const success = await manager.loadPanel(`${env}-settings-container`, env);
            if (success === false) return;

            btn.disabled = false;
            btn.classList.remove('disabled');
            bsCollapse.show();
        });

        /* Ripristina la distruzione totale del contenitore alla chiusura */
        document.addEventListener('hidden.bs.collapse', (e) => {
            if (e.target && e.target.id.startsWith('main_collapse_')) {
                const env = e.target.id.replace('main_collapse_', '');
                manager.resetContainer(`${env}-settings-container`);
            }
        });
    }
};

if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita per i settings:", action);
}