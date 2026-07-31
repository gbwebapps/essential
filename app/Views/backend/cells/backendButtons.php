<div>
    <form method="post" id="<?= $id_output ?>" class="d-inline-block" data-message="<?= $message ?>">
        <button type="submit" class="<?= $btn_left ?>">
            <?= $icon_left ?> <?= $text_left ?>
        </button>
    </form>

    <button type="submit" class="<?= $btn_right ?>" form="<?= "{$controller}_{$action}" ?>">
        <?= $icon_right ?> <?= $text_right ?>
    </button>
</div>