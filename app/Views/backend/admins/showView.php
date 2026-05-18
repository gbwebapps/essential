<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="show-admins-container">
                <?= $this->include('backend/admins/partials/show/showPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>