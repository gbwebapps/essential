<div id="permissionsData">

    <div class="card-header rounded-0" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
        <h2 class="card-title text-center text-lg-start mb-0"><?= lang('backend/admins.panels.permissions'); ?></h2>
    </div>

    <div class="card-body">

        <?php foreach($permissions as $permission): ?>

            <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border">
                <div class="col-6 text-start">
                    <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
                </div>
                <div class="col-6 text-end">
                    <a href="#" class="select-all" data-controller="<?= $permission['controller']; ?>">
                        <i class="fa-solid fa-check-double"></i>
                        <?= lang('backend/admins.links.selectAll'); ?>
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

        <div class="error_permissions text-danger text-center fw-bold pt-2">&nbsp;</div>
    </div>

</div>