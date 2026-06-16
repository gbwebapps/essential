<!-- Struttura principale del form di aggiornamento (Partial) -->

<!-- Form per aggiornamento dati generali -->
<form id="getGeneralData"></form>

<!-- Form per aggiornamento metadati -->
<form id="getMetaData"></form>

<!-- Form per aggiornamento permessi -->
<form id="getPermissions"></form>

<div class="row">
    <div class="col-8 offset-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="admins_edit">

                <!-- General Data -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.generalData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>" form="getGeneralData">
                    <input type="hidden" name="context" value="edit" form="getGeneralData">
                    <button type="submit" class="btn btn-sm btn-secondary" form="getGeneralData">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </div>
                <div id="generalData">
                    <?= $this->include('backend/admins/partials/edit/generalDataPartial', $this->data); ?>
                </div>
                <!-- End General Data -->

                <!-- Permissions -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.permissions'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>" form="getPermissions">
                    <input type="hidden" name="context" value="edit" form="getPermissions">
                    <button type="submit" class="btn btn-sm btn-secondary" form="getPermissions">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </div>
                <div id="permissions">
                    <?= $this->include('backend/admins/partials/edit/permissionsPartial'); ?>
                </div>
                <!-- End Permissions -->

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="uploadPreview">
                    <!-- Inclusione del componente per la preview delle immagini -->
                    <!-- $this->include('backend/components/uploadPreviewImg/uploadPreviewImg_view'); -->
                </div>
                <!-- Fine Sezione: Upload e Preview Immagini Profilo -->

                <!-- Sezione: Caricamento Documentazione Allegata -->
                <div id="uploadDocuments">
                    <!-- Inclusione del componente per la gestione dei documenti -->
                    <!-- $this->include('backend/components/uploadPreviewDoc/uploadPreviewDoc_view'); -->
                </div>
                <!-- Fine Sezione: Caricamento Documentazione Allegata -->

                <!-- Meta Data -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.metaData'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>" form="getMetaData">
                    <button type="submit" class="btn btn-sm btn-secondary" form="getMetaData">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </div>
                <div id="metaData">
                    <?= $this->include('backend/admins/partials/common/metaDataPartial', $this->data); ?>
                </div>
                <!-- End Meta Data -->

                <!-- UUID -->
                <input type="hidden" name="uuid" id="uuid" value="<?= esc($admin->uuid); ?>">

            </form>

        </div>
    </div>
</div>