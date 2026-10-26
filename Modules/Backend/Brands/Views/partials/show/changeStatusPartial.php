<?php
    $status = (int) $brand->status;

    if($status === 1):
        $statusText = lang('backend/brands.labels.active');
        $statusClass = "app-text-success fw-bold btn btn-link shadow-none";
    elseif($status === 0):
        $statusText = lang('backend/brands.labels.unactive');
        $statusClass = "app-text-danger fw-bold btn btn-link shadow-none";
    endif;
?>
<form <?= ui_binding('backend/brands.messages.areYouSureChangeStatus', 'data-message', [$brand->brand], true); ?> class="changeStatus" data-controller="brands" data-message="<?= esc(sprintf(lang('backend/brands.messages.areYouSureChangeStatus'), $brand->brand), 'attr'); ?>">
    <input type="hidden" name="uuid" value="<?= esc($brand->uuid, 'attr'); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= $statusClass; ?>">
        <?= ui_value($statusText, false); ?>
    </button>
</form>
