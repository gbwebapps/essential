<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="row">
        <div class="col-12">
            <div id="index-auth-container">
                <?= $this->include('backend/auth/partials/index/indexPartial'); ?>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('isLogout')): ?>
        <script>localStorage.clear();</script>
    <?php session()->destroy(); endif; ?>

<?= $this->endSection() ?>