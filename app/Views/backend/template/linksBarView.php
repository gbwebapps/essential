<!-- Verifica se l'array dei link è stato passato alla vista -->
<?php if(isset($linksBar)): ?>
    <!-- Usiamo la row di Bootstrap per inquadrare la barra nel layout -->
    <div class="row">
        <!-- Mobile First: col-12 per occupare tutto lo spazio -->
        <div class="col-12">
            <div class="bar">
                <div class="row">
                    <!-- Ciclo per generare dinamicamente i collegamenti della barra -->
                    <?php foreach($linksBar as $link): ?>

                        <div class="col-3">

                            <?php /* Controllo per evitare il rendering di elementi vuoti */ ?>
                            <?php if( ! empty($link)): ?>

                                <?php 
                                    /* Estrazione sicura dei dati del link con valori di fallback */
                                    $route = $link['route'] ?? '';
                                    $icon = $link['icon'] ?? '';
                                    $label = $link['label'] ?? '';
                                    $id = $link['id'] ?? '';
                                ?>

                                <!-- Singolo elemento della barra con link dinamico -->
                                <div class="bar-item">
                                    <a href="<?= base_url($route); ?>">
                                        <?= $icon; ?> <?= $label; ?>
                                    </a>
                                </div>

                            <?php endif; ?>
                            
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>