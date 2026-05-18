<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="edit-admins-container">
                <?= $this->include('backend/admins/partials/edit/editPartial'); ?>
            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>