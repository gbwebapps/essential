<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="showAll-admins-container">
                <?= $this->include('backend/admins/partials/showAll/showAllPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>