<form id="getGeneralData"></form>
<form id="getMetaData"></form>
<form id="getImages"></form>

<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">
        <div class="card">
            <form id="products-edit" data-unsaved-check>
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/products.panels.generalData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>" form="getGeneralData">
                    <input type="hidden" name="context" value="edit" form="getGeneralData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getGeneralData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/products.buttons.reload'); ?>
                    </button>
                </div>
                <div id="generalData">
                    <?= $this->include(\Modules\Backend\Products\Config\Views::EDIT_GENERAL_DATA_PARTIAL, $this->data); ?>
                </div>

                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>

                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/components/galleryOne.title'); ?></h2>
                    <input form="getImages" type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="entity" value="<?= esc($entity, 'attr'); ?>">
                    <input form="getImages" type="hidden" name="context" value="<?= esc($context, 'attr'); ?>">
                    <button form="getImages" type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/components/galleryOne.buttons.reload'); ?>
                    </button>
                </div>
                <div id="galleryOne">
                    <?= $this->include('backend/components/galleryOne/galleryOneView', $this->data); ?>
                </div>

                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/products.panels.metaData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>" form="getMetaData">
                    <button type="submit" class="btn btn-sm app-btn-secondary" form="getMetaData">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= lang('backend/products.buttons.reload'); ?>
                    </button>
                </div>
                <div id="metaData">
                    <?= $this->include(\Modules\Backend\Products\Config\Views::META_DATA_PARTIAL, $this->data); ?>
                </div>

                <input type="hidden" name="uuid" id="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
            </form>
        </div>
    </div>
</div>
