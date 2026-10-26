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
                        <?= $sections['tokens']['icon']; ?>
                        <?= ui_text('backend/account.titles.tokens', [], false); ?>
                    </h2>
                    <div>
                        <form id="getTokens">
                            <button type="submit" class="btn btn-sm app-btn-secondary">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> <?= ui_text('backend/account.buttons.reload', [], false); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <div id="tokens-account-container">
                    <?= $this->include('backend/account/partials/tokens/tokensPartial'); ?>
                </div>
            </div>
        </div>

        <div class="col-2"></div>
        
    </div>
    
<?= $this->endSection() ?>