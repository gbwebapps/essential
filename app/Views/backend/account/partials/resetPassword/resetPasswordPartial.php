<div class="card-body">
	<div class="row">
		<div class="col-12">
			<div class="d-flex align-items-center justify-content-center">
				<form id="getResetPassword" data-message="<?= lang('backend/account.messages.areYouSureStartingReset'); ?>">
					<button type="submit" class="btn btn-sm btn-success">
						<i class="fa-solid fa-unlock"></i> <?= lang('backend/account.buttons.resetPassword'); ?>
					</button>
				</form>
			</div>
		</div>
		<?php if(isset($expiringDate) && $expiringDate): ?>
			<div class="col-12 mt-3">
				<div class="text-center fw-bold">
					<?= sprintf(lang('backend/account.messages.expiringDate'), $expiringDate); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>