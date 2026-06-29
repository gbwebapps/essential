<div class="card-body">

    <?php foreach($permissions as $permission): ?>

        <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border rounded-1">
            <div class="col-6 text-start">
                <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
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
                                $permClass = "btn btn-link text-success fw-bold shadow-none p-0";
                            else:
                                $permText = lang('backend/admins.labels.notAssigned');
                                $permClass = "btn btn-link text-danger fw-bold shadow-none p-0";
                            endif;
                        ?>

                        <div class="col-3 text-center py-1">
                            <ul class="list-group list-group-flush border rounded-2">
                                <li class="list-group-item" style="min-height: 75px;">
                                    <div class="fw-bold text-dark d-block mb-1">
                                        <span class="<?= $permClass; ?>" style="cursor: default;">
                                            <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($isGroupActive): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-8"><?= lang('backend/admins.labels.assignedToGroup'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border fs-8"><?= lang('backend/admins.labels.notAssignedToGroup'); ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="<?= $permClass; ?>" style="cursor: default;"><?= $permText; ?></span>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>