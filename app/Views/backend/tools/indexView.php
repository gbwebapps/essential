<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="container">
        <div class="row g-0">
            <div class="col-12">
                
                <div class="accordion" id="mainToolsDashboard">

                    <?php foreach(['system', 'manageAudits', 'dbMaintenance', 'backups', 'cleanSpace'] as $env): ?>

                        <?php
                            /* Assegnazione icona specifica per ciascun pannello */
                            $icon = match($env) {
                                'system' => 'fa-computer', 
                                'manageAudits' => 'fa-clock-rotate-left',
                                'dbMaintenance' => 'fa-database',
                                'backups' => 'fa-box-archive', 
                                'cleanSpace' => 'fa-eraser', 
                                default => 'fa-wrench'
                            };
                        ?>

                        <div class="accordion-item mb-3 border shadow-sm">
                            <h2 class="accordion-header" id="main_heading_<?= $env; ?>">
                                <button class="accordion-button collapsed shadow-none bg-light text-secondary py-3 btn-trigger-<?= $env; ?>-tools" data-env="<?= $env; ?>" type="button" aria-expanded="false" aria-controls="main_collapse_<?= $env; ?>">
                                    <h2 class="card-title mb-0 fs-5">
                                        <i class="fa-solid <?= $icon; ?> me-2"></i><?= lang('backend/tools.panels.' . $env); ?>
                                    </h2>
                                </button>
                            </h2>
                            <div id="main_collapse_<?= $env; ?>" class="accordion-collapse collapse" aria-labelledby="main_heading_<?= $env; ?>" data-bs-parent="#mainToolsDashboard">
                                <div id="<?= $env; ?>-tools-container" class="accordion-body bg-white border-top mb-0"></div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>