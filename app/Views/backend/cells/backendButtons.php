<div>
    <?php if($action === 'show'): ?>

        <button type="button" class="<?= esc($btn_left, 'attr'); ?>" id="<?= esc($id_left, 'attr'); ?>" data-message-left="<?= esc($message_left, 'attr'); ?>">
            <?= $icon_left; ?> <?= esc($text_left); ?>
        </button>
        <button type="button" class="<?= esc($btn_right, 'attr'); ?>" id="<?= esc($id_right, 'attr'); ?>" data-message-right="<?= esc($message_right, 'attr'); ?>">
            <?= $icon_right; ?> <?= esc($text_right); ?>
        </button>

    <?php else: ?>

        <form method="post" id="<?= esc($id_output, 'attr'); ?>" class="d-inline-block" data-message="<?= esc($message, 'attr'); ?>">
            <button type="submit" class="<?= esc($btn_left, 'attr'); ?>">
                <?= $icon_left; ?> <?= esc($text_left); ?>
            </button>
        </form>

        <button type="submit" class="<?= esc($btn_right, 'attr'); ?>" form="<?= esc("{$controller}-{$action}", 'attr'); ?>">
            <?= $icon_right; ?> <?= esc($text_right); ?>
        </button>

    <?php endif; ?>
</div>
