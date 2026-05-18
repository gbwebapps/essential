<div class="card-body">
    <div class="row">

        <div class="col-md-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item fw-bold"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/admins.labels.created'); ?></li>
                <li class="list-group-item"><?= convertDate(esc($admin->created_at)); ?></li>
            </ul>
        </div>

        <?php if( ! is_null($admin->updated_at)): ?>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item fw-bold"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/admins.labels.updated'); ?></li>
                    <li class="list-group-item"><?= convertDate(esc($admin->updated_at)); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($admin->suspended_at)): ?>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item fw-bold"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/admins.labels.suspended'); ?></li>
                    <li class="list-group-item"><?= convertDate(esc($admin->suspended_at)); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if( ! is_null($admin->resetted_at)): ?>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item fw-bold"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/admins.labels.resetted'); ?></li>
                    <li class="list-group-item"><?= convertDate(esc($admin->resetted_at)); ?></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</div>