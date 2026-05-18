<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">

        <div class="col-2">
            <?= $this->include('backend/account/partials/common/leftMenuPartial'); ?>
        </div>

        <div class="col-10">
            <div id="images-account-container">
                <?= $this->include('backend/account/partials/images/imagesPartial'); ?>
            </div>
        </div>
        
    </div>
    
<?= $this->endSection() ?>