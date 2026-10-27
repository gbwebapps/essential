<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="edit-products-container">
                <?= $this->include(\Modules\Backend\Products\Config\Views::EDIT_PARTIAL); ?>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
