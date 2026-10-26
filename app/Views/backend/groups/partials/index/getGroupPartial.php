<div class="card-body p-0">
    <form class="edit-group-form" data-id="<?= esc($group->id, 'attr'); ?>" data-unsaved-check>

        <input type="hidden" name="id" value="<?= esc($group->id, 'attr'); ?>">

        <div class="row mb-3">
            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                <label for="name" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/groups.labels.groupName', [], false); ?></label>
                <input <?= ui_binding('backend/groups.placeholders.groupName', 'placeholder', [], true); ?> type="text" name="name" id="name" class="form-control" placeholder="<?= esc(lang('backend/groups.placeholders.groupName'), 'attr'); ?>" value="<?= esc($group->name, 'attr'); ?>">
                <div class="error_name app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
            <div class="col-12 col-lg-8">
                <label for="description" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/groups.labels.groupDescription', [], false); ?></label>
                <input <?= ui_binding('backend/groups.placeholders.groupDescription', 'placeholder', [], true); ?> type="text" name="description" id="description" class="form-control" placeholder="<?= esc(lang('backend/groups.placeholders.groupDescription'), 'attr'); ?>" value="<?= esc($group->description, 'attr'); ?>">
                <div class="error_description app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>

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
                                /* Verifica se il gruppo possiede già questo specifico permesso */
                                $isChecked = in_array($k, $group_perms) ? 'checked' : ''; 
                            ?>

                            <div class="col-12 col-lg-3 text-center py-1">
                                <ul class="list-group list-group-flush border rounded-2">
                                    <li class="list-group-item">
                                        <label for="<?= esc($k, 'attr'); ?>">
                                            <i class="fa-solid fa-circle-arrow-down"></i> <?= ui_value($v); ?>
                                        </label>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="form-check form-switch d-inline-block">
                                            <input type="checkbox" class="form-check-input <?= esc($permission['controller'], 'attr'); ?>" name="permissions[]" value="<?= esc($k, 'attr'); ?>" id="<?= esc($k, 'attr'); ?>" <?= $isChecked; ?>>
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
                    <button <?= ui_binding('backend/groups.messages.areYouSureDeleteGroup', 'data-message', [$group->name], true); ?> type="button" class="btn app-btn-danger btn-sm btn-delete-group" data-id="<?= esc($group->id, 'attr'); ?>" data-message="<?= esc(sprintf(lang('backend/groups.messages.areYouSureDeleteGroup'), $group->name), 'attr'); ?>">
                        <i class="fa-solid fa-trash-can me-1"></i> <?= ui_text('backend/groups.buttons.delete', [], false); ?>
                    </button>
                    <button <?= ui_binding('backend/groups.messages.areYouSureToReload', 'data-message', [], true); ?> type="button" class="btn app-btn-warning btn-sm btn-refresh-group" data-id="<?= esc($group->id, 'attr'); ?>" data-message="<?= esc(lang('backend/groups.messages.areYouSureToReload'), 'attr'); ?>">
                        <i class="fa-solid fa-refresh me-1"></i> <?= ui_text('backend/groups.buttons.refreshData', [], false); ?>
                    </button>
                    <button type="submit" class="btn app-btn-success btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> <?= ui_text('backend/groups.buttons.sendData', [], false); ?>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
