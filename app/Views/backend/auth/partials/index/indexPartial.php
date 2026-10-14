<div class="container">
    <div class="row justify-content-center" id="buttons_block">
        <?php foreach ($sections as $key => $section): ?>
            <div class="<?= esc($section['class'], 'attr'); ?>">
                <a href="<?= esc(base_url($section['route']), 'attr'); ?>">
                    <div class="card m-2 bg-light text-secondary text-center" id="<?= esc((string) $key, 'attr'); ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="lead"><?= $section['icon_2x']; ?></div>
                                <div class="lead"><?= esc($section['title']); ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
