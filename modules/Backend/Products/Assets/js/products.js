/* Import delle costanti e delle utility condivise del backend */
import { urlbase, action, initOffcanvasAutoClose, initRangeDatePicker, initTomSelects } from './backend.js';

/* Import dei componenti condivisi del CRUD */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';
import { ImportCsvManager } from './components/ImportCsv.js';
import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneManager } from './components/GalleryOne.js';

/**
 * Inizializza le select multiple di marche e categorie.
 *
 * La funzione viene richiamata anche dopo la sostituzione AJAX dei form,
 * perché i nuovi elementi devono ricevere una nuova istanza Tom Select.
 */
const initRelationSelects = function() {
    initTomSelects();
};

/* Mappa delle inizializzazioni specifiche per ogni pagina del modulo */
const actions = {
    index: function() {},

    showAll: function() {

        const productsManager = new ListManager({
            controller: 'products',
            url: urlbase + 'backend/products/showAll',
            rootId: 'products-list',
            containerId: 'showAll-products-container',
            searchFields: [
                'sku',
                'product',
                'status',
                'brand_uuid',
                'category_uuid',
                'quantity-from',
                'quantity-to',
                'price-from',
                'price-to'
            ],
            searchDates: ['created_at', 'updated_at']
        }, {
            /* Aggiorna i tre totali solo dopo un caricamento riuscito della lista. */
            onShowAfter: data => {
                const counts = data.trashCounts;
                const filters = ['active', 'suspended', 'trashed', 'all'];

                if ( ! counts || ! filters.every(filter => Number.isSafeInteger(counts[filter]) && counts[filter] >= 0)
                    || counts.active + counts.suspended + counts.trashed !== counts.all) return;

                productsManager.root.querySelectorAll('[data-trash-count]').forEach(badge => {
                    const filter = badge.dataset.trashCount;
                    if ( ! filters.includes(filter)) return;

                    badge.textContent = String(counts[filter]);
                    badge.hidden = false;
                });
            }
        });
        productsManager.init();

        initRangeDatePicker('#wrapper-products-created_at-from', '#wrapper-products-created_at-to');
        initRangeDatePicker('#wrapper-products-updated_at-from', '#wrapper-products-updated_at-to');

        const deleteManager = new DeleteManager({
            controller: 'products',
            urls: {
                softDelete: urlbase + 'backend/products/softDelete',
                restoreDelete: urlbase + 'backend/products/restoreDelete',
                hardDelete: urlbase + 'backend/products/hardDelete'
            },
            listManager: productsManager
        });
        deleteManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'products',
            url: urlbase + 'backend/products/changeStatus'
        }, {
            onStatusAfter: () => {
                productsManager.showAll();
            }
        });
        changeStatusManager.init();

        const exportManager = new ExportCsvManager({
            controller: 'products'
        });
        exportManager.init();

        const importManager = new ImportCsvManager({
            controller: 'products'
        }, {
            onImportAfter: () => {
                productsManager.showAll();
            }
        });
        importManager.init();

        initOffcanvasAutoClose('products-actionsOffcanvas');
    },

    add: function() {

        initRelationSelects();

        const imagePreviewManager = new UploadPreviewImgManager();

        const addManager = new AddManager({
            url: urlbase + 'backend/products/add',
            formSelector: '#products-add',
            resetSelector: '#add-reset',
            containerId: 'add-products-container',
            imagePreviewManager: imagePreviewManager
        }, {
            onAddAfter: initRelationSelects,
            onResetAfter: initRelationSelects
        });
        addManager.init();
    },

    edit: function() {

        initRelationSelects();

        const imagePreviewManager = new UploadPreviewImgManager();
        const galleryOneManager = new GalleryOneManager();

        const editManager = new EditManager({
            formSelector: '#products-edit',
            url: urlbase + 'backend/products/edit',
            refreshSelector: '#edit-refresh',
            containerId: 'edit-products-container',
            imagePreviewManager: imagePreviewManager,
            galleryOneManager: galleryOneManager
        }, {
            onEditAfter: initRelationSelects,
            onRefreshAfter: initRelationSelects
        });
        editManager.init();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/products/getGeneralData',
            formSelector: '#getGeneralData'
        }, {
            onGeneralDataAfter: initRelationSelects
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/products/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/products/getGeneralData',
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        new GalleryOneManager();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/products/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'products',
            url: urlbase + 'backend/products/changeStatus'
        }, {
            onStatusAfter: data => {
                const status = document.getElementById('changeStatusPartial');

                if (status) {
                    status.innerHTML = data.statusView;
                }

                const meta = document.getElementById('metaData');

                if (meta) {
                    meta.innerHTML = data.metaView;
                }
            }
        });
        changeStatusManager.init();
    }
};

/* Esegue soltanto l'inizializzazione prevista dalla pagina corrente */
if (actions[action]) {
    actions[action]();
} else {
    console.error('Azione non definita:', action);
}
