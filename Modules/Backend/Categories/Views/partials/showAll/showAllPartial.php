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

                                        <!-- Colonna category -->
                                        <th style="width: 25%;">
                                            <a class="sort" href="#" data-column="category" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'category') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/categories.labels.category'); ?> <?= (($posts['column'] == 'category') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna percorso -->
                                        <th style="width: 25%;">
                                            <?= lang('backend/categories.labels.path'); ?>
                                        </th>

                                        <!-- Colonna status -->
                                        <th style="width: 27.5%; text-align: center;">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/categories.labels.status'); ?>&nbsp;<?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna azioni -->
                                        <th style="width: 7.5%;">&nbsp;</th>

                                    </tr>
                                </thead>
                                <tbody id="categoriesBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $category): ?>

                                        <!-- Definisco un default se non c'é immagine -->
                                        <?php $cover = ($category->cover ?? null); ?>
                                        <?php $isTrashed = ( ! is_null($category->deleted_at)); ?>
                                        
                                        <tr class="border-end border-start table-row-110">

                                            <!-- Cella allegati (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end fw-bold py-3">
                                                <span class="badge app-bg-info category-attachments-badge"><?= $category->images_num; ?></span>
                                            </td>

                                            <!-- Cella immagine (rowspan rimosso, aggiunto py-3 per altezza) -->
                                            <td class="align-middle text-center border-end app-bg-surface category-cover-cell py-3<?= is_null($cover) ? ' no-preview-image' : ''; ?>">
                                                <?php if(is_null($cover)): ?>
                                                    <span class="fa-stack" role="img" aria-label="<?= esc(lang('backend/categories.labels.noImage'), 'attr'); ?>">
                                                        <i class="fa-solid fa-camera fa-stack-1x"></i>
                                                        <i class="fa-solid fa-slash fa-stack-1x"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <img src="<?= esc(base_url('images/backend/categories/' . rawurlencode($category->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($category->uuid, 'attr'); ?>" aria-expanded="false" title="<?= esc(lang('backend/categories.buttons.showSystemDetails'), 'attr'); ?>">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- Cella category -->
                                            <td class="align-middle fw-bold">
                                                <span><?= esc($category->category); ?></span>
                                            </td>

                                            <!-- Cella percorso -->
                                            <td class="align-middle">
                                                <span><?= esc($category->category_path ?? $category->category); ?></span>
                                            </td>

                                            <!-- Cella status -->
                                            <td class="align-middle text-center">

                                                <?php if( ! $isTrashed): ?>
                                                    <?php
                                                        $status = (int) $category->status;

                                                        if($status === 1):
                                                            $statusText = lang('backend/categories.labels.active');
                                                            $statusClass ="app-text-success btn btn-link shadow-none fw-bold";
                                                        elseif($status === 0):
                                                            $statusText = lang('backend/categories.labels.unactive');
                                                            $statusClass ="app-text-danger btn btn-link shadow-none fw-bold";
                                                        endif;
                                                    ?>
                                                    <form class="changeStatus" data-controller="categories" data-message="<?= esc(sprintf(lang('backend/categories.messages.areYouSureChangeStatus'), $category->category), 'attr'); ?>">
                                                        <input type="hidden" name="uuid" value="<?= esc($category->uuid, 'attr'); ?>">
                                                        <button type="submit" class="<?= esc($statusClass, 'attr'); ?>">
                                                            <?= $statusText; ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if($isTrashed): ?>
                                                        <span class="badge app-bg-danger category-deleted-status p-2 border-0 rounded-1"><?= lang('backend/categories.labels.deleted'); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                            </td>

                                            <!-- Cella Azioni Rapide -->
                                            <td class="align-middle text-start">
                                                
                                                <div class="d-flex flex-column gap-1">
                                                    
                                                    <?php if( ! $isTrashed): ?>
                                                        
                                                        <!-- Pulsante Dettaglio -->
                                                        <a href="<?= esc(base_url('backend/categories/show/' . rawurlencode($category->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-eye fa-fw"></i> <?= lang('backend/categories.actions.show'); ?>
                                                        </a>

                                                        <!-- Pulsante Aggiorna -->
                                                        <a href="<?= esc(base_url('backend/categories/edit/' . rawurlencode($category->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-pen fa-fw"></i> <?= lang('backend/categories.actions.edit'); ?>
                                                        </a>

                                                        <!-- Pulsante Elimina (Soft Delete) -->
                                                        <form class="deleteRecord m-0 p-0" data-controller="categories" data-message="<?= esc(sprintf(lang('backend/categories.messages.areYouSureSoftDelete'), $category->category), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($category->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/categories.actions.softDelete'); ?>
                                                            </button>
                                                        </form>
                                                        
                                                    <?php else: ?>
                                                        
                                                        <!-- Pulsante Ripristina (Restore Delete) -->
                                                        <form class="restoreRecord m-0 p-0" data-controller="categories" data-message="<?= esc(sprintf(lang('backend/categories.messages.areYouSureRestoreDelete'), $category->category), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($category->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= lang('backend/categories.actions.restoreDelete'); ?>
                                                            </button>
                                                        </form>

                                                        <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                        <form class="hardDeleteRecord m-0 p-0" data-controller="categories" data-message="<?= esc(sprintf(lang('backend/categories.messages.areYouSureHardDelete'), $category->category), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($category->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/categories.actions.hardDelete'); ?>
                                                            </button>
                                                        </form>
                                                        
                                                    <?php endif; ?>
                                                    
                                                </div>
                                                
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore (Metadati a comparsa fluida) -->
                                        <tr class="border-end border-start">

                                            <!-- Colspan 7 copre l'intera tabella. Nessun padding sul td per evitare scatti nell'animazione -->
                                            <td colspan="7" class="p-0 border-0">
                                                
                                                <!-- Contenitore collassabile puntato dal bottone -->
                                                <div class="collapse" id="meta-<?= esc($category->uuid, 'attr'); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom app-bg-surface app-text small">
                                                        
                                                        <!-- Parte creato -->
                                                        <?= lang('backend/categories.labels.createdAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($category->created_at), 'conversational'); ?></span>

                                                        <!-- Parte aggiornato -->
                                                        <?php if( ! is_null($category->updated_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/categories.labels.updatedAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($category->updated_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte ripristinato -->
                                                        <?php if( ! is_null($category->restored_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/categories.labels.restoredAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($category->restored_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <!-- Parte eliminato (Cestinato) -->
                                                        <?php if($isTrashed): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/categories.labels.deletedAt'); ?> <span class="fw-bold app-text-danger"><?= convertDate(esc($category->deleted_at), 'conversational'); ?></span>
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
                <div class="text-center py-3 fw-bold"><?= lang('backend/categories.messages.noCategoriesFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= view(\Modules\Backend\Categories\Config\Views::CATEGORY_TREE_PARTIAL, [
    'categoryTreeDisplay' => 'modal',
    'categoryTree' => $categoryTree ?? [],
    'selectedCategoryUuid' => null,
    'selectedCategoryPath' => null,
]); ?>
