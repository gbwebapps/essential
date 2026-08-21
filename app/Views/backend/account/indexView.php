<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

<div class="container">
    
    <div class="row justify-content-center" id="buttons-block">
        
        <?php if ( ! empty($sections)): ?>
            <?php foreach ($sections as $key => $section): ?>
                
                <!-- Gestione griglia fissa nella vista: 1 colonna mobile, 2 tablet, 3 desktop -->
                <div class="<?= $section['class']; ?>">
                    <a href="<?= base_url($section['route']); ?>" class="text-decoration-none text-reset">
                        
                        <div class="card h-100 bg-light text-secondary shadow-sm" id="<?= $key; ?>">
                            <!-- Flexbox: colonna su mobile piccolo, riga da smartphone orizzontale in su -->
                            <div class="card-body d-flex flex-column flex-sm-row align-items-center justify-content-center justify-content-sm-between p-4">
                                
                                <div class="lead mb-3 mb-sm-0">
                                    <?= $section['icon_2x']; ?>
                                </div>
                                
                                <div class="lead fw-bold text-center text-sm-end">
                                    <?= $section['title']; ?>
                                </div>
                                
                            </div>
                        </div>
                        
                    </a>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    
</div>

<?= $this->endSection() ?>