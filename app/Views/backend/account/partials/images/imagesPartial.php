<!-- Sezione: Upload e Preview Immagini Profilo -->
<div id="uploadPreview">
    <?= $this->include('backend/components/uploadPreviewImg/uploadPreviewImgView'); ?>
</div>
<!-- End Sezione: Upload e Preview Immagini Profilo -->

<!-- Gallery One -->
<div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
    <h2 class="card-title text-start mb-0"><?= lang('backend/components/galleryOneImg.title'); ?></h2>
    <form id="getImages">
        <input type="hidden" name="uuid" value="<?= esc($currentAdmin->uuid); ?>">
        <input type="hidden" name="entity" value="<?= esc($entity); ?>">
        <input type="hidden" name="context" value="<?= esc($context); ?>">
        <button type="submit" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/components/galleryOneImg.buttons.reload'); ?>
        </button>
    </form>
</div>
<div id="galleryOne">
    <?= $this->include('backend/components/galleryOneImg/galleryOneImgView', $this->data); ?>
</div>
<!-- End Gallery One -->