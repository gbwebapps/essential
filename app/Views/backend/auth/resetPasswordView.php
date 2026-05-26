<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="reset-password-auth-container">
                <?= $this->include('backend/auth/partials/resetPassword/resetPasswordPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>