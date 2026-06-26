<div class="card-body">

    <?php foreach($permissions as $permission): ?>

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

        <div class="row mb-3">
            <div class="col-12">
                <div class="row">

                    <?php foreach($permission['perms'] as $k => $v): ?>

                        <?php 
                            /* Verifichiamo se il permesso è incluso al GRUPPO di appartenenza dell'utente */
                            $isGroupActive = in_array($k, $group_perms);

                            /* Verifichiamo se esiste un'eccezione attiva salvata sul database per questo utente */
                            $hasException = array_key_exists($k, $admin_exceptions);
                            
                            /* L'interruttore finale è attivo se l'eccezione dice 1, OPPURE se non c'è eccezione ma il GRUPPO lo ha attivo */
                            $isAdminActive = $hasException ? ($admin_exceptions[$k] === 1) : $isGroupActive;
                            $isChecked = $isAdminActive ? 'checked' : ''; 
                        ?>

                        <div class="col-3 text-center py-1">
                            <ul class="list-group list-group-flush border rounded-2">
                                <li class="list-group-item" style="min-height: 75px;">
                                    <label for="exc_<?= $k; ?>" class="fw-bold text-dark d-block mb-1">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
                                    </label>
                                    
                                    <?php if ($isGroupActive): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8"><?= lang('backend/admins.labels.assignedToGroup'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border fs-8"><?= lang('backend/admins.labels.notAssignedToGroup'); ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item">
                                    <div class="form-check form-switch d-inline-block">
                                        <input type="checkbox" class="form-check-input btn-trigger-admin-perm-switch <?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="exc_<?= $k; ?>" <?= $isChecked; ?>>
                                    </div>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

    <div class="error_permissions text-danger text-center small fw-bold">&nbsp;</div>
</div>