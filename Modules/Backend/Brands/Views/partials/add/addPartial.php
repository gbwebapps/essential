<!-- Struttura principale del form di inserimento (Partial) -->
<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="brands-add" data-unsaved-check>

                <!-- General Data -->
                <div id="generalData">

                    <div class="card-header">
                        <h2 class="card-title text-center text-lg-start mb-0"><?= lang('backend/brands.panels.generalData'); ?></h2>
                    </div>

                    <div class="card-body">

                        <!-- Brand e Status -->
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="brand" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.brand'); ?></label>
                                    <input type="text" id="brand" name="brand" class="form-control" placeholder="<?= esc(lang('backend/brands.placeholders.brand'), 'attr'); ?>">
                                    <!-- Contenitore dinamico per l'errore di validazione -->
                                    <div class="error_brand app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="status" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.status'); ?>
                                    </label>
                                    <select name="status" class="form-select" id="status">
                                        <option value="1"><?= lang('backend/brands.labels.active'); ?></option>
                                        <option value="0"><?= lang('backend/brands.labels.unactive'); ?></option>
                                    </select>
                                    <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Brand e Status -->

                        <!-- Note Aggiuntive -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">
                                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.note'); ?></label>
                                    <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/brands.placeholders.note'), 'attr'); ?>"></textarea>
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