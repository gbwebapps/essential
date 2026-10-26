<div class="left-menu">

    <div class="list-group">

        <?php foreach ($sections as $key => $section): ?>

            <a href="<?= base_url($section['route']); ?>"
               class="list-group-item list-group-item-action<?= ($action === $key) ? ' active' : ''; ?>">
                <?= $section['icon']; ?>
                <?= ui_value($section['title'], false); ?>
            </a>
            
        <?php endforeach; ?>

    </div>

</div>