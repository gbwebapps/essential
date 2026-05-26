<div>
    <form method="post" id="<?= $id_output ?>" class="d-inline-block" data-message="<?= $message ?>">
        <button type="submit" class="btn <?= $btn_left ?> mx-1">
            <?= $icon_left ?> <?= $text_left ?>
        </button>
    </form>

    <button type="submit" class="btn <?= $btn_right ?> mx-1" form="<?= "{$controller}_{$action}" ?>">
        <?= $icon_right ?> <?= $text_right ?>
    </button>
</div>