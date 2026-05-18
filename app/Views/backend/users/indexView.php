<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-users-container">
                <?= $this->include('backend/users/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>