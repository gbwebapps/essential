<div class="container">
    <div class="row justify-content-center" id="buttons_block">
        <?php foreach ($sections as $key => $section): ?>
            <div class="<?= $section['class']; ?>">
                <a href="<?= base_url($section['route']); ?>">
                    <div class="card m-2 bg-light text-secondary text-center" id="<?= $key; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="lead"><?= $section['icon_3x']; ?></div>
                                <div class="lead"><?= $section['title']; ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
