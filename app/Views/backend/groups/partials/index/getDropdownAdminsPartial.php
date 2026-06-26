<?php if ( ! empty($admins)): ?>
    <div class="list-group position-absolute top-100 start-0 w-100 shadow-sm z-3" style="max-height: 200px; overflow-y: auto;">
        <?php foreach ($admins as $admin): ?>
            <button type="button" 
                    class="list-group-item list-group-item-action text-start btn-select-admin" 
                    data-id="<?= $admin['uuid'] ?>" 
                    data-identity="<?= esc($admin['identity']) ?>">
                <i class="fa-solid fa-user me-2 text-muted"></i>
                <?= esc($admin['identity']) ?>
            </button>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="list-group position-absolute top-100 start-0 w-100 shadow-sm z-3">
        <div class="list-group-item text-muted text-center py-2 fs-7">
            <i class="fa-solid fa-exclamation-circle me-1"></i> <?= lang('backend/groups.messages.noAdminFound'); ?>
        </div>
    </div>
<?php endif; ?>