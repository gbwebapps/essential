/* Import delle costanti e utility da backend.js */
import { urlbase, controller, action, apiFetch, showToast, askConfirm, smoothReplace } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { ListManager, AddManager, EditManager, DeleteManager, ChangeStatusManager, GeneralDataManager, MetaDataManager } from './components/Crud.js';
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

        let deleteManager = new DeleteManager({
            controller: controller,
            url: urlbase + 'backend/admins/delete',
            listManager: adminsManager
        });
        deleteManager.init();

        let changeStatusManager = new ChangeStatusManager({
            controller: controller,
            url: urlbase + 'backend/admins/changeStatus',
            listManager: adminsManager
        });
        changeStatusManager.init();

        /* Listener per il reset password nella vista showAll */
        document.addEventListener('submit', async function(e) {
            if (e.target.matches('.reset_admin')) {
                e.preventDefault();
                const message = e.target.dataset.message;
                const form_data = new FormData(e.target);

                const ok = await askConfirm(message);
                if (ok) {
                    reset_password(form_data, message);
                }
            }
        });

        /* Funzione per il reset password nella vista showAll */
        function reset_password(form_data) {
            apiFetch(urlbase + 'backend/admins/reset', {
                method: 'POST',
                headers: {'X-CSRF-Token': form_data.get('csrf_token') },
                body: form_data
            })
                .then(response => response.json())
                .then(data => {
                    if (data.result === 'no_current_admin_logged') {
                        window.location.href = urlbase + 'backend/auth/login';
                    } else if (data.result === '404') {
                        window.location.href = urlbase + 'backend/404';
                    } else {
                        if (data.errors) {
                            showToast('danger', data.errors);
                        } else {
                            if (data.result === false) {
                                showToast('danger', data.message);
                            } else if (data.result === true) {
                                showToast('success', data.message);
                                adminsManager.showAll();
                            }
                        }
                    }
                })
                .catch(error => console.error(error));
        }

    },
    add: function() {

        // let imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');
        // let docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');

        let addManager = new AddManager({
            formIds: ['admins_add'],
            url: urlbase + 'backend/admins/add',
            resetId: 'add_reset',
            containerId: 'add-admins-container', 
            // imagePreviewManager: imagePreviewManager,
            // docPreviewManager: docPreviewManager
        });

    },
    edit: function() {

        // let galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // let imagePreviewManager = new UploadPreviewImgManager('#inputImages', '#preview_images', '#buttonImages');

        // let galleryOneDocManager = new GalleryOneDocManager('#documents_data');
        // let docPreviewManager = new UploadPreviewDocManager('#inputDocuments', '#preview_documents', '#buttonDocuments');

        let editManager = new EditManager({
            formIds: ['admins_edit'],
            url: urlbase + 'backend/admins/edit',
            refreshId: 'edit_refresh',
            containerId: 'edit-admins-container', 
            // imagePreviewManager: imagePreviewManager,
            // galleryOneImgManager: galleryOneImgManager,
            // docPreviewManager: docPreviewManager,
            // galleryOneDocManager: galleryOneDocManager
        });

        let generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData'
        });
        generalDataManager.init();

        let metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData'
        });
        metaDataManager.init();
    },

    show: function() {

        let generalDataManager = new GeneralDataManager({
            url: urlbase + 'backend/admins/getGeneralData'
        });
        generalDataManager.init();

        let metaDataManager = new MetaDataManager({
            url: urlbase + 'backend/admins/getMetaData'
        });
        metaDataManager.init();

        let statusManager = new ChangeStatusManager({
            url: urlbase + 'backend/admins/changeStatus'
        }, {
            onStatusAfter: data => {
                const el = document.getElementById('change_status_partial');
                if (el) el.innerHTML = data.status_view;

                const meta = document.getElementById('meta_data');
                if (meta) meta.innerHTML = data.meta_view;
            }
        });
        statusManager.init();

        // let galleryOneImgManager = new GalleryOneImgManager('#images_data');
        // let galleryOneDocManager = new GalleryOneDocManager('#documents_data');

        /* Listener per il refresh Tokens data */
        document.addEventListener('submit', function(e) {
            if (e.target.matches('#get_tokens_data')) {
                e.preventDefault();
                const form_data = new FormData(e.target);
                get_tokens_data(form_data);
            }
        });

        /* Funzione per il refresh Tokens data */
        function get_tokens_data(form_data) {
            apiFetch(urlbase + 'backend/admins/getTokensData', {
                method: 'POST',
                headers: {'X-CSRF-Token': form_data.get('csrf_token') },
                body: form_data
            })
                .then(response => response.json())
                .then(data => {
                    if (data.result === 'no_current_admin_logged') {
                        window.location.href = urlbase + 'backend/auth/login';
                    } else if (data.result === '404') {
                        window.location.href = urlbase + 'backend/404';
                    } else {
                        if (data.errors) {
                            showToast('danger', data.errors);
                        } else {
                            if (data.result === false) {
                                showToast('danger', data.message);
                            } else if (data.result === true) {
                                smoothReplace(document.getElementById('tokens_data'), data.output);
                            }
                        }
                    }
                })
                .catch(error => console.error(error));
        }

        /* Listener per il cambio permesso nella vista show */
        document.addEventListener('submit', async function(e) {
            if (e.target.matches('.change_permission')) {
                e.preventDefault();
                const message = e.target.dataset.message;
                const form_data = new FormData(e.target);

                const ok = await askConfirm(message);
                if (ok) {
                    change_permission(form_data, message);
                }
            }
        });

        /* Funzione per il cambio permesso nella vista show */
        function change_permission(form_data) {
            apiFetch(urlbase + 'backend/admins/changePermission', {
                method: 'POST',
                headers: {'X-CSRF-Token': form_data.get('csrf_token') },
                body: form_data
            })
                .then(response => response.json())
                .then(data => {
                    if (data.result === 'no_current_admin_logged') {
                        window.location.href = urlbase + 'backend/auth/login';
                    } else if (data.result === '404') {
                        window.location.href = urlbase + 'backend/404';
                    } else {
                        if (data.errors) {
                            showToast('danger', data.errors);
                        } else {
                            if (data.result === false) {
                                showToast('danger', data.message);
                            } else if (data.result === true) {
                                smoothReplace(document.getElementById('permissions_data'), data.permissions_view);
                                smoothReplace(document.getElementById('meta_data'), data.meta_view);
                                showToast('success', data.message);
                            }
                        }
                    }
                })
                .catch(error => console.error(error));
        }

        /* Listener per l'eliminazione di un token dalla lista tokens nella vista show */
        document.addEventListener('submit', async function(e) {
            if (e.target.matches('.delete_token')) {
                e.preventDefault();

                const message = e.target.dataset.message;
                const form_data = new FormData(e.target);

                const ok = await askConfirm(message);
                if (ok) {
                    delete_token(form_data, message);
                }
            }
        });

        /* Funzione per l'eliminazione di un token dalla lista tokens nella vista show */
        function delete_token(form_data) {
            apiFetch(urlbase + 'backend/admins/deleteToken', {
                method: 'POST',
                headers: {'X-CSRF-Token': form_data.get('csrf_token') },
                body: form_data
            })
                .then(response => response.json())
                .then(data => {
                    if (data.result === 'no_current_admin_logged') {
                        window.location.href = urlbase + 'backend/auth/login';
                    } else if (data.result === '404') {
                        window.location.href = urlbase + 'backend/404';
                    } else {
                        if (data.errors) {
                            showToast('danger', data.errors);
                        } else {
                            if (data.result === false) {
                                showToast('danger', data.message);
                            } else if (data.result === true) {
                                smoothReplace(document.getElementById('tokens_data'), data.output);
                                showToast('success', data.message);
                            }
                        }
                    }
                })
                .catch(error => console.error(error));
        }
    }
};

/* Listener per il refresh Permissions data */
document.addEventListener('submit', function(e) {
    if (e.target.matches('#get_permissions_data')) {
        e.preventDefault();
        const form_data = new FormData(e.target);
        get_permissions_data(form_data);
    }
});

/* Funzione per il refresh Permissions data */
function get_permissions_data(form_data) {
    apiFetch(urlbase + 'backend/admins/getPermissionsData', {
        method: 'POST',
        headers: {'X-CSRF-Token': form_data.get('csrf_token') },
        body: form_data
    })
        .then(response => response.json())
        .then(data => {
            if (data.result === 'no_current_admin_logged') {
                window.location.href = urlbase + 'backend/auth/login';
            } else if (data.result === '404') {
                window.location.href = urlbase + 'backend/404';
            } else {
                if (data.errors) {
                    showToast('danger', data.errors);
                } else {
                    if (data.result === false) {
                        showToast('danger', data.message);
                    } else if (data.result === true) {
                        smoothReplace(document.getElementById('permissions_data'), data.output);
                    }
                }
            }
        })
        .catch(error => console.error(error));
}

/* Listener per il link select all nei form add ed edit per selezionare tutti i check box dei permessi */
document.addEventListener('click', function(e) {
    if (e.target.matches('.select_all')) {
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
