<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="verify-auth-container">
                <?= $this->include('backend/auth/partials/verify/verifyPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>