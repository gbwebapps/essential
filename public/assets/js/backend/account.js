/* Import delle costanti e utility da backend.js */
import { action, urlbase } from './backend.js';

/* Import dei componenti dalla sottocartella */
import { EditManager, GetPermissionsManager, GetTokensManager, DeleteTokenManager, ResetPasswordManager, SecurityManager } from './modules/Account.js';

/* Import componenti immagini */
import { UploadPreviewImgManager } from './components/UploadPreview.js';
import { GalleryOneManager } from './components/GalleryOne.js';

const actions = {
    index: function() {}, 
    general: function() {}, 
    edit: function() {

    	const editManager = new EditManager();
        editManager.init();

	}, 
    permissions: function() {

        const permissionsManager = new GetPermissionsManager();
        permissionsManager.init();
        
    }, 
    images: function() {

        const galleryOneManager = new GalleryOneManager({
            showGalleryUrl: urlbase + 'backend/account/galleryOne/showGallery',
            deleteImageUrl: urlbase + 'backend/account/galleryOne/deleteImage',
            removeCoverUrl: urlbase + 'backend/account/galleryOne/removeCover',
            setCoverUrl: urlbase + 'backend/account/galleryOne/setCover'
        });
        const imagePreviewManager = new UploadPreviewImgManager(galleryOneManager, {
            saveImagesUrl: urlbase + 'backend/account/uploadPreview/saveImages'
        });

    }, 
    tokens: function() {

        const tokensManager = new GetTokensManager();
        tokensManager.init();

        const deleteTokenManager = new DeleteTokenManager();
        deleteTokenManager.init();
        
    }, 
    resetPassword: function() {

        const resetPasswordManager = new ResetPasswordManager();
        resetPasswordManager.init();

    },
    security: function() {

        const securityManager = new SecurityManager();
        securityManager.init();

    }
};

/* Se esiste una funzione per l'azione corrente, eseguila */
if (actions[action]) {
    actions[action]();
} else {
    console.error("Azione non definita:", action);
}

