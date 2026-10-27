/* Import delle costanti e utility da backend.js */
import { urlbase, action, initOffcanvasAutoClose, initRangeDatePicker } from './backend.js';

/* Import dei componenti condivisi del CRUD */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';
import { ImportCsvManager } from './components/ImportCsv.js';
import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneManager } from './components/GalleryOne.js';
import { GetTreeManager } from './modules/Categories.js';

const initTreeManager = function() {
    const treeManager = new GetTreeManager({
        url: urlbase + 'backend/categories/getTree'
    });
    treeManager.init();
};

const actions = {
    index: function(){},

    showAll: function() {

        initTreeManager();

        const categoriesManager = new ListManager({
            controller: 'categories',
            url: urlbase + 'backend/categories/showAll',
            rootId: 'categories-list',
            containerId: 'showAll-categories-container',
            searchFields: ['category', 'status'],
            searchDates: ['created_at', 'updated_at']
        }, {
            /* Aggiorna i tre totali solo dopo un caricamento riuscito della lista. */
            onShowAfter: data => {
                const counts = data.trashCounts;
                const filters = ['active', 'suspended', 'trashed', 'all'];

                if ( ! counts || ! filters.every(filter => Number.isSafeInteger(counts[filter]) && counts[filter] >= 0)
                    || counts.active + counts.suspended + counts.trashed !== counts.all) return;

                categoriesManager.root.querySelectorAll('[data-trash-count]').forEach(badge => {
                    const filter = badge.dataset.trashCount;
                    if ( ! filters.includes(filter)) return;

                    badge.textContent = String(counts[filter]);
                    badge.hidden = false;
                });
            }
        });
        categoriesManager.init();

        initRangeDatePicker('#wrapper-categories-created_at-from', '#wrapper-categories-created_at-to');
        initRangeDatePicker('#wrapper-categories-updated_at-from', '#wrapper-categories-updated_at-to');

        const deleteManager = new DeleteManager({
            controller: 'categories',
            urls: {
                softDelete: urlbase + 'backend/categories/softDelete',
                restoreDelete: urlbase + 'backend/categories/restoreDelete',
                hardDelete: urlbase + 'backend/categories/hardDelete'
            },
            listManager: categoriesManager
        });
        deleteManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'categories',
            url: urlbase + 'backend/categories/changeStatus'
        }, {
            onStatusAfter: () => {
                categoriesManager.showAll();
            }
        });
        changeStatusManager.init();

        const exportManager = new ExportCsvManager({ controller: 'categories' });
        exportManager.init();

        const importManager = new ImportCsvManager({
            controller: 'categories'
        }, {
            onImportAfter: () => {
                categoriesManager.showAll();
            }
        });
        importManager.init();

        initOffcanvasAutoClose('categories-actionsOffcanvas');
    },

    add: function() {

        initTreeManager();

        const imagePreviewManager = new UploadPreviewImgManager();

        const addManager = new AddManager({
            url: urlbase + 'backend/categories/add',
            formSelector: '#categories-add',
            resetSelector: '#add-reset',
            containerId: 'add-categories-container',
            imagePreviewManager: imagePreviewManager
        });
        addManager.init();
    },

    edit: function() {

        initTreeManager();

        const imagePreviewManager = new UploadPreviewImgManager();
        const galleryOneManager = new GalleryOneManager();

        const editManager = new EditManager({
            formSelector: '#categories-edit',
            url: urlbase + 'backend/categories/edit',
            refreshSelector: '#edit-refresh',
            containerId: 'edit-categories-container',
            imagePreviewManager: imagePreviewManager,
            galleryOneManager: galleryOneManager
        });
        editManager.init();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/categories/getGeneralData',
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/categories/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        initTreeManager();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/categories/getGeneralData',
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        new GalleryOneManager();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/categories/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'categories',
            url: urlbase + 'backend/categories/changeStatus'
        }, {
            onStatusAfter: data => {
                const status = document.getElementById('changeStatusPartial');
                if (status) status.innerHTML = data.statusView;

                const meta = document.getElementById('metaData');
                if (meta) meta.innerHTML = data.metaView;
            }
        });
        changeStatusManager.init();
    }
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
