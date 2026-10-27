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

                                        <!-- Colonna brand -->
                                        <th style="width: 30%;">
                                            <a class="sort" href="#" data-column="brand" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'brand') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/brands.labels.brand', [], false); ?> <?= (($posts['column'] == 'brand') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna status -->
                                        <th style="width: 47.5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= ui_text('backend/brands.labels.status', [], false); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna azioni -->
                                        <th style="width: 7.5%;">&nbsp;</th>

                                    </tr>
                                </thead>
                                <tbody id="brandsBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $brand): ?>

                                        <!-- Definisco un default se non c'é immagine -->
                                        <?php $cover = ($brand->cover ?? null); ?>
                                        <?php $isTrashed = ( ! is_null($brand->deleted_at)); ?>
                                        
                                        <tr class="border-end border-start table-row-110">

                                            <!-- Cella allegati (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end fw-bold py-3">
                                                <span class="badge app-bg-info brand-attachments-badge"><?= $brand->images_num; ?></span>
                                            </td>

                                            <!-- Cella immagine (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end app-bg-surface brand-cover-cell py-3<?= is_null($cover) ? ' no-preview-image' : ''; ?>">
                                                <?php if(is_null($cover)): ?>
                                                    <span <?= ui_binding('backend/brands.labels.noImage', 'aria-label', [], true); ?> class="fa-stack" role="img" aria-label="<?= esc(lang('backend/brands.labels.noImage'), 'attr'); ?>">
                                                        <i class="fa-solid fa-camera fa-stack-1x"></i>
                                                        <i class="fa-solid fa-slash fa-stack-1x"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <img src="<?= esc(base_url('images/backend/brands/' . rawurlencode($brand->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button <?= ui_binding('backend/brands.buttons.showSystemDetails', 'title', [], true); ?> class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($brand->uuid, 'attr'); ?>" aria-expanded="false" title="<?= esc(lang('backend/brands.buttons.showSystemDetails'), 'attr'); ?>">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- Cella brand -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($brand->brand); ?></span>
                                            </td>

                                            <!-- Cella status -->
                                            <td class="align-middle text-center">

                                                <?php if( ! $isTrashed): ?>
                                                    <?php
                                                        $status = (int) $brand->status;

                                                        if($status === 1):
                                                            $statusText = lang('backend/brands.labels.active');
                                                            $statusClass ="app-text-success btn btn-link shadow-none fw-bold";
                                                        elseif($status === 0):
                                                            $statusText = lang('backend/brands.labels.unactive');
                                                            $statusClass ="app-text-danger btn btn-link shadow-none fw-bold";
                                                        endif;
                                                    ?>
                                                    <form <?= ui_binding('backend/brands.messages.areYouSureChangeStatus', 'data-message', [$brand->brand], true); ?> class="changeStatus" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureChangeStatus'), $brand->brand), 'attr'); ?>">
                                                        <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                        <button type="submit" class="<?= esc($statusClass, 'attr'); ?>">
                                                            <?= ui_value($statusText, false); ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if($isTrashed): ?>
                                                        <span class="badge app-bg-danger brand-deleted-status p-2 border-0 rounded-1"><?= ui_text('backend/brands.labels.deleted', [], false); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                            </td>

                                            <!-- Cella Azioni Rapide -->
                                            <td class="align-middle text-start">
                                                
                                                <div class="d-flex flex-column gap-1">
                                                    
                                                    <?php if( ! $isTrashed): ?>
                                                        
                                                        <!-- Pulsante Dettaglio -->
                                                        <a href="<?= esc(base_url('backend/brands/show/' . rawurlencode($brand->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-eye fa-fw"></i> <?= ui_text('backend/brands.actions.show', [], false); ?>
                                                        </a>

                                                        <!-- Pulsante Aggiorna -->
                                                        <a href="<?= esc(base_url('backend/brands/edit/' . rawurlencode($brand->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-pen fa-fw"></i> <?= ui_text('backend/brands.actions.edit', [], false); ?>
                                                        </a>

                                                        <!-- Pulsante Elimina (Soft Delete) -->
                                                        <form <?= ui_binding('backend/brands.messages.areYouSureSoftDelete', 'data-message', [$brand->brand], true); ?> class="deleteRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureSoftDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= ui_text('backend/brands.actions.softDelete', [], false); ?>
                                                            </button>
                                                        </form>
                                                        
                                                    <?php else: ?>
                                                        
                                                        <!-- Pulsante Ripristina (Restore Delete) -->
                                                        <form <?= ui_binding('backend/brands.messages.areYouSureRestoreDelete', 'data-message', [$brand->brand], true); ?> class="restoreRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureRestoreDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= ui_text('backend/brands.actions.restoreDelete', [], false); ?>
                                                            </button>
                                                        </form>

                                                        <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                        <form <?= ui_binding('backend/brands.messages.areYouSureHardDelete', 'data-message', [$brand->brand], true); ?> class="hardDeleteRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureHardDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= ui_text('backend/brands.actions.hardDelete', [], false); ?>
                                                            </button>
                                                        </form>
                                                        
                                                    <?php endif; ?>
                                                    
                                                </div>
                                                
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore (Metadati a comparsa fluida) -->
                                        <tr class="border-end border-start">

                                            <!-- Colspan 6 copre l'intera tabella. Nessun padding sul td per evitare scatti nell'animazione -->
                                            <td colspan="6" class="p-0 border-0">
                                                
                                                <!-- Contenitore collassabile puntato dal bottone -->
                                                <div class="collapse" id="meta-<?= esc($brand->uuid, 'attr'); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom app-bg-surface app-text small">
                                                        
                                                        <!-- Parte creato -->
                                                        <?= ui_text('backend/brands.labels.createdAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($brand->created_at), 'conversational'); ?></span>

                                                        <!-- Parte aggiornato -->
                                                        <?php if( ! is_null($brand->updated_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/brands.labels.updatedAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($brand->updated_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte ripristinato -->
                                                        <?php if( ! is_null($brand->restored_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/brands.labels.restoredAt', [], false); ?> <span class="fw-bold app-text-success"><?= ui_date(esc($brand->restored_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte eliminato (Cestinato) -->
                                                        <?php if($isTrashed): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= ui_text('backend/brands.labels.deletedAt', [], false); ?> <span class="fw-bold app-text-danger"><?= ui_date(esc($brand->deleted_at), 'conversational'); ?></span>
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
                <div class="text-center py-3 fw-bold"><?= ui_text('backend/brands.messages.noBrandsFound', [], false); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
