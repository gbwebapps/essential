<div class="card-body">

    <?php foreach($permissions as $permission): ?>

        <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border">
            <div class="col-6 text-start">
                <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
            </div>
            <div class="col-6 text-end">
                <a href="#" class="select-all" data-controller="<?= $permission['controller']; ?>">
                    <i class="fa-solid fa-square-check"></i>
                    <?= lang('backend/admins.links.selectAll'); ?>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="row">

                    <?php foreach($permission['perms'] as $k => $v): ?>

                        <?php 
                            /* Determino lo stato del checkbox combinando gruppo ed eccezioni */
                            $isChecked = false;
                            
                            if (array_key_exists($k, $user_exceptions)):
                                /* Se esiste un'eccezione, comanda il flag allow (1 = checked, 0 = unchecked) */
                                $isChecked = ($user_exceptions[$k] === 1);
                            else:
                                /* Altrimenti l'utente eredita direttamente lo stato del suo gruppo */
                                $isChecked = in_array($k, $group_perms);
                            endif;
                        ?>

                        <div class="col-3 text-center py-1">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <label for="<?= $k; ?>"><?= $v; ?></label>
                                </li>
                                <li class="list-group-item">
                                    <input type="checkbox" class="<?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="<?= $k; ?>"<?= ($isChecked) ? ' checked' : ''; ?>>
                                </li>
                            </ul>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endforeach; ?>

    <div class="error_permissions text-danger text-center small fw-bold pt-2">&nbsp;</div>
</div>