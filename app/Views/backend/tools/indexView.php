<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-tools-container">
                <?= $this->include('backend/tools/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>