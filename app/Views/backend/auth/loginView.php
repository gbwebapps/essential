<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-lg-12">
            <div id="login-auth-container">
                <?= $this->include('backend/auth/partials/login/loginPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>