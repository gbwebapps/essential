<!-- Modale nascosto per la conferma di azioni (funge da alert alternativo a quello di default del browser) -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><?= ui_text('backend/global.modals.globalTitle', [], false); ?></h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-sm app-btn-danger btn-cancel">
                        <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/global.buttons.no', [], false); ?>
                    </button>
                    <button type="button" class="btn btn-sm app-btn-success btn-ok">
                        <i class="fa-solid fa-check"></i> <?= ui_text('backend/global.buttons.yes', [], false); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>