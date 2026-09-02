<!-- Modale nascosto per la configurazione dell'esportazione PDF valido in tutte le viste show -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-export"></i> <?= lang('backend/global.modals.exportPdfTitle'); ?>
                </h5>
            </div>

            <div class="modal-body">
                <form id="exportPdfForm">

                    <!-- Scelta dell'orientamento con icone FontAwesome -->
                    <div class="row mb-3 text-center">
                        <div class="col-12 mb-2 text-start">
                            <span class="lead fw-bold">
                                <i class="fa-solid fa-arrow-circle-down"></i> <?= lang('backend/global.modals.exportPdfOrientation'); ?>
                            </span>
                        </div>
                        <div class="col-6">
                            <label class="cursor-pointer w-100" for="pdf_portrait">
                                <i class="fa-solid fa-file-lines fa-3x text-secondary mb-2"></i>
                                <div class="form-check d-flex justify-content-center align-items-center mt-2">
                                    <input class="form-check-input me-2 mt-0" type="radio" name="pdf_orientation" id="pdf_portrait" value="portrait" checked>
                                    <span class="fw-bold"><?= lang('backend/global.modals.exportPdfOrientationVert'); ?></span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="col-6">
                            <label class="cursor-pointer w-100" for="pdf_landscape">
                                <!-- Icona ruotata per simulare il foglio in orizzontale -->
                                <i class="fa-solid fa-file-lines fa-3x text-secondary mb-2" style="transform: rotate(-90deg);"></i>
                                <div class="form-check d-flex justify-content-center align-items-center mt-2">
                                    <input class="form-check-input me-2 mt-0" type="radio" name="pdf_orientation" id="pdf_landscape" value="landscape">
                                    <span class="fw-bold"><?= lang('backend/global.modals.exportPdfOrientationHoriz'); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Scelta del Formato -->
                    <div class="row mb-3">
                        <div class="col-12 text-start mb-2">
                            <span class="lead fw-bold">
                                <i class="fa-solid fa-arrow-circle-down"></i> <?= lang('backend/global.modals.exportPdfFormat'); ?>
                            </span>
                        </div>
                        <div class="col-12">
                            <select class="form-select form-select-sm" id="pdf_format" name="pdf_format">
                                <option value="a3">A3</option>
                                <option value="a4" selected>A4</option>
                                <option value="a5">A5</option>
                                <option value="letter">Letter</option>
                                <option value="legal">Legal</option>
                                <option value="tabloid">Tabloid</option>
                            </select>
                        </div>
                    </div>

                    <!-- Scelta dei Margini per lato -->
                    <div class="row mb-3">
                        <div class="col-12 text-start mb-2">
                            <span class="lead fw-bold">
                                <i class="fa-solid fa-arrow-circle-down"></i> <?= lang('backend/global.modals.exportPdfMargin'); ?>
                            </span>
                        </div>
                        <div class="col-12">
                            <div class="row g-2">
                                <div class="col-3">
                                    <label for="pdf_margin_top" class="form-label small mb-0"><?= lang('backend/global.modals.exportPdfMarginSup'); ?></label>
                                    <input type="number" class="form-control form-control-sm text-center" id="pdf_margin_top" name="pdf_margin_top" value="10" min="0">
                                </div>
                                <div class="col-3">
                                    <label for="pdf_margin_right" class="form-label small mb-0"><?= lang('backend/global.modals.exportPdfMarginRgt'); ?></label>
                                    <input type="number" class="form-control form-control-sm text-center" id="pdf_margin_right" name="pdf_margin_right" value="10" min="0">
                                </div>
                                <div class="col-3">
                                    <label for="pdf_margin_bottom" class="form-label small mb-0"><?= lang('backend/global.modals.exportPdfMarginInf'); ?></label>
                                    <input type="number" class="form-control form-control-sm text-center" id="pdf_margin_bottom" name="pdf_margin_bottom" value="10" min="0">
                                </div>
                                <div class="col-3">
                                    <label for="pdf_margin_left" class="form-label small mb-0"><?= lang('backend/global.modals.exportPdfMarginLft'); ?></label>
                                    <input type="number" class="form-control form-control-sm text-center" id="pdf_margin_left" name="pdf_margin_left" value="10" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scelta della Qualità (Compressione JPEG) -->
                    <div class="row mb-3">
                        <div class="col-12 text-start mb-2">
                            <span class="lead fw-bold">
                                <i class="fa-solid fa-arrow-circle-down"></i> <?= lang('backend/global.modals.exportPdfCompression'); ?>
                            </span>
                        </div>
                        <div class="col-12">
                            <label for="pdf_quality" class="form-label fw-bold d-flex justify-content-between">
                                <span><?= lang('backend/global.modals.exportPdfImgQuality'); ?></span>
                                <span id="pdf_quality_label">80%</span>
                            </label>
                            <input type="range" class="form-range form-range-sm" id="pdf_quality" name="pdf_quality" min="10" max="100" step="10" value="80">
                        </div>
                    </div>

                </form>
            </div>
            
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i> <?= lang('backend/global.buttons.close'); ?>
            </button>
                <button type="submit" class="btn btn-success btn-sm" form="exportPdfForm">
                    <i class="fa-solid fa-file-export"></i> <?= lang('backend/global.buttons.exportPdf'); ?>
            </button>
            </div>

        </div>
    </div>
</div>