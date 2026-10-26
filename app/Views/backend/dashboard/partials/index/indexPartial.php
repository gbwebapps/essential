<?php if ($dashboardCards !== []): ?>
    <div class="dashboard-summaries" style="--dashboard-columns: <?= count($dashboardCards); ?>;">
        <?php foreach ($dashboardCards as $entity => $card): ?>
            <?= view('backend/dashboard/partials/index/summaryPartial', ['entity' => $entity, 'card' => $card, 'dashboardPresenter' => $dashboardPresenter]); ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="app-text-muted" role="status"><?= ui_text('backend/dashboard.messages.noSections', [], false); ?></p>
<?php endif; ?>

<?php if ($dashboardRankings !== [] || $dashboardTotals !== []): ?>
    <h2 class="dashboard-analysis-title"><?= ui_text('backend/dashboard.panels.warehouse', [], false); ?></h2>
    <?php if (array_key_exists('inventoryValue', $dashboardTotals)): ?>
        <section class="dashboard-inventory-value" data-dashboard-inventory-value aria-labelledby="dashboard-inventory-value-title">
            <div>
                <h3 id="dashboard-inventory-value-title"><?= ui_text('backend/dashboard.panels.inventoryValue', [], false); ?></h3>
                <p><?= ui_text('backend/dashboard.descriptions.inventoryValue', [], false); ?></p>
            </div>
            <?php if ($dashboardTotals['inventoryValue'] === null): ?>
                <p role="status"><?= ui_text('backend/dashboard.messages.unavailable', [], false); ?></p>
            <?php else: ?>
                <strong class="dashboard-inventory-amount"><?= esc($dashboardPresenter->formatValue($dashboardTotals['inventoryValue'])); ?></strong>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <div class="dashboard-rankings">
        <?php foreach ($dashboardRankings as $entity => $chart): ?>
            <?= view('backend/dashboard/partials/index/rankingPartial', ['entity' => $entity, 'chart' => $chart, 'dashboardPresenter' => $dashboardPresenter]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
