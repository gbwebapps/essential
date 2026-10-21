<?php
    $status = (int) $category->status;

    if($status === 1):
        $statusText = lang('backend/categories.labels.active');
        $statusClass = "app-text-success fw-bold btn btn-link shadow-none";
    elseif($status === 0):
        $statusText = lang('backend/categories.labels.unactive');
        $statusClass = "app-text-danger fw-bold btn btn-link shadow-none";
    endif;
?>
<form class="changeStatus" data-controller="categories" data-message="<?= esc(sprintf(lang('backend/categories.messages.areYouSureChangeStatus'), $category->category), 'attr'); ?>">
    <input type="hidden" name="uuid" value="<?= esc($category->uuid, 'attr'); ?>">
    <input type="hidden" name="context" value="show">
    <button type="submit" class="<?= $statusClass; ?>">
        <?= $statusText; ?>
    </button>
</form>
