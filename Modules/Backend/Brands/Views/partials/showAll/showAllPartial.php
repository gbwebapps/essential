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
                                                <?= lang('backend/brands.labels.brand'); ?> <?= (($posts['column'] == 'brand') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna status -->
                                        <th style="width: 47.5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/brands.labels.status'); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
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
                                                    <span class="fa-stack" role="img" aria-label="<?= esc(lang('backend/brands.labels.noImage'), 'attr'); ?>">
                                                        <i class="fa-solid fa-camera fa-stack-1x"></i>
                                                        <i class="fa-solid fa-slash fa-stack-1x"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <img src="<?= esc(base_url('images/backend/brands/' . rawurlencode($brand->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($brand->uuid, 'attr'); ?>" aria-expanded="false" title="<?= esc(lang('backend/brands.buttons.showSystemDetails'), 'attr'); ?>">
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
                                                    <form class="changeStatus" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureChangeStatus'), $brand->brand), 'attr'); ?>">
                                                        <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                        <button type="submit" class="<?= esc($statusClass, 'attr'); ?>">
                                                            <?= $statusText; ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if($isTrashed): ?>
                                                        <span class="badge app-bg-danger brand-deleted-status p-2 border-0 rounded-1"><?= lang('backend/brands.labels.deleted'); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                            </td>

                                            <!-- Cella Azioni Rapide -->
                                            <td class="align-middle text-start">
                                                
                                                <div class="d-flex flex-column gap-1">
                                                    
                                                    <?php if( ! $isTrashed): ?>
                                                        
                                                        <!-- Pulsante Dettaglio -->
                                                        <a href="<?= esc(base_url('backend/brands/show/' . rawurlencode($brand->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-eye fa-fw"></i> <?= lang('backend/brands.actions.show'); ?>
                                                        </a>

                                                        <!-- Pulsante Aggiorna -->
                                                        <a href="<?= esc(base_url('backend/brands/edit/' . rawurlencode($brand->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-pen fa-fw"></i> <?= lang('backend/brands.actions.edit'); ?>
                                                        </a>

                                                        <!-- Pulsante Elimina (Soft Delete) -->
                                                        <form class="deleteRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureSoftDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/brands.actions.softDelete'); ?>
                                                            </button>
                                                        </form>
                                                        
                                                    <?php else: ?>
                                                        
                                                        <!-- Pulsante Ripristina (Restore Delete) -->
                                                        <form class="restoreRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureRestoreDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= lang('backend/brands.actions.restoreDelete'); ?>
                                                            </button>
                                                        </form>

                                                        <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                        <form class="hardDeleteRecord m-0 p-0" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureHardDelete'), $brand->brand), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/brands.actions.hardDelete'); ?>
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
                                                        <?= lang('backend/brands.labels.createdAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($brand->created_at), 'conversational'); ?></span>

                                                        <!-- Parte aggiornato -->
                                                        <?php if( ! is_null($brand->updated_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/brands.labels.updatedAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($brand->updated_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte ripristinato -->
                                                        <?php if( ! is_null($brand->restored_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/brands.labels.restoredAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($brand->restored_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte eliminato (Cestinato) -->
                                                        <?php if($isTrashed): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/brands.labels.deletedAt'); ?> <span class="fw-bold app-text-danger"><?= convertDate(esc($brand->deleted_at), 'conversational'); ?></span>
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
                <div class="text-center py-3 fw-bold"><?= lang('backend/brands.messages.noBrandsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
