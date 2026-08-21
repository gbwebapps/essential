<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <!-- Blocco Menu: 100% larghezza su mobile/tablet, 3/12 su desktop -->
        <div class="col-12 col-lg-3 mb-2 mb-lg-0">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-12 col-lg-9">
            <div class="card mt-3 mt-lg-0">
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0">
                        <?= $sections['resetPassword']['icon']; ?>
                        <?= lang('backend/account.titles.resetPassword'); ?>
                    </h2>
                </div>
                <div id="reset-account-container">
                    <?= $this->include('backend/account/partials/resetPassword/resetPasswordPartial'); ?>
                </div>
            </div>
        </div>

        <div class="col-2"></div>
        
    </div>
    
<?= $this->endSection() ?>