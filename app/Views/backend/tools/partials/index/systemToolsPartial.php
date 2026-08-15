<div class="card-body">
    <div class="row g-4">
        
        <!-- Colonna Sinistra: Framework, Locali, Server -->
        <div class="col-12 col-lg-6">
            
            <!-- Framework -->
            <div class="card shadow-sm border mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-code me-2 text-primary"></i> <?= lang('backend/tools.labels.framework'); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.ciVersion'); ?></span>
                        <strong><?= $sysInfo['framework']['ci_version']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.environment'); ?></span>
                        <?php 
                            /* Badge verde per produzione, giallo per sviluppo */
                            $envClass = ($sysInfo['framework']['environment'] === 'production') ? 'bg-success' : 'bg-warning text-dark'; 
                        ?>
                        <span class="badge <?= $envClass; ?>"><?= ucfirst($sysInfo['framework']['environment']); ?></span>
                    </li>
                </ul>
            </div>

            <!-- Impostazioni Locali -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-earth-europe me-2 text-primary"></i> <?= lang('backend/tools.labels.local'); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.timezone'); ?></span>
                        <strong><?= $sysInfo['local']['timezone']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.language'); ?></span>
                        <strong><?= $sysInfo['local']['locale']; ?></strong>
                    </li>
                </ul>
            </div>

            <!-- Server -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-server me-2 text-primary"></i> <?= lang('backend/tools.labels.server'); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.os'); ?></span>
                        <strong><?= $sysInfo['server']['os']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.webServer'); ?></span>
                        <strong><?= $sysInfo['server']['software']; ?></strong>
                    </li>
                </ul>
            </div>

            <!-- Estensioni -->
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-puzzle-piece me-2 text-primary"></i> <?= lang('backend/tools.labels.extensions'); ?>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach($sysInfo['extensions'] as $ext => $loaded): ?>
                            <div class="badge border <?= $loaded ? 'border-success text-success' : 'border-danger text-danger'; ?> bg-transparent p-2">
                                <i class="fa-solid <?= $loaded ? 'fa-check' : 'fa-xmark'; ?> me-1"></i> <?= strtoupper($ext); ?>
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
                <div class="card-header bg-light fw-bold">
                    <i class="fa-brands fa-php me-2 text-primary"></i> <?= lang('backend/tools.labels.phpCore'); ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.phpVersion'); ?></span>
                        <strong><?= $sysInfo['php']['version']; ?> (<?= $sysInfo['php']['architecture']; ?>)</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.sapi'); ?></span>
                        <strong><?= $sysInfo['php']['sapi']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.memoryLimit'); ?></span>
                        <strong><?= $sysInfo['php']['memory_limit']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.maxExecutionTime'); ?></span>
                        <strong><?= $sysInfo['php']['max_execution_time']; ?> <?= lang('backend/tools.labels.seconds'); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.uploadMax'); ?></span>
                        <strong><?= $sysInfo['php']['upload_max_filesize']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.postMax'); ?></span>
                        <strong><?= $sysInfo['php']['post_max_size']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.maxFileUploads'); ?></span>
                        <strong><?= $sysInfo['php']['max_file_uploads']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.maxInputVars'); ?></span>
                        <strong><?= $sysInfo['php']['max_input_vars']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.displayErrors'); ?></span>
                        <?php 
                            /* Rosso se abilitato in produzione, verde se disabilitato */
                            $errClass = ($sysInfo['php']['display_errors'] === 'On') ? 'text-danger' : 'text-success'; 
                        ?>
                        <strong class="<?= $errClass; ?>"><?= $sysInfo['php']['display_errors']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= lang('backend/tools.labels.opcache'); ?></span>
                        <?php 
                            /* Verde se abilitato, giallo se disabilitato */
                            $opClass = ($sysInfo['php']['opcache'] === 'On') ? 'text-success' : 'text-warning'; 
                        ?>
                        <strong class="<?= $opClass; ?>"><?= $sysInfo['php']['opcache']; ?></strong>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>