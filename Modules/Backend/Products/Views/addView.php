<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>
    <div class="row">
        <div class="col-12">
            <div id="add-products-container">
                <?= $this->include(\Modules\Backend\Products\Config\Views::ADD_PARTIAL); ?>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
