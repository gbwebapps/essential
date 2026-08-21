<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <!-- Blocco Menu: 100% larghezza su mobile/tablet, 3/12 su desktop -->
        <div class="col-12 col-lg-3 mb-2 mb-lg-0">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-12 col-lg-9">
            <div class="card mt-3 mt-lg-0">
                <div id="images-account-container">
                    <?= $this->include('backend/account/partials/images/imagesPartial'); ?>
                </div>
            </div>
        </div>
        
    </div>
    
<?= $this->endSection() ?>