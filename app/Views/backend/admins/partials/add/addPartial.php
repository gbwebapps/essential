<!-- Struttura principale del form di inserimento (Partial) -->
<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="admins-add" data-unsaved-check>

                <!-- General Data -->
                <div id="generalData">

                    <div class="card-header">
                        <h2 class="card-title text-center text-lg-start mb-0"><?= ui_text('backend/admins.panels.generalData', [], false); ?></h2>
                    </div>

                    <div class="card-body">

                        <!-- Nome e Cognome -->
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.firstname', [], false); ?></label>
                                    <input <?= ui_binding('backend/admins.placeholders.firstname', 'placeholder', [], true); ?> type="text" id="firstname" name="firstname" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.firstname'), 'attr'); ?>">
                                    <!-- Contenitore dinamico per l'errore di validazione -->
                                    <div class="error_firstname app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.lastname', [], false); ?></label>
                                    <input <?= ui_binding('backend/admins.placeholders.lastname', 'placeholder', [], true); ?> type="text" id="lastname" name="lastname" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.lastname'), 'attr'); ?>">
                                    <div class="error_lastname app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Nome e Cognome -->

                        <!-- Email e Telefono -->
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.email', [], false); ?></label>
                                    <input <?= ui_binding('backend/admins.placeholders.email', 'placeholder', [], true); ?> type="text" id="email" name="email" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.email'), 'attr'); ?>">
                                    <div class="error_email app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.phone', [], false); ?></label>
                                    <input <?= ui_binding('backend/admins.placeholders.phone', 'placeholder', [], true); ?> type="text" id="phone" name="phone" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.phone'), 'attr'); ?>">
                                    <div class="error_phone app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Email e Telefono -->

                        <!-- Gruppo e Stato Attivazione -->
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="group_id" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.group', [], false); ?>
                                    </label>
                                    <select name="group_id" class="form-select" id="group_id">
                                        <?php if (! empty($groups)): ?>
                                            <?php foreach ($groups as $group): ?>
                                                <option value="<?= esc($group->id, 'attr'); ?>"><?= esc($group->name); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="error_group_id app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="status" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.status', [], false); ?>
                                    </label>
                                    <select name="status" class="form-select" id="status">
                                        <option <?= ui_binding('backend/admins.labels.active', 'text', [], false); ?> value="1"><?= lang('backend/admins.labels.active'); ?></option>
                                        <option <?= ui_binding('backend/admins.labels.unactive', 'text', [], false); ?> value="0"><?= lang('backend/admins.labels.unactive'); ?></option>
                                    </select>
                                    <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Stato Attivazione -->

                        <!-- Note Aggiuntive -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">
                                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/admins.labels.note', [], false); ?></label>
                                    <textarea <?= ui_binding('backend/admins.placeholders.note', 'placeholder', [], true); ?> name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.note'), 'attr'); ?>"></textarea>
                                    <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Note Aggiuntive -->

                    </div>

                </div>
                <!-- End General Data -->

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>
                <!-- End Sezione: Upload e Preview Immagini Profilo -->

            </form>

        </div>
    </div>
</div>
