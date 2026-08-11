<!-- Modale nascosto per la conferma di azioni (funge da alert alternativo a quello di default del browser) -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><?= lang('backend/global.modals.globalTitle'); ?></h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-danger btn-cancel"><?= lang('backend/global.buttons.no'); ?></button>
                    <button type="button" class="btn btn-success btn-ok"><?= lang('backend/global.buttons.yes'); ?></button>
                </div>
            </div>
        </div>
    </div>