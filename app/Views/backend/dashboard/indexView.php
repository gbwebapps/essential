<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-lg-12">
            <div id="index-dashboard-container">
                <?= $this->include('backend/dashboard/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>