import { urlbase, action, initOffcanvasAutoClose, initRangeDatePicker, initTomSelects } from './backend.js';
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './modules/Crud.js';
import { ExportCsvManager } from './components/ExportCsv.js';
import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneManager } from './components/GalleryOne.js';

const actions = {
    index: function(){},

    showAll: function() {
        const productsManager = new ListManager({
            controller: 'products',
            url: urlbase + 'backend/products/showAll',
            rootId: 'products-list',
            containerId: 'showAll-products-container',
            searchFields: ['sku', 'product', 'status', 'brand_uuid', 'category_uuid', 'quantity-from', 'quantity-to', 'price-from', 'price-to'],
            searchDates: ['created_at', 'updated_at']
        });
        productsManager.init();

        initRangeDatePicker('#wrapper-products-created_at-from', '#wrapper-products-created_at-to');
        initRangeDatePicker('#wrapper-products-updated_at-from', '#wrapper-products-updated_at-to');

        new DeleteManager({
            controller: 'products',
            urls: {
                softDelete: urlbase + 'backend/products/softDelete',
                restoreDelete: urlbase + 'backend/products/restoreDelete',
                hardDelete: urlbase + 'backend/products/hardDelete'
            },
            listManager: productsManager
        }).init();

        new ChangeStatusManager({
            controller: 'products',
            url: urlbase + 'backend/products/changeStatus'
        }, {
            onStatusAfter: () => productsManager.showAll()
        }).init();

        new ExportCsvManager({ controller: 'products' }).init();
        initOffcanvasAutoClose('products-actionsOffcanvas');
    },

    add: function() {
        initTomSelects();
        const imagePreviewManager = new UploadPreviewImgManager();

        new AddManager({
            url: urlbase + 'backend/products/add',
            formSelector: '#products-add',
            resetSelector: '#add-reset',
            containerId: 'add-products-container',
            imagePreviewManager: imagePreviewManager
        }, {
            onAddAfter: initTomSelects,
            onResetAfter: initTomSelects
        }).init();
    },

    edit: function() {
        initTomSelects();
        const imagePreviewManager = new UploadPreviewImgManager();
        const galleryOneManager = new GalleryOneManager();

        new EditManager({
            formSelector: '#products-edit',
            url: urlbase + 'backend/products/edit',
            refreshSelector: '#edit-refresh',
            containerId: 'edit-products-container',
            imagePreviewManager: imagePreviewManager,
            galleryOneManager: galleryOneManager
        }, {
            onEditAfter: initTomSelects,
            onRefreshAfter: initTomSelects
        }).init();

        new GeneralDataManager({
            url: urlbase + 'backend/products/getGeneralData',
            formSelector: '#getGeneralData'
        }, {
            onGeneralDataAfter: initTomSelects
        }).init();

        new MetaDataManager({
            url: urlbase + 'backend/products/getMetaData',
            formSelector: '#getMetaData'
        }).init();
    },

    show: function() {
        new GeneralDataManager({
            url: urlbase + 'backend/products/getGeneralData',
            formSelector: '#getGeneralData'
        }).init();

        new GalleryOneManager();

        new MetaDataManager({
            url: urlbase + 'backend/products/getMetaData',
            formSelector: '#getMetaData'
        }).init();

        new ChangeStatusManager({
            controller: 'products',
            url: urlbase + 'backend/products/changeStatus'
        }, {
            onStatusAfter: data => {
                const status = document.getElementById('changeStatusPartial');
                if (status) status.innerHTML = data.statusView;

                const meta = document.getElementById('metaData');
                if (meta) meta.innerHTML = data.metaView;
            }
        }).init();
    }
};

if (actions[action]) {
    actions[action]();
} else {
    console.error('Azione non definita:', action);
}
