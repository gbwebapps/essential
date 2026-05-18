<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-messages-container">
                <?= $this->include('backend/messages/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>