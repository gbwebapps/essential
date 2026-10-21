<div class="card-body py-0">
    <div class="row">
        <div class="col-md-12">
            <?php if(isset($data['records']) && count($data['records'])): ?>
                <?= $this->include('backend/template/paginationView'); ?>
                <div class="table-responsive border-top">
                    <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-down"></i>' : '<i class="fa-solid fa-arrow-up"></i>'; ?>
                    <div class="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>
                    <table class="table table-condensed mb-0 text-nowrap">
                        <thead>
                            <tr class="sorting">
                                <th class="text-center"><i class="fa-solid fa-paperclip"></i></th>
                                <th class="text-center"><i class="fa-solid fa-image"></i></th>
                                <th>&nbsp;</th>
                                <?php foreach(['sku', 'product', 'quantity', 'price', 'status'] as $column): ?>
                                    <th class="<?= $column === 'status' ? 'text-center' : ''; ?>">
                                        <a class="sort" href="#" data-column="<?= esc($column, 'attr'); ?>" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == $column) ? 'asc' : 'desc'); ?>">
                                            <?= lang('backend/products.labels.' . $column); ?><?= (($posts['column'] == $column) ? '&nbsp;' . $icon : ''); ?>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody id="productsBody">
                            <?php foreach($data['records'] as $product): ?>
                                <?php $cover = ($product->cover ?? null); ?>
                                <?php $isTrashed = ( ! is_null($product->deleted_at)); ?>
                                <tr class="border-end border-start table-row-110">
                                    <td class="align-middle text-center border-end fw-bold py-3"><span class="badge app-bg-info product-attachments-badge"><?= $product->images_num; ?></span></td>
                                    <td class="align-middle text-center border-end app-bg-surface product-cover-cell py-3<?= is_null($cover) ? ' no-preview-image' : ''; ?>">
                                        <?php if(is_null($cover)): ?>
                                            <span class="fa-stack" role="img" aria-label="<?= esc(lang('backend/products.labels.noImage'), 'attr'); ?>"><i class="fa-solid fa-camera fa-stack-1x"></i><i class="fa-solid fa-slash fa-stack-1x"></i></span>
                                        <?php else: ?>
                                            <img src="<?= esc(base_url('images/backend/products/' . rawurlencode($product->uuid) . '/small/' . rawurlencode($cover)), 'attr'); ?>" class="img-polaroid" alt="">
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle fw-bold">
                                        <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 app-text shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($product->uuid, 'attr'); ?>" aria-expanded="false"><i class="fa-solid fa-chevron-right"></i></button>
                                    </td>
                                    <td class="align-middle fw-bold"><?= esc($product->sku); ?></td>
                                    <td class="align-middle fw-bold"><?= esc($product->product); ?></td>
                                    <td class="align-middle"><?= esc((string) $product->quantity); ?></td>
                                    <td class="align-middle"><?= esc(number_format((float) $product->price, 2, ',', '.')); ?></td>
                                    <td class="align-middle text-center">
                                        <?php if( ! $isTrashed): ?>
                                            <?php $statusText = ((int) $product->status === 1) ? lang('backend/products.labels.active') : lang('backend/products.labels.unactive'); ?>
                                            <?php $statusClass = ((int) $product->status === 1) ? 'app-text-success' : 'app-text-danger'; ?>
                                            <form class="changeStatus" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureChangeStatus'), $product->product), 'attr'); ?>">
                                                <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                <button type="submit" class="<?= $statusClass; ?> btn btn-link shadow-none fw-bold"><?= $statusText; ?></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge app-bg-danger product-deleted-status p-2 border-0 rounded-1"><?= lang('backend/products.labels.deleted'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-start">
                                        <div class="d-flex flex-column gap-1">
                                            <?php if( ! $isTrashed): ?>
                                                <a href="<?= esc(base_url('backend/products/show/' . rawurlencode($product->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action"><i class="fa-solid fa-eye fa-fw"></i> <?= lang('backend/products.actions.show'); ?></a>
                                                <a href="<?= esc(base_url('backend/products/edit/' . rawurlencode($product->uuid)), 'attr'); ?>" class="text-decoration-none badge app-bg-muted-subtle app-text-muted-emphasis action"><i class="fa-solid fa-pen fa-fw"></i> <?= lang('backend/products.actions.edit'); ?></a>
                                                <form class="deleteRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureSoftDelete'), $product->product), 'attr'); ?>">
                                                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                    <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0"><i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/products.actions.softDelete'); ?></button>
                                                </form>
                                            <?php else: ?>
                                                <form class="restoreRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureRestoreDelete'), $product->product), 'attr'); ?>">
                                                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                    <button type="submit" class="btn badge app-bg-success-subtle app-text-success-emphasis action shadow-none w-100 border-0"><i class="fa-solid fa-trash-arrow-up fa-fw"></i> <?= lang('backend/products.actions.restoreDelete'); ?></button>
                                                </form>
                                                <form class="hardDeleteRecord m-0 p-0" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureHardDelete'), $product->product), 'attr'); ?>">
                                                    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
                                                    <button type="submit" class="btn badge app-bg-danger-subtle app-text-danger-emphasis action shadow-none w-100 border-0"><i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/products.actions.hardDelete'); ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-end border-start">
                                    <td colspan="9" class="p-0 border-0">
                                        <div class="collapse" id="meta-<?= esc($product->uuid, 'attr'); ?>">
                                            <div class="p-2 border-bottom app-bg-surface app-text small">
                                                <?= lang('backend/products.labels.brands'); ?>: <span class="fw-bold"><?= esc($product->brands ?? ''); ?></span>
                                                &nbsp;&bull;&nbsp; <?= lang('backend/products.labels.categories'); ?>: <span class="fw-bold"><?= esc($product->categories ?? ''); ?></span>
                                                &nbsp;&bull;&nbsp; <?= lang('backend/products.labels.createdAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($product->created_at), 'conversational'); ?></span>
                                                <?php if( ! is_null($product->updated_at)): ?>&nbsp;&bull;&nbsp; <?= lang('backend/products.labels.updatedAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($product->updated_at), 'conversational'); ?></span><?php endif; ?>
                                                <?php if( ! is_null($product->restored_at)): ?>&nbsp;&bull;&nbsp; <?= lang('backend/products.labels.restoredAt'); ?> <span class="fw-bold app-text-success"><?= convertDate(esc($product->restored_at), 'conversational'); ?></span><?php endif; ?>
                                                <?php if($isTrashed): ?>&nbsp;&bull;&nbsp; <?= lang('backend/products.labels.deletedAt'); ?> <span class="fw-bold app-text-danger"><?= convertDate(esc($product->deleted_at), 'conversational'); ?></span><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= $this->include('backend/template/paginationView'); ?>
            <?php else: ?>
                <div class="text-center py-3 fw-bold"><?= lang('backend/products.messages.noProductsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
