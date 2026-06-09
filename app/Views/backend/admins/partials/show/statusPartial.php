<?php
    $status = (int) $admin->status;

    if($status === 1):
        $statusText = lang('backend/admins.labels.active');
        $statusClass = "text-success fw-bold btn btn-link shadow-none";
    elseif($status === 0):
        $statusText = lang('backend/admins.labels.unactive');
        $statusClass = "text-danger fw-bold btn btn-link shadow-none";
    endif;
?>
<form class="changeStatus" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureChangeStatus'), esc($admin->firstname), esc($admin->lastname)); ?>">
    <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= $statusClass; ?>">
        <?= $statusText; ?>
    </button>
</form>