<?php
    $status = (int) $product->status;
    $statusText = ($status === 1) ? lang('backend/products.labels.active') : lang('backend/products.labels.unactive');
    $statusClass = ($status === 1) ? 'app-text-success fw-bold btn btn-link shadow-none' : 'app-text-danger fw-bold btn btn-link shadow-none';
?>
<form class="changeStatus" data-controller="products" data-message="<?= esc(sprintf(lang('backend/products.messages.areYouSureChangeStatus'), $product->product), 'attr'); ?>">
    <input type="hidden" name="uuid" value="<?= esc($product->uuid, 'attr'); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= esc($statusClass, 'attr'); ?>"><?= $statusText; ?></button>
</form>
