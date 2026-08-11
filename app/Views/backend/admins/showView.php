<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="show-admins-container">
                <?= $this->include('backend/admins/partials/show/showPartial'); ?>
            </div>
        </div>
    </div>

    <!-- ######################### MODALE ESPORTAZIONE PDF ######################### -->
    <?= $this->include('backend/template/exportPdfView', $this->data); ?>
    <!-- ######################### FINE MODALE ESPORTAZIONE PDF ######################### -->
    
<?= $this->endSection() ?>