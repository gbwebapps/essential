<div class="card-body">
    <div class="row">
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/products.labels.createdAt'); ?></li>
                <li class="list-group-item fw-bold"><?= convertDate(esc($product->created_at), 'conversational'); ?></li>
            </ul>
        </div>
        <?php if( ! is_null($product->updated_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/products.labels.updatedAt'); ?></li>
                    <li class="list-group-item fw-bold"><?= convertDate(esc($product->updated_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
        <?php if( ! is_null($product->restored_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/products.labels.restoredAt'); ?></li>
                    <li class="list-group-item fw-bold"><?= convertDate(esc($product->restored_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
