<form id="account-edit" data-unsaved-check>
    <div class="card-body pb-0">

    	<!-- Nome e cognome -->
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="mb-2">
                    <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.firstname', [], false); ?></label>
					<input <?= ui_binding('backend/account.placeholders.firstname', 'placeholder', [], true); ?> type="text" id="firstname" name="firstname" value="<?= esc($currentAdmin->firstname, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.firstname'), 'attr'); ?>">
                    <div class="error_firstname app-text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="mb-2">
                    <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.lastname', [], false); ?></label>
					<input <?= ui_binding('backend/account.placeholders.lastname', 'placeholder', [], true); ?> type="text" id="lastname" name="lastname" value="<?= esc($currentAdmin->lastname, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.lastname'), 'attr'); ?>">
                    <div class="error_lastname app-text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Nome e cognome -->

        <!-- Email e Phone -->
        <div class="row">
            <div class="col-12 col-lg-6">
                <!-- Campo email -->
                <div class="mb-2">
                    <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.email', [], false); ?></label>
					<input <?= ui_binding('backend/account.placeholders.email', 'placeholder', [], true); ?> type="email" id="email" name="email" value="<?= esc($currentAdmin->email, 'attr'); ?>" class="form-control" autocomplete="email" placeholder="<?= esc(lang('backend/account.placeholders.email'), 'attr'); ?>">
                    <div class="error_email app-text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <!-- Campo phone -->
                <div class="mb-2">
                    <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.phone', [], false); ?></label>
					<input <?= ui_binding('backend/account.placeholders.phone', 'placeholder', [], true); ?> type="tel" id="phone" name="phone" value="<?= esc($currentAdmin->phone, 'attr'); ?>" class="form-control" autocomplete="tel" placeholder="<?= esc(lang('backend/account.placeholders.phone'), 'attr'); ?>">
                    <div class="error_phone app-text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Email e Phone -->

		<div class="row">
			<div class="col-12">
				<div class="mb-2">
					<label for="currentPassword" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.currentPassword', [], false); ?></label>
					<div class="input-group">
						<button <?= ui_binding('backend/account.buttons.showPassword', 'aria-label', [], true); ?> class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
							<i class="fa-solid fa-eye app-text-muted"></i>
						</button>
						<input <?= ui_binding('backend/account.errors.currentPasswordRequired', 'data-required-message', [], true); ?> <?= ui_binding('backend/account.placeholders.currentPassword', 'placeholder', [], true); ?> type="password" id="currentPassword" name="currentPassword" class="form-control" autocomplete="current-password" data-required-message="<?= esc(lang('backend/account.errors.currentPasswordRequired'), 'attr'); ?>" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
					</div>
					<div class="form-text"><?= ui_text('backend/account.labels.currentPasswordEmailHelp', [], false); ?></div>
					<div class="error_currentPassword app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
				</div>
			</div>
		</div>

        <!-- Note Aggiuntive -->
        <div class="row">
            <div class="col-12">
                <div>
                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/account.labels.note', [], false); ?></label>
					<textarea <?= ui_binding('backend/account.placeholders.note', 'placeholder', [], true); ?> name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.note'), 'attr'); ?>"><?= esc($currentAdmin->note); ?></textarea>
                    <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Note Aggiuntive -->

    </div>
</form>
