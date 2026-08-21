<div class="card-body p-0 mt-4">
    <div class="ps-2 py-1 bg-light fw-bold small mb-2 border text-center rounded-2">Gruppo <?= esc($name); ?></div>
    <form id="edit-admin-exceptions-form" data-uuid="<?= $uuid; ?>">

        <input type="hidden" name="uuid" value="<?= $uuid; ?>">

        <?php foreach($permissions as $key => $permission): ?>

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

            <?php $marginClass = ($key === array_key_last($permissions)) ? '' : 'mb-3'; ?>

            <div class="row <?= $marginClass; ?>">

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
                                        <label for="exc_<?= $k; ?>" class="fw-bold text-dark d-block mb-1">
                                            <i class="fa-solid fa-circle-arrow-down"></i><?= $v; ?>
                                        </label>
                                        
                                        <?php if ($isGroupActive): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-8"><?= lang('backend/groups.labels.assignedToGroup'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border fs-8"><?= lang('backend/groups.labels.notAssignedToGroup'); ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="form-check form-switch d-inline-block">
                                            <input type="checkbox" class="form-check-input btn-trigger-admin-perm-switch <?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="exc_<?= $k; ?>" <?= $isChecked; ?>>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>

        <div class="error_exceptions text-danger text-center small fw-bold pb-2">&nbsp;</div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                    <button type="button" class="btn btn-warning text-dark btn-sm btn-refresh-admin-perms" data-uuid="<?= $uuid; ?>" data-message="Sei sicuro di voler ricaricare i dati originari di questo amministratore?">
                        <i class="fa-solid fa-refresh me-1"></i>Ricarica dati
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Invia dati
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>