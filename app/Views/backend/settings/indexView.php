<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="container">
        <div class="row g-0">
            <div class="col-12">
                
                <div class="accordion" id="mainSettingsDashboard">

                    <?php foreach(['auth', 'upload', 'email'] as $env): ?>

                        <div class="accordion-item mb-3 border shadow-sm">
                            <h2 class="accordion-header" id="main_heading_<?= $env; ?>">
                                <button class="accordion-button collapsed shadow-none bg-light text-secondary py-3 btn-trigger-<?= $env; ?>-settings" data-env="<?= $env; ?>" type="button" aria-expanded="false" aria-controls="main_collapse_<?= $env; ?>">
                                    <h2 class="card-title mb-0 fs-5">

                                        <?php if($env === 'auth'): $icon = 'fa-shield-halved'; elseif($env === 'upload'): $icon = 'fa-upload'; elseif($env === 'email'): $icon = 'fa-envelope'; endif; ?>

                                        <i class="fa-solid <?= $icon; ?> me-2"></i><?= lang('backend/settings.panels.' . $env . 'Setting'); ?>
                                    </h2>
                                </button>
                            </h2>
                            <div id="main_collapse_<?= $env; ?>" class="accordion-collapse collapse" aria-labelledby="main_heading_<?= $env; ?>" data-bs-parent="#mainSettingsDashboard">
                                <div id="<?= $env; ?>-settings-container" class="accordion-body bg-white border-top mb-0"></div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>