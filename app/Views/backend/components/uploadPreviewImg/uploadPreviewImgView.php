<style>
    .drop-zone-custom {
        height: 4.5rem; 
        border: 2px dashed #adb5bd; /* Grigio Bootstrap intermedio */
        border-radius: 0.375rem; /* Bordo arrotondato standard Bootstrap */
        background-color: #f8f9fa; /* Sfondo bg-light */
        transition: background-color 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
    }

    /* Attivata via JS quando il file è sopra l'area */
    .drop-zone-custom.is-dragover {
        background-color: #e9ecef;
        border-color: #6c757d;
    }
</style>

<!-- Definizione della variabile per capire se la vista è standalone o dentro un form -->
<?php $saveImages = $saveImages ?? false; ?>

<div class="card-header <?= ( ! $saveImages) ? 'rounded-0' : ''; ?>" <?= ( ! $saveImages) ? 'style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);"' : ''; ?>>

    <div class="row">
        <div class="col-6">
            <h2 class="card-title text-center text-lg-start mb-0">
                <?= lang('backend/components/uploadPreviewImg.title'); ?>
            </h2>
        </div>
        <div class="col-6 d-flex justify-content-end align-items-center">
            <div>
                <input type="file" name="images[]" id="inputImages" style="display: none;" multiple>
                <button type="button" class="btn btn-sm btn-secondary" id="buttonImages">
                    <i class="fa-solid fa-arrow-pointer me-1"></i><?= lang('backend/components/uploadPreviewImg.buttons.uploadImages'); ?>
                </button>
            </div>

            <?php if ($saveImages): ?>
                <div class="ms-1">
                    <form id="saveImages">
                        <input type="hidden" name="uuid" value="<?= esc($uuid); ?>">
                        <input type="hidden" name="entity" value="<?= esc($entity); ?>">
                        <input type="hidden" name="context" value="<?= esc($context); ?>">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fa-solid fa-upload me-1"></i> <?= lang('backend/components/uploadPreviewImg.buttons.sendImages'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<div class="card-body pb-0"> 
    <div class="row">
        <div class="col-lg-12">
            
            <!-- 1. Area Drop Zone indipendente dal ciclo di vita delle anteprime -->
            <div id="drop-zone-area" class="drop-zone-custom d-flex align-items-center justify-content-center p-3 mb-3 text-muted w-100">
                <span class="fw-bold">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Trascina le immagini per caricarle.
                </span>
            </div>

            <!-- 2. Contenitore esclusivo per l'iniezione JS delle miniature -->
            <div id="previewImages" class="row justify-content-center align-items-center" data-required-text="<?= lang('backend/components/uploadPreviewImg.messages.notImagesSelected'); ?>"></div>
            
        </div>
    </div>
</div>