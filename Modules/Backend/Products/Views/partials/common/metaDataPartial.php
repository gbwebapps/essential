<div class="card-body">
    <div class="row">

        <!-- Data di creazione -->
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/products.labels.createdAt', [], false); ?>
                </li>
                <li class="list-group-item fw-bold"><?= ui_date(esc($product->created_at), 'conversational'); ?></li>
            </ul>
        </div>
        <!-- End Data di creazione -->

        <!-- Data di aggiornamento -->
        <?php if( ! is_null($product->updated_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/products.labels.updatedAt', [], false); ?>
                    </li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($product->updated_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
        <!-- End Data di aggiornamento -->

        <!-- Data di ripristino -->
        <?php if( ! is_null($product->restored_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/products.labels.restoredAt', [], false); ?>
                    </li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($product->restored_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
        <!-- End Data di ripristino -->

    </div>
</div>
