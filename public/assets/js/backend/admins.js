/* Import delle costanti e utility da backend.js */
import { urlbase, action, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './components/Crud.js';
import { ChangeGroupManager, GetPermissionsManager, ChangePermissionManager, GetTokensManager, DeleteTokenManager, ResetPasswordManager } from './components/Admins.js';

import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneImgManager } from './components/GalleryOne.js';

const actions = {
    index: function(){},
    showAll: function() {

        const adminsManager = new ListManager({
            controller: 'admins',
            url: urlbase + 'backend/admins/showAll',
            containerId: 'showAll-admins-container',
            searchFields: ['firstname', 'lastname', 'email', 'phone']
        });
        adminsManager.init();

        const deleteManager = new DeleteManager({
            controller: 'admins',
            url: urlbase + 'backend/admins/delete',
            listManager: adminsManager
        });
        deleteManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'admins',
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
            onResetAfter: data => {
                adminsManager.showAll();
            }
        });
        adminResetManager.init();
    },
    add: function() {

        const imagePreviewManager = new UploadPreviewImgManager();

        const addManager = new AddManager({
            url: urlbase + 'backend/admins/add',
            formSelector: '#admins_add',
            resetSelector: '#add_reset', 
            containerId: 'add-admins-container', 
            imagePreviewManager: imagePreviewManager
        });
        addManager.init();

    },
    edit: function() {

        const imagePreviewManager = new UploadPreviewImgManager();
        const galleryOneImgManager = new GalleryOneImgManager();

        const editManager = new EditManager({
            formSelector: '#admins_edit',
            url: urlbase + 'backend/admins/edit',
            refreshSelector: '#edit_refresh',
            containerId: 'edit-admins-container', 
            imagePreviewManager: imagePreviewManager,
            galleryOneImgManager: galleryOneImgManager
        });
        editManager.init();

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData', 
            formSelector: '#getGeneralData'
        }, {
            onGeneralDataAfter: (data) => {
                /* Se il server restituisce la doppia vista dell'anagrafica, sincronizziamo il pannello */
                if (data.permissions_output) {
                    const permissionsEl = document.getElementById('permissions');
                    if (permissionsEl) {
                        smoothReplace(permissionsEl, data.permissions_output);
                    }
                }
            }
        });
        generalDataManager.init();

        const changeGroupManager = new ChangeGroupManager();
        changeGroupManager.init();

        const permissionsManager = new GetPermissionsManager({
            onPermissionsAfter: (data) => {
                /* Se il server restituisce il group_id reale, riallineiamo la select dell'anagrafica */
                if (data.group_id !== undefined) {
                    const selectGroup = document.getElementById('group_id');
                    if (selectGroup) {
                        selectGroup.value = data.group_id;
                    }
                }
            }
        });
        permissionsManager.init();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData', 
            formSelector: '#getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        const generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData', 
            formSelector: '#getGeneralData'
        });
        generalDataManager.init();

        const galleryOneImgManager = new GalleryOneImgManager();

        const metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData', 
            formSelector: '#getMetaData'
        });
        metaDataManager.init();

        const changeStatusManager = new ChangeStatusManager({
            controller: 'admins', 
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

        const changePermissionManager = new ChangePermissionManager();
        changePermissionManager.init();

        const permissionsManager = new GetPermissionsManager();
        permissionsManager.init();

        const tokensManager = new GetTokensManager();
        tokensManager.init();

        const deleteTokenManager = new DeleteTokenManager();
        deleteTokenManager.init();
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
