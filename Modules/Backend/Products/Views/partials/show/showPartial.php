<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card" id="show-wrapper">

            <!-- Dati generali -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                <h2 class="card-title text-start mb-0"><?= lang('backend/products.panels.generalData'); ?></h2>
                <form id="getGeneralData">
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                    <input type="hidden" name="context" value="show">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/products.buttons.reload'); ?>
                    </button>
                </form>
            </div>

            <div id="generalData">
                <?= $this->include(\Modules\Backend\Products\Config\Views::SHOW_GENERAL_DATA_PARTIAL, $this->data); ?>
            </div>
            <!-- End Dati generali -->

            <!-- Galleria immagini -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= lang('backend/components/galleryOne.title'); ?></h2>
                <form id="getImages">
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                    <input type="hidden" name="entity" value="<?= esc($entity, 'attr'); ?>">
                    <input type="hidden" name="context" value="<?= esc($context, 'attr'); ?>">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/components/galleryOne.buttons.reload'); ?>
                    </button>
                </form>
            </div>

            <div id="galleryOne">
                <?= $this->include('backend/components/galleryOne/galleryOneView', $this->data); ?>
            </div>
            <!-- End Galleria immagini -->

            <!-- Metadati -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= lang('backend/products.panels.metaData'); ?></h2>
                <form id="getMetaData">
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/products.buttons.reload'); ?>
                    </button>
                </form>
            </div>

            <div id="metaData">
                <?= $this->include(\Modules\Backend\Products\Config\Views::META_DATA_PARTIAL, $this->data); ?>
            </div>
            <!-- End Metadati -->

        </div>

    </div>
</div>
