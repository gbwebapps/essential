<div class="card-body py-0">
    <div class="row">
        <div class="col-md-12">

            <!-- Se esiste l'array e contiene almeno un record... -->
            <?php if(isset($data['records']) && count($data['records'])): ?>

                <!-- Paginazione superiore -->
                <?= $this->include('backend/template/paginationView'); ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive border-top">

                            <!-- Icona da visualizzare a fianco al nome colonna, se ascendente o discendente -->
                            <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-circle-down"></i>' : '<i class="fa-solid fa-arrow-circle-up"></i>'; ?>

                            <!-- Numero dei record visualizzati in una pagina. Serve al Trick per evitare che all'eliminazione
                            dell'ultimo record in una pagina che non è la prima, la visualizzazione rimanga bloccata e non passi alla pagina successiva. -->
                            <div id="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>

                            <table class="table table-condensed mb-0">
                                <thead>
                                    <tr class="sorting">

                                        <!-- Icona allegati -->
                                        <th style="width: 5%;" class="text-center">
                                            <i class="fa-solid fa-paperclip"></i>
                                        </th>

                                        <!-- Icona immagine -->
                                        <th style="width: 7.5%;" class="text-center">
                                            <i class="fa-solid fa-image"></i>
                                        </th>

                                        <!-- Colonna firstname -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="firstname" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'firstname') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.firstname'); ?> <?= (($posts['column'] == 'firstname') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna lastname -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="lastname" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'lastname') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.lastname'); ?>&nbsp;<?= (($posts['column'] == 'lastname') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna email -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="email" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'email') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.email'); ?>&nbsp;<?= (($posts['column'] == 'email') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna phone -->
                                        <th style="width: 12.5%;">
                                            <a class="sort" href="#" data-column="phone" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'phone') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.phone'); ?>&nbsp;<?= (($posts['column'] == 'phone') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna status -->
                                        <th style="width: 5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.status'); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna azioni -->
                                        <th style="width: 12.5%;">&nbsp;</th>

                                    </tr>
                                </thead>
                                <tbody id="adminsBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $admin): ?>

                                        <!-- Definisco un default se non c'é immagine -->
                                        <?php $cover = ($admin->cover ?? null); ?>

                                        <!-- Definisco lo stile dello status dipendendo dalla sua attivazione o meno -->
                                        <?php
                                            if($admin->status === '1'):
                                                $class = ' text-success fw-bold';
                                            elseif($admin->status === '2'):
                                                $class = ' text-danger fw-bold';
                                            endif;
                                        ?>

                                        <tr>
                                            <!-- Cella allegati -->
                                            <td rowspan="2" class="align-middle text-center border-end fw-bold">
                                                <span class="badge bg-info"><?= $admin->images_num; ?></span>
                                                <span class="badge bg-secondary"><?= $admin->docs_num; ?></span>
                                            </td>

                                            <!-- Cella immagine -->
                                            <td rowspan="2" class="align-middle text-center border-end bg-light">
                                                <?php if(is_null($cover)): ?>
                                                    <span class="fw-bold text-danger"><?= lang('backend/admins.labels.noImage'); ?></span>
                                                <?php else: ?>
                                                    <img src="<?= base_url('images/admins/' . esc($admin->uuid) . '/small/' . $cover); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella firstname -->
                                            <td class="align-middle"><b><?= esc($admin->firstname); ?></b></td>

                                            <!-- Cella lastname -->
                                            <td class="align-middle"><b><?= esc($admin->lastname); ?></b></td>

                                            <!-- Cella email -->
                                            <td class="align-middle"><b><?= esc($admin->email); ?></b></td>

                                            <!-- Cella phone -->
                                            <td class="align-middle"><b><?= esc($admin->phone); ?></b></td>

                                            <!-- Cella status -->
                                            <td class="align-middle text-center">
                                                <?php
                                                    if($admin->status === '1'):
                                                        $status_text = lang('backend/admins.labels.active');
                                                        $status_class="text-success fw-bold btn btn-link shadow-none";
                                                    elseif($admin->status === '0'):
                                                        $status_text = lang('backend/admins.labels.unactive');
                                                        $status_class="text-danger fw-bold btn btn-link shadow-none";
                                                    endif;
                                                ?>
                                                <form class="change_status" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureChangeStatus'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                    <button type="submit" class="<?= $status_class; ?>">
                                                        <?= $status_text; ?>
                                                    </button>
                                                </form>
                                            </td>

                                            <!-- Cella Dropdown Menu -->
                                            <td class="align-middle text-end">
                                                <!-- Pulsante Azioni -->
                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?= lang('backend/admins.buttons.actions'); ?>
                                                </button>

                                                <!-- Corpo Dropdown -->
                                                <ul class="dropdown-menu dropdown-menu-end">

                                                    <!-- Pulsante Dettaglio -->
                                                    <li>
                                                        <a class="dropdown-item" href="<?= base_url('admin/admins/show/' . esc($admin->uuid)); ?>">
                                                            <i class="fa-solid fa-circle-info"></i> <?= lang('backend/admins.actions.show'); ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>

                                                    <!-- Pulsante Aggiorna -->
                                                    <li>
                                                        <a class="dropdown-item" href="<?= base_url('admin/admins/edit/' . esc($admin->uuid)); ?>">
                                                            <i class="fa-solid fa-pen-to-square"></i> <?= lang('backend/admins.actions.edit'); ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>

                                                    <!-- Pulsante Reset password -->
                                                    <li>
                                                        <form class="reset_admin" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureReset'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                            <button type="submit" class="dropdown-item btn-link text-secondary">
                                                                <i class="fa-solid fa-unlock"></i> <?= lang('backend/admins.actions.reset'); ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>

                                                    <!-- Pulsante Elimina -->
                                                    <li>
                                                        <form class="delete_record" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                            <button type="submit" class="dropdown-item btn-link text-secondary">
                                                                <i class="fa-solid fa-trash"></i> <?= lang('backend/admins.actions.delete'); ?>
                                                            </button>
                                                        </form>
                                                    </li>

                                                </ul>
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore -->
                                        <tr>
                                            <td colspan="7" class="align-middle">

                                                <!-- Parte creato -->
                                                <small><?= lang('backend/admins.labels.created'); ?> <span class="fw-bold"><?= convertDate(esc($admin->created_at)); ?></span></small>

                                                <!-- Parte aggiornato -->
                                                <?php if( ! is_null($admin->updated_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <small><?= lang('backend/admins.labels.updated'); ?> <span class="fw-bold"><?= convertDate(esc($admin->updated_at)); ?></span></small>
                                                <?php endif; ?>

                                                <!-- Parte sospeso -->
                                                <?php if( ! is_null($admin->suspended_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <small><?= lang('backend/admins.labels.suspended'); ?> <span class="fw-bold text-danger"><?= convertDate(esc($admin->suspended_at)); ?></span></small>
                                                <?php endif; ?>

                                                <!-- Parte resettato -->
                                                <?php if( ! is_null($admin->resetted_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <small><?= lang('backend/admins.labels.resetted'); ?> <span class="fw-bold text-danger"><?= convertDate(esc($admin->resetted_at)); ?></span></small>
                                                <?php endif; ?>

                                            </td>
                                        </tr>
                                        <!-- Fine Riga inferiore -->

                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Paginazione inferiore -->
                <?= $this->include('backend/template/paginationView'); ?>

            <!-- ...altrimenti visualizzo messaggio adeguato. -->
            <?php else: ?>
                <div class="text-center text-danger py-3 fw-bold"><?= lang('backend/admins.messages.noRecordsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
