<div class="card-body">
    <form id="dbMaintenance-tools-form" autocomplete="off"
      data-lang-total-space="<?= lang('backend/tools.labels.totalSpace'); ?>"
      data-lang-rows="<?= lang('backend/tools.labels.rows'); ?>"
      data-lang-overhead="<?= lang('backend/tools.labels.overhead'); ?>">
        <div class="row">
            <div class="col-12">

                <?php
                    /* Inizializzazione variabili per i totali */
                    $totalSize = 0;
                    $totalOverhead = 0;
                    $allTableNames = [];

                    /* Calcoliamo i totali e popoliamo l'array dei nomi */
                    foreach ($tables as $t):
                        $totalSize += $t['size'];
                        $totalOverhead += $t['overhead'];
                        $allTableNames[] = $t['name'];
                    endforeach;
                ?>

                <!-- Contenitore principale della lista -->
                <ul class="list-group">

                    <!-- Header riassuntivo del Database -->
                    <li class="list-group-item list-group-item-light d-flex justify-content-between align-items-center py-3">
                        
                        <!-- Blocco Sinistra: Nome Database e Dettagli Tecnici -->
                        <div class="col-3 text-start">
                            <h5 class="mb-1 fw-bold"><i class="fa-solid fa-database me-2"></i><?= $database['dbName']; ?></h5>
                            <div class="text-muted">
                                <span class="me-3"><i class="fa-solid fa-microchip me-1"></i><?= $database['dbDriver']; ?> <?= $database['dbVersion']; ?></span>
                            </div>
                        </div>

                        <!-- Blocco Centrale: Totali Spazio e Overhead -->
                        <div class="col-7">
                            <div class="text-muted infoDb d-flex justify-content-center">
                                <span class="me-3"><i class="fa-solid fa-hard-drive me-2"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), number_format($totalSize, 2)); ?></span>
                                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= sprintf(lang('backend/tools.labels.overhead'), number_format($totalOverhead, 2)); ?></span>
                            </div>
                        </div>

                        <!-- Blocco Destra: Azione Globale -->
                        <div class="col-2 text-start">
                            <!-- Passiamo l'intero array di tabelle codificato in JSON al dataset -->
                            <button type="button" class="btn btn-sm btn-primary btn-optimize-all" data-tables='<?= json_encode($allTableNames); ?>'>
                                <i class="fa-solid fa-wand-magic-sparkles me-2"></i><?= lang('backend/tools.buttons.dbOptimize'); ?>
                            </button>
                        </div>

                    </li>

                    <?php foreach($tables as $table): ?>
                    
                        <!-- Aggiunto data-table per identificare la riga univoca -->
                        <li class="list-group-item py-3" data-table="<?= $table['name']; ?>">
                            
                            <div class="row align-items-center">
                                <div class="col-3 text-start">
                                    <h6 class="fw-bold mb-0">
                                        <i class="fa-solid fa-table me-2"></i><?= $table['name']; ?>
                                    </h6>
                                </div>
                                <div class="col-7 text-start">
                                    <div class="text-muted d-flex justify-content-around"> 
                                        <span class="me-3"><i class="fa-solid fa-list me-2"></i><?= sprintf(lang('backend/tools.labels.rows'), $table['rows']); ?></span>
                                        <span class="me-3"><i class="fa-solid fa-hard-drive me-2"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), number_format($table['size'], 2)); ?></span>
                                        <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= sprintf(lang('backend/tools.labels.overhead'), number_format($table['overhead'], 2)); ?></span>
                                    </div>
                                </div>
                                <div class="col-2 text-start">
                                    <a href="#" class="btn-optimize-table d-block" data-table="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/tools.buttons.tableOptimize'); ?>
                                    </a>

                                    <!-- Esporta lista csv diretta (Modificato id in class) -->
                                    <a href="#" class="export-entity d-block" data-export-entity="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/admins.links.export'); ?>
                                    </a>
                                    <!-- End esporta lista csv -->

                                    <!-- Importa lista csv (Modificato id in class) -->
                                    <a href="#" class="import-entity d-block" data-import-entity="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/admins.links.import'); ?>
                                    </a>
                                    <!-- End importa lista csv -->
                                </div>
                            </div>

                        </li>

                    <?php endforeach; ?>

                </ul>
            </div>
        </div>
    </form>
</div>

<div id="import-modal-container"></div>