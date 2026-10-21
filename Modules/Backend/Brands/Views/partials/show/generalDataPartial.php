<div class="card-body">
    <div class="row mb-3">

        <!-- Campo brand e status -->
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/brands.labels.brand'); ?></li>
                <li class="list-group-item fw-bold"><?= esc($brand->brand); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-6">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/brands.labels.status'); ?></li>
                <li class="list-group-item">
                    <div id="changeStatusPartial">
                        <?= $this->include(\Modules\Backend\Brands\Config\Views::CHANGE_STATUS_PARTIAL, $this->data); ?>
                    </div>
                </li>
            </ul>
        </div>
        <!-- End Campo brand e status -->

    </div>

    <?php if( ! empty($brand->note)): ?>
        <div class="row">

            <!-- Campo note -->
            <div class="col-12">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= lang('backend/brands.labels.note'); ?></li>
                    <li class="list-group-item fw-bold"><?= esc($brand->note); ?></li>
                </ul>
            </div>
            <!-- End Campo note -->

        </div>
    <?php endif; ?>
</div>