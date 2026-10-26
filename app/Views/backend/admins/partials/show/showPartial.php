<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">
        <div class="card" id="show-wrapper">

            <!-- General Data -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                <h2 class="card-title text-start mb-0"><?= ui_text('backend/admins.panels.generalData', [], false); ?></h2>
                <form id="getGeneralData">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                    <input type="hidden" name="context" value="show">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/admins.buttons.reload', [], false); ?>
                    </button>
                </form>
            </div>
            <div id="generalData">
                <?= $this->include('backend/admins/partials/show/generalDataPartial', $this->data); ?>
            </div>
            <!-- End General Data -->

            <!-- Permissions -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= ui_text('backend/admins.panels.permissions', [], false); ?></h2>
                <form id="getPermissions">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                    <input type="hidden" name="context" value="show">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/admins.buttons.reload', [], false); ?>
                    </button>
                </form>
            </div>
            <div id="permissions">
                <?= $this->include('backend/admins/partials/show/permissionsPartial', $this->data); ?>
            </div>
            <!-- End Permissions -->

            <!-- Tokens -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= ui_text('backend/admins.panels.tokens', [], false); ?></h2>
                <form id="getTokens">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/admins.buttons.reload', [], false); ?>
                    </button>
                </form>
            </div>
            <div id="tokens">
                <?= $this->include('backend/admins/partials/show/tokensPartial', $this->data); ?>
            </div>
            <!-- End Tokens -->

            <!-- Gallery One -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= ui_text('backend/components/galleryOne.title', [], false); ?></h2>
                <form id="getImages">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                    <input type="hidden" name="entity" value="<?= esc($entity, 'attr'); ?>">
                    <input type="hidden" name="context" value="<?= esc($context, 'attr'); ?>">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/components/galleryOne.buttons.reload', [], false); ?>
                    </button>
                </form>
            </div>
            <div id="galleryOne">
                <?= $this->include('backend/components/galleryOne/galleryOneView', $this->data); ?>
            </div>
            <!-- End Gallery One -->

            <!-- Meta Data -->
            <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                <h2 class="card-title text-start mb-0"><?= ui_text('backend/admins.panels.metaData', [], false); ?></h2>
                <form id="getMetaData">
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                    <button type="submit" class="btn btn-sm app-btn-secondary">
                        <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/admins.buttons.reload', [], false); ?>
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
