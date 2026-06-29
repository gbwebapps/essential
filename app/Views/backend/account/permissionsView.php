<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <div class="col-2">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-7 offset-1">
            <div class="card mt-3 mt-lg-0">
                <div class="card-header rounded-0 d-flex justify-content-between align-items-center">
                    <h2 class="card-title text-start mb-0">
                        <?= $sections['permissions']['icon']; ?>
                        <?= lang('backend/account.titles.permissions'); ?>
                    </h2>
                    <div>
                        <form id="getPermissions">
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/account.buttons.reload'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <div id="permissions-account-container">
                    <?= $this->include('backend/account/partials/permissions/permissionsPartial'); ?>
                </div>
            </div>
        </div>

        <div class="col-2"></div>
        
    </div>
    
<?= $this->endSection() ?>