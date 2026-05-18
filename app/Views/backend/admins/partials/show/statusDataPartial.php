<?php
    if($admin->active === '1'):
        $status_text = lang('backend/admins.labels.active');
        $status_class="text-success fw-bold btn btn-link shadow-none";
    elseif($admin->active === '0'):
        $status_text = lang('backend/admins.labels.unactive');
        $status_class="text-danger fw-bold btn btn-link shadow-none";
    endif;
?>
<form class="change_status" data-message="<?= sprintf(lang('backend/admins.messages.are_you_sure_change_status'), esc($admin->firstname), esc($admin->lastname)); ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= $status_class; ?>">
        <?= $status_text; ?>
    </button>
</form>