<div class="card-body">

    <!-- Identificazione e stato -->
    <div class="row mb-3">
        <div class="col-12 col-lg-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.sku', [], false); ?>
                </li>
                <li class="list-group-item fw-bold"><?= esc($product->sku); ?></li>
            </ul>
        </div>

        <div class="col-12 col-lg-5">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.product', [], false); ?>
                </li>
                <li class="list-group-item fw-bold"><?= esc($product->product); ?></li>
            </ul>
        </div>

        <div class="col-12 col-lg-3">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.status', [], false); ?>
                </li>
                <li class="list-group-item">
                    <div id="changeStatusPartial">
                        <?= $this->include(\Modules\Backend\Products\Config\Views::CHANGE_STATUS_PARTIAL, $this->data); ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <!-- End Identificazione e stato -->

    <!-- Quantità e prezzo -->
    <div class="row mb-3">
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.quantity', [], false); ?>
                </li>
                <li class="list-group-item fw-bold"><?= esc((string) $product->quantity); ?></li>
            </ul>
        </div>

        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.price', [], false); ?>
                </li>
                <li class="list-group-item fw-bold"><?= esc(number_format((float) $product->price, 2, ',', '.')); ?></li>
            </ul>
        </div>
    </div>
    <!-- End Quantità e prezzo -->

    <!-- Associazioni -->
    <div class="row mb-3">
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.brands', [], false); ?>
                </li>
                <li class="list-group-item fw-bold">
                    <?php foreach($product->brands as $brand): ?>
                        <span class="badge app-bg-muted-subtle app-text-muted-emphasis me-1 mb-1"><?= esc($brand->brand); ?></span>
                    <?php endforeach; ?>
                </li>
            </ul>
        </div>

        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.categories', [], false); ?>
                </li>
                <li class="list-group-item fw-bold">
                    <?php foreach($product->categories as $category): ?>
                        <span class="badge app-bg-muted-subtle app-text-muted-emphasis me-1 mb-1"><?= esc($category->path); ?></span>
                    <?php endforeach; ?>
                </li>
            </ul>
        </div>
    </div>
    <!-- End Associazioni -->

    <!-- Note aggiuntive -->
    <?php if( ! empty($product->note)): ?>
        <div class="row">
            <div class="col-12">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/products.labels.note', [], false); ?>
                    </li>
                    <li class="list-group-item fw-bold"><?= esc($product->note); ?></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <!-- End Note aggiuntive -->

</div>
