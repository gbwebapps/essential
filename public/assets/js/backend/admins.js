/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, apiFetch, showToast, askConfirm, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './components/Crud.js';
import { ResetPasswordManager } from './components/Auth.js';
import { GetPermissionsManager, ChangePermissionManager, GetTokensManager, DeleteTokenManager } from './components/Admins.js';

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
            url: urlbase + 'backend/admins/add',
            formSelector: '#admins_add', /* <--- Passiamo il selettore */
            resetSelector: '#add_reset', /* <--- Passiamo il selettore del pulsante */
            containerId: 'add-admins-container', 
        });
        addManager.init();

    },
    edit: function() {

        // const galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // const imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');

        // const galleryOneDocManager = new GalleryOneDocManager('#documents_data');
        // const docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');

        const editManager = new EditManager({
            formSelector: '#admins_edit',
            url: urlbase + 'backend/admins/edit',
            refreshSelector: '#edit_refresh',
            containerId: 'edit-admins-container', 
        });
        editManager.init();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData', 
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData', 
            formSelector: '#getMetaData'
        });
        metaDataManager.init();

        const permissionsManager = new GetPermissionsManager({
            url: urlbase + 'backend/admins/getPermissions', 
            formSelector: '#getPermissions'
        });
        permissionsManager.init();
    },

    show: function() {

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData', 
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData', 
            formSelector: '#getMetaData'
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

        const changePermissionManager = new ChangePermissionManager({
            controller: controller,
            url: urlbase + 'backend/admins/changePermission'
        });
        changePermissionManager.init();

        const permissionsManager = new GetPermissionsManager({
            url: urlbase + 'backend/admins/getPermissions', 
            formSelector: '#getPermissions'
        });
        permissionsManager.init();

        const tokensManager = new GetTokensManager({
            url: urlbase + 'backend/admins/getTokens', 
            formSelector: '#getTokens'
        });
        tokensManager.init();

        const deleteTokenManager = new DeleteTokenManager({
            controller: controller,
            url: urlbase + 'backend/admins/deleteToken',
        });
        deleteTokenManager.init();

        // const galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // const galleryOneDocManager = new GalleryOneDocManager('#documents_data');
    }
};

/* Listener per il link select all nei form add ed edit per selezionare tutti i check box dei permessi */
document.addEventListener('click', function(e) {
    if (e.target.matches('.select-all')) {
        e.preventDefault();
        const controller = e.target.dataset.controller;
        const checkboxes = document.querySelectorAll(`input[type="checkbox"].${controller}`);
        const anyChecked = Array.from(checkboxes).some(el => el.checked);
        const newState = !anyChecked;
        checkboxes.forEach(el => el.checked = newState);
    }
});

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}
