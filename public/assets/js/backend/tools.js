/* Import delle costanti da backend.js */
import { urlbase, action, confirmDiscardChanges } from './backend.js';

/* Import della classe logica polivalente */
import { ToolsManager } from './modules/Tools.js';

/* Import dei gestori CSV */
import { ExportCsvManager } from './components/ExportCsv.js';
import { ImportCsvManager } from './components/ImportCsv.js';

const actions = {
    index: function() {

        const manager = new ToolsManager();
        
        /* Variabili per memorizzare le istanze e non ricrearle ad ogni click */
        let exportManager = null;
        let importManager = null;

        /* Delegazione globale per i click sui pulsanti accordion */
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.accordion-header button[data-env]');
            if (!btn) return;

            e.preventDefault();

            const env = btn.dataset.env;
            const mainCollapse = document.getElementById(`main_collapse_${env}`);
            if (!mainCollapse) return;

            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(mainCollapse, { toggle: false });

            if (mainCollapse.classList.contains('show') || mainCollapse.classList.contains('collapsing')) {
                const confirmed = await confirmDiscardChanges(mainCollapse);
                if (!confirmed) return;

                bsCollapse.hide();
                return;
            }

            const currentCollapse = document.querySelector(
                '#mainToolsDashboard > .accordion-item > .accordion-collapse.show, ' +
                '#mainToolsDashboard > .accordion-item > .accordion-collapse.collapsing'
            );

            if (currentCollapse && currentCollapse !== mainCollapse) {
                const confirmed = await confirmDiscardChanges(currentCollapse);
                if (!confirmed) return;
            }

            const container = document.getElementById(`${env}-tools-container`);

            /* Carica il pannello via AJAX solo se il contenitore è vuoto */
            if ( ! container || container.innerHTML.trim() === '') {
                const success = await manager.loadPanel(`${env}-tools-container`, env);
                if (success === false) return;
                
                /* Controlla se l'accordion aperto è quello della manutenzione */
                if (env === 'dbMaintenance') {
                    
                    if ( ! exportManager) {
                        exportManager = new ExportCsvManager({
                            linkId: '.export-entity',
                            urlModal: urlbase + 'backend/export/database/showModal',
                            urlExport: urlbase + 'backend/export/database/generate'
                        });
                        exportManager.init();
                    }
                    
                    if ( ! importManager) {
                        importManager = new ImportCsvManager({
                            linkId: '.import-entity',
                            urlModal: urlbase + 'backend/import/database/showModal',
                            urlProcess: urlbase + 'backend/import/database/processCsv'
                        });
                        importManager.init();
                    }
                }
            }

            bsCollapse.show();
        });

        /* Delegazione globale per il reset del contenitore alla chiusura */
        document.addEventListener('hidden.bs.collapse', (e) => {
            if (e.target && e.target.id.startsWith('main_collapse_')) {
                const env = e.target.id.replace('main_collapse_', '');
                manager.resetContainer(`${env}-tools-container`);
            }
        });

    }
};

if (actions[action]) {
    actions[action]();
}
