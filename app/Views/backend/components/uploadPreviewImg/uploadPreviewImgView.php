<!-- Definizione della variabile per capire se la vista è standalone o dentro un form -->
<?php $saveImages = $saveImages ?? false; ?>

<div class="card-header <?= ( ! $saveImages) ? 'rounded-0' : ''; ?> d-flex justify-content-between align-items-center" <?= ( ! $saveImages) ? 'style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);"' : ''; ?>>
    <h2 class="card-title text-center text-lg-start mb-0">
        <?= lang('backend/components/uploadPreviewImg.panel'); ?>
    </h2>

    <?php if ($saveImages): ?>
        <div class="ms-1">
            <form id="saveImages">
                <input type="hidden" name="uuid" value="<?= esc($uuid); ?>">
                <input type="hidden" name="entity" value="<?= esc($entity); ?>">
                <input type="hidden" name="context" value="<?= esc($context); ?>">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-upload"></i> <?= lang('backend/components/uploadPreviewImg.sendImages'); ?>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div>
            <input type="file" name="images[]" id="inputImages" style="display: none;" multiple>
            <button type="button" class="btn btn-sm btn-secondary" id="buttonImages">
                <i class="fa-solid fa-arrow-pointer"></i><?= lang('backend/components/uploadPreviewImg.uploadImages'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="card-body"> 
    <div class="row">
        <div class="col-lg-12">
            <div id="previewImages" class="row justify-content-center align-items-center" data-required-text="<?= lang('backend/components/uploadPreviewImg.notImagesSelected'); ?>"></div>
        </div>
    </div>
</div>