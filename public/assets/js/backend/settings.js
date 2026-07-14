/* Import delle costanti e utility da backend.js */
import { urlbase, action } from './backend.js';

/* Import della classe logica polivalente */
import { SettingsManager } from './components/Settings.js';

const actions = {
    index: function() {

        /* --- GESTIONE FLUIDA E PARAMETRIZZATA DEI PANNELLI --- */
        /* Selezioniamo tutti i pulsanti della testata dell'accordion che recano l'attributo data-env */
        const triggerButtons = document.querySelectorAll('.accordion-header button[data-env]');

        triggerButtons.forEach(btn => {
            const env = btn.dataset.env;
            const mainCollapse = document.getElementById(`main_collapse_${env}`);

            if (mainCollapse) {
                /* Istanzia il manager passando le nuove rotte comuni e l'ID del form (es. auth-settings) */
                const manager = new SettingsManager('backend/settings/openSettings', 'backend/settings/saveSettings', 'backend/settings/deleteSettings', `${env}-settings`);
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

                    /* Passiamo il valore env nel corpo della richiesta modificando leggermente il loadPanel nel file manager */
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

/* Esecuzione dell'azione corrente in base al contesto del controller */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita per i settings:", action);
}