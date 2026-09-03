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

                            <table class="table table-condensed mb-0 text-nowrap">
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
                                        <?php $isTrashed = ( ! is_null($admin->deleted_at)); ?>
                                        <?php $isSuperadmin = (int) $admin->superadmin === 1; ?>
                                        
                                        <tr class="<?= $isTrashed ? 'text-decoration-line-through' : ''; ?> <?= $isSuperadmin ? 'table-light' : ''; ?>">
                                            <!-- Cella allegati -->
                                            <td rowspan="2" class="align-middle text-center border-end fw-bold">
                                                <span class="badge bg-info"><?= $admin->images_num; ?></span>
                                            </td>

                                            <!-- Cella immagine -->
                                            <td rowspan="2" class="align-middle text-center border-end bg-light">
                                                <?php if(is_null($cover)): ?>
                                                    <span class="fw-bold text-danger"><?= lang('backend/admins.labels.noImage'); ?></span>
                                                <?php else: ?>
                                                    <img src="<?= base_url('images/backend/admins/' . esc($admin->uuid) . '/small/' . $cover); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella firstname -->
                                            <td class="align-middle border-bottom-0 fw-bold">
                                                <span><?= esc($admin->firstname); ?></span>
                                            </td>

                                            <!-- Cella lastname -->
                                            <td class="align-middle border-bottom-0 fw-bold">
                                                <span><?= esc($admin->lastname); ?></span>
                                            </td>

                                            <!-- Cella email -->
                                            <td class="align-middle border-bottom-0 fw-bold">
                                                <span><?= esc($admin->email); ?></span>
                                            </td>

                                            <!-- Cella phone -->
                                            <td class="align-middle border-bottom-0 fw-bold">
                                                <span><?= esc($admin->phone); ?></span>
                                            </td>

                                            <!-- Cella status -->
                                            <td class="align-middle text-center border-bottom-0">
                                                <?php if( ! $isSuperadmin): ?>

                                                    <?php if( ! $isTrashed): ?>
                                                        <?php
                                                            $status = (int) $admin->status;

                                                            if($status === 1):
                                                                $statusText = lang('backend/admins.labels.active');
                                                                $statusClass ="text-success btn btn-link shadow-none fw-bold";
                                                            elseif($status === 0):
                                                                $statusText = lang('backend/admins.labels.unactive');
                                                                $statusClass ="text-danger btn btn-link shadow-none fw-bold";
                                                            endif;
                                                        ?>
                                                        <form class="changeStatus" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureChangeStatus'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                            <button type="submit" class="<?= $statusClass; ?>">
                                                                <?= $statusText; ?>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <?php if($isTrashed): ?>
                                                            <span class="badge bg-danger p-2 border-0 rounded-1"><?= lang('backend/admins.labels.deleted'); ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella Dropdown Menu -->
                                            <td class="align-middle text-end border-bottom-0">

                                                <?php if( ! $isSuperadmin): ?>

                                                    <!-- Pulsante Azioni -->
                                                    <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <?= lang('backend/admins.buttons.actions'); ?>
                                                    </button>

                                                    <!-- Corpo Dropdown -->
                                                    <ul class="dropdown-menu dropdown-menu-end">

                                                        <?php if( ! $isTrashed): ?>
                                                            <!-- Pulsante Dettaglio -->
                                                            <li>
                                                                <a class="dropdown-item" href="<?= base_url('backend/admins/show/' . esc($admin->uuid)); ?>">
                                                                    <i class="fa-solid fa-user"></i> <?= lang('backend/admins.actions.show'); ?>
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Pulsante Aggiorna -->
                                                            <li>
                                                                <a class="dropdown-item" href="<?= base_url('backend/admins/edit/' . esc($admin->uuid)); ?>">
                                                                    <i class="fa-solid fa-user-pen"></i> <?= lang('backend/admins.actions.edit'); ?>
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Pulsante Reset password -->
                                                            <li>
                                                                <form class="resetAdmin" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureReset'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                    <button type="submit" class="dropdown-item btn-link text-secondary">
                                                                        <i class="fa-solid fa-unlock"></i> <?= lang('backend/admins.actions.reset'); ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Pulsante Elimina (Soft Delete) -->
                                                            <li>
                                                                <form class="deleteRecord" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureSoftDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                    <button type="submit" class="dropdown-item btn-link text-secondary">
                                                                        <i class="fa-solid fa-trash"></i> <?= lang('backend/admins.actions.softDelete'); ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php else: ?>
                                                            <!-- Pulsante Ripristina (Restore Delete) -->
                                                            <li>
                                                                <form class="restoreRecord" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureRestoreDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                    <button type="submit" class="dropdown-item btn-link text-success">
                                                                        <i class="fa-solid fa-trash-arrow-up"></i> <?= lang('backend/admins.actions.restoreDelete'); ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                            <li>
                                                                <form class="hardDeleteRecord" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureHardDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                    <button type="submit" class="dropdown-item btn-link text-danger">
                                                                        <i class="fa-solid fa-trash"></i> <?= lang('backend/admins.actions.hardDelete'); ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>

                                                    </ul>

                                                <?php endif; ?>

                                            </td>
                                        </tr>

                                        <!-- Riga inferiore -->
                                        <tr class="<?= $isTrashed ? 'text-decoration-line-through' : ''; ?> <?= $isSuperadmin ? 'table-light' : ''; ?>">
                                            <td colspan="7" class="align-middle small">

                                                <!-- Parte creato -->
                                                <?= lang('backend/admins.labels.createdAt'); ?> <span class="fw-bold"><?= convertDate(esc($admin->created_at)); ?></span>

                                                <!-- Parte aggiornato -->
                                                <?php if( ! is_null($admin->updated_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <?= lang('backend/admins.labels.updatedAt'); ?> <span class="fw-bold"><?= convertDate(esc($admin->updated_at)); ?></span>
                                                <?php endif; ?>

                                                <!-- Parte sospeso -->
                                                <?php if( ! is_null($admin->suspended_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <?= lang('backend/admins.labels.suspendedAt'); ?> <span class="fw-bold text-danger"><?= convertDate(esc($admin->suspended_at)); ?></span>
                                                <?php endif; ?>

                                                <!-- Parte resettato -->
                                                <?php if( ! is_null($admin->resetted_at)): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <?= lang('backend/admins.labels.resettedAt'); ?> <span class="fw-bold text-danger"><?= convertDate(esc($admin->resetted_at)); ?></span>
                                                <?php endif; ?>

                                                <!-- Parte eliminato (Cestinato) -->
                                                <?php if($isTrashed): ?>
                                                    &nbsp;&bull;&nbsp;
                                                    <?= lang('backend/admins.labels.deletedAt'); ?> <span class="fw-bold text-danger"><?= convertDate(esc($admin->deleted_at)); ?></span>
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
                <div class="text-center py-3 fw-bold"><?= lang('backend/admins.messages.noAdminsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>