<div class="card-body">
    <div class="row mb-3">

        <!-- Campo nome e cognome -->
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.firstname'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->firstname); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-6">
            <!-- Campo lastname -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.lastname'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->lastname); ?></li>
            </ul>
        </div>
        <!-- End Campo nome e cognome -->

    </div>
    <div class="row mb-3">

        <!-- Campo email e telefono -->
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.email'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->email); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.phone'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->phone); ?></li>
            </ul>
        </div>
        <!-- End Campo email e telefono -->

    </div>
    <div class="row">

        <!-- Campo gruppo e status -->
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.group'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($admin->groupName); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.status'); ?></li>
                <li class="list-group-item">
                    <div id="changeStatusPartial">
                        <?= $this->include('backend/admins/partials/show/changeStatusPartial', $this->data); ?>
                    </div>
                </li>
            </ul>
        </div>
        <!-- End Campo gruppo e status -->

    </div>
    <?php if( ! empty($admin->note)): ?>
        <div class="row">

            <!-- Campo note -->
            <div class="col-12">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/admins.labels.note'); ?></li>
                    <li class="list-group-item fw-bold"><?= esc($admin->note); ?></li>
                </ul>
            </div>
            <!-- End Campo note -->

        </div>
    <?php endif; ?>
</div>