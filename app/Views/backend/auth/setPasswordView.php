<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="set-password-auth-container">
                <?= $this->include('backend/auth/partials/setPassword/setPasswordPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>