<div class="card-body">
	<div class="row">
		<div class="col-12">
			<form id="getResetPassword" class="w-100" data-message="<?= esc(lang('backend/account.messages.areYouSureStartingReset'), 'attr'); ?>">
				<div class="row justify-content-start">
					<div class="col-12 col-lg-4">
						<label for="currentPassword" class="form-label"><?= lang('backend/account.labels.currentPassword'); ?></label>
						<div class="input-group">
							<button class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
								<i class="fa-solid fa-eye app-text-muted"></i>
							</button>
							<input type="password" id="currentPassword" name="currentPassword" class="form-control" autocomplete="current-password" data-required-message="<?= esc(lang('backend/account.errors.currentPasswordRequired'), 'attr'); ?>" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
						</div>
						<div class="error_currentPassword app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
					</div>
				</div>
				<div class="row justify-content-start">
					<div class="col-12 col-lg-4">
						<button type="submit" class="btn app-btn-success btn-sm w-100">
							<i class="fa-solid fa-unlock"></i> <?= lang('backend/account.buttons.resetPassword'); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
		<?php if(isset($expiringDate) && $expiringDate): ?>
			<div class="col-12 mt-3">
				<div class="text-start fw-bold">
					<?= sprintf(lang('backend/account.messages.expiringDate'), $expiringDate); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
