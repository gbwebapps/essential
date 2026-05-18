<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <div class="col-2">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-10">
            <div id="permissions-account-container">
                <?= $this->include('backend/account/partials/permissions/permissionsPartial'); ?>
            </div>
        </div>
        
    </div>
    
<?= $this->endSection() ?>