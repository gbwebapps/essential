<!-- Barra di navigazione superiore fissa -->
<nav class="navbar navbar-expand-lg fixed-top bg-light border-bottom">
    <div class="container-fluid">
        <!-- Logo e link alla dashboard -->
        <a class="navbar-brand" href="<?= base_url('backend/dashboard'); ?>">
            <img src="<?= base_url('assets/img/logo_essential.png'); ?>" alt="Essential Logo" height="28">
        </a>
        
        <!-- Il menu viene mostrato solo se l'utente è autenticato -->
        <?php if (isset($currentAdmin) && $currentAdmin): ?>
            <!-- Pulsante toggle per visualizzazione mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!-- Dropdown profilo utente -->
                    <li class="nav-item dropdown">
                        <!-- Verifica se la sezione corrente appartiene all'area utente/account -->
                        <?php $isActive = (isset($controller) && in_array($controller, ['account'])); ?>
                        
                        <a class="nav-link dropdown-toggle<?= $isActive ? ' active fw-bold' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-circle"></i> <?= esc($currentAdmin->firstname ?? ''); ?> <?= esc($currentAdmin->lastname ?? ''); ?>
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end"> 
                            <?php

                                /* 1. Preparazione lista visibile: filtraggio in base ai permessi superadmin */
                                $visibleItems = [];
                                foreach ($menuTopRight as $item):

                                    /* Verifica di sicurezza sul controller per evitare errori se non definito */
                                    $itemController = $item['controller'] ?? '';

                                    /* Se l'utente non è superadmin, nascondi le voci relative alla gestione utenti */
                                    if ( ! ((int) ($currentAdmin->superadmin ?? 0) === 1) && in_array($itemController, ['admins', 'groups', 'audits', 'tokens'])):
                                        continue;
                                    endif;
                                    $visibleItems[] = $item;
                                endforeach;

                                /* 2. Rendering degli elementi del menu filtrati */
                                $total = count($visibleItems);
                                foreach ($visibleItems as $index => $ele): 

                                    /* Controllo sicuro per evitare notice se il controller manca */
                                    $eleController = $ele['controller'] ?? '';
                                    $activeClass = (isset($controller) && $controller === $eleController) ? ' active' : '';
                                    
                                    /* Gestione dinamica dell'indirizzo o dell'identificativo */
                                    $href = isset($ele['route']) ? base_url($ele['route']) : '#'; 
                                    $dataIdAttr = isset($ele['id']) ? ' data-id="' . esc($ele['id']) . '"' : '';

                                    /* Gestione dinamica del messaggio di conferma */
                                    $dataMessageAttr = isset($ele['message']) ? ' data-message="' . esc($ele['message']) . '"' : '';
                            ?>
                                <li>
                                    <a class="dropdown-item<?= $activeClass; ?>" href="<?= $href; ?>"<?= $dataIdAttr; ?><?= $dataMessageAttr; ?>>
                                        <?= $ele['icon'] ?? ''; ?> <span class="ms-1"><?= $ele['label'] ?? ''; ?></span>
                                    </a>
                                </li>

                                <!-- Aggiunge un separatore tra le voci, evitando di inserirlo dopo l'ultima -->
                                <?php if ($index < $total - 1): ?>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>