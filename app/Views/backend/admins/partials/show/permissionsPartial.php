<div class="card-body">

    <?php foreach($permissions as $permission): ?>

        <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border">
            <div class="col-6 text-start">
                <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="row">

                    <?php foreach($permission['perms'] as $k => $v): ?>

                        <div class="col-3 text-center py-1">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <label><?= $v; ?></label>
                                </li>

                                <?php
                                    if(in_array($k, $perms)):
                                        $perm_text = lang('backend/admins.labels.assigned');
                                        $perm_class="btn btn-link text-success fw-bold shadow-none";
                                    elseif( ! in_array($k, $perms)):
                                        $perm_text = lang('backend/admins.labels.notAssigned');
                                        $perm_class="btn btn-link text-danger fw-bold shadow-none";
                                    endif;
                                ?>

                                <li class="list-group-item">
                                    <form class="changePermission" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureChangePermission'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                        <input type="hidden" name="permission" value="<?= $k; ?>">
                                        <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                        <button type="submit" class="<?= $perm_class; ?>">
                                            <?= $perm_text; ?>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>