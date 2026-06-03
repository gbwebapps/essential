<div class="row">
    <div class="col-8 offset-2">
        <div class="card">

            <!-- General Data -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.generalData'); ?></h2>
                <form id="getGeneralData">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                    <input type="hidden" name="context" value="show" form="getGeneralData">
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </form>
            </div>
            <div id="generalData">
                <?= $this->include('backend/admins/partials/show/generalDataPartial', $this->data); ?>
            </div>
            <!-- End General Data -->

            <!-- Gallery One -->
            <div id="galleryOne">
                <!-- $this->include('backend/components/galleryOneImg/galleryOneImgView', $this->data); -->
            </div>
            <!-- End Gallery One -->

            <!-- Meta Data -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.metaData'); ?></h2>
                <form id="getMetaData">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </form>
            </div>
            <div id="metaData">
                <?= $this->include('backend/admins/partials/common/metaDataPartial', $this->data); ?>
            </div>
            <!-- End Meta Data -->

        </div>
    </div>
</div>