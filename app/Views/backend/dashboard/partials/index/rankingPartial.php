<?php $rankingEntity = ucfirst($chart['entity']); ?>
<?php $isValue = $chart['metric'] === 'value'; ?>
<figure class="dashboard-ranking<?= $isValue ? ' dashboard-ranking-value' : ''; ?>" data-dashboard-ranking="<?= esc($entity, 'attr'); ?>" aria-labelledby="dashboard-<?= esc($entity, 'attr'); ?>-ranking-title">
    <h3 id="dashboard-<?= esc($entity, 'attr'); ?>-ranking-title"><?= ui_text('backend/dashboard.panels.' . ($isValue ? 'valueBy' : 'productsBy') . $rankingEntity, [], false); ?></h3>
    <p class="dashboard-ranking-description"><?= ui_text('backend/dashboard.descriptions.top' . ($isValue ? 'Value' : '') . $rankingEntity, [\Config\Backend\Dashboard::RANKING_LIMIT], true); ?></p>

    <?php if ($chart['unavailable']): ?>
        <p class="dashboard-empty" role="status"><?= ui_text('backend/dashboard.messages.unavailable', [], false); ?></p>
    <?php elseif ($chart['items'] === []): ?>
        <p class="dashboard-empty" role="status"><?= ui_text('backend/dashboard.messages.noAssociations', [], false); ?></p>
    <?php else: ?>
        <ol class="dashboard-bars">
            <?php foreach ($chart['items'] as $item): ?>
                <li>
                    <span class="dashboard-bar-label" title="<?= esc($item['label'], 'attr'); ?>"><?= esc($item['label']); ?></span>
                    <div class="dashboard-bar-track" aria-hidden="true"><div class="dashboard-bar-fill" style="width: <?= $item['share']; ?>%;"></div></div>
                    <span class="dashboard-bar-value"><?= esc($isValue ? $dashboardPresenter->formatValue($item['value']) : $dashboardPresenter->formatCount($item['count'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <figcaption class="dashboard-ranking-note"><?= ui_text('backend/dashboard.descriptions.multiple' . $rankingEntity, [], false); ?><?= $isValue ? ' ' . ui_text('backend/dashboard.descriptions.valuesNotAdditive') : ''; ?></figcaption>
</figure>
