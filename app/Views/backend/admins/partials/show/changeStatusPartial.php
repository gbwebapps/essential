<?php
    $status = (int) $admin->status;

    if($status === 1):
        $statusText = lang('backend/admins.labels.active');
        $statusClass = "app-text-success fw-bold btn btn-link shadow-none";
    elseif($status === 0):
        $statusText = lang('backend/admins.labels.unactive');
        $statusClass = "app-text-danger fw-bold btn btn-link shadow-none";
    endif;
?>
<form <?= ui_binding('backend/admins.messages.areYouSureChangeStatus', 'data-message', [$admin->firstname, $admin->lastname], true); ?> class="changeStatus" data-message="<?= esc(sprintf(lang('backend/admins.messages.areYouSureChangeStatus'), $admin->firstname, $admin->lastname), 'attr'); ?>">
    <input type="hidden" name="uuid" value="<?= esc($admin->uuid, 'attr'); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= $statusClass; ?>">
        <?= ui_value($statusText, false); ?>
    </button>
</form>
