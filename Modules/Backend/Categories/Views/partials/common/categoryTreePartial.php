<?php
    $categoryTreeDisplay = $categoryTreeDisplay ?? 'section';
    $categoryTree = $categoryTree ?? [];
    $selectedCategoryUuid = $selectedCategoryUuid ?? null;
    $selectedCategoryPath = $selectedCategoryPath ?? null;
    $categoryTreeContext = $categoryTreeContext ?? 'add';

    $renderCategoryTree = static function(array $nodes, int $level = 0) use (&$renderCategoryTree, $selectedCategoryUuid): string {
        if (empty($nodes)):
            return '';
        endif;

        ob_start();
?>
        <ul class="list-unstyled mb-0<?= ($level > 0) ? ' ms-4 ps-3 border-start' : ''; ?>">
            <?php foreach($nodes as $node): ?>
                <?php
                    $hasChildren = ! empty($node['children']);
                    $hasProducts = ((int) ($node['has_products'] ?? 0) === 1);
                    $isSelected = ($selectedCategoryUuid !== null && $node['uuid'] === $selectedCategoryUuid);
                    $isDeleted = ($node['deleted_at'] !== null);
                ?>
                <li class="py-1">
                    <div class="d-flex align-items-center gap-2<?= $isSelected ? ' fw-bold app-text-info' : ''; ?>">
                        <i class="fa-solid <?= $hasChildren ? 'fa-folder-tree' : ($hasProducts ? 'fa-boxes-stacked' : 'fa-folder'); ?> fa-fw" aria-hidden="true"></i>
                        <span><?= esc($node['category']); ?></span>

                        <?php if($hasChildren): ?>
                            <span class="badge app-bg-muted-subtle app-text-muted-emphasis"><?= ui_text('backend/categories.labels.containsCategories', [], false); ?></span>
                        <?php endif; ?>

                        <?php if($hasProducts): ?>
                            <span class="badge app-bg-info"><?= ui_text('backend/categories.labels.containsProducts', [], false); ?></span>
                        <?php endif; ?>

                        <?php if( ! $hasChildren && ! $hasProducts): ?>
                            <span class="badge app-bg-muted-subtle app-text-muted-emphasis"><?= ui_text('backend/categories.labels.emptyCategory', [], false); ?></span>
                        <?php endif; ?>

                        <?php if($isSelected): ?>
                            <span class="badge app-bg-info"><?= ui_text('backend/categories.labels.currentCategory', [], false); ?></span>
                        <?php endif; ?>

                        <?php if($isDeleted): ?>
                            <span class="badge app-bg-danger"><?= ui_text('backend/categories.labels.deleted', [], false); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if($hasChildren): ?>
                        <?= $renderCategoryTree($node['children'], $level + 1); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
<?php
        return (string) ob_get_clean();
    };

    $treeHtml = $renderCategoryTree($categoryTree);

    $renderCategoryTreeContent = static function() use ($selectedCategoryPath, $treeHtml): string {
        ob_start();
?>
        <?php if($selectedCategoryPath !== null): ?>
            <div class="mb-3">
                <i class="fa-solid fa-route me-1"></i><?= ui_text('backend/categories.labels.path', [], false); ?>:
                <span class="fw-bold"><?= esc($selectedCategoryPath); ?></span>
            </div>
        <?php endif; ?>

        <?php if($treeHtml !== ''): ?>
            <?= $treeHtml; ?>
        <?php else: ?>
            <div class="text-center py-3 fw-bold"><?= ui_text('backend/categories.messages.noTreeCategories', [], false); ?></div>
        <?php endif; ?>
<?php
        return (string) ob_get_clean();
    };

    $treeContentHtml = $renderCategoryTreeContent();
?>

<?php if($categoryTreeDisplay === 'modal'): ?>

    <div class="modal fade" id="categoriesTreeModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="false" aria-labelledby="categoriesTreeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="categoriesTreeModalLabel">
                        <i class="fa-solid fa-folder-tree me-1"></i><?= ui_text('backend/categories.panels.categoryTree', [], false); ?>
                    </h5>
                </div>
                <div class="modal-body" id="categoryTreeModalPanel">
                    <?= $treeContentHtml; ?>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-sm app-btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i><?= ui_text('backend/global.buttons.close', [], false); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php elseif($categoryTreeDisplay === 'content'): ?>

    <?= $treeContentHtml; ?>

<?php else: ?>

    <div class="card-header rounded-0 d-flex justify-content-between align-items-center" style="border-top: var(--bs-card-border-width) solid var(--bs-card-border-color);">
        <h2 class="card-title text-start mb-0">
            <?= ui_text('backend/categories.panels.categoryTree', [], false); ?>
        </h2>
        <input type="hidden" name="context" value="<?= esc($categoryTreeContext, 'attr'); ?>" form="getTree">
        <?php if($selectedCategoryUuid !== null): ?>
            <input type="hidden" name="uuid" value="<?= esc($selectedCategoryUuid, 'attr'); ?>" form="getTree">
        <?php endif; ?>
        <button type="submit" class="btn btn-sm app-btn-secondary" form="getTree">
            <i class="fa-solid fa-arrows-rotate me-1"></i><?= ui_text('backend/categories.buttons.reload', [], false); ?>
        </button>
    </div>
    <div class="card-body" id="categoryTreePanel">
        <?= $treeContentHtml; ?>
    </div>

<?php endif; ?>
