/* Import delle costanti da backend.js */
import { action } from './backend.js';

/* Import della classe logica polivalente */
import { ToolsManager } from './components/Tools.js';

const actions = {
    index: function() {

        const manager = new ToolsManager();

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

                    const container = document.getElementById(`${env}-tools-container`);

                    /* Carica il pannello via AJAX solo se il contenitore è vuoto */
                    if ( ! container || container.innerHTML.trim() === '') {
                        const success = await manager.loadPanel(`${env}-tools-container`, env);
                        if (success === false) return;
                    }

                    bsCollapse.show();
                });

                /* Reset del contenitore alla chiusura dell'accordion */
                mainCollapse.addEventListener('hidden.bs.collapse', (e) => {
                    if (e.target === mainCollapse) {
                        manager.resetContainer(`${env}-tools-container`);
                    }
                });
            }
        });

    }
};

if (actions[action]) {
    actions[action]();
}