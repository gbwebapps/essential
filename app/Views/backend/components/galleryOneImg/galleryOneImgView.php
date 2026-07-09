<?php /* app/Views/backend/components/galleryOneImg/galleryOneImgView.php */ ?>
<div id="imagesData">
    <div class="card-body">
        <div class="row" id="galleryOneImg-container-<?= esc($uuid); ?>">
            <?php if ( ! empty($images) && is_array($images)): ?>
                <?php foreach ($images as $img): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3 mb-3 text-center">
                        <div class="position-relative preview-item rounded overflow-hidden bg-white p-1 border shadow-sm"
                             data-id="<?= esc($img['id']); ?>"
                             data-uuid="<?= esc($uuid); ?>"
                             data-entity="<?= esc($entity); ?>"
                             data-context="<?= esc($context); ?>" 
                             data-filename="<?= esc($img['filename']); ?>">
                            
                            <?php if ($context === 'edit'): ?>
                                <img src="<?= base_url('images/backend/' . $entity . '/' . esc($uuid) . '/medium/' . esc($img['filename'])); ?>"
                                     alt="Image"
                                     class="img-fluid w-100 d-block">
                                
                                <?php if ((int) $img['is_cover'] === 1): ?>
                                    <!-- Usiamo direttamente l'icona con un'ombra nativa per massima pulizia grafica -->
                                    <i class="fa-solid fa-circle-check fa-2x position-absolute top-0 start-0 m-2 text-white shadow"></i>
                                <?php endif; ?>
                                
                                <div class="gallery-one-overlay d-flex justify-content-center align-items-center">
                                    <?php if ((int) $img['is_cover'] === 1): ?>
                                        <i class="fa-solid fa-minus galleryOneImgAction text-white mx-2 fs-5"
                                           title="<?= lang('backend/components/galleryOneImg.removeCover'); ?>"
                                           data-action="removeCover"
                                           data-message="<?= lang('backend/components/galleryOneImg.areYouSureRemoveCover'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-check galleryOneImgAction text-white mx-2 fs-5"
                                           title="<?= lang('backend/components/galleryOneImg.setCover'); ?>"
                                           data-action="setCover"
                                           data-message="<?= lang('backend/components/galleryOneImg.areYouSureSetCover'); ?>">
                                        </i>
                                    <?php endif; ?>
                                    
                                    <i class="fa-solid fa-trash galleryOneImgAction text-white mx-2 fs-5"
                                       title="<?= lang('backend/components/galleryOneImg.delete'); ?>"
                                       data-action="deleteImage"
                                       data-message="<?= lang('backend/components/galleryOneImg.areYouSureDeleteImage'); ?>">
                                    </i>
                                    
                                    <a href="<?= base_url('images/backend/' . $entity . '/' . esc($uuid) . '/large/' . esc($img['filename'])); ?>"
                                       target="_blank"
                                       class="text-white mx-2 fs-5"
                                       title="<?= lang('backend/components/galleryOneImg.viewImage'); ?>">
                                        <i class="fa-solid fa-image"></i>
                                    </a>
                                </div>
                                
                            <?php elseif ($context === 'show'): ?>
                                <a href="<?= base_url('images/backend/' . $entity . '/' . esc($uuid) . '/large/' . esc($img['filename'])); ?>"
                                   target="_blank"
                                   class="gallery-one-link d-block">
                                    <img src="<?= base_url('images/backend/' . $entity . '/' . esc($uuid) . '/medium/' . esc($img['filename'])); ?>"
                                         alt="Image"
                                         class="img-fluid w-100 d-block">

                                         <?php if ((int) $img['is_cover'] === 1): ?>
                                             <!-- Usiamo direttamente l'icona con un'ombra nativa per massima pulizia grafica -->
                                             <i class="fa-solid fa-circle-check fa-2x position-absolute top-0 start-0 m-2 text-white shadow"></i>
                                         <?php endif; ?>
                                </a>
                                <?php if ((int) $img['is_cover'] === 1): ?>
                                    <div class="gallery-one-overlay d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-circle-check fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-danger fw-bold py-3">
                    <?= lang('backend/components/galleryOneImg.noImagesFound'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>