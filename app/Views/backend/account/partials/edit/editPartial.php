<form id="account-edit">
    <div class="card-body">

    	<!-- Nome e cognome -->
        <div class="row">
            <div class="col-6">
                <div class="mb-2">
                    <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.firstname'); ?></label>
                    <input type="text" id="firstname" name="firstname" value="<?= esc($currentAdmin->firstname); ?>" class="form-control" placeholder="<?= lang('backend/account.placeholders.firstname'); ?>">
                    <div class="error_firstname text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-6">
                <div class="mb-2">
                    <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.lastname'); ?></label>
                    <input type="text" id="lastname" name="lastname" value="<?= esc($currentAdmin->lastname); ?>" class="form-control" placeholder="<?= lang('backend/account.placeholders.lastname'); ?>">
                    <div class="error_lastname text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Nome e cognome -->

        <!-- Email e Phone -->
        <div class="row">
            <div class="col-6">
                <!-- Campo email -->
                <div class="mb-2">
                    <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.email'); ?></label>
                    <input type="text" id="email" name="email" value="<?= esc($currentAdmin->email); ?>" class="form-control" placeholder="<?= lang('backend/account.placeholders.email'); ?>">
                    <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-6">
                <!-- Campo phone -->
                <div class="mb-2">
                    <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.phone'); ?></label>
                    <input type="text" id="phone" name="phone" value="<?= esc($currentAdmin->phone); ?>" class="form-control" placeholder="<?= lang('backend/account.placeholders.phone'); ?>">
                    <div class="error_phone text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Email e Phone -->

        <!-- Note Aggiuntive -->
        <div class="row">
            <div class="col-12">
                <div class="mb-2">
                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.note'); ?></label>
                    <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= lang('backend/account.placeholders.note'); ?>"><?= esc($currentAdmin->note); ?></textarea>
                    <div class="error_note text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Note Aggiuntive -->

    </div>
</form>