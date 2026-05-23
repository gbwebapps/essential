<!-- Barra di navigazione inferiore fissa (Footer Nav) -->
<nav class="navbar fixed-bottom bg-light border-top"> 
    <div class="container-fluid d-flex align-items-center justify-content-between">

        <!-- Il contenuto viene renderizzato solo per utenti autenticati-->
        <?php if (isset($currentAdmin) && $currentAdmin): ?>

            <!-- Sezione Sinistra: Menu a comparsa verso l'alto (Moduli) -->
            <div class="btn-group dropup me-auto">
                <button type="button" class="btn btn-secondary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-cubes"></i> 
                    <span class="d-none d-md-inline"><?= lang('backend/global.labels.modules'); ?></span>
                </button>

                <ul class="dropdown-menu">
                    <?php
                        /* Ciclo di generazione delle voci del menu moduli */
                        $totalLeft = count($menuBottomLeft);
                        foreach ($menuBottomLeft as $index => $ele): 
                            $active = (isset($controller) && $controller === $ele['controller']) ? ' active' : '';
                    ?>
                        <li>
                            <a class="dropdown-item<?= $active; ?>" href="<?= base_url($ele['route']); ?>">
                                <span class="me-2"><?= $ele['icon'] ?? ''; ?></span> <?= $ele['label'] ?? ''; ?>
                            </a>
                        </li>
                        <?php /* Inserimento del divisore se non è l'ultimo elemento */ ?>
                        <?php if ($index < $totalLeft - 1): ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Sezione Centrale: Pulsanti di azione dinamici (es. Salva, Aggiungi, Elimina) -->
            <div id="dynamic_actions" class="mx-auto">
                <?= view_cell('BackendButtonsCell::render', ['controller' => $controller, 'action'=> $action]); ?>
            </div>

            <!-- Sezione Destra: Menu Servizi (visibile solo agli utenti Master) -->
            <?php if ((int) $currentAdmin->master === 1): ?>
                <div class="btn-group dropup ms-auto">
                    <button type="button" class="btn btn-secondary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-gears"></i> 
                        <span class="d-none d-md-inline"><?= lang('backend/global.labels.services'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php
                            /* Ciclo di generazione delle voci del menu servizi */
                            $totalRight = count($menuBottomRight);
                            foreach ($menuBottomRight as $index => $ele): 
                                $active = (isset($controller) && $controller === $ele['controller']) ? ' active' : '';
                        ?>
                            <li>
                                <a class="dropdown-item<?= $active; ?>" href="<?= base_url($ele['route']); ?>">
                                    <span class="me-2"><?= $ele['icon'] ?? ''; ?></span> <?= $ele['label'] ?? ''; ?>
                                </a>
                            </li>
                            <?php if ($index < $totalRight - 1): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Spacer tecnico per mantenere la simmetria del flexbox quando il menu Servizi è nascosto -->
                <div class="ms-auto" style="width: 40px;">&nbsp;</div>
            <?php endif; ?>

        <?php else: ?>

            <!-- Contenuto centrato -->
            <div class="text-center w-100">
                <a href="https://www.gbwebapps.com" target="_blank">
                    <img src="<?= base_url('assets/img/logo_gbwebapps.png'); ?>" alt="" height="30">
                </a>
            </div>

        <?php endif; ?>

    </div>
</nav>