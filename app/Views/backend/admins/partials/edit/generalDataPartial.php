<div class="card-body">
    <!-- Nome e Cognome -->
    <div class="row">
        <div class="col-6">
            <div class="mb-2">
                <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.firstname'); ?></label>
                <input type="text" id="firstname" name="firstname" value="<?= esc($admin->firstname); ?>" class="form-control" placeholder="<?= lang('backend/admins.placeholders.firstname'); ?>">
                <!-- Contenitore dinamico per l'errore di validazione -->
                <div class="error_firstname text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-6">
            <div class="mb-2">
                <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.lastname'); ?></label>
                <input type="text" id="lastname" name="lastname" value="<?= esc($admin->lastname); ?>" class="form-control" placeholder="<?= lang('backend/admins.placeholders.lastname'); ?>">
                <div class="error_lastname text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Email e Telefono -->
    <div class="row">
        <div class="col-6">
            <div class="mb-2">
                <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.email'); ?></label>
                <input type="text" id="email" name="email" value="<?= esc($admin->email); ?>" class="form-control" placeholder="<?= lang('backend/admins.placeholders.email'); ?>">
                <div class="error_email text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-6">
            <div class="mb-2">
                <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.phone'); ?></label>
                <input type="text" id="phone" name="phone" value="<?= esc($admin->phone); ?>" class="form-control" placeholder="<?= lang('backend/admins.placeholders.phone'); ?>">
                <div class="error_phone text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Stato Attivazione -->
    <div class="row">
        <div class="col-6">
            <div class="mb-2">
                <label for="status" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.status'); ?></label>
                <select name="active" class="form-select" id="status">
                    <option value="1"<?= ($admin->active === '1') ? ' selected' : ''; ?>><?= lang('backend/admins.labels.active'); ?></option>
                    <option value="0"<?= ($admin->active === '0') ? ' selected' : ''; ?>><?= lang('backend/admins.labels.unactive'); ?></option>
                </select>
                <div class="error_active text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Note Aggiuntive -->
    <div class="row">
        <div class="col-12">
            <div class="mb-2">
                <label for="notes"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.notes'); ?></label>
                <textarea name="notes" id="notes" rows="7" class="form-control"><?= esc($admin->notes); ?></textarea>
                <div class="error_notes text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
</div>