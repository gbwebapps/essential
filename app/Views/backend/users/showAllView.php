<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="showAll-users-container">
                <?= $this->include('backend/users/partials/showAll/showAllPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>