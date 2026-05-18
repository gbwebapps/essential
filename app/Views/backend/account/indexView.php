<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

<div class="container">
    
    <div class="row justify-content-center" id="buttons-block">
        
        <?php if ( ! empty($sections)): ?>
            <?php foreach ($sections as $key => $section): ?>
                
                <div class="<?= $section['class']; ?> mb-4">
                    <a href="<?= base_url($section['route']); ?>" class="text-decoration-none text-reset">
                        
                        <div class="card h-100 bg-light text-secondary shadow-sm" id="<?= $key; ?>">
                            <div class="card-body d-flex align-items-center justify-content-between p-4">
                                
                                <div class="lead">
                                    <?= $section['icon_3x']; ?>
                                </div>
                                
                                <div class="lead fw-bold">
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