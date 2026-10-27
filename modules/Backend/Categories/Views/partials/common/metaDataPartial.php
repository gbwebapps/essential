<div class="card-body">
    <div class="row">

        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/categories.labels.createdAt', [], false); ?></li>
                <li class="list-group-item fw-bold"><?= ui_date(esc($category->created_at), 'conversational'); ?></li>
            </ul>
        </div>

        <?php if( ! is_null($category->updated_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/categories.labels.updatedAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($category->updated_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($category->restored_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/categories.labels.restoredAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($category->restored_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>
