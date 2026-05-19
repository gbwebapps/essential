<!-- Verifica la presenza dell'array options prima di renderizzare il dropdown -->
<?php if(isset($options)): ?>
    <div class="dropdown pb-2">
        <!-- Pulsante di attivazione del menu Opzioni -->
        <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= lang('backend/global.labels.options'); ?>
        </button>
        
        <!-- Menu a comparsa allineato alla fine del contenitore -->
        <ul class="dropdown-menu dropdown-menu-end">
            <?php 
                /* Calcolo del totale per la gestione dei separatori visivi */
                $totalItems = count($options); 
                foreach($options as $key => $item): 
            ?>
                
                <?php 
                    /* Preparazione delle variabili opzionali per ogni singola voce */
                    $class = $item['class'] ?? ''; 
                    $icon = $item['icon'] ?? ''; 
                    $id = $item['id'] ?? ''; 
                ?>
                
                <li>
                    <!-- Link dell'opzione con classi e ID dinamici -->
                    <a class="dropdown-item <?= $class; ?>" href="<?= base_url($item['route']); ?>" id="<?= $id; ?>">
                        <?= $icon; ?> <?= $item['label']; ?>
                    </a>
                </li>

                <!-- Inserimento della riga di separazione (divider) se non siamo all'ultimo elemento -->
                <?php if ($key < $totalItems - 1): ?>
                    <li><hr class="dropdown-divider"></li>
                <?php endif; ?>

            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>