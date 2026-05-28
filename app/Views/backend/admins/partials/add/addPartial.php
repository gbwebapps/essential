<!-- Struttura principale del form di inserimento (Partial) -->
<div class="row">
    <div class="col-8 offset-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="admins_add">

                <!-- Sezione: Dati Anagrafici e Contatti -->
                <div id="general_data">

                    <div class="card-header">
                        <h2 class="card-title text-center text-lg-start mb-0"><?= lang('backend/admins.panels.general_data'); ?></h2>
                    </div>

                    <div class="card-body">
                        <!-- Nome e Cognome -->
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-2">
                                    <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.firstname'); ?></label>
                                    <input type="text" id="firstname" name="firstname" class="form-control" placeholder="<?= lang('backend/admins.placeholders.firstname'); ?>">
                                    <!-- Contenitore dinamico per l'errore di validazione -->
                                    <div class="error_firstname text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.lastname'); ?></label>
                                    <input type="text" id="lastname" name="lastname" class="form-control" placeholder="<?= lang('backend/admins.placeholders.lastname'); ?>">
                                    <div class="error_lastname text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>

                        <!-- Email e Telefono -->
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-2">
                                    <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.email'); ?></label>
                                    <input type="text" id="email" name="email" class="form-control" placeholder="<?= lang('backend/admins.placeholders.email'); ?>">
                                    <div class="error_email text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.phone'); ?></label>
                                    <input type="text" id="phone" name="phone" class="form-control" placeholder="<?= lang('backend/admins.placeholders.phone'); ?>">
                                    <div class="error_phone text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>

                        <!-- Stato Attivazione -->
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-2">
                                    <label for="status" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.status'); ?>
                                    </label>
                                    <select name="status" class="form-select" id="status">
                                        <option value="1"><?= lang('backend/admins.labels.active'); ?></option>
                                        <option value="0"><?= lang('backend/admins.labels.unactive'); ?></option>
                                    </select>
                                    <div class="error_status text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>

                        <!-- Note Aggiuntive -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">
                                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.note'); ?></label>
                                    <textarea name="note" id="note" rows="7" class="form-control"></textarea>
                                    <div class="error_note text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="upload_preview">
                    <!-- Inclusione del componente per la preview delle immagini -->
                    <!-- $this->include('backend/components/uploadPreviewImg/uploadPreviewImgView'); -->
                </div>

                <!-- Sezione: Caricamento Documentazione Allegata -->
                <div id="upload_documents">
                    <!-- Inclusione del componente per la gestione dei documenti -->
                    <!-- $this->include('backend/components/uploadPreviewDoc/uploadPreviewDocView'); -->
                </div>

            </form>

        </div>
    </div>
</div>