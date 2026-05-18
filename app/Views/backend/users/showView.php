<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="show-users-container">
                <?= $this->include('backend/users/partials/show/showPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>