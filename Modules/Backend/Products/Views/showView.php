<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="show-products-container">
                <?= $this->include(\Modules\Backend\Products\Config\Views::SHOW_PARTIAL); ?>
            </div>
        </div>
    </div>

    <!-- Modale esportazione PDF -->
    <?= $this->include('backend/template/exportPdfView', $this->data); ?>
    <!-- End Modale esportazione PDF -->

<?= $this->endSection() ?>
