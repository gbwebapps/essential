<?php $rankingEntity = ucfirst($chart['entity']); ?>
<?php $isValue = $chart['metric'] === 'value'; ?>
<figure class="dashboard-ranking<?= $isValue ? ' dashboard-ranking-value' : ''; ?>" data-dashboard-ranking="<?= esc($entity, 'attr'); ?>" aria-labelledby="dashboard-<?= esc($entity, 'attr'); ?>-ranking-title">
    <h3 id="dashboard-<?= esc($entity, 'attr'); ?>-ranking-title"><?= lang('backend/dashboard.panels.' . ($isValue ? 'valueBy' : 'productsBy') . $rankingEntity); ?></h3>
    <p class="dashboard-ranking-description"><?= esc(sprintf(lang('backend/dashboard.descriptions.top' . ($isValue ? 'Value' : '') . $rankingEntity), \Config\Backend\Dashboard::RANKING_LIMIT)); ?></p>

    <?php if ($chart['unavailable']): ?>
        <p class="dashboard-empty" role="status"><?= lang('backend/dashboard.messages.unavailable'); ?></p>
    <?php elseif ($chart['items'] === []): ?>
        <p class="dashboard-empty" role="status"><?= lang('backend/dashboard.messages.noAssociations'); ?></p>
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

    <figcaption class="dashboard-ranking-note"><?= lang('backend/dashboard.descriptions.multiple' . $rankingEntity); ?><?= $isValue ? ' ' . lang('backend/dashboard.descriptions.valuesNotAdditive') : ''; ?></figcaption>
</figure>
