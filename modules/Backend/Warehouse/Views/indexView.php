<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <?= $this->include(\Modules\Backend\Warehouse\Config\Views::INDEX_PARTIAL); ?>

<?= $this->endSection() ?>
