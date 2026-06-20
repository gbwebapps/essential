<div class="row py-2">
	<div class="col-12">
		<form id="new-group">

			<div class="row mb-4">
				<div class="col-4">
					<label for="name" class="pb-1">Nome gruppo</label>
					<input type="text" name="name" id="name" class="form-control" placeholder="Inserisci nome...">
					<div class="error_name text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
				</div>
				<div class="col-8">
					<label for="description" class="pb-1">Descrizione gruppo</label>
					<input type="text" name="description" id="description" class="form-control" placeholder="Inserisci descrizione...">
					<div class="error_description text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
				</div>
			</div>

			<?php foreach($permissions as $permission): ?>

			    <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border">
			        <div class="col-6 text-start">
			            <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
			        </div>
			        <div class="col-6 text-end">
			            <a href="#" class="select-all" data-controller="<?= $permission['controller']; ?>">
			                <i class="fa-solid fa-square-check"></i>
			                <?= lang('backend/groups.links.selectAll'); ?>
			            </a>
			        </div>
			    </div>

			    <div class="row">
			        <div class="col-12">
			            <div class="row">

			                <?php foreach($permission['perms'] as $k => $v): ?>

			                    <div class="col-3 text-center py-1">
			                        <ul class="list-group list-group-flush">
			                            <li class="list-group-item">
			                                <label for="<?= $k; ?>"><?= $v; ?></label>
			                            </li>
			                            <li class="list-group-item">
			                                <input type="checkbox" class="<?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="<?= $k; ?>">
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
				<div class="col-12 d-flex align-middle justify-content-center">
					<input type="submit" class="btn btn-danger btn-sm me-1" value="Reset">
					<input type="submit" class="btn btn-success btn-sm ms-1" value="Salva">
				</div>
			</div>

		</form>
	</div>
</div>