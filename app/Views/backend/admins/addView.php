<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="add-admins-container">
                <?= $this->include('backend/admins/partials/add/addPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>