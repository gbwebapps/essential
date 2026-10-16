<div class="card-body">

    <?php foreach($permissions as $key => $permission): ?>

        <div class="row mb-2 g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-1">
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

                            /* Configurazione del link pulsante e del verbo per il messaggio di conferma */
                            if ($isAssigned):
                                $permText = lang('backend/admins.labels.assigned');
                                $permClass = "btn btn-link app-text-success fw-bold shadow-none p-0";
                                $actionVerb = lang('backend/admins.labels.removeVerb'); /* Es: "rimuovere" */
                            else:
                                $permText = lang('backend/admins.labels.notAssigned');
                                $permClass = "btn btn-link app-text-danger fw-bold shadow-none p-0";
                                $actionVerb = lang('backend/admins.labels.assignVerb'); /* Es: "assegnare" */
                            endif;
                        ?>

                        <div class="col-12 col-lg-3 text-center py-1">
                            <ul class="list-group list-group-flush border rounded-2">
                                <li class="list-group-item" style="min-height: 75px;">
                                    <form class="changePermission fw-bold app-text d-block mb-1" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureChangePermission'), $actionVerb, $v, $admin->firstname, $admin->lastname), 'attr'); ?>">
                                        <input type="hidden" name="permission" value="<?= esc($k, 'attr'); ?>">
                                        <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                        <button type="submit" class="<?= esc($permClass, 'attr'); ?>">
                                            <i class="fa-solid fa-circle-arrow-down"></i><?= esc($v); ?>
                                        </button>
                                    </form>
                                    
                                    <?php if ($isGroupActive): ?>
                                        <span class="badge app-bg-inverse app-text-inverse border app-border-success-subtle fs-8"><?= lang('backend/admins.labels.assignedToGroup'); ?></span>
                                    <?php else: ?>
                                        <span class="badge app-bg-surface app-text border fs-8"><?= lang('backend/admins.labels.notAssignedToGroup'); ?></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item">
                                    <span class="<?= esc($permClass, 'attr'); ?>" style="cursor: default;"><?= esc($permText); ?></span>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

</div>
