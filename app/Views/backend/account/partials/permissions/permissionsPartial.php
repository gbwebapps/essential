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
                                    <span><?= $v; ?></span>
                                </li>

                                <?php
                                    if(in_array($k, $perms)):
                                        $permText = lang('backend/account.labels.assigned');
                                        $permClass="text-success fw-bold shadow-none";
                                    else:
                                        $permText = lang('backend/account.labels.notAssigned');
                                        $permClass="text-danger fw-bold shadow-none";
                                    endif;
                                ?>

                                <li class="list-group-item">
                                    <span class="<?= $permClass; ?>">
                                        <?= $permText; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>