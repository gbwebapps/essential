<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-brands-container">
                <?= $this->include(\Modules\Backend\Brands\Config\Views::INDEX_PARTIAL); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>
