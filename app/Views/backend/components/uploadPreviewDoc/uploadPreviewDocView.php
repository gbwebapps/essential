<?php $save_documents = (isset($save_documents)) ? $save_documents : false; ?>

<div class="card-header<?= (( ! $save_documents) ? ' rounded-0 ' : ' '); ?>d-flex justify-content-between align-items-center" style="<?= (( ! $save_documents) ? 'border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);' : ''); ?>">
    <h2 class="card-title text-center text-lg-start mb-0">
        <i class="fa-solid fa-upload"></i>
        <?= lang('backend/uploadPreviewDoc.panel'); ?>
    </h2>

    <?= (($save_documents) ? '<div class="d-flex justify-content-end align-items-center">' : ''); ?>

    <div<?= (($save_documents) ? ' class="me-1 "' : ''); ?>>
        <input type="file" name="documents[]" id="inputDocuments" style="display: none;" multiple>
        <button type="button" class="btn btn-sm btn-secondary" id="buttonDocuments">
            <i class="fa-solid fa-arrow-pointer"></i><?= lang('backend/uploadPreviewDoc.upload_documents'); ?>
        </button>
    </div>

    <?php if($save_documents): ?>

        <div class="ms-1">
            <form id="save_documents">
                <input type="hidden" name="uuid" value="<?= esc($uuid); ?>">
                <input type="hidden" name="entity" value="<?= esc($entity); ?>">
                <input type="hidden" name="context" value="<?= esc($context); ?>">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-upload"></i><?= lang('backend/uploadPreviewDoc.send_documents'); ?>
                </button>
            </form>
        </div>

    <?php endif; ?>

    <?= (($save_documents) ? '</div>' : ''); ?>

</div>
<div class="card-body">
    <div class="row">
        <div class="col-12">
            <div id="preview_documents" class="row" aria-live="polite" data-empty-text="<?= lang('backend/uploadPreviewDoc.upload_documents_preview'); ?>" data-required-text="<?= lang('backend/uploadPreviewDoc.not_documents_selected'); ?>">
                <div class="d-flex justify-content-center align-items-center">
                    <i class="fa-solid fa-file-signature"></i><?= lang('backend/uploadPreviewDoc.upload_documents_preview'); ?>
                </div>
            </div>
        </div>
    </div>
</div>