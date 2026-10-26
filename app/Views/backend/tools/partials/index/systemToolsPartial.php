<div class="card-body">
    <div class="row g-4">
        
        <!-- Colonna Sinistra: Framework, Locali, Server -->
        <div class="col-12 col-lg-6">
            
            <!-- Framework -->
            <div class="card shadow-sm border mb-4">
                <div class="card-header app-bg-surface fw-bold">
                    <i class="fa-solid fa-code me-2 app-text-accent"></i> <?= ui_text('backend/tools.labels.framework', [], false); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.ciVersion', [], false); ?></span>
                        <strong><?= esc($sysInfo['framework']['ci_version']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.environment', [], false); ?></span>
                        <?php 
                            /* Badge verde per produzione, giallo per sviluppo */
                            $envClass = ($sysInfo['framework']['environment'] === 'production') ? 'app-bg-success' : 'app-bg-warning';
                        ?>
                        <span class="badge <?= esc($envClass, 'attr'); ?>"><?= esc(ucfirst($sysInfo['framework']['environment'])); ?></span>
                    </li>
                </ul>
            </div>

            <!-- Impostazioni Locali -->
            <div class="card shadow-sm mb-4">
                <div class="card-header app-bg-surface fw-bold">
                    <i class="fa-solid fa-earth-europe me-2 app-text-accent"></i> <?= ui_text('backend/tools.labels.local', [], false); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.timezone', [], false); ?></span>
                        <strong><?= esc($sysInfo['local']['timezone']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.language', [], false); ?></span>
                        <strong><?= esc($sysInfo['local']['locale']); ?></strong>
                    </li>
                </ul>
            </div>

            <!-- Server -->
            <div class="card shadow-sm mb-4">
                <div class="card-header app-bg-surface fw-bold">
                    <i class="fa-solid fa-server me-2 app-text-accent"></i> <?= ui_text('backend/tools.labels.server', [], false); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.os', [], false); ?></span>
                        <strong><?= esc($sysInfo['server']['os']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.webServer', [], false); ?></span>
                        <strong><?= esc($sysInfo['server']['software']); ?></strong>
                    </li>
                </ul>
            </div>

            <!-- Estensioni -->
            <div class="card shadow-sm">
                <div class="card-header app-bg-surface fw-bold">
                    <i class="fa-solid fa-puzzle-piece me-2 app-text-accent"></i> <?= ui_text('backend/tools.labels.extensions', [], false); ?>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach($sysInfo['extensions'] as $ext => $loaded): ?>
                            <div class="badge border <?= esc($loaded ? 'app-border-success app-text-success' : 'app-border-danger app-text-danger', 'attr'); ?> bg-transparent p-2">
                                <i class="fa-solid <?= esc($loaded ? 'fa-check' : 'fa-xmark', 'attr'); ?> me-1"></i> <?= esc(strtoupper($ext)); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Colonna Destra: PHP ed Estensioni -->
        <div class="col-12 col-lg-6">
            
            <!-- PHP Core -->
            <div class="card shadow-sm">
                <div class="card-header app-bg-surface fw-bold">
                    <i class="fa-brands fa-php me-2 app-text-accent"></i> <?= ui_text('backend/tools.labels.phpCore', [], false); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.phpVersion', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['version']); ?> (<?= esc($sysInfo['php']['architecture']); ?>)</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.sapi', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['sapi']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.memoryLimit', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['memory_limit']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.maxExecutionTime', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['max_execution_time']); ?> <?= ui_text('backend/tools.labels.seconds', [], true); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.uploadMax', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['upload_max_filesize']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.postMax', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['post_max_size']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.maxFileUploads', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['max_file_uploads']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.maxInputVars', [], false); ?></span>
                        <strong><?= esc($sysInfo['php']['max_input_vars']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.displayErrors', [], false); ?></span>
                        <?php 
                            /* Rosso se abilitato in produzione, verde se disabilitato */
                            $errClass = ($sysInfo['php']['display_errors'] === 'On') ? 'app-text-danger' : 'app-text-success';
                        ?>
                        <strong class="<?= esc($errClass, 'attr'); ?>"><?= esc($sysInfo['php']['display_errors']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= ui_text('backend/tools.labels.opcache', [], false); ?></span>
                        <?php 
                            /* Verde se abilitato, giallo se disabilitato */
                            $opClass = ($sysInfo['php']['opcache'] === 'On') ? 'app-text-success' : 'app-text-warning';
                        ?>
                        <strong class="<?= esc($opClass, 'attr'); ?>"><?= esc($sysInfo['php']['opcache']); ?></strong>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
