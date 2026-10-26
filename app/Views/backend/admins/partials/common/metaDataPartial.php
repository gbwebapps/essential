<div class="card-body">
    <div class="row">

        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/admins.labels.createdAt', [], false); ?></li>
                <li class="list-group-item fw-bold"><?= ui_date(esc($admin->created_at), 'conversational'); ?></li>
            </ul>
        </div>

        <?php if( ! is_null($admin->updated_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/admins.labels.updatedAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($admin->updated_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($admin->suspended_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/admins.labels.suspendedAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($admin->suspended_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($admin->resetted_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/admins.labels.resettedAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($admin->resetted_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($admin->restored_at)): ?>
            <div class="col-12 col-lg-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-circle-arrow-down"></i> <?= ui_text('backend/admins.labels.restoredAt', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= ui_date(esc($admin->restored_at), 'conversational'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>
