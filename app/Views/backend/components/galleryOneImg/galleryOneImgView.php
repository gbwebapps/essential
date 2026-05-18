<div id="images_data">
    <div class="card-body">
        <div class="row" id="galleryOneImg-container-<?= esc($uuid); ?>">
            <?php if ( ! empty($images) && is_array($images)): ?>
                <?php foreach ($images as $img): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3 mb-3 text-center">
                        <div class="gallery-one-container-image position-relative"
                             data-id="<?= esc($img->id); ?>"
                             data-uuid="<?= esc($uuid); ?>"
                             data-entity="<?= esc($entity); ?>"
                             data-context="<?= esc($context); ?>"
                             data-csrf-name="<?= config('security.csrf_name'); ?>"
                             data-csrf-value="<?= esc($app->getSecurity()->generateCsrfToken()); ?>">
                            <?php if ($context === 'edit'): ?>
                                <img src="<?= base_url('images/' . $entity . '/' . esc($uuid) . '/medium/' . esc($img->filename)); ?>"
                                     alt="Image"
                                     class="img-thumbnail mw-100 h-auto overGalleryOneImg">
                                <?php if ($img->is_cover === 1): ?>
                                    <div class="gallery-one-checked">
                                        <i class="fa-solid fa-check-circle fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="gallery-one-overlay d-flex justify-content-center align-items-center">
                                    <?php if ($img->is_cover === 1): ?>
                                        <i class="fa-solid fa-minus galleryOneImgAction text-white mx-2"
                                           title="<?= lang('backend.galleryOneImg.remove_cover'); ?>"
                                           data-action="remove"
                                           data-message="<?= lang('backend.galleryOneImg.sure_remove_cover'); ?>">
                                        </i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-check galleryOneImgAction text-white mx-2"
                                           title="<?= lang('backend.galleryOneImg.set_cover'); ?>"
                                           data-action="set"
                                           data-message="<?= lang('backend.galleryOneImg.sure_set_cover'); ?>">
                                        </i>
                                    <?php endif; ?>
                                    <i class="fa-solid fa-trash galleryOneImgAction text-white mx-2"
                                       title="<?= lang('backend.galleryOneImg.delete'); ?>"
                                       data-action="delete"
                                       data-message="<?= lang('backend.galleryOneImg.sure_delete_image'); ?>">
                                    </i>
                                    <a href="<?= base_url('images/' . $entity . '/' . esc($uuid) . '/large/' . esc($img->filename)); ?>"
                                       target="_blank"
                                       class="text-white mx-2"
                                       title="<?= lang('backend.galleryOneImg.view_image'); ?>">
                                        <i class="fa-solid fa-image"></i>
                                    </a>
                                </div>
                            <?php elseif ($context === 'show'): ?>
                                <a href="<?= base_url('images/' . $entity . '/' . esc($uuid) . '/large/' . esc($img->filename)); ?>"
                                   target="_blank"
                                   class="gallery-one-link">
                                    <img src="<?= base_url('images/' . $entity . '/' . esc($uuid) . '/medium/' . esc($img->filename)); ?>"
                                         alt="Image"
                                         class="img-thumbnail mw-100 h-auto overGalleryOneImg">
                                </a>
                                <?php if ($img->is_cover === 1): ?>
                                    <div class="gallery-one-checked">
                                        <i class="fa-solid fa-check-circle fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-danger fw-bold">
                    <?= lang('backend.galleryOneImg.no_images_found'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
