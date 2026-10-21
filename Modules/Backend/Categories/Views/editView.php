<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="edit-categories-container">
                <?= $this->include(\Modules\Backend\Categories\Config\Views::EDIT_PARTIAL); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>
