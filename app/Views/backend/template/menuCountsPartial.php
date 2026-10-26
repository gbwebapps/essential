<?php if (isset($menuCounts[$menuCountEntity])): ?>
    <span class="d-inline-flex flex-shrink-0 gap-1 ms-auto align-middle" data-menu-counts="<?= esc($menuCountEntity, 'attr'); ?>">
        <?php foreach (['active' => 'success', 'suspended' => 'warning', 'trashed' => 'danger'] as $countType => $countColor): ?>
            <?php $countLabel = lang('backend/global.menuCounts.' . $countType); ?>
            <span <?= ui_binding('backend/global.menuCounts.' . $countType, 'data-label'); ?> <?= ui_binding('backend/global.menuCounts.' . $countType, 'title', [], false, ': ' . $menuCounts[$menuCountEntity][$countType]); ?> <?= ui_binding('backend/global.menuCounts.' . $countType, 'aria-label', [], false, ': ' . $menuCounts[$menuCountEntity][$countType]); ?> class="badge app-bg-<?= $countColor; ?> rounded-1" data-menu-count="<?= $countType; ?>" data-label="<?= esc($countLabel, 'attr'); ?>" title="<?= esc($countLabel . ': ' . $menuCounts[$menuCountEntity][$countType], 'attr'); ?>" aria-label="<?= esc($countLabel . ': ' . $menuCounts[$menuCountEntity][$countType], 'attr'); ?>"><?= esc($menuCounts[$menuCountEntity][$countType]); ?></span>
        <?php endforeach; ?>
    </span>
<?php endif; ?>
