<!-- Struttura principale del form di aggiornamento (Partial) -->

<!-- Form per aggiornamento dati generali -->
<form id="get_general_data">
    <?= csrf_field() ?>
</form>

<!-- Form per aggiornamento metadati -->
<form id="get_meta_data">
    <?= csrf_field() ?>
</form>

<div class="row">
    <div class="col-8 offset-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="admins_edit">
                <!-- Generazione del token di sicurezza CSRF -->
                <?= csrf_field() ?>

                <!-- Sezione: Dati Anagrafici e Contatti -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.general_data'); ?></h2>
                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>" form="get_general_data">
                    <input type="hidden" name="context" value="edit">
                    <button type="submit" class="btn btn-sm btn-secondary" form="get_general_data">
                        <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                    </button>
                </div>
                <div id="general_data">
                    <?= $this->include('backend/admins/partials/edit/generalDataPartial', $this->data); ?>
                </div>

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="upload_preview">
                    <!-- Inclusione del componente per la preview delle immagini -->
                    <!-- $this->include('backend/components/uploadPreviewImg/uploadPreviewImg_view'); -->
                </div>

                <!-- Sezione: Caricamento Documentazione Allegata -->
                <div id="upload_documents">
                    <!-- Inclusione del componente per la gestione dei documenti -->
                    <!-- $this->include('backend/components/uploadPreviewDoc/uploadPreviewDoc_view'); -->
                </div>

                <!-- Meta Data -->
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
                    <h2 class="card-title text-start mb-0"><?= lang('backend/admins.panels.meta_data'); ?></h2>
                    <div>                            
                        <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>" form="get_meta_data">
                        <button type="submit" class="btn btn-sm btn-secondary" form="get_meta_data">
                            <i class="fa-solid fa-arrows-rotate"></i><?= lang('backend/admins.buttons.reload'); ?>
                        </button>
                    </div>
                </div>
                <div id="meta_data">
                    <?= $this->include('backend/admins/partials/common/metaDataPartial', $this->data); ?>
                </div>
                <!-- End Meta Data -->

                <!-- UUID -->
                <input type="hidden" name="uuid" id="uuid" value="<?= esc($admin->uuid); ?>">

            </form>

        </div>
    </div>
</div>