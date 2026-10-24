<div class="card-body py-0">
    <div class="row">
        <div class="col-md-12">

            <!-- Visualizza la tabella soltanto quando sono presenti record -->
            <?php if(isset($data['records']) && count($data['records'])): ?>

                <!-- Paginazione superiore -->
                <?= $this->include('backend/template/paginationView'); ?>

                <div class="row">
                    <div class="col-md-12">

                        <div class="table-responsive border-top">

                            <!-- Indicatore dell'ordinamento corrente -->
                            <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-down"></i>' : '<i class="fa-solid fa-arrow-up"></i>'; ?>

                            <!-- Numero residuo di record usato dal gestore della paginazione -->
                            <div class="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>

                            <table class="table table-condensed mb-0 text-nowrap">
                                <thead>
                                    <tr class="sorting">

                                        <!-- Allegati -->
                                        <th style="width: 5%;" class="text-center">
                                            <i class="fa-solid fa-paperclip"></i>
                                        </th>

                                        <!-- Immagine -->
                                        <th style="width: 7.5%;" class="text-center">
                                            <i class="fa-solid fa-image"></i>
                                        </th>

                                        <!-- Apertura dettagli di sistema -->
                                        <th style="width: 2.5%;" class="text-center">&nbsp;</th>

                                        <!-- SKU -->
                                        <th style="width: 8%;">
                                            <a class="sort" href="#" data-column="sku" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'sku') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.sku'); ?> <?= (($posts['column'] == 'sku') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Prodotto -->
                                        <th style="width: 22%;">
                                            <a class="sort" href="#" data-column="product" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'product') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.product'); ?> <?= (($posts['column'] == 'product') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Quantità -->
                                        <th style="width: 8%;">
                                            <a class="sort" href="#" data-column="quantity" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'quantity') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.quantity'); ?> <?= (($posts['column'] == 'quantity') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Prezzo -->
                                        <th style="width: 10%;">
                                            <a class="sort" href="#" data-column="price" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'price') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.price'); ?> <?= (($posts['column'] == 'price') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Valore totale -->
                                        <th style="width: 12%;">
                                            <a class="sort" href="#" data-column="total_value" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'total_value') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.totalValue'); ?> <?= (($posts['column'] == 'total_value') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Stato -->
                                        <th style="width: 15%;" class="text-center">
                                            <a class="sort" href="#" data-column="status" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'status') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/products.labels.status'); ?> <?= (($posts['column'] == 'status') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Azioni rapide -->
                                        <th style="width: 10%;">&nbsp;</th>

                                    </tr>
                                </thead>

                                <tbody id="productsBody">

                                    <?php foreach($data['records'] as $product): ?>

                                        <?php $cover = ($product->cover ?? null); ?>
                                        <?php $isTrashed = ( ! is_null($product->deleted_at)); ?>

                                        <tr class="border-end border-start table-row-110">

                                            <!-- Numero allegati -->
                                            <td class="align-middle text-center border-end fw-bold py-3">
                                                <span class="badge app-bg-info product-attachments-badge"><?= $product->images_num; ?></span>
                                            </td>

                                            <!-- Copertina -->
                                            <td class="align-middle text-center border-end app-bg-surface product-cover-cell py-3<?= is_null($cover) ? ' no-preview-image' : ''; ?>">
                                                <?php if(is_null($cover)): ?>
                                                    <span class="fa-stack" role="img" aria-label="<?= esc(lang('backend/products.labels.noImage'), 'attr'); ?>">
                                                        <i class="fa-solid fa-camera fa-stack-1x"></i>
                                                        <i class="fa-solid fa-slash fa-stack-1x"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <img src="<?= esc(base_url('images/backend/products/' . rawurlencode($product->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                                <?php endif; ?>
                                            </td>

                                            <!-- Dettagli di sistema -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($product->uuid, 'attr'); ?>" aria-expanded="false">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- SKU -->
                                            <td class="align-middle fw-bold">
                                                <?= esc($product->sku); ?>
                                            </td>

                                            <!-- Prodotto -->
                                            <td class="align-middle fw-bold">
                                                <?= esc($product->product); ?>
                                            </td>

                                            <!-- Quantità -->
                                            <td class="align-middle">
                                                <?= esc((string) $product->quantity); ?>
                                            </td>

                                            <!-- Prezzo -->
                                            <td class="align-middle">
                                                <?= esc(number_format((float) $product->price, 2, ',', '.')); ?>
                                            </td>

                                            <!-- Valore totale -->
                                            <td class="align-middle fw-bold">
                                                <?= esc(number_format((float) $product->total_value, 2, ',', '.')); ?>
                                            </td>

                                            <!-- Stato -->
                                            <td class="align-middle text-center">

                                                <?php if( ! $isTrashed): ?>
                                                    <?php
                                                        $status = (int) $product->status;

                                                        if($status === 1):
                                                            $statusText = lang('backend/products.labels.active');
                                                            $statusClass = 'app-text-success btn btn-link shadow-none fw-bold';
                                                        else:
                                                            $statusText = lang('backend/products.labels.unactive');
                                                            $statusClass = 'app-text-danger btn btn-link shadow-none fw-bold';
                                                        endif;
                                                    ?>

                                                    <form class="changeStatus" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureChangeStatus'), $product->product), 'attr'); ?>">
                                                        <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                        <button type="submit" class="<?= esc($statusClass, 'attr'); ?>">
                                                            <?= $statusText; ?>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge app-bg-danger product-deleted-status p-2 border-0 rounded-1"><?= lang('backend/products.labels.deleted'); ?></span>
                                                <?php endif; ?>

                                            </td>

                                            <!-- Azioni rapide -->
                                            <td class="align-middle text-start">
                                                <div class="d-flex flex-column gap-1">

                                                    <?php if( ! $isTrashed): ?>

                                                        <a href="<?= esc(base_url('backend/products/show/' . rawurlencode($product->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-eye fa-fw"></i> <?= lang('backend/products.actions.show'); ?>
                                                        </a>

                                                        <a href="<?= esc(base_url('backend/products/edit/' . rawurlencode($product->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action">
                                                            <i class="fa-solid fa-pen fa-fw"></i> <?= lang('backend/products.actions.edit'); ?>
                                                        </a>

                                                        <form class="deleteRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureSoftDelete'), $product->product), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/products.actions.softDelete'); ?>
                                                            </button>
                                                        </form>

                                                    <?php else: ?>

                                                        <form class="restoreRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureRestoreDelete'), $product->product), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= lang('backend/products.actions.restoreDelete'); ?>
                                                            </button>
                                                        </form>

                                                        <form class="hardDeleteRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureHardDelete'), $product->product), 'attr'); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                            <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0">
                                                                <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/products.actions.hardDelete'); ?>
                                                            </button>
                                                        </form>

                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                            <!-- End Azioni rapide -->

                                        </tr>

                                        <!-- Riga collassabile con relazioni e metadati -->
                                        <tr class="border-end border-start">
                                            <td colspan="10" class="p-0 border-0">
                                                <div class="collapse" id="meta-<?= esc($product->uuid, 'attr'); ?>">
                                                    <div class="p-2 border-bottom app-bg-surface app-text small">

                                                        <?= lang('backend/products.labels.brands'); ?>:
                                                        <span class="fw-bold"><?= esc($product->brands ?? ''); ?></span>

                                                        &nbsp;&bull;&nbsp;
                                                        <?= lang('backend/products.labels.categories'); ?>:
                                                        <span class="fw-bold"><?= esc($product->categories ?? ''); ?></span>

                                                        &nbsp;&bull;&nbsp;
                                                        <?= lang('backend/products.labels.createdAt'); ?>
                                                        <span class="fw-bold app-text-success"><?= convertDate(esc($product->created_at), 'conversational'); ?></span>

                                                        <?php if( ! is_null($product->updated_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/products.labels.updatedAt'); ?>
                                                            <span class="fw-bold app-text-success"><?= convertDate(esc($product->updated_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <?php if( ! is_null($product->restored_at)): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/products.labels.restoredAt'); ?>
                                                            <span class="fw-bold app-text-success"><?= convertDate(esc($product->restored_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                        <?php if($isTrashed): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= lang('backend/products.labels.deletedAt'); ?>
                                                            <span class="fw-bold app-text-danger"><?= convertDate(esc($product->deleted_at), 'conversational'); ?></span>
                                                        <?php endif; ?>

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- End Riga collassabile con relazioni e metadati -->

                                    <?php endforeach; ?>

                                </tbody>
                            </table>

                        </div>

                    </div>
                </div>

                <!-- Paginazione inferiore -->
                <?= $this->include('backend/template/paginationView'); ?>

            <?php else: ?>

                <div class="text-center py-3 fw-bold"><?= lang('backend/products.messages.noProductsFound'); ?></div>

            <?php endif; ?>

        </div>
    </div>
</div>
