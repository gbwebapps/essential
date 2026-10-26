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
                            <div class="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>

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
                                                <?= ui_text('backend/admins.labels.firstname', [], false); ?> <?= (($posts['column'] == 'firstname') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna lastname -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="lastname" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'lastname') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/admins.labels.lastname', [], false); ?>&nbsp;<?= (($posts['column'] == 'lastname') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna email -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="email" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'email') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/admins.labels.email', [], false); ?>&nbsp;<?= (($posts['column'] == 'email') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna phone -->
                                        <th style="width: 12.5%;">
                                            <a class="sort" href="#" data-column="phone" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'phone') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/admins.labels.phone', [], false); ?>&nbsp;<?= (($posts['column'] == 'phone') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna status -->
                                        <th style="width: 7.5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/admins.labels.status', [], false); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
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
                                        
                                        <tr class="border-end border-start table-row-110 <?= $isSuperadmin ? 'table-bg-superadmin' : ''; ?>">

                                            <!-- Cella allegati (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end fw-bold py-3">
                                                <span class="badge app-bg-info admin-attachments-badge"><?= $admin->images_num; ?></span>
                                            </td>

                                            <!-- Cella immagine (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end app-bg-surface admin-cover-cell py-3<?= is_null($cover) ? ' no-preview-image' : ''; ?>">
                                                <?php if(is_null($cover)): ?>
                                                    <span <?= ui_binding('backend/admins.labels.noImage', 'aria-label', [], true); ?> class="fa-stack" role="img" aria-label="<?= esc(lang('backend/admins.labels.noImage'), 'attr'); ?>">
                                                        <i class="fa-solid fa-camera fa-stack-1x"></i>
                                                        <i class="fa-solid fa-slash fa-stack-1x"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <img src="<?= esc(base_url('images/backend/admins/' . rawurlencode($admin->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($admin->uuid, 'attr'); ?>" aria-expanded="false" title="Mostra dettagli di sistema">
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
                                                                $statusClass ="app-text-success btn btn-link shadow-none fw-bold";
                                                            elseif($status === 0):
                                                                $statusText = lang('backend/admins.labels.unactive');
                                                                $statusClass ="app-text-danger btn btn-link shadow-none fw-bold";
                                                            endif;
                                                        ?>
                                                        <form <?= ui_binding('backend/admins.messages.areYouSureChangeStatus', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="changeStatus" data-controller="admins" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureChangeStatus'), $admin->firstname, $admin->lastname), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                                            <button type="submit" class="<?= esc($statusClass, 'attr'); ?>">
                                                                <?= ui_value($statusText, false); ?>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <?php if($isTrashed): ?>
                                                            <span class="badge app-bg-danger admin-deleted-status p-2 border-0 rounded-1"><?= ui_text('backend/admins.labels.deleted', [], false); ?></span>
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
                                                            <a href="<?= esc(base_url('backend/admins/show/' . rawurlencode($admin->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                                <i class="fa-solid fa-user fa-fw"></i> <?= ui_text('backend/admins.actions.show', [], false); ?>
                                                            </a>

                                                            <!-- Pulsante Aggiorna -->
                                                            <a href="<?= esc(base_url('backend/admins/edit/' . rawurlencode($admin->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                                <i class="fa-solid fa-user-pen fa-fw"></i> <?= ui_text('backend/admins.actions.edit', [], false); ?>
                                                            </a>

                                                            <!-- Pulsante Reset password -->
                                                            <form <?= ui_binding('backend/admins.messages.areYouSureReset', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="resetAdmin m-0 p-0" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureReset'), $admin->firstname, $admin->lastname), 'attr'); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                                                <button type="submit" class="btn badge app-bg-muted-subtle app-text-muted-emphasis action shadow-none w-100 border-0">
                                                                    <i class="fa-solid fa-unlock fa-fw"></i> <?= ui_text('backend/admins.actions.reset', [], false); ?>
                                                                </button>
                                                            </form>

                                                            <!-- Pulsante Elimina (Soft Delete) -->
                                                            <form <?= ui_binding('backend/admins.messages.areYouSureSoftDelete', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="deleteRecord m-0 p-0" data-controller="admins" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureSoftDelete'), $admin->firstname, $admin->lastname), 'attr'); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                                                <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                    <i class="fa-solid fa-xmark fa-fw"></i> <?= ui_text('backend/admins.actions.softDelete', [], false); ?>
                                                                </button>
                                                            </form>
                                                            
                                                        <?php else: ?>
                                                            
                                                            <!-- Pulsante Ripristina (Restore Delete) -->
                                                            <form <?= ui_binding('backend/admins.messages.areYouSureRestoreDelete', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="restoreRecord m-0 p-0" data-controller="admins" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureRestoreDelete'), $admin->firstname, $admin->lastname), 'attr'); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                                                <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0">
                                                                    <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= ui_text('backend/admins.actions.restoreDelete', [], false); ?>
                                                                </button>
                                                            </form>

                                                            <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                            <form <?= ui_binding('backend/admins.messages.areYouSureHardDelete', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="hardDeleteRecord m-0 p-0" data-controller="admins" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureHardDelete'), $admin->firstname, $admin->lastname), 'attr'); ?>">
                                                                <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
                                                                <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                    <i class="fa-solid fa-xmark fa-fw"></i> <?= ui_text('backend/admins.actions.hardDelete', [], false); ?>
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
                                                <div class="collapse" id="meta-<?= esc($admin->uuid, 'attr'); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom app-bg-surface app-text small">
                                                        
                                                        <!-- Parte creato -->
                                                        <?= ui_text('backend/admins.labels.createdAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($admin->created_at), 'conversational'); ?></span>

                                                        <!-- Parte aggiornato -->
                                                        <?php if( ! is_null($admin->updated_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/admins.labels.updatedAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($admin->updated_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte sospeso -->
                                                        <?php if( ! is_null($admin->suspended_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/admins.labels.suspendedAt', [], false); ?> <span class="fw-bold app-text-danger"><?= ui_date(esc($admin->suspended_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte resettato -->
                                                        <?php if( ! is_null($admin->resetted_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/admins.labels.resettedAt', [], false); ?> <span class="fw-bold app-text-warning"><?= ui_date(esc($admin->resetted_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte ripristinato -->
                                                        <?php if( ! is_null($admin->restored_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/admins.labels.restoredAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($admin->restored_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte eliminato (Cestinato) -->
                                                        <?php if($isTrashed): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/admins.labels.deletedAt', [], false); ?> <span class="fw-bold app-text-danger"><?= ui_date(esc($admin->deleted_at), 'conversational'); ?></span>
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
                <div class="text-center py-3 fw-bold"><?= ui_text('backend/admins.messages.noAdminsFound', [], false); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
