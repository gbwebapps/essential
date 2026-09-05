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
                            <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-down"></i>' : '<i class="fa-solid fa-arrow-up"></i>'; ?>

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

                                        <th style="width: 2.5%;" class="text-center">&nbsp;</th>

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
                                        <th style="width: 7.5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/admins.labels.status'); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna azioni -->
                                        <th style="width: 7.5%;">&nbsp;</th>

                                    </tr>
                                </thead>
                                <tbody id="adminsBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $admin): ?>

                                        <!-- Definisco un default se non c'é immagine -->
                                        <?php $cover = ($admin->cover ?? null); ?>
                                        <?php $isTrashed = ( ! is_null($admin->deleted_at)); ?>
                                        <?php $isSuperadmin = (int) $admin->superadmin === 1; ?>
                                        
                                        <tr class="border-end border-start table-row-110<?= $isTrashed ? ' text-decoration-line-through' : ''; ?> <?= $isSuperadmin ? ' table-bg-superadmin' : ''; ?>">

                                            <!-- Cella allegati (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end fw-bold py-3">
                                                <span class="badge bg-info"><?= $admin->images_num; ?></span>
                                            </td>

                                            <!-- Cella immagine (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end bg-light py-3">
                                                <?php if(is_null($cover)): ?>
                                                    <span class="fw-bold text-danger"><?= lang('backend/admins.labels.noImage'); ?></span>
                                                <?php else: ?>
                                                    <img src="<?= base_url('images/backend/admins/' . esc($admin->uuid) . '/small/' . $cover); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 text-secondary shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($admin->uuid); ?>" aria-expanded="false" title="Mostra dettagli di sistema">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- Cella firstname -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($admin->firstname); ?></span>
                                            </td>

                                            <!-- Cella lastname -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($admin->lastname); ?></span>
                                            </td>

                                            <!-- Cella email -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($admin->email); ?></span>
                                            </td>

                                            <!-- Cella phone -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($admin->phone); ?></span>
                                            </td>

                                            <!-- Cella status -->
                                            <td class="align-middle text-center">
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

                                            <!-- Cella Azioni Rapide -->
                                            <td class="align-middle text-start">
                                                
                                                <?php if( ! $isSuperadmin): ?>
                                                    
                                                    <div class="d-flex flex-column gap-1">
                                                        
                                                        <?php if( ! $isTrashed): ?>
                                                            
                                                            <!-- Pulsante Dettaglio -->
                                                            <a href="<?= base_url('backend/admins/show/' . esc($admin->uuid)); ?>" class="text-decoration-none text-secondary action">
                                                                <i class="fa-solid fa-user fa-fw"></i> <?= lang('backend/admins.actions.show'); ?>
                                                            </a>

                                                            <!-- Pulsante Aggiorna -->
                                                            <a href="<?= base_url('backend/admins/edit/' . esc($admin->uuid)); ?>" class="text-decoration-none text-secondary action">
                                                                <i class="fa-solid fa-user-pen fa-fw"></i> <?= lang('backend/admins.actions.edit'); ?>
                                                            </a>

                                                            <!-- Pulsante Reset password -->
                                                            <form class="resetAdmin m-0 p-0" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureReset'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                <button type="submit" class="btn btn-link p-0 m-0 text-secondary text-decoration-none action shadow-none">
                                                                    <i class="fa-solid fa-unlock fa-fw"></i> <?= lang('backend/admins.actions.reset'); ?>
                                                                </button>
                                                            </form>

                                                            <!-- Pulsante Elimina (Soft Delete) -->
                                                            <form class="deleteRecord m-0 p-0" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureSoftDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                <button type="submit" class="btn btn-link p-0 m-0 text-danger text-decoration-none action shadow-none">
                                                                    <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/admins.actions.softDelete'); ?>
                                                                </button>
                                                            </form>
                                                            
                                                        <?php else: ?>
                                                            
                                                            <!-- Pulsante Ripristina (Restore Delete) -->
                                                            <form class="restoreRecord m-0 p-0" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureRestoreDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                <button type="submit" class="btn btn-link p-0 m-0 text-success text-decoration-none action shadow-none">
                                                                    <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= lang('backend/admins.actions.restoreDelete'); ?>
                                                                </button>
                                                            </form>

                                                            <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                            <form class="hardDeleteRecord m-0 p-0" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureHardDelete'), esc($admin->firstname), esc($admin->lastname)); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
                                                                <button type="submit" class="btn btn-link p-0 m-0 text-danger text-decoration-none action shadow-none">
                                                                    <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/admins.actions.hardDelete'); ?>
                                                                </button>
                                                            </form>
                                                            
                                                        <?php endif; ?>
                                                        
                                                    </div>
                                                    
                                                <?php endif; ?>
                                                
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore (Metadati a comparsa fluida) -->
                                        <tr class="border-end border-start">

                                            <!-- Colspan 9 copre l'intera tabella. Nessun padding sul td per evitare scatti nell'animazione -->
                                            <td colspan="9" class="p-0 border-0">
                                                
                                                <!-- Contenitore collassabile puntato dal bottone -->
                                                <div class="collapse" id="meta-<?= esc($admin->uuid); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom <?= $isTrashed ? 'text-decoration-line-through' : ''; ?> <?= $isSuperadmin ? 'bg-light text-dark' : 'bg-light text-secondary'; ?> small">
                                                        
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

                                                    </div>
                                                </div>

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