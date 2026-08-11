<div>
    <?php if($action === 'show'): ?>

        <button type="button" class="<?= $btn_left; ?>" id="<?= $id_left; ?>" data-message-left="<?= $message_left; ?>">
            <?= $icon_left; ?> <?= $text_left; ?>
        </button>
        <button type="button" class="<?= $btn_right; ?>" id="<?= $id_right; ?>" data-message-right="<?= $message_right; ?>">
            <?= $icon_right; ?> <?= $text_right; ?>
        </button>

    <?php else: ?>

        <form method="post" id="<?= $id_output ?>" class="d-inline-block" data-message="<?= $message; ?>">
            <button type="submit" class="<?= $btn_left; ?>">
                <?= $icon_left; ?> <?= $text_left; ?>
            </button>
        </form>

        <button type="submit" class="<?= $btn_right; ?>" form="<?= "{$controller}-{$action}" ?>">
            <?= $icon_right; ?> <?= $text_right; ?>
        </button>

    <?php endif; ?>
</div>