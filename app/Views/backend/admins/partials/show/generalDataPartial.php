<div class="card-body">
    <div class="row mb-3">
        <div class="col-6 ">
            <!-- Campo firstname -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.firstname'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->firstname); ?></li>
            </ul>
        </div>
        <div class="col-6">
            <!-- Campo lastname -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.lastname'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->lastname); ?></li>
            </ul>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-6">
            <!-- Campo email -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.email'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->email); ?></li>
            </ul>
        </div>
        <div class="col-6">
            <!-- Campo phone -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.phone'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->phone); ?></li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <!-- Campo status -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.status'); ?></li>
                <li class="list-group-item">
                    <div id="change_status_partial">
                        <?= $this->include('backend/admins/partials/show/statusDataPartial', $this->data); ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <!-- Campo note -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.notes'); ?></li>
                <li class="list-group-item fw-bold">
                    <?= is_null($admin->notes) ? lang('backend/admins.messages.no_notes') : esc($admin->notes); ?>
                </li>
            </ul>
        </div>
    </div>
</div>