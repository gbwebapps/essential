<div class="card-body">

    <?php foreach($permissions as $key => $permission): ?>

        <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border rounded-1">
            <div class="col-6 text-start">
                <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
            </div>
            <div class="col-6 text-end"></div>
        </div>

        <?php $marginClass = ($key === array_key_last($permissions)) ? '' : 'mb-3'; ?>

            <div class="row <?= $marginClass; ?>">

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

                        <div class="col-12 col-lg-3 text-center py-1">
                            <ul class="list-group list-group-flush border rounded-2">
                                <li class="list-group-item" style="min-height: 75px;">
                                    <label class="fw-bold text-dark d-block mb-1">
                                        <form class="changePermission" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureChangePermission'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                            <input type="hidden" name="permission" value="<?= $k; ?>">
                                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                            <button type="submit" class="<?= $permClass; ?>">
                                                <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
                                            </button>
                                        </form>
                                    </label>
                                    
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