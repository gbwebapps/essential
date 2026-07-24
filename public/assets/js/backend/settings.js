/* Import delle costanti e utility da backend.js */
import { action } from './backend.js';

/* Import della classe logica polivalente */
import { SettingsManager } from './components/Settings.js';

const actions = {
    index: function() {
        
        /* Istanziamo il manager UNA SOLA VOLTA globalmente per tutti i pannelli */
        const manager = new SettingsManager();

        /* Gestione dell'apertura visiva e del caricamento dinamico dei pannelli */
        const triggerButtons = document.querySelectorAll('.accordion-header button[data-env]');

        triggerButtons.forEach(btn => {
            const env = btn.dataset.env;
            const mainCollapse = document.getElementById(`main_collapse_${env}`);

            if (mainCollapse) {
                const bsCollapse = new bootstrap.Collapse(mainCollapse, { toggle: false });

                btn.addEventListener('click', async (e) => {
                    e.preventDefault();

                    if (mainCollapse.classList.contains('show') || mainCollapse.classList.contains('collapsing')) {
                        bsCollapse.hide();
                        return;
                    }

                    const container = document.getElementById(`${env}-settings-container`);
                    if (container && container.innerHTML.trim() !== '') {
                        bsCollapse.show();
                        return;
                    }

                    /* Chiamiamo il metodo del manager globale */
                    const success = await manager.loadPanel(`${env}-settings-container`, env);
                    if (success === false) return;

                    btn.disabled = false;
                    btn.classList.remove('disabled');
                    bsCollapse.show();
                });

                mainCollapse.addEventListener('hidden.bs.collapse', (e) => {
                    if (e.target === mainCollapse) {
                        manager.resetContainer(`${env}-settings-container`);
                    }
                });
            }
        });
    }
};

if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita per i settings:", action);
}