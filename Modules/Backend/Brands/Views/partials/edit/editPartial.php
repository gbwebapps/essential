<!-- Struttura principale del form di aggiornamento (Partial) -->

<!-- Form per aggiornamento dati generali -->
<form id="getGeneralData"></form>

<!-- Form per aggiornamento metadati -->
<form id="getMetaData"></form>

<!-- Form per aggiornamento immagini -->
<form id="getImages"></form>

<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="brands-edit" data-unsaved-check>

                <!-- General Data -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/brands.panels.generalData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>" form="getGeneralData">
                    <input type="hidden" name="context" value="edit" form="getGeneralData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getGeneralData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/brands.buttons.reload'); ?>
                    </button>
                </div>
                <div id="generalData">
                    <?= $this->include(\Modules\Backend\Brands\Config\Views::EDIT_GENERAL_DATA_PARTIAL, $this->data); ?>
                </div>
                <!-- End General Data -->

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>
                <!-- Fine Sezione: Upload e Preview Immagini Profilo -->

                <!-- Gallery One -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/components/galleryOne.title'); ?></h2>
                    <input form="getImages" type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="entity" value="<?= esc($entity, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="context" value="<?= esc($context, 'attr'); ?>">
                    <button form="getImages" type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/components/galleryOne.buttons.reload'); ?>
                    </button>
                </div>
                <div id="galleryOne">
                    <?= $this->include('backend/components/galleryOne/galleryOneView', $this->data); ?>
                </div>
                <!-- End Gallery One -->

                <!-- Meta Data -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/brands.panels.metaData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>" form="getMetaData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getMetaData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/brands.buttons.reload'); ?>
                    </button>
                </div>
                <div id="metaData">
                    <?= $this->include(\Modules\Backend\Brands\Config\Views::META_DATA_PARTIAL, $this->data); ?>
                </div>
                <!-- End Meta Data -->

                <!-- UUID -->
                <input type="hidden" name="uuid" id="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">

            </form>

        </div>

    </div>
</div>