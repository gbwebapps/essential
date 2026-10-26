<div class="card-body">
	<div class="row">
		<div class="col-12">
			<form <?= ui_binding('backend/account.messages.areYouSureStartingReset', 'data-message', [], true); ?> id="getResetPassword" class="w-100" data-message="<?= esc(lang('backend/account.messages.areYouSureStartingReset'), 'attr'); ?>" data-unsaved-check>
				<div class="row justify-content-start">
					<div class="col-12 col-lg-4">
						<label for="currentPassword" class="form-label"><?= ui_text('backend/account.labels.currentPassword', [], false); ?></label>
						<div class="input-group">
							<button <?= ui_binding('backend/account.buttons.showPassword', 'aria-label', [], true); ?> class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
								<i class="fa-solid fa-eye app-text-muted"></i>
							</button>
							<input <?= ui_binding('backend/account.errors.currentPasswordRequired', 'data-required-message', [], true); ?> <?= ui_binding('backend/account.placeholders.currentPassword', 'placeholder', [], true); ?> type="password" id="currentPassword" name="currentPassword" class="form-control" autocomplete="current-password" data-required-message="<?= esc(lang('backend/account.errors.currentPasswordRequired'), 'attr'); ?>" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
						</div>
						<div class="error_currentPassword app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
					</div>
				</div>
				<div class="row justify-content-start">
					<div class="col-12 col-lg-4">
						<button type="submit" class="btn app-btn-success btn-sm w-100">
							<i class="fa-solid fa-unlock"></i> <?= ui_text('backend/account.buttons.resetPassword', [], false); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
		<?php if(isset($expiringDate) && $expiringDate): ?>
			<div class="col-12 mt-3">
				<div class="text-start fw-bold">
					<?= ui_text('backend/account.messages.expiringDate', [$expiringDate], false); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
