<?php $save_images = (isset($save_images)) ? $save_images : false; ?>

<div class="card-header<?= (( ! $save_images) ? ' rounded-0 ' : ' '); ?>d-flex justify-content-between align-items-center" style="<?= (( ! $save_images) ? 'border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);' : ''); ?>">
    <h2 class="card-title text-center text-lg-start mb-0">
        <i class="fa-solid fa-upload"></i>
        <?= lang('backend/uploadPreviewImg.panel'); ?>
    </h2>

    <?= (($save_images) ? '<div class="d-flex justify-content-end align-items-center">' : ''); ?>

    <div<?= (($save_images) ? ' class="me-1 "' : ''); ?>>
        <input type="file" name="images[]" id="inputImages" style="display: none;" multiple>
        <button type="button" class="btn btn-sm btn-secondary" id="buttonImages">
            <i class="fa-solid fa-arrow-pointer"></i><?= lang('backend/uploadPreviewImg.upload_images'); ?>
        </button>
    </div>

        <?php if($save_images): ?>

            <div class="ms-1">
                <form id="save_images">
                    <?= csrf_field() ?>
                    <input type="hidden" name="uuid" value="<?= esc($uuid); ?>">
                    <input type="hidden" name="entity" value="<?= esc($entity); ?>">
                    <input type="hidden" name="context" value="<?= esc($context); ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-upload"></i><?= lang('backend/uploadPreviewImg.send_images'); ?>
                    </button>
                </form>
            </div>

        <?php endif; ?>

    <?= (($save_images) ? '</div>' : ''); ?>

</div>
<div class="card-body">
    <div class="row">
        <div class="col-12">
            <div id="preview_images" class="row" aria-live="polite" data-empty-text="<?= lang('backend/uploadPreviewImg.upload_images_preview'); ?>" data-required-text="<?= lang('backend/uploadPreviewImg.not_images_selected'); ?>">
                <div class="d-flex justify-content-center align-items-center">
                    <i class="fa-solid fa-image"></i><?= lang('backend/uploadPreviewImg.upload_images_preview'); ?>
                </div>
            </div>
        </div>
    </div>
</div>