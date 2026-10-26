<div class="card-body pb-0">
    <div class="ps-2 py-1 app-bg-surface fw-bold small mb-2 border text-center rounded-2">
        <?= ui_text('backend/groups.labels.groupNameBar', [esc($groupName->name)], false); ?>
    </div>

    <?php foreach($permissions as $permission): ?>

        <div class="row mb-2 g-0 app-bg-surface permission-section-title pt-2 ps-2 pe-2 border rounded-1">
            <div class="col-6 text-start">
                <h5><?= $permission['icon']; ?> <?= ui_value(ucfirst($permission['title'])); ?></h5>
            </div>
            <div class="col-6 text-end"></div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="row">

                    <?php foreach($permission['perms'] as $k => $v): ?>

                        <?php 
                            /* Verifichiamo se il permesso è incluso nel gruppo di appartenenza dell'utente */
                            $isGroupActive = in_array($k, $group_perms);

                            /* Calcolo dello stato combinato effettivo dell'utente */
                            $isAssigned = false;
                            if (array_key_exists($k, $admin_exceptions)):
                                /* Se c'è un'eccezione, comanda il valore dell'eccezione */
                                $isAssigned = ($admin_exceptions[$k] === 1);
                            else:
                                /* Se non c'è un'eccezione, comanda l'appartenenza al gruppo */
                                $isAssigned = $isGroupActive;
                            endif;

                            /* Configurazione del link pulsante in base allo stato effettivo */
                            if ($isAssigned):
                                $permText = lang('backend/admins.labels.assigned');
                                $permClass = "btn btn-link app-text-success fw-bold shadow-none p-0";
                            else:
                                $permText = lang('backend/admins.labels.notAssigned');
                                $permClass = "btn btn-link app-text-danger fw-bold shadow-none p-0";
                            endif;
                        ?>

                        <div class="col-12 col-lg-3 text-center py-1">
                            <ul class="list-group list-group-flush border rounded-2">
                                <li class="list-group-item" style="min-height: 75px;">
                                    <div class="fw-bold app-text d-block mb-1">
                                        <span class="<?= $permClass; ?>" style="cursor: default;">
                                            <i class="fa-solid fa-circle-arrow-down"></i><?= ui_value($v, false); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($isGroupActive): ?>
                                        <span class="badge app-bg-inverse app-text-inverse border app-border-success-subtle fs-8"><?= ui_text('backend/admins.labels.assignedToGroup', [], false); ?></span>
                                    <?php else: ?>
                                        <span class="badge app-bg-surface app-text border fs-8"><?= ui_text('backend/admins.labels.notAssignedToGroup', [], false); ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="<?= $permClass; ?>" style="cursor: default;"><?= ui_value($permText, false); ?></span>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>
