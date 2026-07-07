<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <div class="col-2">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-7 offset-1">
            <div class="card mt-3 mt-lg-0">
                <div class="card-header">
                    <h2 class="card-title text-center text-lg-start mb-0">
                        <?= $sections['security']['icon']; ?>
                        <?= lang('backend/account.titles.security'); ?>
                    </h2>
                </div>
                <div id="security-account-container">
                    <?= $this->include('backend/account/partials/security/securityOptionsPartial'); ?>
                </div>
            </div>
        </div>
        
        <div class="col-2"></div>
        
    </div>
    
<?= $this->endSection() ?>