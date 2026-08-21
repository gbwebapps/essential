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
                    <li class="list-group-item list-group-item-light py-3">
                        <!-- Impostato text-start su tutto il blocco -->
                        <div class="row text-start align-items-lg-center">
                            
                            <!-- Blocco Sinistra: Nome Database e Dettagli Tecnici -->
                            <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                                <!-- Rimossi i bordi e i margini dal div -->
                                <div>
                                    <h5 class="mb-1 fw-bold"><i class="fa-solid fa-database me-2"></i><?= $database['dbName']; ?></h5>
                                    <div class="text-muted">
                                        <span><i class="fa-solid fa-microchip me-1"></i><?= $database['dbDriver']; ?> <?= $database['dbVersion']; ?></span>
                                    </div>
                                </div>
                                <!-- Inserita la linea visibile solo su mobile e tablet portrait -->
                                <hr class="d-lg-none my-3 text-secondary">
                            </div>

                            <!-- Blocco Centrale: Totali Spazio e Overhead -->
                            <div class="col-12 col-lg-7 mb-3 mb-lg-0">
                                <!-- Rimosso justify-content-center e modificato l'allineamento verticale per mobile -->
                                <div class="text-muted infoDb d-flex flex-column flex-lg-row align-items-start align-items-lg-center text-center">
                                    <span class="mb-2 mb-lg-0 me-lg-4"><i class="fa-solid fa-hard-drive me-2"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), number_format($totalSize, 2)); ?></span>
                                    <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= sprintf(lang('backend/tools.labels.overhead'), number_format($totalOverhead, 2)); ?></span>
                                </div>
                            </div>

                            <!-- Blocco Destra: Azione Globale -->
                            <div class="col-12 col-lg-2">
                                <!-- Passiamo l'intero array di tabelle codificato in JSON al dataset -->
                                <button type="button" class="btn btn-sm btn-primary btn-optimize-all w-100" data-tables='<?= json_encode($allTableNames); ?>'>
                                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i><?= lang('backend/tools.buttons.dbOptimize'); ?>
                                </button>
                            </div>

                        </div>
                    </li>

                    <?php foreach($tables as $table): ?>

                        <li class="list-group-item py-3" data-table="<?= $table['name']; ?>">
                            
                            <div class="row text-start align-items-lg-center">
                                
                                <!-- Blocco Sinistra: Nome Tabella -->
                                <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                                    <div class="fw-bold">
                                        <i class="fa-solid fa-table me-2"></i><?= $table['name']; ?>
                                    </div>
                                    <!-- Linea visibile solo su mobile e tablet portrait -->
                                    <hr class="d-lg-none my-3 text-secondary">
                                </div>
                                
                                <!-- Blocco Centrale: Statistiche -->
                                <div class="col-12 col-lg-7 mb-3 mb-lg-0">
                                    <div class="text-muted d-flex flex-column flex-lg-row justify-content-lg-around align-items-start align-items-lg-center"> 
                                        <span class="mb-2 mb-lg-0"><i class="fa-solid fa-list me-2"></i><?= sprintf(lang('backend/tools.labels.rows'), $table['rows']); ?></span>
                                        <span class="mb-2 mb-lg-0"><i class="fa-solid fa-hard-drive me-2"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), number_format($table['size'], 2)); ?></span>
                                        <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= sprintf(lang('backend/tools.labels.overhead'), number_format($table['overhead'], 2)); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Blocco Destra: Azioni -->
                                <div class="col-12 col-lg-2">
                                    <a href="#" class="btn-optimize-table d-block mb-2 text-decoration-none" data-table="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/tools.buttons.tableOptimize'); ?>
                                    </a>
                                    <a href="#" class="export-entity d-block mb-2 text-decoration-none" data-export-entity="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/admins.links.export'); ?>
                                    </a>
                                    <a href="#" class="import-entity d-block text-decoration-none" data-import-entity="<?= $table['name']; ?>">
                                        <i class="fa-solid fa-circle-arrow-right me-1"></i> <?= lang('backend/admins.links.import'); ?>
                                    </a>
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