<div class="row py-2">
    <div class="col-12">
        <form class="edit-group-form" data-id="<?= $group->id; ?>">

            <input type="hidden" name="id" value="<?= $group->id; ?>">

            <div class="row mb-4">
                <div class="col-4">
                    <label for="name" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/groups.labels.groupName'); ?></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="<?= lang('backend/groups.placeholders.groupName'); ?>" value="<?= esc($group->name); ?>">
                    <div class="error_name text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
                <div class="col-8">
                    <label for="description" class="pb-1"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/groups.labels.groupDescription'); ?></label>
                    <input type="text" name="description" id="description" class="form-control" placeholder="<?= lang('backend/groups.placeholders.groupDescription'); ?>" value="<?= esc($group->description); ?>">
                    <div class="error_description text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
            </div>

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
                                    /* Verifica se il gruppo possiede già questo specifico permesso */
                                    $isChecked = in_array($k, $group_perms) ? 'checked' : ''; 
                                ?>

                                <div class="col-3 text-center py-1">
                                    <ul class="list-group list-group-flush border rounded-2">
                                        <li class="list-group-item">
                                            <label for="<?= $k; ?>">
                                                <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
                                            </label>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input <?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="<?= $k; ?>" <?= $isChecked; ?>>
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

            <div class="row mt-3">
                <div class="col-12 d-flex align-middle justify-content-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-group me-2" data-id="<?= $group->id; ?>" data-message="<?= lang('backend/groups.messages.areYouSureDeleteGroup'); ?>">
                        <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/groups.buttons.delete'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-refresh-group mx-2" data-id="<?= $group->id; ?>" data-message="<?= lang('backend/groups.messages.areYouSureToReload'); ?>">
                        <i class="fa-solid fa-refresh me-1"></i><?= lang('backend/groups.buttons.refreshData'); ?>
                    </button>
                    <button type="submit" class="btn btn-success btn-sm ms-2">
                        <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/groups.buttons.sendData'); ?>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>