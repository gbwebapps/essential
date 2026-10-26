<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="container-fluid container-lg px-0">
        <div class="row g-0">
            <div class="col-12">
                
                <div class="accordion" id="mainToolsDashboard">

                    <?php foreach(['system', 'manageAudits', 'manageLogs', 'dbMaintenance', 'backups', 'cleanSpace'] as $env): ?>

                        <?php
                            /* Assegnazione icona specifica per ciascun pannello */
                            $icon = match($env) {
                                'system' => 'fa-computer', 
                                'manageAudits' => 'fa-clock-rotate-left',
                                'manageLogs' => 'fa-user-clock',
                                'dbMaintenance' => 'fa-database',
                                'backups' => 'fa-box-archive', 
                                'cleanSpace' => 'fa-eraser', 
                                default => 'fa-wrench'
                            };
                        ?>

                        <div class="accordion-item mb-3 border shadow-sm">
                            <h2 class="accordion-header" id="main_heading_<?= esc($env, 'attr'); ?>">
                                <button class="accordion-button collapsed shadow-none app-bg-surface app-text-muted py-3 btn-trigger-<?= esc($env, 'attr'); ?>-tools" data-env="<?= esc($env, 'attr'); ?>" type="button" aria-expanded="false" aria-controls="main_collapse_<?= esc($env, 'attr'); ?>">
                                    <h2 class="card-title mb-0 fs-5">
                                        <i class="fa-solid <?= esc($icon, 'attr'); ?> me-2"></i><?= ui_text('backend/tools.panels.' . $env, [], true); ?>
                                    </h2>
                                </button>
                            </h2>
                            <div id="main_collapse_<?= esc($env, 'attr'); ?>" class="accordion-collapse collapse" aria-labelledby="main_heading_<?= esc($env, 'attr'); ?>" data-bs-parent="#mainToolsDashboard">
                                <div id="<?= esc($env, 'attr'); ?>-tools-container" class="accordion-body app-bg-surface-elevated border-top mb-0"></div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>
