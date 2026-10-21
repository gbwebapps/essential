<div class="card-body">
    <div class="row">

        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/brands.labels.createdAt'); ?></li>
                <li class="list-group-item fw-bold"><?= convertDate(esc($brand->created_at), 'conversational'); ?></li>
            </ul>
        </div>

        <?php if( ! is_null($brand->updated_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/brands.labels.updatedAt'); ?></li>
                    <li class="list-group-item fw-bold"><?= convertDate(esc($brand->updated_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($brand->restored_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/brands.labels.restoredAt'); ?></li>
                    <li class="list-group-item fw-bold"><?= convertDate(esc($brand->restored_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>
