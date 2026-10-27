<?php
    $status = (int) $product->status;

    if($status === 1):
        $statusText = lang('backend/products.labels.active');
        $statusClass = 'app-text-success fw-bold btn btn-link shadow-none';
    else:
        $statusText = lang('backend/products.labels.unactive');
        $statusClass = 'app-text-danger fw-bold btn btn-link shadow-none';
    endif;
?>

<form <?= ui_binding('backend/products.messages.areYouSureChangeStatus', 'data-message', [$product->product], true); ?> class="changeStatus" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureChangeStatus'), $product->product), 'attr'); ?>">
    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= esc($statusClass, 'attr'); ?>"><?= ui_value($statusText, false); ?></button>
</form>
