<div class="row py-2">
    <div class="col-12">
        <form class="edit-group-form" data-id="<?= $group->id; ?>">

            <input type="hidden" name="id" value="<?= $group->id; ?>">

            <div class="row mb-4">
                <div class="col-4">
                    <label Kharma for="name" class="pb-1">Nome gruppo</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Inserisci nome..." value="<?= esc($group->name); ?>">
                    <div class="error_name text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
                <div class="col-8">
                    <label for="description" class="pb-1">Descrizione gruppo</label>
                    <input type="text" name="description" id="description" class="form-control" placeholder="Inserisci descrizione..." value="<?= esc($group->description); ?>">
                    <div class="error_description text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                </div>
            </div>

            <?php foreach($permissions as $permission): ?>

                <div class="row mb-2 g-0 bg-light pt-2 ps-2 pe-2 border">
                    <div class="col-6 text-start">
                        <h5><?= $permission['icon']; ?> <?= ucfirst($permission['title']); ?></h5>
                    </div>
                    <div class="col-6 text-end">
                        <a href="#" class="select-all" data-controller="<?= $permission['controller']; ?>">
                            <i class="fa-solid fa-square-check"></i>
                            <?= lang('backend/groups.links.selectAll'); ?>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="row">

                            <?php foreach($permission['perms'] as $k => $v): ?>

                                <?php 
                                    /* Verifica se il gruppo possiede già questo specifico permesso */
                                    $isChecked = in_array($k, $group_perms) ? 'checked' : ''; 
                                ?>

                                <div class="col-3 text-center py-1">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <label for="<?= $k; ?>"><?= $v; ?></label>
                                        </li>
                                        <li class="list-group-item">
                                            <input type="checkbox" class="<?= $permission['controller']; ?>" name="permissions[]" value="<?= $k; ?>" id="<?= $k; ?>" <?= $isChecked; ?>>
                                        </li>
                                    </ul>
                                </div>

                            <?php endforeach; ?>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

            <div class="error_permissions text-danger text-center small fw-bold pb-2">&nbsp;</div>

            <div class="row mt-3">
                <div class="col-12 d-flex align-middle justify-content-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-group" data-id="<?= $group->id; ?>" data-message="Sei sicuro di voler eliminare questo gruppo?">
                        <i class="fa-solid fa-trash-can me-1"></i>Elimina
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-refresh-group mx-2" data-id="<?= $group->id; ?>" data-message="Sei sicuro di voler ricaricare i dati?">
                        <i class="fa-solid fa-refresh me-1"></i>Ricarica dati
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Invia dati
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>