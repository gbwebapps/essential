<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="showAll-messages-container">
                <?= $this->include('backend/messages/partials/showAll/showAllPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>