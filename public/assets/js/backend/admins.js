/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, apiFetch, showToast, askConfirm, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './components/Crud.js';
import { ResetPasswordManager } from './components/Auth.js';

import { GalleryOneImgManager } from './components/GalleryOneImgManager.js';
import { UploadPreviewImgManager } from './components/UploadPreviewImgManager.js';
import { GalleryOneDocManager } from './components/GalleryOneDocManager.js';
import { UploadPreviewDocManager } from './components/UploadPreviewDocManager.js';

const actions = {
    index: function(){},
    showAll: function() {

        const adminsManager = new ListManager({
            controller: controller,
            url: urlbase + 'backend/admins/showAll',
            containerId: 'showAll-admins-container',
            searchFields: ['firstname', 'lastname', 'email', 'phone']
        });
        adminsManager.init();

        const deleteManager = new DeleteManager({
            controller: controller,
            url: urlbase + 'backend/admins/delete',
            listManager: adminsManager
        });
        deleteManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: controller,
            url: urlbase + 'backend/admins/changeStatus'
        }, {
            onStatusAfter: data => {
                if (typeof adminsManager !== 'undefined' && typeof adminsManager.showAll === 'function') {
                    adminsManager.showAll();
                }
            }
        });
        changeStatusManager.init();

        const adminResetManager = new ResetPasswordManager({
            formSelector: '.resetAdmin',
            url: `${urlbase}backend/admins/resetPassword`,
            listManager: adminsManager /* Passo l'istanza per ricaricare la tabella */
        });
        adminResetManager.init();
    },
    add: function() {

        // const imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');
        // const docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');

        const addManager = new AddManager({
            formIds: ['admins_add'],
            url: urlbase + 'backend/admins/add',
            resetId: 'add_reset',
            containerId: 'add-admins-container', 
            // imagePreviewManager: imagePreviewManager,
            // docPreviewManager: docPreviewManager
        });

    },
    edit: function() {

        // const galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // const imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');

        // const galleryOneDocManager = new GalleryOneDocManager('#documents_data');
        // const docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');

        const editManager = new EditManager({
            formIds: ['admins_edit'],
            url: urlbase + 'backend/admins/edit',
            refreshId: 'edit_refresh',
            containerId: 'edit-admins-container', 
            // imagePreviewManager: imagePreviewManager,
            // galleryOneImgManager: galleryOneImgManager,
            // docPreviewManager: docPreviewManager,
            // galleryOneDocManager: galleryOneDocManager
        });

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData'
        });
        metaDataManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: controller, 
            url: urlbase + 'backend/admins/changeStatus'
        }, {
            onStatusAfter: data => {
                const el = document.getElementById('changeStatusPartial');
                if (el) el.innerHTML = data.statusView;

                const meta = document.getElementById('metaData');
                if (meta) meta.innerHTML = data.metaView;
            }
        });
        changeStatusManager.init();

        // const galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // const galleryOneDocManager = new GalleryOneDocManager('#documents_data');
    }
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
