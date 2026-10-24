<article class="dashboard-card" data-dashboard-summary="<?= esc($entity, 'attr'); ?>" aria-labelledby="dashboard-<?= esc($entity, 'attr'); ?>-title">
    <header class="dashboard-card-header">
        <h2 id="dashboard-<?= esc($entity, 'attr'); ?>-title"><i class="fa-solid <?= esc($card['icon'], 'attr'); ?>" aria-hidden="true"></i> <?= esc(lang($card['label'])); ?></h2>
        <?php if ($entity === 'admins'): ?>
            <span class="dashboard-role"><?= lang('backend/dashboard.labels.superadminOnly'); ?></span>
        <?php endif; ?>
    </header>
    <div class="dashboard-summary-body">
        <div class="dashboard-donut<?= $card['total'] === 0 ? ' dashboard-donut-empty' : ''; ?>"
             style="--dashboard-active-end: <?= $card['activeEnd']; ?>%; --dashboard-suspended-end: <?= $card['suspendedEnd']; ?>%;"
             role="img" aria-label="<?= esc(lang('backend/global.menuCounts.active') . ': ' . $card['counts']['active'] . ', ' . lang('backend/global.menuCounts.suspended') . ': ' . $card['counts']['suspended'] . ', ' . lang('backend/global.menuCounts.trashed') . ': ' . $card['counts']['trashed'], 'attr'); ?>">
            <div class="dashboard-donut-core">
                <span class="dashboard-total"><?= esc($dashboardPresenter->formatCount($card['total'])); ?></span>
                <span class="dashboard-total-label"><?= lang('backend/dashboard.labels.total'); ?></span>
            </div>
        </div>
        <dl class="dashboard-states">
            <?php foreach (['active', 'suspended', 'trashed'] as $state): ?>
                <div>
                    <dt><span class="dashboard-dot dashboard-state-<?= $state; ?>" aria-hidden="true"></span><?= lang('backend/global.menuCounts.' . $state); ?></dt>
                    <dd><?= esc($dashboardPresenter->formatCount($card['counts'][$state])); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
    <a class="dashboard-card-footer" href="<?= esc(base_url($card['route']), 'attr'); ?>" aria-label="<?= esc(lang('backend/dashboard.links.showAll') . ': ' . lang($card['label']), 'attr'); ?>">
        <span><?= lang('backend/dashboard.links.showAll'); ?></span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
    </a>
</article>
