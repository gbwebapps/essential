<div class="card-body p-0">
	<form id="new-group">

		<div class="row mb-3">
			<div class="col-12 col-lg-4 mb-2 mb-lg-0">
				<label for="name" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/groups.labels.groupName'); ?></label>
				<input type="text" name="name" id="name" class="form-control" placeholder="<?= lang('backend/groups.placeholders.groupName'); ?>">
				<div class="error_name text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
			</div>
			<div class="col-12 col-lg-8">
				<label for="description" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/groups.labels.groupDescription'); ?></label>
				<input type="text" name="description" id="description" class="form-control" placeholder="<?= lang('backend/groups.placeholders.groupDescription'); ?>">
				<div class="error_description text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
			</div>
		</div>

		<?php foreach($permissions as $key => $permission): ?>

		    <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
		        <div class="col-6 text-start">
		            <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
		        </div>
		        <div class="col-6 text-end">
		            <a href="#" class="select-all" data-controller="<?= $permission['controller']; ?>">
		                <i class="fa-solid fa-square-check"></i>
		                <?= lang('backend/global.links.selectAll'); ?>
		            </a>
		        </div>
		    </div>

		    <?php $marginClass = ($key === array_key_last($permissions)) ? '' : 'mb-3'; ?>

		    <div class="row <?= $marginClass; ?>">

		        <div class="col-12">
		            <div class="row">

		                <?php foreach($permission['perms'] as $k => $v): ?>

		                    <div class="col-12 col-lg-3 text-center py-1">
		                        <ul class="list-group list-group-flush border rounded-2">
		                            <li class="list-group-item">
		                                <label for="<?= $k; ?>">
		                                    <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
		                                </label>
		                            </li>
		                            <li class="list-group-item">
		                                <div class="form-check form-switch d-inline-block">
		                                    <input type="checkbox" class="form-check-input <?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="<?= $k; ?>">
		                                </div>
		                            </li>
		                        </ul>
		                    </div>

		                <?php endforeach; ?>

		            </div>
		        </div>
		    </div>

		<?php endforeach; ?>

		<div class="error_permissions text-danger text-center small fw-bold pb-2">&nbsp;</div>

		<div class="row">
            <div class="col-12">
            	<div class="d-flex flex-column flex-md-row justify-content-center gap-2">
            		<button type="button" class="btn btn-warning text-darka btn-sm btn-reset-group" data-message="<?= lang('backend/groups.messages.areYouSureToResetData'); ?>">
                	    <i class="fa-solid fa-refresh me-1"></i><?= lang('backend/groups.buttons.resetData'); ?>
                	</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/groups.buttons.sendData'); ?>
                    </button>
            	</div>
            </div>
        </div>

	</form>
</div>