<!-- Struttura principale del form di aggiornamento -->

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
            <form id="products-edit" data-unsaved-check>

                <!-- Dati generali -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0"><?= ui_text('backend/products.panels.generalData', [], false); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>" form="getGeneralData">
                    <input type="hidden" name="context" value="edit" form="getGeneralData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getGeneralData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/products.buttons.reload', [], false); ?>
                    </button>
                </div>

                <div id="generalData">
                    <?= $this->include(\Modules\Backend\Products\Config\Views::EDIT_GENERAL_DATA_PARTIAL, $this->data); ?>
                </div>
                <!-- End Dati generali -->

                <!-- Upload e anteprima immagini -->
                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>
                <!-- End Upload e anteprima immagini -->

                <!-- Galleria immagini -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= ui_text('backend/components/galleryOne.title', [], false); ?></h2>
                    <input form="getImages" type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="entity" value="<?= esc($entity, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="context" value="<?= esc($context, 'attr'); ?>">
                    <button form="getImages" type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/components/galleryOne.buttons.reload', [], false); ?>
                    </button>
                </div>

                <div id="galleryOne">
                    <?= $this->include('backend/components/galleryOne/galleryOneView', $this->data); ?>
                </div>
                <!-- End Galleria immagini -->

                <!-- Metadati -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= ui_text('backend/products.panels.metaData', [], false); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>" form="getMetaData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getMetaData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/products.buttons.reload', [], false); ?>
                    </button>
                </div>

                <div id="metaData">
                    <?= $this->include(\Modules\Backend\Products\Config\Views::META_DATA_PARTIAL, $this->data); ?>
                </div>
                <!-- End Metadati -->

                <!-- UUID immutabile del prodotto -->
                <input type="hidden" name="uuid" id="uuid" value="<?= esc($product->uuid, 'attr'); ?>">

            </form>

        </div>

    </div>
</div>
