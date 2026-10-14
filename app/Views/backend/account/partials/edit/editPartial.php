<form id="account-edit">
    <div class="card-body pb-0">

    	<!-- Nome e cognome -->
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="mb-2">
                    <label for="firstname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.firstname'); ?></label>
					<input type="text" id="firstname" name="firstname" value="<?= esc($currentAdmin->firstname, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.firstname'), 'attr'); ?>">
                    <div class="error_firstname text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="mb-2">
                    <label for="lastname" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.lastname'); ?></label>
					<input type="text" id="lastname" name="lastname" value="<?= esc($currentAdmin->lastname, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.lastname'), 'attr'); ?>">
                    <div class="error_lastname text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Nome e cognome -->

        <!-- Email e Phone -->
        <div class="row">
            <div class="col-12 col-lg-6">
                <!-- Campo email -->
                <div class="mb-2">
                    <label for="email" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.email'); ?></label>
					<input type="email" id="email" name="email" value="<?= esc($currentAdmin->email, 'attr'); ?>" class="form-control" autocomplete="email" placeholder="<?= esc(lang('backend/account.placeholders.email'), 'attr'); ?>">
                    <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <!-- Campo phone -->
                <div class="mb-2">
                    <label for="phone" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.phone'); ?></label>
					<input type="tel" id="phone" name="phone" value="<?= esc($currentAdmin->phone, 'attr'); ?>" class="form-control" autocomplete="tel" placeholder="<?= esc(lang('backend/account.placeholders.phone'), 'attr'); ?>">
                    <div class="error_phone text-danger fw-bold small pt-1">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Email e Phone -->

		<div class="row">
			<div class="col-12">
				<div class="mb-2">
					<label for="currentPassword" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.currentPassword'); ?></label>
					<div class="input-group">
						<button class="btn btn-secondary bg-white border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
							<i class="fa-solid fa-eye text-muted"></i>
						</button>
						<input type="password" id="currentPassword" name="currentPassword" class="form-control" autocomplete="current-password" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
					</div>
					<div class="form-text"><?= lang('backend/account.labels.currentPasswordEmailHelp'); ?></div>
					<div class="error_currentPassword text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
				</div>
			</div>
		</div>

        <!-- Note Aggiuntive -->
        <div class="row">
            <div class="col-12">
                <div>
                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/account.labels.note'); ?></label>
					<textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/account.placeholders.note'), 'attr'); ?>"><?= esc($currentAdmin->note); ?></textarea>
                    <div class="error_note text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
            </div>
        </div>
        <!-- End Note Aggiuntive -->

    </div>
</form>
