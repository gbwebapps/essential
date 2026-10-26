<div class="card-body p-0 mt-4">
    <div class="ps-2 py-1 app-bg-surface fw-bold small mb-2 border text-center rounded-2">
        <?= ui_text('backend/groups.labels.groupNameBar', [esc($name)], false); ?>
    </div>
    <form id="edit-admin-exceptions-form" data-uuid="<?= esc($uuid, 'attr'); ?>" data-unsaved-check>

        <input type="hidden" name="uuid" value="<?= esc($uuid, 'attr'); ?>">

        <?php foreach($permissions as $key => $permission): ?>

            <div class="row mb-2 g-0 app-bg-surface permission-section-title pt-2 ps-2 pe-2 border rounded-2">
                <div class="col-6 text-start">
                    <h5><?= $permission['icon']; ?> <?= ui_value(ucfirst($permission['title'])); ?></h5>
                </div>
                <div class="col-6 text-end">
                    <a href="#" class="select-all" data-controller="<?= esc($permission['controller'], 'attr'); ?>">
                        <i class="fa-solid fa-square-check"></i>
                        <?= ui_text('backend/global.links.selectAll', [], false); ?>
                    </a>
                </div>
            </div>

            <?php $marginClass = ($key === array_key_last($permissions)) ? '' : 'mb-3'; ?>

            <div class="row <?= esc($marginClass, 'attr'); ?>">

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

                            <div class="col-12 col-lg-3 text-center py-1">
                                <ul class="list-group list-group-flush border rounded-2">
                                    <li class="list-group-item" style="min-height: 75px;">
                                        <label for="exc_<?= esc($k, 'attr'); ?>" class="fw-bold app-text d-block mb-1">
                                            <i class="fa-solid fa-circle-arrow-down"></i><?= ui_value($v); ?>
                                        </label>
                                        
                                        <?php if ($isGroupActive): ?>
                                            <span class="badge app-bg-inverse app-text-inverse border app-border-success-subtle fs-8"><?= ui_text('backend/groups.labels.assignedToGroup', [], false); ?></span>
                                        <?php else: ?>
                                            <span class="badge app-bg-surface app-text border fs-8"><?= ui_text('backend/groups.labels.notAssignedToGroup', [], false); ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="form-check form-switch d-inline-block">
                                            <input type="checkbox" class="form-check-input btn-trigger-admin-perm-switch <?= esc($permission['controller'], 'attr'); ?>" name="permissions[]" value="<?= esc($k, 'attr'); ?>" id="exc_<?= esc($k, 'attr'); ?>" <?= $isChecked; ?>>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>

        <div class="error_permissions app-text-danger text-center small fw-bold pb-2">&nbsp;</div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                    <button <?= ui_binding('backend/groups.messages.areYouSureToReload', 'data-message', [], true); ?> type="button" class="btn app-btn-warning btn-sm btn-refresh-admin-perms" data-uuid="<?= esc($uuid, 'attr'); ?>" data-message="<?= esc(lang('backend/groups.messages.areYouSureToReload'), 'attr'); ?>">
                        <i class="fa-solid fa-refresh me-1"></i> <?= ui_text('backend/groups.buttons.resetData', [], false); ?>
                    </button>
                    <button type="submit" class="btn app-btn-success btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> <?= ui_text('backend/groups.buttons.sendData', [], false); ?>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
