/* Import delle costanti e utility da backend.js */
import { urlbase, action, initOffcanvasAutoClose, initRangeDatePicker } from './backend.js';

/* Import dei componenti condivisi del CRUD */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';
import { ImportCsvManager } from './components/ImportCsv.js';
import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneManager } from './components/GalleryOne.js';

const actions = {
    index: function(){},

    showAll: function() {

        const brandsManager = new ListManager({
            controller: 'brands',
            url: urlbase + 'backend/brands/showAll',
            rootId: 'brands-list',
            containerId: 'showAll-brands-container',
            searchFields: ['brand', 'status'],
            searchDates: ['created_at', 'updated_at']
        });
        brandsManager.init();

        initRangeDatePicker('#wrapper-brands-created_at-from', '#wrapper-brands-created_at-to');
        initRangeDatePicker('#wrapper-brands-updated_at-from', '#wrapper-brands-updated_at-to');

        const deleteManager = new DeleteManager({
            controller: 'brands',
            urls: {
                softDelete: urlbase + 'backend/brands/softDelete',
                restoreDelete: urlbase + 'backend/brands/restoreDelete',
                hardDelete: urlbase + 'backend/brands/hardDelete'
            },
            listManager: brandsManager
        });
        deleteManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'brands',
            url: urlbase + 'backend/brands/changeStatus'
        }, {
            onStatusAfter: () => {
                brandsManager.showAll();
            }
        });
        changeStatusManager.init();

        const exportManager = new ExportCsvManager({ controller: 'brands' });
        exportManager.init();

        const importManager = new ImportCsvManager({
            controller: 'brands'
        }, {
            onImportAfter: () => {
                brandsManager.showAll();
            }
        });
        importManager.init();

        initOffcanvasAutoClose('brands-actionsOffcanvas');
    },

    add: function() {

        const imagePreviewManager = new UploadPreviewImgManager();

        const addManager = new AddManager({
            url: urlbase + 'backend/brands/add',
            formSelector: '#brands-add',
            resetSelector: '#add-reset',
            containerId: 'add-brands-container',
            imagePreviewManager: imagePreviewManager
        });
        addManager.init();
    },

    edit: function() {

        const imagePreviewManager = new UploadPreviewImgManager();
        const galleryOneManager = new GalleryOneManager();

        const editManager = new EditManager({
            formSelector: '#brands-edit',
            url: urlbase + 'backend/brands/edit',
            refreshSelector: '#edit-refresh',
            containerId: 'edit-brands-container',
            imagePreviewManager: imagePreviewManager,
            galleryOneManager: galleryOneManager
        });
        editManager.init();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/brands/getGeneralData',
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/brands/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/brands/getGeneralData',
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        new GalleryOneManager();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/brands/getMetaData',
            formSelector: '#getMetaData'
        });
        metaDataManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'brands',
            url: urlbase + 'backend/brands/changeStatus'
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
